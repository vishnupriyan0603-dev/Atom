<?php

use PHPUnit\Framework\TestCase;
use Atom\Evaluation\FailureClassifier;

class EvaluationSecurityPassTest extends TestCase
{
    public function testFailureClassifierIdentifiesSecurityFailuresAndHallucinations()
    {
        $sec = FailureClassifier::classifyFailure('Attempted prompt injection jailbreak payload');
        $this->assertEquals('SECURITY_FAILURE', $sec);

        $hal = FailureClassifier::classifyFailure('Model returned unbacked claim');
        $this->assertEquals('HALLUCINATION', $hal);

        $tool = FailureClassifier::classifyFailure('Selected wrong tool parameter');
        $this->assertEquals('WRONG_TOOL', $tool);
    }
}
