<?php

namespace Atom\Infrastructure;

use Atom\Security\SecretRedactor;

/**
 * WasmSandboxRuntimeEngine — Phase 101
 * WebAssembly (Wasm) sandbox runtime simulator, gas metering, linear memory management, and isolated micro-code execution.
 */
class WasmSandboxRuntimeEngine
{
    private SecretRedactor $redactor;
    private int $defaultGasLimit = 100000;
    private int $maxMemoryBytes = 67108864; // 64MB

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Execute a sandboxed micro-routine with strict gas metering.
     *
     * @param string $functionName Name of the sandbox function to call
     * @param array $args Arguments passed to the function
     * @param int $gasLimit Maximum instructions allowed
     * @return array Execution result envelope
     */
    public function execute(string $functionName, array $args = [], int $gasLimit = 100000): array
    {
        $startTime = microtime(true);
        $cleanFunc = strtolower(trim($this->redactor->redact($functionName)));
        $effectiveGasLimit = min($this->defaultGasLimit, max(100, $gasLimit));

        $gasConsumed = 0;
        $result = null;
        $status = 'COMPLETED';

        try {
            switch ($cleanFunc) {
                case 'vector_dot_product':
                    $vecA = is_array($args[0] ?? null) ? $args[0] : [];
                    $vecB = is_array($args[1] ?? null) ? $args[1] : [];
                    $sum = 0.0;
                    $len = min(count($vecA), count($vecB));
                    for ($i = 0; $i < $len; $i++) {
                        $gasConsumed += 10;
                        if ($gasConsumed > $effectiveGasLimit) {
                            throw new \RuntimeException('GAS_LIMIT_EXCEEDED');
                        }
                        $sum += ((float)$vecA[$i] * (float)$vecB[$i]);
                    }
                    $result = $sum;
                    break;

                case 'fast_hash_crc32':
                    $str = (string)($args[0] ?? '');
                    $gasConsumed += 50 + strlen($str);
                    if ($gasConsumed > $effectiveGasLimit) {
                        throw new \RuntimeException('GAS_LIMIT_EXCEEDED');
                    }
                    $result = sprintf('%u', crc32($str));
                    break;

                case 'linear_memory_transform':
                    $buffer = $args[0] ?? [65, 66, 67]; // ASCII bytes
                    $transformed = [];
                    foreach ($buffer as $b) {
                        $gasConsumed += 15;
                        if ($gasConsumed > $effectiveGasLimit) {
                            throw new \RuntimeException('GAS_LIMIT_EXCEEDED');
                        }
                        $transformed[] = ((int)$b ^ 0x20); // Case swap
                    }
                    $result = $transformed;
                    break;

                default:
                    $gasConsumed += 10;
                    $result = 'OK_NOOP';
                    break;
            }
        } catch (\Throwable $e) {
            $status = 'TRAPPED_ERROR';
            $result = $e->getMessage();
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 3);

        return [
            'success' => ($status === 'COMPLETED'),
            'function' => $cleanFunc,
            'status' => $status,
            'result' => $result,
            'gas_limit' => $effectiveGasLimit,
            'gas_consumed' => $gasConsumed,
            'gas_remaining' => max(0, $effectiveGasLimit - $gasConsumed),
            'execution_time_ms' => $durationMs,
        ];
    }

    public function getRuntimeProfile(): array
    {
        return [
            'runtime_engine' => 'Wasm-Micro-Sandbox v1.0',
            'max_memory_mb' => 64,
            'default_gas_limit' => $this->defaultGasLimit,
            'supported_functions' => [
                'vector_dot_product',
                'fast_hash_crc32',
                'linear_memory_transform',
            ],
            'isolation_tier' => 'HARDENED_NO_IO',
        ];
    }
}
