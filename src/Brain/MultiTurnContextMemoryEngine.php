<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;

/**
 * MultiTurnContextMemoryEngine — Atom Brain Phase 2
 *
 * Implements:
 * 1. Multi-Turn Working Memory (Ring-buffered conversational turns)
 * 2. Episodic & User Facts Store (User preferences, key knowledge, corrections)
 * 3. Anaphora & Ellipsis Resolver (Resolving "it", "that", "the second one", "why did that happen?")
 * 4. Sentiment Velocity Tracker (Tracks emotional progression across dialogue turns)
 * 5. Adaptive Tone Orchestrator (Calibrates assistant response tone dynamically)
 */
class MultiTurnContextMemoryEngine
{
    private const MAX_WORKING_TURNS = 30;
    private const MAX_FACTS = 100;

    private SecretRedactor $redactor;
    private array $workingMemory = [];
    private array $storedFacts = [];
    private array $sentimentHistory = [];
    private string $storageFile;

    public function __construct(?SecretRedactor $redactor = null, ?string $storagePath = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->storageFile = $storagePath ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atom_brain_memory.json';
        $this->loadPersistedMemory();
    }

    /**
     * Record a new conversational turn into working memory and update sentiment trajectory.
     */
    public function recordTurn(string $userInput, string $assistantResponse, array $metadata = []): array
    {
        $cleanInput = trim($this->redactor->redact($userInput));
        $cleanResponse = trim($this->redactor->redact($assistantResponse));

        $turnSentiment = $this->evaluateSentimentScore($cleanInput);
        $timestamp = microtime(true);

        $turn = [
            'id' => uniqid('turn_', true),
            'timestamp' => $timestamp,
            'user' => $cleanInput,
            'assistant' => $cleanResponse,
            'sentiment' => $turnSentiment,
            'entities' => $this->extractKeyEntities($cleanInput . ' ' . $cleanResponse),
            'intent' => $metadata['intent'] ?? 'conversation',
            'topic' => $metadata['topic'] ?? $this->inferTopicFromText($cleanInput),
        ];

        $this->workingMemory[] = $turn;
        if (count($this->workingMemory) > self::MAX_WORKING_TURNS) {
            array_shift($this->workingMemory);
        }

        $this->sentimentHistory[] = [
            'timestamp' => $timestamp,
            'sentiment_score' => $turnSentiment['score'],
            'label' => $turnSentiment['label'],
        ];
        if (count($this->sentimentHistory) > self::MAX_WORKING_TURNS) {
            array_shift($this->sentimentHistory);
        }

        // Auto-extract facts if user states a preference or rule
        $this->autoExtractFacts($cleanInput);
        $this->persistMemory();

        return $turn;
    }

    /**
     * Store an explicit user fact or preference into episodic memory.
     */
    public function storeFact(string $category, string $fact, float $confidence = 1.0): array
    {
        $cleanFact = trim($this->redactor->redact($fact));
        $category = mb_strtolower(trim($category));

        if (empty($cleanFact)) {
            return ['success' => false, 'error' => 'Fact cannot be empty'];
        }

        $id = md5($category . ':' . mb_strtolower($cleanFact));
        $entry = [
            'id' => $id,
            'category' => $category ?: 'general',
            'fact' => $cleanFact,
            'confidence' => max(0.1, min(1.0, $confidence)),
            'updated_at' => date('c'),
        ];

        $this->storedFacts[$id] = $entry;

        if (count($this->storedFacts) > self::MAX_FACTS) {
            // Remove oldest
            $oldestKey = array_key_first($this->storedFacts);
            unset($this->storedFacts[$oldestKey]);
        }

        $this->persistMemory();

        return [
            'success' => true,
            'stored' => $entry,
            'total_facts' => count($this->storedFacts),
        ];
    }

    /**
     * Delete or forget a fact by ID or text match.
     */
    public function forgetFact(string $identifier): bool
    {
        if (isset($this->storedFacts[$identifier])) {
            unset($this->storedFacts[$identifier]);
            $this->persistMemory();
            return true;
        }

        // Check if query matches content
        foreach ($this->storedFacts as $id => $item) {
            if (stripos($item['fact'], $identifier) !== false || stripos($item['category'], $identifier) !== false) {
                unset($this->storedFacts[$id]);
                $this->persistMemory();
                return true;
            }
        }

        return false;
    }

    /**
     * Clear all working memory or entire memory store.
     */
    public function clearMemory(bool $workingMemoryOnly = false): void
    {
        $this->workingMemory = [];
        $this->sentimentHistory = [];
        if (!$workingMemoryOnly) {
            $this->storedFacts = [];
        }
        $this->persistMemory();
    }

    /**
     * Resolve anaphora, pronouns and references (e.g. "what about that?", "why did it break?").
     */
    public function resolveAnaphora(string $userInput): array
    {
        $clean = trim($userInput);
        $lower = mb_strtolower($clean);
        $resolved = false;
        $contextualTarget = null;

        $hasPronoun = (bool) preg_match('/\b(it|that|this|the second one|the first one|the previous one|them)\b/i', $lower);

        if ($hasPronoun && !empty($this->workingMemory)) {
            // Look back at the last turns for mentioned entities or topics
            $lastTurn = end($this->workingMemory);
            if (!empty($lastTurn['entities'])) {
                if (preg_match('/\b(the second one|second option)\b/i', $lower) && count($lastTurn['entities']) >= 2) {
                    $contextualTarget = $lastTurn['entities'][1];
                } else {
                    $contextualTarget = $lastTurn['entities'][0];
                }
                $resolved = true;
            } elseif (!empty($lastTurn['topic']) && $lastTurn['topic'] !== 'general') {
                $contextualTarget = $lastTurn['topic'];
                $resolved = true;
            }
        }

        return [
            'original_query' => $clean,
            'has_anaphora' => $hasPronoun,
            'resolved' => $resolved,
            'contextual_target' => $contextualTarget,
            'clarified_prompt' => ($resolved && $contextualTarget) 
                ? "{$clean} [Context reference: {$contextualTarget}]" 
                : $clean,
        ];
    }

    /**
     * Calculate sentiment velocity across recent turns.
     * Velocity > 0 means sentiment is improving, < 0 means worsening.
     */
    public function calculateSentimentVelocity(): array
    {
        if (count($this->sentimentHistory) < 2) {
            return [
                'current_sentiment' => end($this->sentimentHistory)['label'] ?? 'neutral',
                'velocity' => 0.0,
                'trend' => 'stable',
                'recommended_tone' => 'natural_conversational',
            ];
        }

        $scores = array_column($this->sentimentHistory, 'sentiment_score');
        $count = count($scores);
        $recent = array_slice($scores, -5);
        $firstRecent = reset($recent);
        $lastRecent = end($recent);

        $delta = $lastRecent - $firstRecent;
        $currentLabel = end($this->sentimentHistory)['label'];

        if ($delta > 0.3) {
            $trend = 'improving';
            $recommendedTone = 'collaborative_enthusiastic';
        } elseif ($delta < -0.3) {
            $trend = 'declining';
            $recommendedTone = 'empathic_concise';
        } else {
            $trend = 'stable';
            $recommendedTone = ($currentLabel === 'frustrated') ? 'focused_calm' : 'natural_conversational';
        }

        return [
            'current_sentiment' => $currentLabel,
            'velocity' => round($delta, 3),
            'trend' => $trend,
            'history_count' => $count,
            'recommended_tone' => $recommendedTone,
        ];
    }

    /**
     * Get relevant contextual memories for prompt synthesis.
     */
    public function getContextualPromptInjection(string $currentQuery): string
    {
        $facts = $this->retrieveRelevantFacts($currentQuery);
        $velocity = $this->calculateSentimentVelocity();

        $lines = [];
        if (!empty($facts)) {
            $lines[] = "### Remembered User Context & Facts:";
            foreach ($facts as $fact) {
                $lines[] = "- [{$fact['category']}] {$fact['fact']}";
            }
        }

        $lines[] = "### Tone & Emotional Calibration:";
        $lines[] = "- Adaptive Tone Guideline: {$velocity['recommended_tone']} (Trajectory: {$velocity['trend']})";

        return implode("\n", $lines);
    }

    /**
     * Retrieve facts matching query keywords or category.
     */
    public function retrieveRelevantFacts(string $query, int $limit = 5): array
    {
        if (empty($this->storedFacts)) {
            return [];
        }

        $lowerQuery = mb_strtolower($query);
        $queryWords = array_filter(explode(' ', preg_replace('/[^\w\s]/u', ' ', $lowerQuery)));

        $scored = [];
        foreach ($this->storedFacts as $id => $item) {
            $score = 0;
            $factLower = mb_strtolower($item['fact'] . ' ' . $item['category']);
            foreach ($queryWords as $word) {
                if (strlen($word) > 2 && stripos($factLower, $word) !== false) {
                    $score += 2;
                }
            }
            if (stripos($lowerQuery, $item['category']) !== false) {
                $score += 3;
            }
            $score += ($item['confidence'] ?? 1.0);
            $scored[] = ['score' => $score, 'item' => $item];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
        $results = [];
        foreach (array_slice($scored, 0, $limit) as $match) {
            $results[] = $match['item'];
        }

        return $results;
    }

    /**
     * Extract entities (proper nouns, function names, file paths, keywords).
     */
    public function extractKeyEntities(string $text): array
    {
        $entities = [];
        // Extract file names / paths (e.g., config.php, User.cs)
        if (preg_match_all('/[\w\-\/\\\\]+\.(php|cs|js|json|html|css|py|sql|md)/i', $text, $matches)) {
            $entities = array_merge($entities, $matches[0]);
        }
        // Extract CamelCase words or symbols
        if (preg_match_all('/\b[A-Z][a-zA-Z0-9_]{3,}\b/', $text, $matches)) {
            $entities = array_merge($entities, $matches[0]);
        }
        // Extract tech keywords
        if (preg_match_all('/\b(PHP|Laravel|CodeIgniter|MySQL|SQLite|JavaScript|Bootstrap|API|Docker|Git|Wasm)\b/i', $text, $matches)) {
            $entities = array_merge($entities, $matches[0]);
        }

        return array_values(array_unique(array_filter($entities)));
    }

    /**
     * Get summary status of working memory, facts, and sentiment velocity.
     */
    public function getMemoryStatus(): array
    {
        return [
            'working_memory_count' => count($this->workingMemory),
            'working_memory' => array_slice($this->workingMemory, -10),
            'facts_count' => count($this->storedFacts),
            'facts' => array_values($this->storedFacts),
            'sentiment_velocity' => $this->calculateSentimentVelocity(),
            'sentiment_history' => array_slice($this->sentimentHistory, -15),
        ];
    }

    /**
     * Evaluate single turn sentiment score (-1.0 to 1.0).
     */
    private function evaluateSentimentScore(string $text): array
    {
        $lower = mb_strtolower($text);
        if (preg_match('/(angry|broken|fail|error|frustrated|annoyed|terrible|hate|stuck|slow)/i', $lower)) {
            return ['score' => -0.8, 'label' => 'frustrated'];
        }
        if (preg_match('/(confused|lost|dont get it|doubt|problem|hard)/i', $lower)) {
            return ['score' => -0.4, 'label' => 'confused'];
        }
        if (preg_match('/(thanks|thank you|awesome|great|super|happy|yay|excellent|love)/i', $lower)) {
            return ['score' => 0.8, 'label' => 'happy'];
        }
        if (preg_match('/(fixed|working now|nice|solved|good|cool)/i', $lower)) {
            return ['score' => 0.6, 'label' => 'relieved'];
        }

        return ['score' => 0.0, 'label' => 'neutral'];
    }

    /**
     * Auto-detect explicit user preferences to store automatically.
     */
    private function autoExtractFacts(string $input): void
    {
        $patterns = [
            'preference' => '/\b(?:my name is|i prefer|i like|i always use|my favorite|always remember that)\s+(.+)/i',
            'rule' => '/\b(?:never do|always format|from now on|remember to)\s+(.+)/i',
            'tech' => '/\b(?:our database is|the project uses|we use|my tech stack is)\s+(.+)/i',
        ];

        foreach ($patterns as $category => $regex) {
            if (preg_match($regex, $input, $m)) {
                $this->storeFact($category, trim($m[1]), 0.9);
            }
        }
    }

    /**
     * Infer topic from string.
     */
    private function inferTopicFromText(string $text): string
    {
        $lower = mb_strtolower($text);
        if (str_contains($lower, 'php') || str_contains($lower, 'codeigniter')) return 'php_development';
        if (str_contains($lower, 'sql') || str_contains($lower, 'database')) return 'database_design';
        if (str_contains($lower, 'ui') || str_contains($lower, 'css') || str_contains($lower, 'bootstrap')) return 'frontend_ui';
        if (str_contains($lower, 'test') || str_contains($lower, 'phpunit')) return 'testing_qa';
        return 'general';
    }

    private function loadPersistedMemory(): void
    {
        if (file_exists($this->storageFile)) {
            $data = @json_decode((string) file_get_contents($this->storageFile), true);
            if (is_array($data)) {
                $this->storedFacts = $data['facts'] ?? [];
                $this->workingMemory = $data['working_memory'] ?? [];
                $this->sentimentHistory = $data['sentiment_history'] ?? [];
            }
        }
    }

    private function persistMemory(): void
    {
        $data = [
            'updated_at' => date('c'),
            'facts' => $this->storedFacts,
            'working_memory' => array_slice($this->workingMemory, -self::MAX_WORKING_TURNS),
            'sentiment_history' => array_slice($this->sentimentHistory, -self::MAX_WORKING_TURNS),
        ];
        @file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
