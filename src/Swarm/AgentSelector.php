<?php

namespace Atom\Swarm;

class AgentSelector
{
    /**
     * Selects appropriate specialized agents for task roles.
     */
    public function selectAgentForRole(string $role, int $userId = 1): AgentDefinition
    {
        $role = strtolower($role);

        $defaultDefs = [
            'researcher' => [
                'name'          => 'Researcher Agent',
                'slug'          => 'researcher-agent',
                'role'          => 'worker',
                'description'   => 'Gathers information, performs searches, and collects evidence',
                'capabilities'  => ['research', 'retrieval'],
                'allowed_tools' => ['web_search', 'fetch_url', 'knowledge_search'],
                'risk_level'    => 'low',
            ],
            'analyst' => [
                'name'          => 'Analyst Agent',
                'slug'          => 'analyst-agent',
                'role'          => 'worker',
                'description'   => 'Analyzes data, compares claims, and evaluates findings',
                'capabilities'  => ['analysis', 'evaluation'],
                'allowed_tools' => ['calculator'],
                'risk_level'    => 'low',
            ],
            'verifier' => [
                'name'          => 'Verifier Agent',
                'slug'          => 'verifier-agent',
                'role'          => 'verifier',
                'description'   => 'Verifies claims, checks sources, and identifies contradictions',
                'capabilities'  => ['verification'],
                'allowed_tools' => ['knowledge_search'],
                'risk_level'    => 'low',
            ],
            'synthesizer' => [
                'name'          => 'Synthesizer Agent',
                'slug'          => 'synthesizer-agent',
                'role'          => 'synthesizer',
                'description'   => 'Aggregates verified evidence into a cohesive final response',
                'capabilities'  => ['summarization', 'synthesis'],
                'allowed_tools' => [],
                'risk_level'    => 'low',
            ],
        ];

        $defData = $defaultDefs[$role] ?? [
            'name'          => ucfirst($role) . ' Agent',
            'slug'          => $role . '-agent',
            'role'          => 'worker',
            'description'   => 'Specialized worker agent',
            'capabilities'  => [$role],
            'allowed_tools' => [],
            'risk_level'    => 'medium',
        ];

        return new AgentDefinition(array_merge($defData, ['owner_user_id' => $userId]));
    }
}
