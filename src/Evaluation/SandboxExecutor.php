<?php

namespace Atom\Evaluation;

class SandboxExecutor
{
    /**
     * Ensures evaluation runs execute safely without mutating production data or invoking destructive tools.
     */
    public function enforceSandboxMode(array $context = []): array
    {
        return array_merge($context, [
            'sandbox_active'     => true,
            'allow_writes'       => false,
            'allow_external_net' => false,
            'mock_destructive'   => true,
        ]);
    }
}
