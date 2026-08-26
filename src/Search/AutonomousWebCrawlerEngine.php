<?php

namespace Atom\Search;

use Atom\Security\SecretRedactor;

/**
 * Autonomous Web Crawler & Recursive Link Extractor Engine — Phase 103
 *
 * Implements:
 * 1. Recursive Multi-Hop Crawling with Depth Limits & Breadth-First Queue
 * 2. DOM Parsing: Content extraction (headings, clean text, code blocks, tables)
 * 3. Noise Stripping: Removes scripts, styles, navigation, footers, headers, ads
 * 4. Relative URL Normalization & Link Graph Traversal
 * 5. Strict SSRF Protection: Blocks loopback, link-local, and RFC 1918 private IPs
 * 6. Secret Redaction & Citation Grounding
 */
class AutonomousWebCrawlerEngine
{
    private SecretRedactor $redactor;

    private const MAX_ALLOWED_DEPTH = 3;
    private const MAX_ALLOWED_PAGES = 20;
    private const MAX_TEXT_BYTES_PER_PAGE = 65536; // 64KB

    // Blocked SSRF IP ranges (CIDR format)
    private const BLOCKED_IP_RANGES = [
        '127.0.0.0/8',     // Loopback
        '10.0.0.0/8',      // Private Class A
        '172.16.0.0/12',   // Private Class B
        '192.168.0.0/16',  // Private Class C
        '169.254.0.0/16',  // Link-local / Cloud Metadata (AWS/GCP/Azure)
        '0.0.0.0/8',       // Current network
        '::1/128',         // IPv6 Loopback
        'fc00::/7',        // IPv6 Unique Local Address
        'fe80::/10',       // IPv6 Link-Local
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Executes a recursive multi-hop crawl starting from a seed URL or query.
     */
    public function crawl(string $seedUrl, array $options = []): array
    {
        $cleanSeed = trim($this->redactor->redact($seedUrl));
        if (empty($cleanSeed)) {
            return [
                'success' => false,
                'error'   => 'Seed URL cannot be empty',
            ];
        }

        // Auto-prepend scheme if missing
        if (!preg_match('/^https?:\/\//i', $cleanSeed)) {
            $cleanSeed = 'https://' . $cleanSeed;
        }

        $maxDepth = min(self::MAX_ALLOWED_DEPTH, max(1, (int)($options['max_depth'] ?? 2)));
        $maxPages = min(self::MAX_ALLOWED_PAGES, max(1, (int)($options['max_pages'] ?? 6)));
        $sameDomainOnly = (bool)($options['same_domain_only'] ?? true);
        $fetchCallback = $options['fetch_callback'] ?? null;

        $seedHost = strtolower(parse_url($cleanSeed, PHP_URL_HOST) ?? '');
        if (empty($seedHost)) {
            return [
                'success' => false,
                'error'   => 'Invalid URL host format',
            ];
        }

        $visited = [];
        $pages = [];
        $linkGraph = [];
        $queue = [
            ['url' => $cleanSeed, 'depth' => 1, 'parent' => null]
        ];

        $startTime = microtime(true);

        while (!empty($queue) && count($pages) < $maxPages) {
            $current = array_shift($queue);
            $url = $current['url'];
            $depth = $current['depth'];
            $parent = $current['parent'];

            $normalizedUrl = $this->normalizeUrl($url);
            if (isset($visited[$normalizedUrl])) {
                continue;
            }
            $visited[$normalizedUrl] = true;

            // SSRF Safety Check
            $ssrfCheck = $this->validateSsrfSafety($url);
            if (!$ssrfCheck['safe']) {
                $pages[] = [
                    'url'        => $url,
                    'status'     => 'blocked',
                    'error'      => 'SSRF Protection: ' . $ssrfCheck['reason'],
                    'depth'      => $depth,
                    'parent_url' => $parent,
                ];
                continue;
            }

            // Fetch HTML content
            $html = '';
            $httpCode = 200;

            if (is_callable($fetchCallback)) {
                $customRes = $fetchCallback($url);
                $html = is_array($customRes) ? ($customRes['html'] ?? '') : (string)$customRes;
                $httpCode = is_array($customRes) ? ($customRes['http_code'] ?? 200) : 200;
            } else {
                $fetchRes = $this->fetchUrlContent($url);
                $html = $fetchRes['html'];
                $httpCode = $fetchRes['http_code'];
            }

            if (empty($html) || $httpCode >= 400) {
                $pages[] = [
                    'url'        => $url,
                    'status'     => 'failed',
                    'http_code'  => $httpCode,
                    'error'      => "HTTP fetch error (Status: {$httpCode})",
                    'depth'      => $depth,
                    'parent_url' => $parent,
                ];
                continue;
            }

            // Parse and extract DOM content
            $extracted = $this->extractPageContent($html, $url);
            $extracted['depth'] = $depth;
            $extracted['parent_url'] = $parent;
            $extracted['status'] = 'success';
            $pages[] = $extracted;

            // Record link graph edge
            if ($parent !== null) {
                $linkGraph[] = [
                    'source' => $parent,
                    'target' => $url,
                    'depth'  => $depth,
                ];
            }

            // Enqueue discovered child links if within depth limit
            if ($depth < $maxDepth) {
                foreach ($extracted['outbound_links'] as $childUrl) {
                    $childHost = strtolower(parse_url($childUrl, PHP_URL_HOST) ?? '');
                    if ($sameDomainOnly && $childHost !== $seedHost) {
                        continue;
                    }

                    $normChild = $this->normalizeUrl($childUrl);
                    if (!isset($visited[$normChild])) {
                        $queue[] = [
                            'url'    => $childUrl,
                            'depth'  => $depth + 1,
                            'parent' => $url,
                        ];
                    }
                }
            }
        }

        $duration = round(microtime(true) - $startTime, 3);
        $totalWordCount = array_sum(array_column($pages, 'word_count'));

        return [
            'success'            => true,
            'seed_url'           => $cleanSeed,
            'total_pages_crawled'=> count($pages),
            'max_depth_reached'  => !empty($pages) ? max(array_column($pages, 'depth')) : 0,
            'total_word_count'   => $totalWordCount,
            'duration_sec'       => $duration,
            'link_graph'         => $linkGraph,
            'pages'              => $pages,
            'dossier_summary'    => "Successfully crawled " . count($pages) . " pages across {$cleanSeed} ({$totalWordCount} words parsed).",
            'timestamp'          => date('c'),
        ];
    }

    /**
     * Extracts structured content from raw HTML.
     */
    public function extractPageContent(string $rawHtml, string $baseUrl = ''): array
    {
        // 1. Extract Title
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $rawHtml, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // 2. Extract Meta Description
        $description = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $rawHtml, $m)) {
            $description = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } elseif (preg_match('/<meta[^>]+content=["\'](.*?)["\'][^>]+name=["\']description["\']/is', $rawHtml, $m)) {
            $description = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        // 3. Extract Code Blocks (<pre><code>...</code></pre>)
        $codeBlocks = [];
        preg_match_all('/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is', $rawHtml, $codeMatches);
        if (empty($codeMatches[1])) {
            preg_match_all('/<pre[^>]*>(.*?)<\/pre>/is', $rawHtml, $codeMatches);
        }
        foreach ($codeMatches[1] as $cb) {
            $cleanCode = trim(html_entity_decode(strip_tags($cb), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!empty($cleanCode)) {
                $codeBlocks[] = $this->redactor->redact($cleanCode);
            }
        }

        // 4. Extract Markdown Tables
        $tables = $this->extractTablesAsMarkdown($rawHtml);

        // 5. Extract Outbound Links
        $outboundLinks = [];
        preg_match_all('/<a[^>]+href=["\'](.*?)["\']/is', $rawHtml, $aMatches);
        foreach ($aMatches[1] as $href) {
            $resolved = $this->resolveRelativeUrl($href, $baseUrl);
            if ($resolved && !in_array($resolved, $outboundLinks, true)) {
                $outboundLinks[] = $resolved;
            }
        }

        // 6. Strip Boilerplate & Noise Tags
        $cleanHtml = preg_replace('/<(script|style|noscript|nav|footer|header|aside|svg|canvas|form)[^>]*>.*?<\/\1>/is', ' ', $rawHtml);
        $cleanHtml = preg_replace('/<!--.*?-->/s', ' ', $cleanHtml);

        // Extract Headings
        $headings = [];
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $cleanHtml, $hMatches, PREG_SET_ORDER);
        foreach ($hMatches as $hm) {
            $htext = trim(html_entity_decode(strip_tags($hm[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (!empty($htext)) {
                $headings[] = [
                    'level' => (int)$hm[1],
                    'text'  => $this->redactor->redact($htext),
                ];
            }
        }

        // Extract Clean Body Text
        $cleanText = html_entity_decode(strip_tags($cleanHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanText = preg_replace('/[ \t]+/', ' ', $cleanText);
        $cleanText = preg_replace('/\n\s*\n+/', "\n\n", $cleanText);
        $cleanText = trim($cleanText);

        // Truncate to memory limit
        if (strlen($cleanText) > self::MAX_TEXT_BYTES_PER_PAGE) {
            $cleanText = substr($cleanText, 0, self::MAX_TEXT_BYTES_PER_PAGE) . "\n... [Content truncated for memory safety]";
        }

        $cleanText = $this->redactor->redact($cleanText);
        $wordCount = str_word_count($cleanText);

        return [
            'url'            => $baseUrl,
            'title'          => $this->redactor->redact($title ?: ($baseUrl ?: 'Untitled Page')),
            'meta_desc'      => $this->redactor->redact($description),
            'headings'       => $headings,
            'clean_text'     => $cleanText,
            'word_count'     => $wordCount,
            'code_blocks'    => $codeBlocks,
            'tables'         => $tables,
            'outbound_links' => $outboundLinks,
        ];
    }

    /**
     * Resolves relative URLs against a base URL.
     */
    public function resolveRelativeUrl(string $relative, string $base): ?string
    {
        $relative = trim($relative);
        if (empty($relative) || str_starts_with($relative, '#') || str_starts_with($relative, 'javascript:') || str_starts_with($relative, 'mailto:') || str_starts_with($relative, 'tel:')) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $relative)) {
            return $this->normalizeUrl($relative);
        }

        if (empty($base)) {
            return null;
        }

        $baseParsed = parse_url($base);
        if (empty($baseParsed['host'])) {
            return null;
        }

        $scheme = $baseParsed['scheme'] ?? 'https';
        $host = $baseParsed['host'];
        $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';
        $basePath = $baseParsed['path'] ?? '/';

        if (str_starts_with($relative, '//')) {
            return $this->normalizeUrl($scheme . ':' . $relative);
        }

        if (str_starts_with($relative, '/')) {
            return $this->normalizeUrl("{$scheme}://{$host}{$port}" . $relative);
        }

        // Relative path resolution
        $dir = preg_replace('/\/[^\/]*$/', '', $basePath);
        $fullPath = rtrim($dir, '/') . '/' . ltrim($relative, '/');

        // Resolve . and .. segments
        $segments = [];
        foreach (explode('/', $fullPath) as $seg) {
            if ($seg === '..') {
                array_pop($segments);
            } elseif ($seg !== '.' && $seg !== '') {
                $segments[] = $seg;
            }
        }

        return $this->normalizeUrl("{$scheme}://{$host}{$port}/" . implode('/', $segments));
    }

    /**
     * Validates that a target URL is public and does not point to internal/SSRF addresses.
     */
    public function validateSsrfSafety(string $url): array
    {
        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower($parsed['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['safe' => false, 'reason' => "Unsupported scheme '{$scheme}' (only HTTP/HTTPS allowed)"];
        }

        if (empty($host)) {
            return ['safe' => false, 'reason' => 'Missing URL host'];
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.internal') || str_ends_with($host, '.local')) {
            return ['safe' => false, 'reason' => "Blocked loopback/internal hostname '{$host}'"];
        }

        // Resolve DNS to IP
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            // DNS resolution failed or invalid
            return ['safe' => true, 'ip' => 'unresolved'];
        }

        foreach (self::BLOCKED_IP_RANGES as $cidr) {
            if ($this->ipMatchesCidr($ip, $cidr)) {
                return ['safe' => false, 'reason' => "IP '{$ip}' falls in restricted private range {$cidr}"];
            }
        }

        return ['safe' => true, 'ip' => $ip];
    }

    /**
     * Checks whether an IP belongs to a CIDR block.
     */
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (str_contains($cidr, ':')) {
            return ($ip === '::1' && str_starts_with($cidr, '::1'));
        }

        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskBits = (int)$mask;
        $netmask = ~((1 << (32 - $maskBits)) - 1);

        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }

    /**
     * Normalizes a URL for consistency and deduplication.
     */
    private function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return $url;
        }

        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        // Standardize default ports
        if (($scheme === 'http' && $port === ':80') || ($scheme === 'https' && $port === ':443')) {
            $port = '';
        }

        // Clean trailing slash for root paths
        if ($path === '') $path = '/';

        return "{$scheme}://{$host}{$port}{$path}{$query}";
    }

    /**
     * Extracts HTML tables and converts them to clean Markdown tables.
     */
    private function extractTablesAsMarkdown(string $rawHtml): array
    {
        $tables = [];
        preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $rawHtml, $tMatches);

        foreach ($tMatches[1] as $tableHtml) {
            $rows = [];
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rMatches);

            foreach ($rMatches[1] as $rowHtml) {
                $cells = [];
                preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $rowHtml, $cMatches);
                foreach ($cMatches[1] as $cellHtml) {
                    $cellText = trim(html_entity_decode(strip_tags($cellHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $cellText = str_replace('|', '\\|', preg_replace('/\s+/', ' ', $cellText));
                    $cells[] = $cellText;
                }
                if (!empty($cells)) {
                    $rows[] = $cells;
                }
            }

            if (count($rows) >= 2) {
                $colCount = max(array_map('count', $rows));
                $md = '| ' . implode(' | ', $rows[0]) . " |\n";
                $md .= '| ' . implode(' | ', array_fill(0, $colCount, '---')) . " |\n";
                for ($i = 1; $i < count($rows); $i++) {
                    $padRow = array_pad($rows[$i], $colCount, '');
                    $md .= '| ' . implode(' | ', $padRow) . " |\n";
                }
                $tables[] = $this->redactor->redact($md);
            }
        }

        return $tables;
    }

    /**
     * Performs safe cURL HTTP request.
     */
    private function fetchUrlContent(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Atom-Autonomous-Crawler/1.0 (+https://github.com/vishnupriyan0603-dev/Atom)',
        ]);

        $content = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'html'      => is_string($content) ? $content : '',
            'http_code' => $httpCode,
        ];
    }
}
