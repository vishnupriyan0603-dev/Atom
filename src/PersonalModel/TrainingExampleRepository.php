<?php

namespace Atom\PersonalModel;

use Atom\Database\Connection;
use PDO;

/**
 * TrainingExampleRepository
 *
 * Manages the atom_training_examples table with full data optimization:
 *
 *  1.  Compare new question with existing questions.
 *  2.  Normalize case, whitespace, punctuation, and obvious wording variations.
 *  3.  Detect exact duplicates.
 *  4.  Detect semantically similar questions (token overlap ≥ 75 %).
 *  5.  If response is identical / substantially equivalent → merge question
 *      into existing group; do NOT create a duplicate record.
 *  6.  If question is similar but answer has useful additional info → keep
 *      best complete answer, merge useful snippets into canonical response.
 *  7.  Remove obsolete, contradictory, empty, or low-quality responses.
 *  8.  Never duplicate the same response unnecessarily.
 *  9.  Prefer one high-quality canonical answer over many repeated answers.
 * 10.  Preserve technical commands, code, URLs, errors, config values.
 * 11.  Do not merge responses that are technically different.
 * 12.  After optimization, validate that no duplicate or contradictory records
 *      remain.
 */
class TrainingExampleRepository
{
    private ?Connection $connection;

    /** Minimum token-overlap ratio to call two questions "similar". */
    private const SIMILARITY_THRESHOLD = 0.75;

    /** Minimum token-overlap ratio to call two responses "substantially equivalent". */
    private const RESPONSE_SIMILARITY_THRESHOLD = 0.80;

    /** Minimum character length for a response to be kept. */
    private const MIN_RESPONSE_LENGTH = 20;

    public function __construct(?Connection $connection)
    {
        $this->connection = $connection;
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Add a training example after full optimization checks (rules 1–12).
     *
     * @param string      $userInput         The question / user message.
     * @param string      $preferredResponse The desired model response.
     * @param string|null $category          Optional category tag.
     * @param string|null $contextSummary    Optional context summary.
     * @param string      $source            Source label, e.g. 'user_approved'.
     * @param string      $quality           Quality tag, e.g. 'GOOD', 'VERIFIED'.
     *
     * @return array ['action' => 'inserted'|'merged'|'skipped'|'merged_response', 'id' => int|null, 'reason' => string]
     */
    public function add(
        string  $userInput,
        string  $preferredResponse,
        ?string $category       = null,
        ?string $contextSummary = null,
        string  $source         = 'user_approved',
        string  $quality        = 'GOOD'
    ): array {
        if (!$this->isConnected()) {
            return ['action' => 'skipped', 'id' => null, 'reason' => 'No database connection'];
        }

        // Rule 7: Reject empty or low-quality responses immediately
        $userInput         = trim($userInput);
        $preferredResponse = trim($preferredResponse);

        if (empty($userInput)) {
            return ['action' => 'skipped', 'id' => null, 'reason' => 'Empty user input'];
        }
        if (strlen($preferredResponse) < self::MIN_RESPONSE_LENGTH) {
            return ['action' => 'skipped', 'id' => null, 'reason' => 'Response too short or low-quality'];
        }

        // Rule 2: Normalize the input for comparison
        $normalizedInput    = $this->normalize($userInput);
        $normalizedResponse = $this->normalize($preferredResponse);

        // Load all existing records for comparison (rules 1-4)
        $existing = $this->loadAll();

        foreach ($existing as $record) {
            $existingNormInput    = $this->normalize((string)$record['user_input']);
            $existingNormResponse = $this->normalize((string)$record['preferred_response']);

            // Rule 3: Exact duplicate question
            if ($existingNormInput === $normalizedInput) {
                // Rule 8/9: Same question, check response
                if ($existingNormResponse === $normalizedResponse) {
                    // Rule 5: Identical response → pure duplicate, skip
                    return [
                        'action' => 'skipped',
                        'id'     => (int)$record['id'],
                        'reason' => 'Exact duplicate question and response — skipped',
                    ];
                }

                // Same question, different response: keep better answer (rule 6)
                return $this->mergeResponse(
                    (int)$record['id'],
                    $record,
                    $preferredResponse,
                    $normalizedResponse,
                    $existingNormResponse,
                    $source,
                    $quality
                );
            }

            // Rule 4: Semantically similar question
            $questionSimilarity = $this->tokenSimilarity($normalizedInput, $existingNormInput);
            if ($questionSimilarity >= self::SIMILARITY_THRESHOLD) {
                $responseSimilarity = $this->tokenSimilarity($normalizedResponse, $existingNormResponse);

                // Rule 5: Similar question, substantially equivalent response → merge question, skip insert
                if ($responseSimilarity >= self::RESPONSE_SIMILARITY_THRESHOLD) {
                    $this->appendQuestionAlias(
                        (int)$record['id'],
                        $userInput,
                        $contextSummary
                    );
                    return [
                        'action' => 'merged',
                        'id'     => (int)$record['id'],
                        'reason' => sprintf(
                            'Question merged into record #%d (question similarity %.0f%%, response similarity %.0f%%)',
                            $record['id'],
                            $questionSimilarity * 100,
                            $responseSimilarity * 100
                        ),
                    ];
                }

                // Rule 6 / Rule 11: Similar question but response has additional useful info
                // Only merge if the new response is longer (richer), otherwise skip
                $newHasCode     = $this->containsTechnicalContent($preferredResponse);
                $existHasCode   = $this->containsTechnicalContent((string)$record['preferred_response']);

                if (strlen($preferredResponse) > strlen((string)$record['preferred_response']) || ($newHasCode && !$existHasCode)) {
                    return $this->mergeResponse(
                        (int)$record['id'],
                        $record,
                        $preferredResponse,
                        $normalizedResponse,
                        $existingNormResponse,
                        $source,
                        $quality
                    );
                }

                // Existing response is already richer — just merge the question alias
                $this->appendQuestionAlias((int)$record['id'], $userInput, $contextSummary);
                return [
                    'action' => 'merged',
                    'id'     => (int)$record['id'],
                    'reason' => sprintf(
                        'Question alias merged into record #%d — existing response is canonical',
                        $record['id']
                    ),
                ];
            }
        }

        // No duplicate or similar record found → insert new canonical record
        $id = $this->insert($userInput, $preferredResponse, $category, $contextSummary, $source, $quality);

        // Rule 12: Post-insert validation — remove any newly-duplicated records
        $this->runPostOptimization();

        return [
            'action' => 'inserted',
            'id'     => $id,
            'reason' => 'New canonical training example inserted',
        ];
    }

    /**
     * Run a full deduplication pass over the entire table (rule 12).
     * Finds and removes duplicate or contradictory records, keeping the
     * highest-quality canonical version.
     *
     * @return array ['removed' => int, 'merged' => int]
     */
    public function optimize(): array
    {
        if (!$this->isConnected()) {
            return ['removed' => 0, 'merged' => 0];
        }

        $records = $this->loadAll();
        $removed = 0;
        $merged  = 0;
        $seen    = []; // normalized_input => record_id

        foreach ($records as $record) {
            $normInput = $this->normalize((string)$record['user_input']);
            $normResp  = $this->normalize((string)$record['preferred_response']);

            // Rule 7: Remove empty or low-quality records
            if (empty($normInput) || strlen((string)$record['preferred_response']) < self::MIN_RESPONSE_LENGTH) {
                $this->deleteRecord((int)$record['id']);
                $removed++;
                continue;
            }

            // Rule 7: Remove REJECTED records
            if (isset($record['quality']) && strtoupper((string)$record['quality']) === 'REJECTED') {
                $this->deleteRecord((int)$record['id']);
                $removed++;
                continue;
            }

            // Rule 3: Exact duplicate check
            if (isset($seen[$normInput])) {
                $canonicalId = $seen[$normInput];
                $canonical   = $this->findById($canonicalId);
                if ($canonical) {
                    $canonNorm = $this->normalize((string)$canonical['preferred_response']);
                    if ($canonNorm === $normResp) {
                        // Pure duplicate → delete current, keep canonical
                        $this->deleteRecord((int)$record['id']);
                        $removed++;
                    } else {
                        // Different response — keep better, delete worse
                        $this->mergeResponse(
                            $canonicalId,
                            $canonical,
                            (string)$record['preferred_response'],
                            $normResp,
                            $canonNorm,
                            (string)($record['source'] ?? 'user_approved'),
                            (string)($record['quality'] ?? 'GOOD')
                        );
                        $this->deleteRecord((int)$record['id']);
                        $merged++;
                    }
                }
                continue;
            }

            // Rule 4: Semantic similarity check against all previously seen records
            $duplicate = false;
            foreach ($seen as $seenNorm => $seenId) {
                $similarity = $this->tokenSimilarity($normInput, $seenNorm);
                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $canonical = $this->findById($seenId);
                    if ($canonical) {
                        $canonNorm  = $this->normalize((string)$canonical['preferred_response']);
                        $respSim    = $this->tokenSimilarity($normResp, $canonNorm);

                        if ($respSim >= self::RESPONSE_SIMILARITY_THRESHOLD) {
                            // Rule 5: Near-duplicate → delete
                            $this->appendQuestionAlias($seenId, (string)$record['user_input'], null);
                            $this->deleteRecord((int)$record['id']);
                            $removed++;
                        } else {
                            // Rule 6/11: Similar question, different useful response → merge
                            $this->mergeResponse(
                                $seenId,
                                $canonical,
                                (string)$record['preferred_response'],
                                $normResp,
                                $canonNorm,
                                (string)($record['source'] ?? 'user_approved'),
                                (string)($record['quality'] ?? 'GOOD')
                            );
                            $this->deleteRecord((int)$record['id']);
                            $merged++;
                        }
                    }
                    $duplicate = true;
                    break;
                }
            }

            if (!$duplicate) {
                $seen[$normInput] = (int)$record['id'];
            }
        }

        return ['removed' => $removed, 'merged' => $merged];
    }

    /**
     * Return all training examples ordered by quality then creation date.
     */
    public function getAll(): array
    {
        if (!$this->isConnected()) {
            return [];
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->query(
                "SELECT * FROM atom_training_examples
                 ORDER BY FIELD(quality,'VERIFIED','GOOD','CORRECTED','UNREVIEWED','REJECTED'), created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Mark a training record as REJECTED and remove it (rule 7).
     */
    public function reject(int $id): bool
    {
        if (!$this->isConnected()) {
            return false;
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("UPDATE atom_training_examples SET quality = 'REJECTED' WHERE id = ?");
            $stmt->execute([$id]);
            // Actually delete rejected records to keep table clean
            $this->deleteRecord($id);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ----------------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------------

    /**
     * Normalize text for comparison (rule 2):
     * lowercase, collapse whitespace, strip punctuation, trim.
     */
    private function normalize(string $text): string
    {
        $text = strtolower($text);
        // Preserve code-like content by keeping alphanumeric + underscores + dots
        $text = preg_replace('/[^\w\s\.]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Compute Jaccard token-similarity between two normalized strings.
     * Returns a float in [0, 1].
     */
    private function tokenSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $tokensA = array_filter(explode(' ', $a));
        $tokensB = array_filter(explode(' ', $b));

        if (empty($tokensA) || empty($tokensB)) {
            return 0.0;
        }

        // Filter out very short stop words to reduce false positives
        $stopWords = ['a', 'an', 'the', 'is', 'in', 'on', 'at', 'to', 'for', 'of', 'and', 'or', 'i', 'my', 'me'];
        $filterFn  = fn($t) => strlen($t) > 2 && !in_array($t, $stopWords, true);

        $filteredA = array_filter($tokensA, $filterFn);
        $filteredB = array_filter($tokensB, $filterFn);

        if (empty($filteredA) || empty($filteredB)) {
            // Fall back to full token sets if filtering removes everything
            $filteredA = $tokensA;
            $filteredB = $tokensB;
        }

        $setA = array_unique($filteredA);
        $setB = array_unique($filteredB);

        $intersection = count(array_intersect($setA, $setB));
        $union        = count(array_unique(array_merge($setA, $setB)));

        return $union === 0 ? 0.0 : ($intersection / $union);
    }

    /**
     * Rule 10: Detect technical content that must be preserved.
     */
    private function containsTechnicalContent(string $text): bool
    {
        return (bool) preg_match(
            '/```|<\?php|http[s]?:\/\/|\$[a-zA-Z_]|\b[A-Z_]{3,}\b|:\d{4}|ERROR|Exception|--[a-z]/i',
            $text
        );
    }

    /**
     * Decide which response is canonical and update the DB record (rules 6, 9, 10, 11).
     */
    private function mergeResponse(
        int    $existingId,
        array  $existingRecord,
        string $newResponse,
        string $newNormResp,
        string $existNormResp,
        string $source,
        string $quality
    ): array {
        // Rule 11: If responses are technically different in meaningful ways, don't merge
        $newHasTech  = $this->containsTechnicalContent($newResponse);
        $existHasTech = $this->containsTechnicalContent((string)$existingRecord['preferred_response']);

        if ($newHasTech && $existHasTech) {
            // Both have technical content — keep whichever is longer (more complete)
            if (strlen($newResponse) > strlen((string)$existingRecord['preferred_response'])) {
                $canonical = $newResponse;
            } else {
                // Existing is already better
                return [
                    'action' => 'skipped',
                    'id'     => $existingId,
                    'reason' => 'Existing response is already canonical (technical, longer)',
                ];
            }
        } elseif ($newHasTech && !$existHasTech) {
            // New response has code/technical detail, existing doesn't — new wins
            $canonical = $newResponse;
        } elseif (!$newHasTech && $existHasTech) {
            // Existing has technical content, new doesn't — keep existing
            return [
                'action' => 'skipped',
                'id'     => $existingId,
                'reason' => 'Existing response retained — contains technical content',
            ];
        } else {
            // Neither has technical content — prefer longer, higher-quality response
            $existQuality = $existingRecord['quality'] ?? 'UNREVIEWED';
            $qualityRank  = ['VERIFIED' => 5, 'GOOD' => 4, 'CORRECTED' => 3, 'UNREVIEWED' => 2, 'REJECTED' => 1];
            $newRank      = $qualityRank[$quality] ?? 2;
            $existRank    = $qualityRank[$existQuality] ?? 2;

            if ($newRank > $existRank) {
                $canonical = $newResponse;
            } elseif ($existRank > $newRank) {
                return [
                    'action' => 'skipped',
                    'id'     => $existingId,
                    'reason' => 'Existing response retained — higher quality rating',
                ];
            } else {
                // Same quality — keep the longer one (rule 9)
                $canonical = strlen($newResponse) >= strlen((string)$existingRecord['preferred_response'])
                    ? $newResponse
                    : (string)$existingRecord['preferred_response'];
            }
        }

        // Update the canonical record
        $this->updateResponse($existingId, $canonical, $quality);

        return [
            'action' => 'merged_response',
            'id'     => $existingId,
            'reason' => "Response merged into record #$existingId — canonical answer updated",
        ];
    }

    /**
     * Append a question alias into context_summary so we track grouped questions
     * without creating new records (rule 5).
     */
    private function appendQuestionAlias(int $id, string $question, ?string $contextSummary): void
    {
        if (!$this->isConnected()) {
            return;
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT context_summary FROM atom_training_examples WHERE id = ?");
            $stmt->execute([$id]);
            $existing = (string)($stmt->fetchColumn() ?: '');

            $alias   = '[ALIAS] ' . $question;
            $newCtx  = $existing ? $existing . "\n" . $alias : $alias;
            if ($contextSummary) {
                $newCtx .= "\n[CTX] " . $contextSummary;
            }

            $upd = $pdo->prepare("UPDATE atom_training_examples SET context_summary = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$newCtx, $id]);
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    private function insert(
        string  $userInput,
        string  $preferredResponse,
        ?string $category,
        ?string $contextSummary,
        string  $source,
        string  $quality
    ): ?int {
        if (!$this->isConnected()) {
            return null;
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO atom_training_examples
                    (category, user_input, context_summary, preferred_response, source, quality, verified)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$category, $userInput, $contextSummary, $preferredResponse, $source, $quality]);
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function updateResponse(int $id, string $response, string $quality): void
    {
        if (!$this->isConnected()) {
            return;
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                UPDATE atom_training_examples
                SET preferred_response = ?, quality = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$response, $quality, $id]);
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    private function deleteRecord(int $id): void
    {
        if (!$this->isConnected()) {
            return;
        }
        $pdo = $this->connection->getPdo();
        try {
            $pdo->prepare("DELETE FROM atom_training_examples WHERE id = ?")->execute([$id]);
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    private function findById(int $id): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }
        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("SELECT * FROM atom_training_examples WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function loadAll(): array
    {
        if (!$this->isConnected()) {
            return [];
        }
        $pdo = $this->connection->getPdo();
        try {
            return $pdo->query(
                "SELECT * FROM atom_training_examples
                 ORDER BY FIELD(quality,'VERIFIED','GOOD','CORRECTED','UNREVIEWED','REJECTED'), id ASC"
            )->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Rule 12: After every insert, clean up any newly introduced duplicates.
     * This is lightweight — only checks records added within the last minute.
     */
    private function runPostOptimization(): void
    {
        // Full optimize() is expensive; post-insert we just check for exact
        // duplicates introduced by race conditions or rapid inserts.
        if (!$this->isConnected()) {
            return;
        }
        $pdo = $this->connection->getPdo();
        try {
            // Find user_inputs that appear more than once (normalized via DB LOWER/TRIM)
            $stmt = $pdo->query("
                SELECT LOWER(TRIM(user_input)) AS norm_input, COUNT(*) AS cnt
                FROM atom_training_examples
                GROUP BY norm_input
                HAVING cnt > 1
            ");
            $dupes = $stmt->fetchAll();

            foreach ($dupes as $dupe) {
                // Keep the highest-quality record, delete the rest
                $sel = $pdo->prepare("
                    SELECT id FROM atom_training_examples
                    WHERE LOWER(TRIM(user_input)) = ?
                    ORDER BY FIELD(quality,'VERIFIED','GOOD','CORRECTED','UNREVIEWED','REJECTED'), id ASC
                ");
                $sel->execute([$dupe['norm_input']]);
                $ids = array_column($sel->fetchAll(), 'id');

                // First id is canonical; delete the rest
                array_shift($ids);
                foreach ($ids as $delId) {
                    $this->deleteRecord((int)$delId);
                }
            }
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    private function isConnected(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }
}
