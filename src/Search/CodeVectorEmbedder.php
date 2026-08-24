<?php

namespace Atom\Search;

/**
 * Code Vector Embedder — Phase 39
 *
 * Transforms source code, function signatures, AST identifiers, and docblocks
 * into deterministic, normalized multi-dimensional vector embeddings.
 */
class CodeVectorEmbedder
{
    private int $dimension;

    public function __construct(int $dimension = 64)
    {
        $this->dimension = max(16, $dimension);
    }

    /**
     * Generates a normalized vector embedding for code text or natural language query.
     */
    public function embed(string $text): array
    {
        $vector = array_fill(0, $this->dimension, 0.0);
        $clean = preg_replace('/[^a-zA-Z0-9_\s]/', ' ', strtolower($text));
        $tokens = array_filter(explode(' ', (string)$clean));

        if (empty($tokens)) {
            return $vector;
        }

        foreach ($tokens as $token) {
            $token = trim($token);
            if (strlen($token) < 2) {
                continue;
            }

            // Feature hashing into dimension buckets
            $hash = crc32($token);
            $idx = abs($hash) % $this->dimension;
            $sign = ($hash & 1) === 1 ? 1.0 : -1.0;

            // Give boosted weight to camelCase symbols and keywords
            $weight = 1.0;
            if (in_array($token, ['function', 'class', 'public', 'private', 'async', 'return', 'encrypt', 'auth', 'webrtc', 'predict'], true)) {
                $weight = 2.5;
            }

            $vector[$idx] += $sign * $weight;
        }

        // L2 Unit Normalization
        $norm = 0.0;
        foreach ($vector as $val) {
            $norm += $val * $val;
        }
        $norm = sqrt($norm);

        if ($norm > 0.0) {
            for ($i = 0; $i < $this->dimension; $i++) {
                $vector[$i] = round($vector[$i] / $norm, 6);
            }
        }

        return $vector;
    }
}
