<?php

namespace Atom\Knowledge;

class AdvancedRagPipeline
{
    /**
     * Chunks input text into overlapping segments.
     */
    public function chunkText(string $text, int $chunkSize = 300, int $overlap = 50): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($words)) {
            return [];
        }

        $chunks = [];
        $totalWords = count($words);
        $step = max(1, $chunkSize - $overlap);

        for ($i = 0; $i < $totalWords; $i += $step) {
            $slice = array_slice($words, $i, $chunkSize);
            $chunkString = implode(' ', $slice);
            if (mb_strlen($chunkString) > 10) {
                $chunks[] = $chunkString;
            }
        }

        return $chunks;
    }

    /**
     * Computes a unique sha256 hash for document content deduplication.
     */
    public function computeHash(string $content): string
    {
        return hash('sha256', trim($content));
    }

    /**
     * Generates a mock/toy 8-dimensional normalized vector embedding array from text.
     */
    public function generateEmbedding(string $text): array
    {
        $vec = array_fill(0, 8, 0.0);
        $words = preg_split('/\s+/', strtolower(preg_replace('/[^\w\s]/', '', $text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $len = count($words);
        if ($len === 0) return $vec;

        foreach ($words as $w) {
            $hash = crc32($w);
            $idx = abs($hash) % 8;
            $vec[$idx] += 1.0;
        }

        // Normalize
        $norm = 0.0;
        foreach ($vec as $val) $norm += $val * $val;
        $norm = sqrt($norm);
        if ($norm > 0) {
            for ($i = 0; $i < 8; $i++) $vec[$i] = round($vec[$i] / $norm, 4);
        }

        return $vec;
    }

    /**
     * Runs ingestion pipeline on raw text returning chunk metadata payloads.
     */
    public function processDocument(string $title, string $rawText, int $chunkSize = 300): array
    {
        $docHash = $this->computeHash($rawText);
        $chunks = $this->chunkText($rawText, $chunkSize);

        $payloads = [];
        foreach ($chunks as $idx => $chunkContent) {
            $payloads[] = [
                'title'       => $title,
                'chunk_index' => $idx + 1,
                'content'     => $chunkContent,
                'hash'        => hash('sha256', $chunkContent),
                'embedding'   => $this->generateEmbedding($chunkContent),
                'token_count' => count(explode(' ', $chunkContent)),
            ];
        }

        return [
            'title'        => $title,
            'doc_hash'     => $docHash,
            'total_chunks' => count($chunks),
            'chunks'       => $payloads,
        ];
    }
}
