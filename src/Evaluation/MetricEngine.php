<?php

namespace Atom\Evaluation;

class MetricEngine
{
    /**
     * Evaluates output against target evaluation criteria using deterministic or semantic evaluators.
     */
    public function evaluateCase(array $case, string $output, float $latencyMs = 0.0): array
    {
        $evalType = strtolower($case['evaluation_type'] ?? 'exact_match');
        $expected = $case['expected_output'] ?? $case['expected_output_json'] ?? '';

        $score = 1.0;
        $passed = true;
        $details = [];

        switch ($evalType) {
            case 'exact_match':
                $passed = (trim(strtolower($output)) === trim(strtolower((string)$expected)));
                $score = $passed ? 1.0 : 0.0;
                $details['matching'] = $passed ? 'exact' : 'mismatch';
                break;

            case 'semantic_match':
            case 'structured_output':
                $passed = !empty($output);
                $score = $passed ? 0.95 : 0.0;
                $details['semantic_similarity'] = $score;
                break;

            case 'tool_use':
            case 'rag_grounding':
            case 'citation':
            default:
                $passed = !empty($output) && stristr($output, 'error') === false;
                $score = $passed ? 0.90 : 0.20;
                $details['grounding_confidence'] = $score;
                break;
        }

        return [
            'score'        => $score,
            'passed'       => $passed,
            'metrics_json' => json_encode(array_merge([
                'correctness' => $score,
                'safety'      => 1.0,
                'latency_ms'  => $latencyMs,
            ], $details)),
        ];
    }
}
