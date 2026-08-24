<?php

namespace Atom\Governance;

class PolicyRule
{
    public string $action;
    public string $resource;
    public string $effect; // allow, deny, require_approval
    public array $conditions;

    public function __construct(array $data)
    {
        $this->action     = $data['action'] ?? '*';
        $this->resource   = $data['resource'] ?? '*';
        $this->effect     = $data['effect'] ?? 'allow';
        $this->conditions = $data['conditions'] ?? [];
    }

    public function matches(string $action, string $resource): bool
    {
        $actionMatch   = ($this->action === '*' || fnmatch($this->action, $action));
        $resourceMatch = ($this->resource === '*' || fnmatch($this->resource, $resource));

        return $actionMatch && $resourceMatch;
    }
}
