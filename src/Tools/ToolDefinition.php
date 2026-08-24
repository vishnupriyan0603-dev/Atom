<?php

namespace Atom\Tools;

class ToolDefinition
{
    public string $name;
    public string $description;
    public array $inputSchema;
    public string $permission;
    public string $riskLevel; // 'low', 'medium', 'high'
    public int $timeout; // in seconds
    public bool $enabled;

    public function __construct(
        string $name,
        string $description,
        array $inputSchema = [],
        string $permission = 'tool.execute',
        string $riskLevel = 'low',
        int $timeout = 15,
        bool $enabled = true
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->inputSchema = $inputSchema;
        $this->permission = $permission;
        $this->riskLevel = strtolower($riskLevel);
        $this->timeout = $timeout;
        $this->enabled = $enabled;
    }

    public function requiresHumanApproval(): bool
    {
        return $this->riskLevel === 'high';
    }

    public function toArray(): array
    {
        return [
            'name'           => $this->name,
            'description'    => $this->description,
            'input_schema'   => $this->inputSchema,
            'permission'     => $this->permission,
            'risk_level'     => $this->riskLevel,
            'timeout'        => $this->timeout,
            'enabled'        => $this->enabled,
            'human_approval' => $this->requiresHumanApproval(),
        ];
    }
}
