<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\AI\FederatedLearningEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 55 — FederatedLearningEngine unit tests (6 tests).
 */
class FederatedLearningEngineTest extends TestCase
{
    private FederatedLearningEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FederatedLearningEngine(new SecretRedactor());
    }

    public function testGetDefaultGlobalWeights(): void
    {
        $weights = $this->engine->getGlobalWeights();

        $this->assertIsArray($weights);
        $this->assertArrayHasKey('layer_dense_0', $weights);
        $this->assertArrayHasKey('layer_dense_1', $weights);
        $this->assertCount(4, $weights['layer_dense_0']);
    }

    public function testAggregateClientUpdatesWithDifferentialPrivacy(): void
    {
        $updates = [
            ['node_id' => 'node_1', 'weights' => ['layer_dense_0' => [0.5, 0.5, 0.5, 0.5], 'layer_dense_1' => [0.1, 0.1, 0.1, 0.1]]],
            ['node_id' => 'node_2', 'weights' => ['layer_dense_0' => [0.5, 0.5, 0.5, 0.5], 'layer_dense_1' => [0.1, 0.1, 0.1, 0.1]]],
        ];

        $res = $this->engine->aggregateWeights($updates);

        $this->assertTrue($res['success']);
        $this->assertSame(2, $res['participating_nodes']);
        $this->assertSame(0.5, $res['privacy_epsilon']);
        $this->assertStringStartsWith('ROUND_', $res['training_round']);
        $this->assertNotEmpty($res['global_weights']);
    }

    public function testNoisePreservesWeightDimensions(): void
    {
        $updates = [
            ['node_id' => 'node_1', 'weights' => ['layer_dense_0' => [0.1, 0.2, 0.3, 0.4], 'layer_dense_1' => [0.5, 0.6, 0.7, 0.8]]],
        ];

        $res = $this->engine->aggregateWeights($updates);

        $this->assertCount(4, $res['global_weights']['layer_dense_0']);
        $this->assertCount(4, $res['global_weights']['layer_dense_1']);
    }

    public function testMultipleAggregationRoundsUpdateGlobalModel(): void
    {
        $updates = [
            ['node_id' => 'node_1', 'weights' => ['layer_dense_0' => [0.9, 0.9, 0.9, 0.9], 'layer_dense_1' => [0.9, 0.9, 0.9, 0.9]]],
        ];

        $res1 = $this->engine->aggregateWeights($updates);
        $res2 = $this->engine->aggregateWeights($updates);

        $this->assertNotSame($res1['training_round'], $res2['training_round']);
    }

    public function testEmptyUpdatesFailsGracefully(): void
    {
        $res = $this->engine->aggregateWeights([]);

        $this->assertFalse($res['success']);
        $this->assertArrayHasKey('global_weights', $res);
    }

    public function testPrivacyGuaranteeStringContainsEpsilon(): void
    {
        $updates = [
            ['node_id' => 'node_1', 'weights' => ['layer_dense_0' => [0.2, 0.2, 0.2, 0.2], 'layer_dense_1' => [0.2, 0.2, 0.2, 0.2]]],
        ];

        $res = $this->engine->aggregateWeights($updates);
        $this->assertStringContainsString('Differential Privacy Active', $res['privacy_guarantee']);
    }
}
