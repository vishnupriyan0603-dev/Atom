<?php

namespace Atom\Swarm;

class AgentDefinition
{
    public int $id;
    public int $ownerUserId;
    public string $name;
    public string $slug;
    public ?string $description;
    public string $role; // coordinator, worker, reviewer, verifier, synthesizer
    public ?string $systemPrompt;
    public array $capabilities;
    public array $allowedTools;
    public array $allowedSkills;
    public string $riskLevel;
    public int $maxSteps;
    public int $maxToolCalls;
    public string $status; // active, disabled, archived
    public int $version;

    public function __construct(array $data)
    {
        $this->id             = (int)($data['id'] ?? 0);
        $this->ownerUserId    = (int)($data['owner_user_id'] ?? 1);
        $this->name           = $data['name'] ?? 'Specialized Agent';
        $this->slug           = $data['slug'] ?? 'specialized-agent';
        $this->description    = $data['description'] ?? null;
        $this->role           = $data['role'] ?? 'worker';
        $this->systemPrompt   = $data['system_prompt'] ?? null;
        $this->capabilities   = is_array($data['capabilities'] ?? null) ? $data['capabilities'] : (json_decode($data['capabilities_json'] ?? '[]', true) ?: []);
        $this->allowedTools   = is_array($data['allowed_tools'] ?? null) ? $data['allowed_tools'] : (json_decode($data['allowed_tools_json'] ?? '[]', true) ?: []);
        $this->allowedSkills  = is_array($data['allowed_skills'] ?? null) ? $data['allowed_skills'] : (json_decode($data['allowed_skills_json'] ?? '[]', true) ?: []);
        $this->riskLevel      = $data['risk_level'] ?? 'medium';
        $this->maxSteps       = (int)($data['max_steps'] ?? 10);
        $this->maxToolCalls   = (int)($data['max_tool_calls'] ?? 10);
        $this->status         = $data['status'] ?? 'active';
        $this->version        = (int)($data['version'] ?? 1);
    }

    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'owner_user_id'       => $this->ownerUserId,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'role'                => $this->role,
            'system_prompt'       => $this->systemPrompt,
            'capabilities_json'   => json_encode($this->capabilities),
            'allowed_tools_json'  => json_encode($this->allowedTools),
            'allowed_skills_json' => json_encode($this->allowedSkills),
            'risk_level'          => $this->riskLevel,
            'max_steps'           => $this->maxSteps,
            'max_tool_calls'      => $this->maxToolCalls,
            'status'              => $this->status,
            'version'             => $this->version,
        ];
    }
}
