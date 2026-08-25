<?php

namespace Atom\Voice;

/**
 * Tamil Phoneme Engine
 *
 * Implements rule-based Tamil phonetics, syllable segmentation, gemination
 * stress detection, and phonetic normalization for natural speech synthesis.
 */
class TamilPhonemeEngine
{
    // Tamil Unicode vowels and consonants
    private const TAMIL_VOWELS = ['அ', 'ஆ', 'இ', 'ஈ', 'உ', 'ஊ', 'எ', 'ஏ', 'ஐ', 'ஒ', 'ஓ', 'ஔ'];
    private const TAMIL_RETROFLEXES = ['ழ', 'ள', 'ற', 'ண', 'ட'];
    private const TAMIL_PLOSIVES = ['க', 'ச', 'ட', 'த', 'ப', 'ற'];

    /**
     * Analyze and extract phonetic features from a Tamil sentence.
     */
    public function analyzePhonetics(string $text): array
    {
        $text = trim($text);
        if (empty($text)) {
            return [];
        }

        $words = preg_split('/\s+/u', $text);
        $wordAnalysis = [];
        $totalRetroflexCount = 0;
        $totalGeminateCount = 0;

        foreach ($words as $word) {
            $retroCount = 0;
            $geminateCount = 0;

            foreach (self::TAMIL_RETROFLEXES as $char) {
                $retroCount += mb_substr_count($word, $char, 'UTF-8');
            }

            // Check for pulli (virama / geminated consonants e.g., த்த, க்க)
            $pulliCount = mb_substr_count($word, '்', 'UTF-8');
            if ($pulliCount >= 1) {
                $geminateCount += $pulliCount;
            }

            $totalRetroflexCount += $retroCount;
            $totalGeminateCount += $geminateCount;

            $wordAnalysis[] = [
                'word'               => $word,
                'char_count'         => mb_strlen($word, 'UTF-8'),
                'retroflex_count'    => $retroCount,
                'geminate_intensity' => $geminateCount,
                'stress_level'       => ($geminateCount > 0 || $retroCount > 0) ? 'elevated' : 'standard'
            ];
        }

        return [
            'total_words'         => count($words),
            'retroflex_count'     => $totalRetroflexCount,
            'gemination_count'    => $totalGeminateCount,
            'pronunciation_rhythm'=> ($totalGeminateCount > 3) ? 'punchy_heroic' : 'natural_conversational',
            'words'               => $wordAnalysis
        ];
    }

    /**
     * Enhance plain Tamil or Tanglish text with natural phonetic pauses and breath breaks.
     */
    public function formatProsodicBreaks(string $text): string
    {
        $formatted = preg_replace('/([,.!?]+)/u', '$1 <break time="150ms"/> ', $text);
        $formatted = preg_replace('/\s+/', ' ', trim($formatted));
        return $formatted;
    }
}
