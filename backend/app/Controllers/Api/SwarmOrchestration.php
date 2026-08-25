<?php

namespace App\Controllers\Api;

use Atom\Swarm\SwarmOrchestrationHub;
use Atom\Voice\AudioDspFilterEngine;
use Atom\Knowledge\NeuralDocumentChunker;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autonomous Multi-Modal Agent Orchestration API Controller — Phase 41
 *
 * Endpoints:
 * - GET  /api/swarm/topology              — Get registered agents and topology
 * - POST /api/swarm/plan                  — Decompose goal into multi-agent work orders
 * - POST /api/swarm/consensus             — Evaluate weighted consensus voting
 * - POST /api/swarm/synthesize            — Merge verified agent contributions
 * - GET  /api/audio-dsp/graph             — Get 10-band Web Audio DSP filter graph
 * - POST /api/audio-dsp/fft               — Compute FFT frequency spectrum bins
 * - POST /api/neural-knowledge/chunk      — Perform semantic AST document chunking
 * - POST /api/neural-knowledge/similarity — Calculate vector cosine similarity
 */
class SwarmOrchestration extends BaseApiController
{
    private static ?SwarmOrchestrationHub $swarmHub = null;
    private static ?AudioDspFilterEngine $dspEngine = null;
    private static ?NeuralDocumentChunker $chunker = null;

    private function getSwarmHub(): SwarmOrchestrationHub
    {
        if (self::$swarmHub === null) {
            self::$swarmHub = new SwarmOrchestrationHub();
        }
        return self::$swarmHub;
    }

    private function getDspEngine(): AudioDspFilterEngine
    {
        if (self::$dspEngine === null) {
            self::$dspEngine = new AudioDspFilterEngine();
        }
        return self::$dspEngine;
    }

    private function getChunker(): NeuralDocumentChunker
    {
        if (self::$chunker === null) {
            self::$chunker = new NeuralDocumentChunker();
        }
        return self::$chunker;
    }

    public function topology(): ResponseInterface
    {
        $topology = $this->getSwarmHub()->getSwarmTopology();
        return $this->respondSuccess($topology);
    }

    public function plan(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $goal = trim($input['goal'] ?? '');

        if (empty($goal)) {
            return $this->respondError('Goal parameter is required.', 400);
        }

        try {
            $plan = $this->getSwarmHub()->planSwarmExecution($goal);
            return $this->respondSuccess($plan);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    public function consensus(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $claims = $input['claims'] ?? [];

        if (empty($claims)) {
            return $this->respondError('Claims array is required.', 400);
        }

        try {
            $result = $this->getSwarmHub()->evaluateConsensus($claims);
            return $this->respondSuccess($result);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    public function synthesize(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $taskTitle = $input['task_title'] ?? 'Synthesized Task';
        $contributions = $input['contributions'] ?? [];

        try {
            $artifact = $this->getSwarmHub()->synthesizeArtifact($taskTitle, $contributions);
            return $this->respondSuccess($artifact);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    public function dspGraph(): ResponseInterface
    {
        $graph = $this->getDspEngine()->getFilterGraph();
        return $this->respondSuccess($graph);
    }

    public function dspFft(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $frequencies = $input['frequencies'] ?? [];
        $fft = $this->getDspEngine()->computeFftSpectrum($frequencies);
        return $this->respondSuccess($fft);
    }

    public function chunk(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $content = $input['content'] ?? '';
        $options = $input['options'] ?? [];

        if (empty($content)) {
            return $this->respondError('Document content is required for chunking.', 400);
        }

        try {
            $chunks = $this->getChunker()->chunkDocument($content, $options);
            return $this->respondSuccess($chunks);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), 500);
        }
    }

    public function similarity(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? [];
        $vecA = $input['vector_a'] ?? [];
        $vecB = $input['vector_b'] ?? [];

        $score = NeuralDocumentChunker::cosineSimilarity($vecA, $vecB);
        return $this->respondSuccess([
            'cosine_similarity' => $score,
            'match_level'       => ($score >= 0.85) ? 'high' : (($score >= 0.65) ? 'moderate' : 'low')
        ]);
    }
}
