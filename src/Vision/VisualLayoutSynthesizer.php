<?php

namespace Atom\Vision;

use Atom\Security\SecretRedactor;

/**
 * VisualLayoutSynthesizer — Phase 42
 * Converts visual UI mockups, bounding box structures, and design elements into responsive code.
 */
class VisualLayoutSynthesizer
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Synthesize code from layout description or multi-modal UI payload.
     *
     * @param array $layoutSpec [ 'title' => '...', 'components' => [...], 'theme' => 'dark'|'light'|'glass', 'framework' => 'bootstrap5'|'tailwind'|'vanilla'|'flutter' ]
     * @return array
     */
    public function synthesize(array $layoutSpec): array
    {
        $title = (string)($layoutSpec['title'] ?? 'Generated UI Component');
        $framework = strtolower((string)($layoutSpec['framework'] ?? 'bootstrap5'));
        $theme = strtolower((string)($layoutSpec['theme'] ?? 'dark'));
        $components = is_array($layoutSpec['components'] ?? null) && !empty($layoutSpec['components'])
            ? $layoutSpec['components']
            : $this->getDefaultComponents($title);

        $code = match ($framework) {
            'tailwind' => $this->generateTailwindCode($title, $components, $theme),
            'vanilla' => $this->generateVanillaCode($title, $components, $theme),
            'flutter' => $this->generateFlutterCode($title, $components, $theme),
            default => $this->generateBootstrapCode($title, $components, $theme),
        };

        // Redact any secrets
        $cleanCode = $this->redactor->redact($code);

        return [
            'success' => true,
            'title' => $title,
            'framework' => $framework,
            'theme' => $theme,
            'component_count' => count($components),
            'components' => $components,
            'generated_code' => $cleanCode,
            'responsive_breakpoints' => ['xs' => '320px', 'sm' => '576px', 'md' => '768px', 'lg' => '992px', 'xl' => '1200px'],
        ];
    }

    /**
     * Parse raw visual mockup text or tags into structured component descriptors.
     */
    public function parseComponentsFromDescription(string $description): array
    {
        $components = [];
        $desc = strtolower($description);

        if (str_contains($desc, 'navbar') || str_contains($desc, 'header') || str_contains($desc, 'menu')) {
            $components[] = ['type' => 'navbar', 'label' => 'Navigation Bar', 'props' => ['brand' => 'ATOM App', 'links' => ['Dashboard', 'Analytics', 'Settings']]];
        }

        if (str_contains($desc, 'metric') || str_contains($desc, 'stat') || str_contains($desc, 'card')) {
            $components[] = ['type' => 'metric_card', 'label' => 'Active Metric Card', 'props' => ['value' => '99.8%', 'title' => 'Uptime SLA', 'color' => 'success']];
        }

        if (str_contains($desc, 'form') || str_contains($desc, 'input') || str_contains($desc, 'login')) {
            $components[] = ['type' => 'form', 'label' => 'Input Form', 'props' => ['fields' => ['Email Address', 'API Key', 'Description'], 'button' => 'Submit Action']];
        }

        if (str_contains($desc, 'table') || str_contains($desc, 'list') || str_contains($desc, 'grid')) {
            $components[] = ['type' => 'table', 'label' => 'Data Grid Table', 'props' => ['columns' => ['ID', 'Name', 'Status', 'Latency'], 'rows' => 3]];
        }

        if (empty($components)) {
            $components = $this->getDefaultComponents('Dashboard Module');
        }

        return $components;
    }

    private function generateBootstrapCode(string $title, array $components, string $theme): string
    {
        $safeTitle = htmlspecialchars(strip_tags($title));
        $bgClass = $theme === 'glass' ? 'bg-dark bg-opacity-75 backdrop-blur border-secondary' : ($theme === 'light' ? 'bg-light text-dark' : 'bg-dark text-white border-secondary');
        $html = "<!-- Generated Bootstrap 5 UI: {$safeTitle} -->\n";
        $html .= "<div class=\"container-fluid py-4\">\n";
        $html .= "    <div class=\"d-flex justify-content-between align-items-center mb-4\">\n";
        $html .= "        <h3 class=\"fw-bold text-info mb-0\">" . htmlspecialchars($title) . "</h3>\n";
        $html .= "        <button class=\"btn btn-sm btn-primary\"><i class=\"bi bi-plus-lg me-1\"></i> Action</button>\n";
        $html .= "    </div>\n\n";

        foreach ($components as $c) {
            $type = $c['type'] ?? 'card';
            switch ($type) {
                case 'navbar':
                    $html .= "    <!-- Navigation Bar -->\n";
                    $html .= "    <nav class=\"navbar navbar-expand-lg navbar-dark {$bgClass} rounded p-3 mb-4\">\n";
                    $html .= "        <a class=\"navbar-brand fw-bold text-info\" href=\"#\">" . htmlspecialchars($c['props']['brand'] ?? 'ATOM') . "</a>\n";
                    $html .= "        <div class=\"navbar-nav ms-auto gap-2\">\n";
                    foreach (($c['props']['links'] ?? ['Home', 'Docs']) as $link) {
                        $html .= "            <a class=\"nav-link text-white small\" href=\"#\">" . htmlspecialchars($link) . "</a>\n";
                    }
                    $html .= "        </div>\n";
                    $html .= "    </nav>\n\n";
                    break;

                case 'metric_card':
                    $html .= "    <!-- Metric Cards Row -->\n";
                    $html .= "    <div class=\"row g-3 mb-4\">\n";
                    $html .= "        <div class=\"col-md-4\">\n";
                    $html .= "            <div class=\"card {$bgClass} p-3 rounded-3 shadow-sm\">\n";
                    $html .= "                <div class=\"text-muted small fw-bold\">" . htmlspecialchars($c['props']['title'] ?? 'Total Requests') . "</div>\n";
                    $html .= "                <div class=\"fs-3 fw-bold text-success\">" . htmlspecialchars($c['props']['value'] ?? '12,450') . "</div>\n";
                    $html .= "            </div>\n";
                    $html .= "        </div>\n";
                    $html .= "    </div>\n\n";
                    break;

                case 'form':
                    $html .= "    <!-- Form Panel -->\n";
                    $html .= "    <div class=\"card {$bgClass} p-4 rounded-3 mb-4 shadow-sm\">\n";
                    $html .= "        <h5 class=\"fw-bold mb-3\">" . htmlspecialchars($c['label'] ?? 'Configuration Form') . "</h5>\n";
                    $html .= "        <form>\n";
                    foreach (($c['props']['fields'] ?? ['Name', 'Value']) as $f) {
                        $html .= "            <div class=\"mb-3\">\n";
                        $html .= "                <label class=\"form-label small text-muted\">" . htmlspecialchars($f) . "</label>\n";
                        $html .= "                <input type=\"text\" class=\"form-control bg-black text-white border-secondary\" placeholder=\"Enter " . htmlspecialchars($f) . "...\">\n";
                        $html .= "            </div>\n";
                    }
                    $html .= "            <button type=\"button\" class=\"btn btn-info fw-bold w-100\">" . htmlspecialchars($c['props']['button'] ?? 'Submit') . "</button>\n";
                    $html .= "        </form>\n";
                    $html .= "    </div>\n\n";
                    break;

                default:
                    $html .= "    <div class=\"card {$bgClass} p-3 rounded-3 mb-3 shadow-sm\">\n";
                    $html .= "        <div class=\"fw-bold text-info\">" . htmlspecialchars($c['label'] ?? 'UI Component') . "</div>\n";
                    $html .= "        <p class=\"text-muted small mb-0\">Dynamic layout component rendered cleanly.</p>\n";
                    $html .= "    </div>\n\n";
                    break;
            }
        }

        $html .= "</div>\n";
        return $html;
    }

    private function generateTailwindCode(string $title, array $components, string $theme): string
    {
        $bg = $theme === 'light' ? 'bg-gray-100 text-gray-900' : 'bg-gray-900 text-gray-100';
        $cardBg = $theme === 'light' ? 'bg-white border-gray-200' : 'bg-gray-800 border-gray-700';

        $html = "<!-- Generated Tailwind CSS UI: {$title} -->\n";
        $html .= "<div class=\"min-h-screen {$bg} p-6 font-sans\">\n";
        $html .= "    <div class=\"flex justify-between items-center mb-6\">\n";
        $html .= "        <h1 class=\"text-2xl font-extrabold text-indigo-400\">" . htmlspecialchars($title) . "</h1>\n";
        $html .= "        <button class=\"px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-semibold transition\">Action</button>\n";
        $html .= "    </div>\n\n";

        foreach ($components as $c) {
            $html .= "    <div class=\"{$cardBg} border rounded-xl p-5 mb-5 shadow-lg\">\n";
            $html .= "        <h2 class=\"text-lg font-bold text-teal-400 mb-2\">" . htmlspecialchars($c['label'] ?? 'Component') . "</h2>\n";
            $html .= "        <p class=\"text-sm text-gray-400\">Tailwind CSS component generated automatically.</p>\n";
            $html .= "    </div>\n";
        }

        $html .= "</div>\n";
        return $html;
    }

    private function generateVanillaCode(string $title, array $components, string $theme): string
    {
        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "    <meta charset=\"UTF-8\">\n    <title>" . htmlspecialchars($title) . "</title>\n";
        $html .= "    <style>\n";
        $html .= "        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }\n";
        $html .= "        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin-bottom: 16px; }\n";
        $html .= "        .btn { background: #3b82f6; color: white; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; }\n";
        $html .= "    </style>\n</head>\n<body>\n";
        $html .= "    <h1>" . htmlspecialchars($title) . "</h1>\n";
        foreach ($components as $c) {
            $html .= "    <div class=\"card\">\n        <h3>" . htmlspecialchars($c['label'] ?? 'Panel') . "</h3>\n    </div>\n";
        }
        $html .= "</body>\n</html>\n";
        return $html;
    }

    private function generateFlutterCode(string $title, array $components, string $theme): string
    {
        $code = "// Generated Flutter UI: {$title}\n";
        $code .= "import 'package:flutter/material.dart';\n\n";
        $code .= "class GeneratedUiScreen extends StatelessWidget {\n";
        $code .= "  const GeneratedUiScreen({super.key});\n\n";
        $code .= "  @override\n";
        $code .= "  Widget build(BuildContext context) {\n";
        $code .= "    return Scaffold(\n";
        $code .= "      backgroundColor: const Color(0xFF0F172A),\n";
        $code .= "      appBar: AppBar(\n";
        $code .= "        title: const Text('" . addslashes($title) . "'),\n";
        $code .= "        backgroundColor: const Color(0xFF1E293B),\n";
        $code .= "      ),\n";
        $code .= "      body: ListView(\n";
        $code .= "        padding: const EdgeInsets.all(16.0),\n";
        $code .= "        children: [\n";
        foreach ($components as $c) {
            $code .= "          Card(\n";
            $code .= "            color: const Color(0xFF1E293B),\n";
            $code .= "            child: Padding(\n";
            $code .= "              padding: const EdgeInsets.all(16.0),\n";
            $code .= "              child: Text('" . addslashes($c['label'] ?? 'Widget') . "', style: const TextStyle(color: Colors.white)),\n";
            $code .= "            ),\n";
            $code .= "          ),\n";
        }
        $code .= "        ],\n";
        $code .= "      ),\n";
        $code .= "    );\n";
        $code .= "  }\n";
        $code .= "}\n";
        return $code;
    }

    private function getDefaultComponents(string $title): array
    {
        return [
            ['type' => 'navbar', 'label' => 'Header Navbar', 'props' => ['brand' => $title, 'links' => ['Home', 'Analytics', 'Settings']]],
            ['type' => 'metric_card', 'label' => 'Throughput KPI', 'props' => ['title' => 'Daily Throughput', 'value' => '4,850 ops/s']],
            ['type' => 'form', 'label' => 'Interactive Parameters', 'props' => ['fields' => ['Client Key', 'Target URI'], 'button' => 'Save Configuration']],
        ];
    }
}
