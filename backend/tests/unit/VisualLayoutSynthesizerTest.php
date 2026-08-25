<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\VisualLayoutSynthesizer;
use Atom\Security\SecretRedactor;

/**
 * Phase 42 — VisualLayoutSynthesizer unit tests (6 tests).
 */
class VisualLayoutSynthesizerTest extends TestCase
{
    private VisualLayoutSynthesizer $synthesizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synthesizer = new VisualLayoutSynthesizer(new SecretRedactor());
    }

    public function testSynthesizeBootstrap5Layout(): void
    {
        $spec = [
            'title' => 'Dashboard Overview',
            'framework' => 'bootstrap5',
            'theme' => 'dark',
            'components' => [
                ['type' => 'navbar', 'label' => 'Header', 'props' => ['brand' => 'ATOM Cloud', 'links' => ['Overview', 'Settings']]],
                ['type' => 'metric_card', 'label' => 'KPI', 'props' => ['title' => 'Requests/sec', 'value' => '1,250']],
                ['type' => 'form', 'label' => 'Config Form', 'props' => ['fields' => ['Client ID', 'Cluster URL'], 'button' => 'Deploy']],
            ],
        ];

        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertSame('bootstrap5', $result['framework']);
        $this->assertStringContainsString('class="container-fluid', $result['generated_code']);
        $this->assertStringContainsString('ATOM Cloud', $result['generated_code']);
        $this->assertStringContainsString('Requests/sec', $result['generated_code']);
        $this->assertStringContainsString('Deploy', $result['generated_code']);
    }

    public function testSynthesizeTailwindLayout(): void
    {
        $spec = [
            'title' => 'Tailwind Analytics',
            'framework' => 'tailwind',
            'theme' => 'dark',
            'components' => [
                ['type' => 'card', 'label' => 'Memory Saturation'],
            ],
        ];

        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertSame('tailwind', $result['framework']);
        $this->assertStringContainsString('class="min-h-screen', $result['generated_code']);
        $this->assertStringContainsString('Memory Saturation', $result['generated_code']);
    }

    public function testSynthesizeFlutterLayout(): void
    {
        $spec = [
            'title' => 'Mobile Dashboard',
            'framework' => 'flutter',
            'components' => [
                ['type' => 'card', 'label' => 'Voice Assistant Node'],
            ],
        ];

        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertSame('flutter', $result['framework']);
        $this->assertStringContainsString("import 'package:flutter/material.dart';", $result['generated_code']);
        $this->assertStringContainsString('class GeneratedUiScreen extends StatelessWidget', $result['generated_code']);
    }

    public function testSynthesizeVanillaLayout(): void
    {
        $spec = [
            'title' => 'Native Web Page',
            'framework' => 'vanilla',
        ];

        $result = $this->synthesizer->synthesize($spec);

        $this->assertTrue($result['success']);
        $this->assertSame('vanilla', $result['framework']);
        $this->assertStringContainsString('<!DOCTYPE html>', $result['generated_code']);
        $this->assertStringContainsString('<style>', $result['generated_code']);
    }

    public function testParseComponentsFromDescription(): void
    {
        $desc = "Create a top navbar menu with brand ATOM. Add a metric card showing 99.9% uptime and a login form with email and password.";
        $components = $this->synthesizer->parseComponentsFromDescription($desc);

        $this->assertNotEmpty($components);
        $types = array_column($components, 'type');
        $this->assertContains('navbar', $types);
        $this->assertContains('metric_card', $types);
        $this->assertContains('form', $types);
    }

    public function testResponsiveBreakpointsMetadata(): void
    {
        $result = $this->synthesizer->synthesize(['title' => 'Responsive Layout']);

        $this->assertArrayHasKey('responsive_breakpoints', $result);
        $this->assertSame('768px', $result['responsive_breakpoints']['md']);
    }
}
