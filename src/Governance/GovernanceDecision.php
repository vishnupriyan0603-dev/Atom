<?php

namespace Atom\Governance;

class GovernanceDecision
{
    public string $decision; // allow, deny, require_approval
    public int $policyId;
    public array $reasonCodes;

    public function __construct(string $decision = 'allow', int $policyId = 1, array $reasonCodes = [])
    {
        $this->decision    = $decision;
        $this->policyId    = $policyId;
        $this->reasonCodes = $reasonCodes ?: ['DEFAULT_ALLOW'];
    }

    public function isAllowed(): bool
    {
        return $this->decision === 'allow';
    }

    public function isDenied(): bool
    {
        return $this->decision === 'deny';
    }

    public function requiresApproval(): bool
    {
        return $this->decision === 'require_approval';
    }
}
