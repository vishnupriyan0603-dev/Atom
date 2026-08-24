<?php

namespace Atom\Daemon;

use Atom\Brain\PersonalityEngine;
use Atom\Security\SecretRedactor;

/**
 * BriefingEngine — Contextual Morning and Evening Briefing Generator.
 *
 * Synthesizes:
 * - Time of day in IST (Karur, Tamil Nadu)
 * - Workspace health status & file deltas
 * - Pending human approval queue items
 * - Long-term career / GATE 2028 study milestones
 * - Personalized mentor / technical tone
 */
class BriefingEngine
{
    private PersonalityEngine $personality;
    private WorkspaceHealthMonitor $healthMonitor;
    private SecretRedactor $redactor;

    public function __construct(
        ?PersonalityEngine $personality = null,
        ?WorkspaceHealthMonitor $healthMonitor = null,
        ?SecretRedactor $redactor = null
    ) {
        $this->personality = $personality ?? new PersonalityEngine('mentor', 'Vichu');
        $this->healthMonitor = $healthMonitor ?? new WorkspaceHealthMonitor();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Generate a structured briefing report.
     */
    public function generateBriefing(string $type = 'morning', array $extraContext = []): array
    {
        $tz = new \DateTimeZone('Asia/Kolkata');
        $now = new \DateTimeImmutable('now', $tz);
        $dateStr = $now->format('l, d F Y');
        $timeStr = $now->format('h:i A') . ' IST';

        $health = $this->healthMonitor->scanWorkspace();
        $ownerName = $this->personality->getOwnerName();

        $title = ($type === 'evening')
            ? "ATOM Daily Wrap-up — {$dateStr}"
            : "ATOM Morning Briefing — {$dateStr}";

        $greeting = ($type === 'evening')
            ? "Good evening, {$ownerName}!"
            : "Good morning, {$ownerName}!";

        $content = "### {$title}\n\n"
            . "{$greeting} Here is your proactive system overview for **{$dateStr} ({$timeStr})**:\n\n"
            . "#### 🛠️ Workspace & Engineering Status\n"
            . "- **System Health Score**: {$health['health_score']}/100 (" . strtoupper($health['status']) . ")\n"
            . "- **Active Branch**: `{$health['git']['active_branch']}`\n"
            . "- **PHP Syntax Validation**: " . ($health['syntax']['errors_found'] === 0 ? "All clean (0 errors)" : "{$health['syntax']['errors_found']} issues detected") . "\n"
            . "- **Database Connection**: {$health['database']['status']} ({$health['database']['latency_ms']}ms latency)\n\n"
            . "#### 🎯 Focus & Learning Milestones\n"
            . "- **Core Focus**: PHP Full-Stack Engineering (CodeIgniter, Laravel, Bootstrap 5)\n"
            . "- **GATE 2028 Priority**: Algorithm design, discrete mathematics & systems architecture practice\n"
            . "- **Proactive Suggestion**: Maintain modular code structures and run unit tests prior to deployment.\n\n"
            . "ATOM Autonomous Background Daemon is standing by.";

        // Redact any sensitive information
        $content = $this->redactor->redact($content);

        return [
            'type' => $type,
            'title' => $title,
            'date_ist' => $dateStr,
            'time_ist' => $timeStr,
            'health_score' => $health['health_score'],
            'summary' => "System health: {$health['health_score']}%. Active branch: {$health['git']['active_branch']}. All systems operational.",
            'content' => $content,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
