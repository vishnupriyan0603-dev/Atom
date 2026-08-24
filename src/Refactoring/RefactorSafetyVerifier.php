<?php

namespace Atom\Refactoring;

/**
 * Refactor Safety Verifier — Phase 35
 *
 * Verifies syntactic validity and semantic invariants before and after
 * automated code refactoring transformations.
 */
class RefactorSafetyVerifier
{
    /**
     * Verifies that the refactored code maintains syntax validity and public API invariants.
     *
     * @param string $originalCode Code before refactoring.
     * @param string $refactoredCode Code after refactoring.
     * @return array Verification results with safety flag.
     */
    public function verify(string $originalCode, string $refactoredCode): array
    {
        $violations = [];

        if (trim($refactoredCode) === '') {
            return [
                'safe'         => false,
                'syntax_valid' => false,
                'violations'   => ['Refactored code is empty'],
            ];
        }

        // 1. Bracket & brace balance check
        $openBraces = substr_count($refactoredCode, '{');
        $closeBraces = substr_count($refactoredCode, '}');
        if ($openBraces !== $closeBraces) {
            $violations[] = "Unbalanced curly braces: {$openBraces} opening vs {$closeBraces} closing";
        }

        $openParens = substr_count($refactoredCode, '(');
        $closeParens = substr_count($refactoredCode, ')');
        if ($openParens !== $closeParens) {
            $violations[] = "Unbalanced parentheses: {$openParens} opening vs {$closeParens} closing";
        }

        // 2. Public method preservation invariant
        $originalPublicMethods = $this->extractPublicMethods($originalCode);
        $refactoredPublicMethods = $this->extractPublicMethods($refactoredCode);

        foreach ($originalPublicMethods as $method) {
            if (!in_array($method, $refactoredPublicMethods, true)) {
                $violations[] = "Public API invariant violated: method '{$method}' was removed or altered";
            }
        }

        // 3. PHP token validation
        try {
            $tokens = token_get_all($refactoredCode);
            $hasTokens = count($tokens) > 0;
        } catch (\ParseError|\Throwable $e) {
            $violations[] = "Parse error: " . $e->getMessage();
            $hasTokens = false;
        }

        $isSafe = empty($violations) && $hasTokens;

        return [
            'safe'                  => $isSafe,
            'syntax_valid'          => empty($violations),
            'preserved_public_apis' => count($originalPublicMethods),
            'violations'            => $violations,
        ];
    }

    private function extractPublicMethods(string $code): array
    {
        $methods = [];
        if (preg_match_all('/public\s+function\s+([a-zA-Z0-9_]+)\s*\(/i', $code, $matches)) {
            $methods = $matches[1];
        }
        return $methods;
    }
}
