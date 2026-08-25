<?php

namespace Atom\Automation;

use Atom\Security\SecretRedactor;

/**
 * GitSemanticReleaseEngine — Phase 59
 * Autonomous Git commit analyzer, Semantic Versioning (SemVer) tag calculator, and Changelog synthesizer.
 */
class GitSemanticReleaseEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Analyze a list of commit messages and determine the next SemVer bump and categorized release notes.
     *
     * @param array $commits Array of commit message strings or commit objects
     * @param string $currentVersion E.g. "v2.0.0"
     * @return array [ 'current_version' => string, 'next_version' => string, 'bump_type' => string, 'changelog' => string, 'categories' => array ]
     */
    public function analyzeRelease(array $commits, string $currentVersion = 'v2.0.0'): array
    {
        if (empty($commits)) {
            return [
                'success' => false,
                'error' => 'Commits list cannot be empty',
                'current_version' => $currentVersion,
                'next_version' => $currentVersion,
                'bump_type' => 'NONE',
                'changelog' => '',
            ];
        }

        $categories = [
            'features' => [],
            'fixes' => [],
            'security' => [],
            'performance' => [],
            'refactoring' => [],
            'others' => [],
        ];

        $hasBreaking = false;
        $hasFeat = false;
        $hasFix = false;

        foreach ($commits as $rawCommit) {
            $msg = is_array($rawCommit) ? ($rawCommit['message'] ?? '') : (string) $rawCommit;
            $cleanMsg = trim($this->redactor->redact($msg));

            if (empty($cleanMsg)) continue;

            if (stripos($cleanMsg, 'BREAKING CHANGE') !== false || preg_match('/^[a-z]+(\([^\)]+\))?!:/i', $cleanMsg)) {
                $hasBreaking = true;
            }

            if (preg_match('/^feat(?:\([^\)]+\))?:\s*(.+)$/i', $cleanMsg, $m)) {
                $hasFeat = true;
                $categories['features'][] = $m[1];
            } elseif (preg_match('/^fix(?:\([^\)]+\))?:\s*(.+)$/i', $cleanMsg, $m)) {
                $hasFix = true;
                $categories['fixes'][] = $m[1];
            } elseif (preg_match('/^sec(?:urity)?(?:\([^\)]+\))?:\s*(.+)$/i', $cleanMsg, $m)) {
                $hasFix = true;
                $categories['security'][] = $m[1];
            } elseif (preg_match('/^perf(?:\([^\)]+\))?:\s*(.+)$/i', $cleanMsg, $m)) {
                $categories['performance'][] = $m[1];
            } elseif (preg_match('/^refactor(?:\([^\)]+\))?:\s*(.+)$/i', $cleanMsg, $m)) {
                $categories['refactoring'][] = $m[1];
            } else {
                $categories['others'][] = $cleanMsg;
            }
        }

        // Determine bump type
        $bumpType = 'PATCH';
        if ($hasBreaking) {
            $bumpType = 'MAJOR';
        } elseif ($hasFeat) {
            $bumpType = 'MINOR';
        }

        $nextVersion = $this->calculateNextSemver($currentVersion, $bumpType);
        $changelog = $this->synthesizeMarkdownChangelog($nextVersion, $categories);

        return [
            'success' => true,
            'current_version' => $currentVersion,
            'next_version' => $nextVersion,
            'bump_type' => $bumpType,
            'total_commits_analyzed' => count($commits),
            'categories' => $categories,
            'changelog_markdown' => $changelog,
        ];
    }

    /**
     * Compute next version string based on SemVer rules.
     */
    public function calculateNextSemver(string $version, string $bumpType): string
    {
        $clean = ltrim(trim($version), 'vV');
        $parts = explode('.', $clean);

        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);
        $patch = (int) ($parts[2] ?? 0);

        switch (strtoupper($bumpType)) {
            case 'MAJOR':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'MINOR':
                $minor++;
                $patch = 0;
                break;
            case 'PATCH':
            default:
                $patch++;
                break;
        }

        return "v{$major}.{$minor}.{$patch}";
    }

    /**
     * Synthesize clean markdown changelog section.
     */
    private function synthesizeMarkdownChangelog(string $version, array $categories): string
    {
        $date = date('Y-m-d');
        $md = "## [{$version}] - {$date}\n\n";

        if (!empty($categories['features'])) {
            $md .= "### 🚀 Features & Subsystems\n";
            foreach ($categories['features'] as $f) {
                $md .= "- {$f}\n";
            }
            $md .= "\n";
        }

        if (!empty($categories['security'])) {
            $md .= "### 🛡️ Security & Zero-Trust\n";
            foreach ($categories['security'] as $s) {
                $md .= "- {$s}\n";
            }
            $md .= "\n";
        }

        if (!empty($categories['fixes'])) {
            $md .= "### 🐛 Bug Fixes\n";
            foreach ($categories['fixes'] as $fx) {
                $md .= "- {$fx}\n";
            }
            $md .= "\n";
        }

        if (!empty($categories['performance'])) {
            $md .= "### ⚡ Performance Improvements\n";
            foreach ($categories['performance'] as $p) {
                $md .= "- {$p}\n";
            }
            $md .= "\n";
        }

        return trim($md);
    }
}
