<?php

namespace Atom\Governance;

class PolicySimulator
{
    private PolicyEngine $engine;

    public function __construct(?PolicyEngine $engine = null)
    {
        $this->engine = $engine ?? new PolicyEngine();
    }

    /**
     * Executes dry-run simulation of policy evaluation without production state mutations.
     */
    public function simulate(int $actorId, string $action, string $resource, array $context = []): array
    {
        $res = $this->engine->evaluate($actorId, $action, $resource, $context);

        return [
            'simulation'   => true,
            'decision'     => $res->decision,
            'reason_codes' => $res->reasonCodes,
            'policy_id'    => $res->policyId,
        ];
    }
}
