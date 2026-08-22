<?php

namespace Atom\Knowledge;

class Chunker
{
    /**
     * Splits a text string into overlapping semantic chunks.
     * Returns an array of text chunks.
     */
    public function chunk(string $text, int $chunkSize = 800, int $overlap = 150): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text)); // Clean spaces
        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $length = strlen($text);
        $pointer = 0;

        while ($pointer < $length) {
            // Take slice
            $slice = substr($text, $pointer, $chunkSize);
            
            // Try to align slice boundary to end of a sentence
            if ($pointer + $chunkSize < $length) {
                $lastDot = strrpos($slice, '. ');
                if ($lastDot !== false && $lastDot > ($chunkSize * 0.6)) {
                    $slice = substr($slice, 0, $lastDot + 1);
                } else {
                    $lastSpace = strrpos($slice, ' ');
                    if ($lastSpace !== false && $lastSpace > ($chunkSize * 0.5)) {
                        $slice = substr($slice, 0, $lastSpace);
                    }
                }
            }

            $slice = trim($slice);
            if (!empty($slice)) {
                $chunks[] = $slice;
            }

            // Move pointer forward by slice length minus overlap
            $actualLength = strlen($slice);
            if ($actualLength <= $overlap) {
                $pointer += $actualLength;
            } else {
                $pointer += ($actualLength - $overlap);
            }
        }

        return $chunks;
    }
}
