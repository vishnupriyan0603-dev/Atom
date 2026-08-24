<?php

namespace Atom\Search;

/**
 * Code Chunk Segmenter — Phase 39
 *
 * Semantic source code chunker that slices raw source files into logical
 * method, function, and class units with precise start/end line coordinates.
 */
class CodeChunkSegmenter
{
    /**
     * Segments source code into discrete functional chunks.
     */
    public function segment(string $code, string $filePath = 'source.php'): array
    {
        $lines = explode("\n", $code);
        $chunks = [];
        $totalLines = count($lines);

        $currentChunkLines = [];
        $chunkStartLine = 1;
        $currentSymbol = 'global_scope';

        for ($i = 0; $i < $totalLines; $i++) {
            $line = $lines[$i];
            $lineNum = $i + 1;

            // Detect function or class declarations
            if (preg_match('/^\s*(?:(?:public|protected|private|static|async)\s+)*(?:function|class|interface|trait)\s+([a-zA-Z0-9_]+)/i', $line, $matches)) {
                if (!empty($currentChunkLines)) {
                    $chunks[] = [
                        'file'       => $filePath,
                        'symbol'     => $currentSymbol,
                        'start_line' => $chunkStartLine,
                        'end_line'   => $lineNum - 1,
                        'content'    => implode("\n", $currentChunkLines),
                    ];
                    $currentChunkLines = [];
                }
                $chunkStartLine = $lineNum;
                $currentSymbol = $matches[1];
            }

            $currentChunkLines[] = $line;
        }

        if (!empty($currentChunkLines)) {
            $chunks[] = [
                'file'       => $filePath,
                'symbol'     => $currentSymbol,
                'start_line' => $chunkStartLine,
                'end_line'   => $totalLines,
                'content'    => implode("\n", $currentChunkLines),
            ];
        }

        return $chunks;
    }
}
