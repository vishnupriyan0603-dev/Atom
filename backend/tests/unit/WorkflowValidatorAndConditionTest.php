<?php

use PHPUnit\Framework\TestCase;
use Atom\Workflow\WorkflowValidator;
use Atom\Workflow\ConditionEngine;
use Atom\Workflow\VariableResolver;

class WorkflowValidatorAndConditionTest extends TestCase
{
    public function testValidatorDetectsMandatoryStartAndEndNodes()
    {
        $validator = new WorkflowValidator();

        $validGraph = [
            'nodes' => [
                ['key' => 'n1', 'type' => 'START'],
                ['key' => 'n2', 'type' => 'AGENT'],
                ['key' => 'n3', 'type' => 'END'],
            ]
        ];
        $res = $validator->validateGraph($validGraph);
        $this->assertTrue($res['valid']);

        $invalidGraph = [
            'nodes' => [
                ['key' => 'n1', 'type' => 'AGENT'],
            ]
        ];
        $res2 = $validator->validateGraph($invalidGraph);
        $this->assertFalse($res2['valid']);
    }

    public function testConditionEngineEvaluatesSafeExpressions()
    {
        $engine = new ConditionEngine();
        $vars = [
            'variables' => ['score' => 0.95],
            'steps'     => ['research' => ['status' => 'success']],
        ];

        $this->assertTrue($engine->evaluate("{{variables.score}} >= 0.8", $vars));
        $this->assertTrue($engine->evaluate("{{steps.research.status}} == 'success'", $vars));
        $this->assertFalse($engine->evaluate("{{variables.score}} < 0.5", $vars));
    }
}
