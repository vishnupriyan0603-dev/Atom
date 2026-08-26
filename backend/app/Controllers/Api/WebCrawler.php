<?php

namespace App\Controllers\Api;

use Atom\Search\AutonomousWebCrawlerEngine;
use Atom\Security\SecretRedactor;

/**
 * Autonomous Web Crawler & Recursive Link Extractor Controller — Phase 103
 */
class WebCrawler extends BaseApiController
{
    private AutonomousWebCrawlerEngine $crawler;

    public function __construct()
    {
        $this->crawler = new AutonomousWebCrawlerEngine(new SecretRedactor());
    }

    /**
     * POST /api/v1/search/crawler/crawl
     * Dispatches a recursive multi-hop crawl.
     */
    public function crawl()
    {
        $json = $this->request->getJSON(true) ?? [];
        $seedUrl = trim($json['url'] ?? ($json['seed_url'] ?? ($json['query'] ?? '')));

        if (empty($seedUrl)) {
            return $this->respondError('Seed URL is required for crawling', 400);
        }

        $options = [
            'max_depth'        => (int)($json['max_depth'] ?? 2),
            'max_pages'        => (int)($json['max_pages'] ?? 6),
            'same_domain_only' => (bool)($json['same_domain_only'] ?? true),
        ];

        $result = $this->crawler->crawl($seedUrl, $options);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Crawling failed', 400);
        }

        return $this->respondSuccess($result, $result['dossier_summary']);
    }

    /**
     * POST /api/v1/search/crawler/extract
     * Extracts structured content from raw HTML or single web page.
     */
    public function extract()
    {
        $json = $this->request->getJSON(true) ?? [];
        $rawHtml = $json['html'] ?? '';
        $url = trim($json['url'] ?? '');

        if (empty($rawHtml) && empty($url)) {
            return $this->respondError('Either url or html is required for content extraction', 400);
        }

        if (!empty($rawHtml)) {
            $extracted = $this->crawler->extractPageContent($rawHtml, $url);
            return $this->respondSuccess($extracted, 'Content extracted successfully');
        }

        $crawlRes = $this->crawler->crawl($url, ['max_depth' => 1, 'max_pages' => 1]);
        if (!$crawlRes['success'] || empty($crawlRes['pages'])) {
            return $this->respondError($crawlRes['error'] ?? 'Extraction failed', 400);
        }

        return $this->respondSuccess($crawlRes['pages'][0], 'Page content extracted successfully');
    }

    /**
     * GET /api/v1/search/crawler/status
     * Returns crawler engine specifications and safety limits.
     */
    public function status()
    {
        return $this->respondSuccess([
            'status'               => 'operational',
            'version'              => '103.0.0',
            'engine'               => 'AutonomousWebCrawlerEngine',
            'max_depth_cap'        => 3,
            'max_pages_cap'        => 20,
            'per_page_text_cap_kb' => 64,
            'ssrf_defense_active'  => true,
            'secret_redaction'     => true,
            'supported_schemes'    => ['http', 'https'],
        ], 'Web Crawler Engine Status');
    }
}
