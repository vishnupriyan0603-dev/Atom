<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\IaCBlueprintGenerator;

class IaCBlueprintGeneratorTest extends TestCase
{
    public function testGeneratesDockerfileBlueprint(): void
    {
        $generator = new IaCBlueprintGenerator();
        $res = $generator->generateBlueprint('docker', ['php_version' => '8.3']);

        $this->assertTrue($res['success']);
        $this->assertEquals('Dockerfile', $res['filename']);
        $this->assertStringContainsString('FROM php:8.3-apache', $res['blueprint']);
    }

    public function testGeneratesDockerComposeBlueprint(): void
    {
        $generator = new IaCBlueprintGenerator();
        $res = $generator->generateBlueprint('compose', ['app_name' => 'atom']);

        $this->assertTrue($res['success']);
        $this->assertEquals('docker-compose.yml', $res['filename']);
        $this->assertStringContainsString('container_name: atom-app', $res['blueprint']);
    }

    public function testGeneratesKubernetesBlueprint(): void
    {
        $generator = new IaCBlueprintGenerator();
        $res = $generator->generateBlueprint('k8s', ['app_name' => 'atom']);

        $this->assertTrue($res['success']);
        $this->assertEquals('k8s-deployment.yaml', $res['filename']);
        $this->assertStringContainsString('kind: Deployment', $res['blueprint']);
    }
}