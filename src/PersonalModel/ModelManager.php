<?php

namespace Atom\PersonalModel;

class ModelManager
{
    /** @var ModelInterface[] */
    private array $models = [];
    
    /** @var array Role mappings, e.g. ['primary' => 'llama3.1', 'teacher' => 'gemini-1.5-flash', 'fallback' => 'gemini-1.5-flash'] */
    private array $roles = [];

    private ?\Atom\ModelGateway\ModelGatewayInterface $gateway = null;

    public function setModelGateway(\Atom\ModelGateway\ModelGatewayInterface $gateway): void
    {
        $this->gateway = $gateway;
    }

    public function getModelGateway(): ?\Atom\ModelGateway\ModelGatewayInterface
    {
        return $this->gateway;
    }

    public function registerModel(string $alias, ModelInterface $model): void
    {
        $this->models[strtolower($alias)] = $model;
    }

    public function setRole(string $role, string $alias): void
    {
        $this->roles[strtolower($role)] = strtolower($alias);
    }

    public function getModelForRole(string $role): ?ModelInterface
    {
        $role = strtolower($role);
        $alias = $this->roles[$role] ?? null;
        if ($alias !== null && isset($this->models[$alias])) {
            return $this->models[$alias];
        }
        return null;
    }

    /**
     * Returns a registered model by provider/model alias (case-insensitive).
     */
    public function getModel(string $alias): ?ModelInterface
    {
        $alias = strtolower($alias);
        return $this->models[$alias] ?? null;
    }

    /**
     * Returns a registered model by provider display name (e.g. 'Gemini', 'Groq', 'Ollama').
     */
    public function getModelByProvider(string $provider): ?ModelInterface
    {
        $provider = strtolower($provider);
        if ($provider === 'openai') {
            return $this->models['openai'] ?? null;
        }
        foreach ($this->models as $alias => $model) {
            if ($alias === $provider) {
                return $model;
            }
            if (strtolower($model->getProviderName()) === $provider) {
                return $model;
            }
        }
        return null;
    }

    /**
     * Executes generation for a role with automatic provider failover.
     *
     * If the primary model fails (e.g. rate limit, network error), the next
     * available cloud provider is tried automatically. Local models (Ollama)
     * are only used as a last resort and only when actually reachable.
     */
    public function generateForRole(string $role, array $messages): ModelResponse
    {
        $model = $this->getModelForRole($role);
        if ($model === null) {
            if ($role === 'primary') {
                $model = $this->getModelForRole('fallback');
            }
            if ($model === null) {
                return new ModelResponse(false, '', "No model configured for role '{$role}'");
            }
        }

        $response = $model->generate($messages);

        // Primary failed (rate limited / offline / transient error) -> fail over
        if (!$response->isSuccess() && $role === 'primary') {
            $lastError = $response->getError();
            foreach ($this->getFailoverCandidates($model) as $candidate) {
                if (!$candidate->isAvailable()) {
                    continue;
                }
                $candidateResponse = $candidate->generate($messages);
                if ($candidateResponse->isSuccess()) {
                    return $candidateResponse;
                }
                $lastError = $candidateResponse->getError();
            }

            return new ModelResponse(false, '', $lastError ?: $response->getError());
        }

        return $response;
    }

    /**
     * Orders the other registered models for failover: cloud providers first
     * (Gemini, Groq, OpenAI, ...), local models (Ollama) always last.
     */
    private function getFailoverCandidates(ModelInterface $excluded): array
    {
        $cloud = [];
        $local = [];
        foreach ($this->models as $alias => $candidate) {
            if ($candidate === $excluded) {
                continue;
            }
            if (strtolower($candidate->getProviderName()) === 'ollama') {
                $local[] = $candidate;
            } else {
                $cloud[] = $candidate;
            }
        }
        return array_merge($cloud, $local);
    }

    /**
     * Generates a response using a specific provider/model, falling back to
     * the 'primary' then 'fallback' role if the requested one fails.
     */
    public function generateForProvider(string $provider, array $messages): ModelResponse
    {
        $requested = $this->getModelByProvider($provider);
        $primary = $this->getModelForRole('primary');

        $attempts = [];
        if ($requested !== null) {
            $attempts[] = $requested;
        }
        if ($primary !== null && $primary !== $requested) {
            $attempts[] = $primary;
        }
        $fallback = $this->getModelForRole('fallback');
        if ($fallback !== null && !in_array($fallback, $attempts, true)) {
            $attempts[] = $fallback;
        }

        if (empty($attempts)) {
            return new ModelResponse(false, '', "No model configured for provider '{$provider}'");
        }

        $lastError = null;
        foreach ($attempts as $index => $model) {
            if ($index > 0 && !$model->isAvailable()) {
                $lastError = $model->getProviderName() . ' is unavailable';
                continue;
            }
            $response = $model->generate($messages);
            if ($response->isSuccess()) {
                return $response;
            }
            $lastError = $response->getError();
        }

        // If requested & fallback roles failed, try all remaining registered providers
        $tried = $attempts;
        foreach ($this->models as $alias => $candidate) {
            if (in_array($candidate, $tried, true)) {
                continue;
            }
            if (!$candidate->isAvailable()) {
                continue;
            }
            $candidateResponse = $candidate->generate($messages);
            if ($candidateResponse->isSuccess()) {
                return $candidateResponse;
            }
            $lastError = $candidateResponse->getError();
        }

        return new ModelResponse(false, '', $lastError ?: 'All AI providers are currently unavailable or rate-limited.');
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
}
