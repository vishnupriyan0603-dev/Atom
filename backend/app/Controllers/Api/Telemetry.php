<?php

namespace App\Controllers\Api;

use Atom\Telemetry\TelemetryManager;
use Atom\Logging\RuntimeErrorLogger;

class Telemetry extends BaseApiController
{
    public function metrics()
    {
        $manager = TelemetryManager::getInstance();
        return $this->respondSuccess($manager->getMetrics());
    }

    public function spans()
    {
        $manager = TelemetryManager::getInstance();
        return $this->respondSuccess($manager->getCompletedSpans());
    }

    /**
     * Record a runtime error from client, server, or CLI.
     */
    public function logError()
    {
        $data = $this->getJsonInput();
        if (empty($data['message'])) {
            return $this->respondError('Error message is required', 400);
        }

        $logger = RuntimeErrorLogger::getInstance();
        $entry = $logger->logError($data);

        return $this->respondSuccess($entry, 'Error recorded and diagnosed successfully');
    }

    /**
     * Retrieve logged runtime errors.
     */
    public function getErrors()
    {
        $status = $this->request->getGet('status');
        $limit = (int)($this->request->getGet('limit') ?? 50);
        $logger = RuntimeErrorLogger::getInstance();
        $errors = $logger->getErrors($status, $limit);

        return $this->respondSuccess([
            'total' => count($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * Diagnose an error signature on-the-fly.
     */
    public function diagnose()
    {
        $data = $this->getJsonInput();
        $message = (string)($data['message'] ?? '');
        if (empty($message)) {
            return $this->respondError('Error message or trace is required for diagnosis', 400);
        }

        $engine = new \Atom\Testing\SelfCorrectionEngine();
        $diagnosis = $engine->diagnoseFailure($message);

        return $this->respondSuccess($diagnosis);
    }

    /**
     * Synthesize automated code fix or patch for a logged error.
     */
    public function autoFix()
    {
        $data = $this->getJsonInput();
        $errorId = (string)($data['error_id'] ?? '');
        if (empty($errorId)) {
            return $this->respondError('Error ID is required', 400);
        }

        $fileContent = isset($data['code']) ? (string)$data['code'] : null;
        $logger = RuntimeErrorLogger::getInstance();
        $result = $logger->autoFix($errorId, $fileContent);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Auto-fix failed', 404);
        }

        return $this->respondSuccess($result, 'Auto-fix synthesized successfully');
    }

    /**
     * Mark an error as resolved.
     */
    public function resolveError()
    {
        $data = $this->getJsonInput();
        $errorId = (string)($data['error_id'] ?? '');
        $notes = (string)($data['notes'] ?? 'Resolved via dashboard');

        if (empty($errorId)) {
            return $this->respondError('Error ID is required', 400);
        }

        $logger = RuntimeErrorLogger::getInstance();
        $ok = $logger->resolveError($errorId, $notes);

        if (!$ok) {
            return $this->respondError('Error record not found', 404);
        }

        return $this->respondSuccess(['error_id' => $errorId, 'status' => 'resolved'], 'Error marked as resolved');
    }

    /**
     * Clear all recorded errors.
     */
    public function clearErrors()
    {
        $logger = RuntimeErrorLogger::getInstance();
        $logger->clearErrors();
        return $this->respondSuccess(['cleared' => true], 'Error log cleared successfully');
    }
}
