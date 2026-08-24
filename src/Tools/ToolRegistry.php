<?php

namespace Atom\Tools;

class ToolRegistry
{
    /** @var array<string, ToolDefinition> */
    private array $definitions = [];
    /** @var array<string, ToolInterface> */
    private array $handlers = [];

    public function registerTool(ToolInterface $tool, ?ToolDefinition $definition = null): void
    {
        $name = $tool->getName();
        $this->handlers[$name] = $tool;

        if ($tool instanceof ExtendedToolInterface) {
            $this->definitions[$name] = $tool->getDefinition();
        } elseif ($definition !== null) {
            $this->definitions[$name] = $definition;
        } else {
            $this->definitions[$name] = new ToolDefinition(
                name: $name,
                description: "Tool {$name}",
                permission: "tool.{$name}",
                riskLevel: 'low'
            );
        }
    }

    public function getDefinition(string $name): ?ToolDefinition
    {
        return $this->definitions[$name] ?? null;
    }

    public function getHandler(string $name): ?ToolInterface
    {
        return $this->handlers[$name] ?? null;
    }

    public function hasTool(string $name): bool
    {
        return isset($this->handlers[$name]);
    }

    public function isEnabled(string $name): bool
    {
        $def = $this->getDefinition($name);
        return $def !== null && $def->enabled;
    }

    public function enableTool(string $name): void
    {
        if (isset($this->definitions[$name])) {
            $this->definitions[$name]->enabled = true;
        }
    }

    public function disableTool(string $name): void
    {
        if (isset($this->definitions[$name])) {
            $this->definitions[$name]->enabled = false;
        }
    }

    /**
     * Validates arguments against schema parameters.
     */
    public function validateInput(string $name, array $input): array
    {
        $def = $this->getDefinition($name);
        if ($def === null) {
            return ['valid' => false, 'error' => "Tool '{$name}' is not registered."];
        }

        if (!empty($def->inputSchema['required'])) {
            foreach ($def->inputSchema['required'] as $reqField) {
                if (!array_key_exists($reqField, $input) || $input[$reqField] === null || $input[$reqField] === '') {
                    return ['valid' => false, 'error' => "Missing required argument '{$reqField}' for tool '{$name}'."];
                }
            }
        }

        return ['valid' => true];
    }

    public function getAllDefinitions(): array
    {
        return array_map(fn($d) => $d->toArray(), array_values($this->definitions));
    }
}
