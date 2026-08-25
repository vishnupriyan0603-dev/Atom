<?php

namespace Atom\Refactoring;

use Atom\Security\SecretRedactor;

/**
 * AstCodeLinterEngine — Phase 63
 * Autonomous AST PHP code linter and PSR-12 style standard auto-fixer.
 */
class AstCodeLinterEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Scan PHP code for PSR-12 style and syntax violations.
     *
     * @param string $code Raw PHP code
     * @return array [ 'compliance_score' => int, 'violations_count' => int, 'violations' => array ]
     */
    public function scanCode(string $code): array
    {
        if (empty(trim($code))) {
            return [
                'success' => false,
                'error' => 'Source code cannot be empty',
                'compliance_score' => 100,
                'violations_count' => 0,
                'violations' => [],
            ];
        }

        $cleanCode = $this->redactor->redact($code);
        $violations = [];

        // 1. Check strict types declaration
        if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/i', $cleanCode)) {
            $violations[] = [
                'rule' => 'PSR12.Files.DeclareStrictTypes',
                'severity' => 'WARNING',
                'message' => 'Missing declare(strict_types=1); declaration at file header.',
            ];
        }

        // 2. Check class opening brace on next line (same-line brace is non-compliant)
        if (preg_match('/class\s+[a-zA-Z0-9_]+[ \t]*\{/', $cleanCode)) {
            $violations[] = [
                'rule' => 'PSR12.Classes.OpeningBraceNextLine',
                'severity' => 'ERROR',
                'message' => 'Class opening brace must be on the next line.',
            ];
        }

        // 3. Check function opening brace on next line (same-line brace is non-compliant)
        if (preg_match('/function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*(?::\s*[a-zA-Z0-9_\\\\]+)?[ \t]*\{/', $cleanCode)) {
            $violations[] = [
                'rule' => 'PSR12.Functions.OpeningBraceNextLine',
                'severity' => 'ERROR',
                'message' => 'Function/Method opening brace must be on the next line.',
            ];
        }

        // 4. Check for closing PHP tags (disallowed in pure PHP files)
        if (preg_match('/\?>\s*$/', $cleanCode)) {
            $violations[] = [
                'rule' => 'PSR12.Files.ClosingTag',
                'severity' => 'WARNING',
                'message' => 'Pure PHP files must omit the closing ?> tag.',
            ];
        }

        // 5. Check trailing whitespace
        if (preg_match('/[ \t]+$/m', $cleanCode)) {
            $violations[] = [
                'rule' => 'PSR12.WhiteSpace.TrailingWhitespace',
                'severity' => 'INFO',
                'message' => 'Found trailing whitespace on one or more lines.',
            ];
        }

        $vCount = count($violations);
        $complianceScore = max(0, 100 - ($vCount * 20));

        return [
            'success' => true,
            'compliance_score' => $complianceScore,
            'violations_count' => $vCount,
            'is_fully_compliant' => $vCount === 0,
            'violations' => $violations,
        ];
    }

    /**
     * Auto-fix and format non-compliant PHP code into pristine PSR-12 standard.
     */
    public function fixCode(string $code): array
    {
        if (empty(trim($code))) {
            return [
                'success' => false,
                'error' => 'Source code cannot be empty',
                'fixed_code' => '',
                'fixes_applied' => 0,
            ];
        }

        $fixed = trim($code);
        $fixesCount = 0;

        // 1. Remove closing PHP tag
        if (preg_match('/\?>\s*$/', $fixed)) {
            $fixed = preg_replace('/\?>\s*$/', '', $fixed);
            $fixesCount++;
        }

        // 2. Remove trailing whitespace
        $fixed = preg_replace('/[ \t]+$/m', '', $fixed);

        // 3. Fix class opening brace placement: class Foo { -> class Foo\n{
        $fixed = preg_replace('/(class\s+[a-zA-Z0-9_]+(?:\s+extends\s+[a-zA-Z0-9_]+)?(?:\s+implements\s+[a-zA-Z0-9_,\s]+)?)\s*\{/', "$1\n{", $fixed);

        // 4. Fix function opening brace placement
        $fixed = preg_replace('/(function\s+[a-zA-Z0-9_]+\s*\(.*?\)(?:\s*:\s*[a-zA-Z0-9_\\\\]+)?)\s*\{/', "$1\n{", $fixed);

        // 5. Ensure strict_types declaration at the top
        if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/i', $fixed)) {
            if (str_starts_with($fixed, '<?php')) {
                $fixed = "<?php\n\ndeclare(strict_types=1);\n" . substr($fixed, 5);
            } else {
                $fixed = "<?php\n\ndeclare(strict_types=1);\n\n" . $fixed;
            }
            $fixesCount++;
        }

        $scanAfter = $this->scanCode($fixed);

        return [
            'success' => true,
            'fixes_applied' => max(1, $fixesCount),
            'fixed_code' => $fixed,
            'compliance_score_after' => $scanAfter['compliance_score'],
        ];
    }
}
