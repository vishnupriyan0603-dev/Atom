<?php

namespace Atom\Knowledge;

/**
 * Neural Document Chunker — Phase 41
 * Intelligent semantic chunking with AST & Markdown header boundary detection,
 * token length estimation, and real-time cosine similarity distance scoring.
 */
class NeuralDocumentChunker
{
    private int $defaultChunkSize;
    private int $defaultOverlap;

    public function __construct(int $defaultChunkSize = 600, int $defaultOverlap = 100)
    {
        $this->defaultChunkSize = max(100, $defaultChunkSize);
        $this->defaultOverlap = max(0, $defaultOverlap);
    }

    /**
     * Chunk document with AST & Markdown header preservation.
     */
    public function chunkDocument(string $content, array $options = []): array
    {
        $content = trim($content);
        if (empty($content)) {
            return [];
        }

        $chunkSize = $options['chunk_size'] ?? $this->defaultChunkSize;
        $overlap   = $options['overlap'] ?? $this->defaultOverlap;
        $docTitle  = $options['doc_title'] ?? 'Document';

        // Detect code vs markdown vs text
        $isCode = preg_match('/(<\?php|function\s+|class\s+|import\s+|export\s+)/i', $content);
        $isMarkdown = preg_match('/(^#+\s+|\*{2}|\[.*\]\(.*\))/m', $content);

        // Extract sections by Markdown headers or code blocks
        $rawSections = preg_split('/(?=^#{1,4}\s+|\n(?=class\s+|function\s+))/m', $content);
        $chunks = [];
        $chunkIndex = 0;

        foreach ($rawSections as $section) {
            $section = trim($section);
            if (empty($section)) continue;

            if (strlen($section) <= $chunkSize) {
                $chunks[] = $this->createChunkObject($section, $chunkIndex++, $docTitle, $isCode ? 'code' : ($isMarkdown ? 'markdown' : 'prose'));
            } else {
                // Sub-split large section with sliding window
                $subChunks = (new Chunker())->chunk($section, $chunkSize, $overlap);
                foreach ($subChunks as $sub) {
                    $chunks[] = $this->createChunkObject($sub, $chunkIndex++, $docTitle, $isCode ? 'code' : ($isMarkdown ? 'markdown' : 'prose'));
                }
            }
        }

        return [
            'document_title' => $docTitle,
            'total_chunks'   => count($chunks),
            'total_chars'    => strlen($content),
            'estimated_tokens'=> $this->estimateTokens($content),
            'chunks'         => $chunks
        ];
    }

    /**
     * Build rich chunk metadata structure.
     */
    private function createChunkObject(string $text, int $index, string $docTitle, string $type): array
    {
        // Extract section header if present
        $header = null;
        if (preg_match('/^#+\s+(.+)$/m', $text, $m)) {
            $header = trim($m[1]);
        }

        return [
            'chunk_id'       => 'chunk_' . sprintf('%04d', $index),
            'index'          => $index,
            'doc_title'      => $docTitle,
            'header'         => $header ?? "Section " . ($index + 1),
            'content'        => $text,
            'char_count'     => strlen($text),
            'token_count'    => $this->estimateTokens($text),
            'content_type'   => $type,
            'sha256'         => hash('sha256', $text)
        ];
    }

    /**
     * Estimate token length using standard 4-char per token heuristic with whitespace adjustment.
     */
    public function estimateTokens(string $text): int
    {
        $words = preg_split('/\s+/', trim($text));
        $wordCount = count(array_filter($words));
        $charCount = strlen($text);

        return (int)ceil(max($wordCount * 1.3, $charCount / 3.8));
    }

    /**
     * Compute Cosine Similarity between two vector embeddings.
     */
    public static function cosineSimilarity(array $vecA, array $vecB): float
    {
        $count = min(count($vecA), count($vecB));
        if ($count === 0) return 0.0;

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $a = (float)$vecA[$i];
            $b = (float)$vecB[$i];

            $dotProduct += ($a * $b);
            $normA += ($a * $a);
            $normB += ($b * $b);
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return round($dotProduct / (sqrt($normA) * sqrt($normB)), 4);
    }
}
