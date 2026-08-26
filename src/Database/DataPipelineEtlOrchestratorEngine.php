<?php

namespace Atom\Database;

use Atom\Security\SecretRedactor;

/**
 * DataPipelineEtlOrchestratorEngine — Phase 93
 * Multi-stage stream ETL pipeline orchestrator, data normalizer, predicate filter, and record sink aggregator.
 */
class DataPipelineEtlOrchestratorEngine
{
    private SecretRedactor $redactor;

    private array $pipelines = [
        'user_activity_sanitizer' => [
            'name' => 'User Activity & Identity Normalizer',
            'transform' => 'trim_and_lowercase_emails',
            'filter_field' => 'active',
            'filter_value' => true,
        ],
        'financial_transaction_enricher' => [
            'name' => 'Financial Order Value Normalizer',
            'transform' => 'round_currency_two_decimals',
            'filter_field' => 'amount_gte',
            'filter_value' => 10.0,
        ],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Run raw data records through an ETL pipeline.
     *
     * @param array $records List of associative record arrays
     * @param string $pipelineId Target pipeline ID
     * @param array $customTransformRules Optional rule overrides
     * @return array ETL processing result envelope
     */
    public function executePipeline(array $records, string $pipelineId = 'user_activity_sanitizer', array $customTransformRules = []): array
    {
        if (empty($records)) {
            return [
                'success' => false,
                'error' => 'Input records list cannot be empty',
                'transformed_records' => [],
                'ingested_count' => 0,
            ];
        }

        $startTime = microtime(true);
        $cleanPipeline = strtolower(trim($pipelineId));
        $config = $this->pipelines[$cleanPipeline] ?? $this->pipelines['user_activity_sanitizer'];

        $ingestedCount = count($records);
        $transformed = [];
        $filteredOutCount = 0;
        $quarantinedCount = 0;

        foreach ($records as $index => $rawRecord) {
            if (!is_array($rawRecord)) {
                $quarantinedCount++;
                continue;
            }

            // 1. Secret redaction on values
            $record = [];
            foreach ($rawRecord as $k => $v) {
                if (is_string($v)) {
                    $record[$k] = $this->redactor->redact($v);
                } else {
                    $record[$k] = $v;
                }
            }

            // 2. Filter Stage
            if ($cleanPipeline === 'user_activity_sanitizer') {
                if (isset($record['active']) && !$record['active']) {
                    $filteredOutCount++;
                    continue;
                }
            } elseif ($cleanPipeline === 'financial_transaction_enricher') {
                if (isset($record['amount']) && (float)$record['amount'] < 10.0) {
                    $filteredOutCount++;
                    continue;
                }
            }

            // 3. Transformation Stage
            if (isset($record['email']) && is_string($record['email'])) {
                $record['email'] = strtolower(trim($record['email']));
            }

            if (isset($record['amount'])) {
                $record['amount'] = round((float)$record['amount'], 2);
                $record['currency_normalized'] = strtoupper($record['currency'] ?? 'USD');
            }

            // Injected pipeline metadata
            $record['_etl_timestamp'] = round(microtime(true), 4);
            $record['_etl_checksum'] = substr(hash('sha256', json_encode($record)), 0, 16);

            $transformed[] = $record;
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'success' => true,
            'pipeline_id' => $cleanPipeline,
            'ingested_count' => $ingestedCount,
            'emitted_count' => count($transformed),
            'filtered_count' => $filteredOutCount,
            'quarantined_count' => $quarantinedCount,
            'execution_time_ms' => $durationMs,
            'records' => $transformed,
        ];
    }

    public function getAvailablePipelines(): array
    {
        return $this->pipelines;
    }
}
