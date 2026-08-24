<?php

namespace Atom\Refactoring;

/**
 * AST Transformation Engine — Phase 35
 *
 * Automated refactoring transformations: Extract Method, Decompose Conditional,
 * Simplify Boolean, and Safe Symbol Renaming.
 */
class ASTTransformationEngine
{
    /**
     * Applies an automated refactoring transformation to source code.
     *
     * @param string $type Transformation type (extract_method, decompose_conditional, simplify_boolean, rename_symbol).
     * @param string $sourceCode PHP source code.
     * @param array $options Parameters for the transformation.
     * @return array Transformed code, diff details, and success status.
     */
    public function transform(string $type, string $sourceCode, array $options = []): array
    {
        switch ($type) {
            case 'extract_method':
                return $this->extractMethod($sourceCode, $options);
            case 'decompose_conditional':
                return $this->decomposeConditional($sourceCode, $options);
            case 'simplify_boolean':
                return $this->simplifyBoolean($sourceCode);
            case 'rename_symbol':
                return $this->renameSymbol($sourceCode, $options);
            default:
                throw new \InvalidArgumentException("Unsupported transformation type: '{$type}'");
        }
    }

    private function extractMethod(string $sourceCode, array $options): array
    {
        $targetBlock = $options['target_block'] ?? '';
        $newMethodName = $options['new_method_name'] ?? 'extractedHelper';
        $params = $options['params'] ?? [];

        if (empty($targetBlock)) {
            throw new \InvalidArgumentException("Target code block to extract cannot be empty");
        }

        if (!str_contains($sourceCode, $targetBlock)) {
            throw new \RuntimeException("Target code block not found in source code");
        }

        $paramList = implode(', ', array_map(fn($p) => '$' . ltrim($p, '$'), $params));
        $callArgs = implode(', ', array_map(fn($p) => '$' . ltrim($p, '$'), $params));
        $methodCall = "\$this->{$newMethodName}({$callArgs});";

        // Replace target block with method call
        $modifiedCode = str_replace($targetBlock, $methodCall, $sourceCode);

        // Append new private method before the last closing brace
        $lastBracePos = strrpos($modifiedCode, '}');
        if ($lastBracePos !== false) {
            $newMethodCode = "\n    private function {$newMethodName}({$paramList})\n    {\n        " . trim($targetBlock) . "\n    }\n";
            $modifiedCode = substr_replace($modifiedCode, $newMethodCode . "}", $lastBracePos, 1);
        }

        return [
            'type'        => 'extract_method',
            'success'     => true,
            'code'        => $modifiedCode,
            'description' => "Extracted block into private method '{$newMethodName}'",
        ];
    }

    private function decomposeConditional(string $sourceCode, array $options): array
    {
        // Replaces nested 'if (!condition) { ... } else { return false; }' with guard clause
        $pattern = '/if\s*\(\s*!\s*([a-zA-Z0-9_\$->()]+)\s*\)\s*\{\s*return\s+false;\s*\}\s*else\s*\{([^}]+)\}/i';
        $replacement = "if (!\$1) {\n    return false;\n}\n\$2";

        $modified = preg_replace($pattern, $replacement, $sourceCode);

        return [
            'type'        => 'decompose_conditional',
            'success'     => true,
            'code'        => $modified,
            'description' => 'Decomposed nested conditional into top-level guard clause',
        ];
    }

    private function simplifyBoolean(string $sourceCode): array
    {
        $exprPattern = '(\$[a-zA-Z0-9_]+(?:\s*->\s*[a-zA-Z0-9_]+(?:\s*\([^)]*\))?)*)';
        $modified = preg_replace('/' . $exprPattern . '\s*===\s*true/i', '$1', $sourceCode);
        $modified = preg_replace('/' . $exprPattern . '\s*===\s*false/i', '!$1', $modified);
        $modified = preg_replace('/if\s*\(\s*(\$[a-zA-Z0-9_]+)\s*\)\s*\{\s*return\s+true;\s*\}\s*else\s*\{\s*return\s+false;\s*\}/i', 'return (bool)$1;', $modified);

        return [
            'type'        => 'simplify_boolean',
            'success'     => true,
            'code'        => $modified,
            'description' => 'Simplified redundant boolean comparisons and ternary returns',
        ];
    }

    private function renameSymbol(string $sourceCode, array $options): array
    {
        $oldName = $options['old_name'] ?? '';
        $newName = $options['new_name'] ?? '';

        if (empty($oldName) || empty($newName)) {
            throw new \InvalidArgumentException("Both 'old_name' and 'new_name' are required for renaming");
        }

        $isVar = str_starts_with($oldName, '$');
        $cleanOld = ltrim($oldName, '$');
        $cleanNew = ltrim($newName, '$');

        if ($isVar) {
            $pattern = '/\$' . preg_quote($cleanOld, '/') . '\b/';
            $replacement = '$' . $cleanNew;
        } else {
            $pattern = '/\b' . preg_quote($cleanOld, '/') . '\b/';
            $replacement = $cleanNew;
        }

        $modified = preg_replace($pattern, $replacement, $sourceCode);

        return [
            'type'        => 'rename_symbol',
            'success'     => true,
            'code'        => $modified,
            'description' => "Renamed symbol '{$oldName}' to '{$newName}'",
        ];
    }
}
