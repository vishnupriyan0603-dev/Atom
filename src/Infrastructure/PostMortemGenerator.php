<?php

namespace Atom\Infrastructure;

/**
 * Post-Mortem Generator — Phase 40
 *
 * Automatically generates Root Cause Analysis (RCA) and incident post-mortem reports.
 */
class PostMortemGenerator
{
    /**
     * Generates structured incident post-mortem report.
     */
    public function generate(array $incidentData): array
    {
        $id = $incidentData['incident_id'] ?? ('inc_' . bin2hex(random_bytes(3)));
        $sev = $incidentData['severity'] ?? 'SEV2_MAJOR';
        $subsystem = $incidentData['subsystem'] ?? 'database_pool';
        $rootCause = $incidentData['root_cause'] ?? 'Unindexed query causing connection pool exhaustion';
        $downtimeMin = (float)($incidentData['downtime_minutes'] ?? 4.2);

        $markdown = "# Incident Post-Mortem: {$id}\n\n";
        $markdown .= "**Severity**: `{$sev}` | **Subsystem**: `{$subsystem}` | **Downtime**: `{$downtimeMin} min`\n\n";
        $markdown .= "## Summary & Impact\n";
        $markdown .= "An unexpected degradation occurred in `{$subsystem}`, resulting in an estimated {$downtimeMin} minutes of partial latency.\n\n";
        $markdown .= "## Root Cause Analysis (RCA)\n";
        $markdown .= "{$rootCause}\n\n";
        $markdown .= "## Remediation & Recovery\n";
        $markdown .= "- Automated self-healing runbook triggered.\n";
        $markdown .= "- Stale connection descriptors purged and traffic normalized.\n\n";
        $markdown .= "## Preventative Action Items\n";
        $markdown .= "1. Add composite index on frequent query predicates.\n";
        $markdown .= "2. Tighten Circuit Breaker trip threshold from 5 to 3 consecutive failures.\n";

        return [
            'incident_id'      => $id,
            'severity'         => $sev,
            'subsystem'        => $subsystem,
            'downtime_minutes' => $downtimeMin,
            'root_cause'       => $rootCause,
            'post_mortem_md'   => $markdown,
        ];
    }
}
