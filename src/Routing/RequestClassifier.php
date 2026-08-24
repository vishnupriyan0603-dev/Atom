<?php

namespace Atom\Routing;

class RequestClassifier
{
    /**
     * Classifies request into structured categories based on metadata and parameters.
     */
    public function classifyRequest(array $requestContext): array
    {
        $operation = strtolower($requestContext['operation'] ?? $requestContext['task'] ?? 'chat');
        $hasTools  = !empty($requestContext['tools']);

        $category = 'simple_chat';
        if ($hasTools) {
            $category = 'tool_use';
        } elseif (strpos($operation, 'code') !== false || strpos($operation, 'coding') !== false) {
            $category = 'coding';
        } elseif (strpos($operation, 'research') !== false) {

            $category = 'research';
        } elseif (strpos($operation, 'rag') !== false || !empty($requestContext['rag'])) {
            $category = 'rag';
        }

        return [
            'category'           => $category,
            'required_features'  => $hasTools ? ['tool_calling'] : [],
            'is_high_risk'       => !empty($requestContext['high_risk']),
        ];
    }
}
