<?php

namespace Atom\Brain;

class ResponseParser
{
    /**
     * Parses the LLM raw response.
     * Returns an array: ['explanation' => string, 'tool_call' => ?array]
     */
    public function parse(string $rawResponse): array
    {
        $result = [
            'explanation' => $rawResponse,
            'tool_call' => null
        ];

        // Try to extract JSON between ```json and ``` or look for JSON block {...}
        $jsonStr = '';
        if (preg_match('/```json\s*(.*?)\s*```/is', $rawResponse, $matches)) {
            $jsonStr = trim($matches[1]);
        } elseif (preg_match('/^\s*\{.*\}\s*$/s', trim($rawResponse))) {
            $jsonStr = trim($rawResponse);
        } else {
            // Find first { and last }
            $start = strpos($rawResponse, '{');
            $end = strrpos($rawResponse, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $jsonStr = substr($rawResponse, $start, $end - $start + 1);
            }
        }

        if (empty($jsonStr)) {
            return $result;
        }

        $data = json_decode($jsonStr, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $result['explanation'] = $data['explanation'] ?? $rawResponse;
            if (isset($data['tool_call']) && is_array($data['tool_call']) && !empty($data['tool_call']['name'])) {
                $result['tool_call'] = $data['tool_call'];
            }
        }

        return $result;
    }
}
