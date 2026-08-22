<?php

namespace Atom\PersonalModel;

use Atom\Database\Connection;

class FeedbackManager
{
    private ?Connection $connection;
    private ?int $sessionId;
    private PersonalProfile $profile;

    public function __construct(?Connection $connection, ?int $sessionId, PersonalProfile $profile)
    {
        $this->connection = $connection;
        $this->sessionId = $sessionId;
        $this->profile = $profile;
    }

    /**
     * Intercepts and parses natural language feedback or command feedback.
     * Returns an array with 'handled' => bool, and optional 'response' => string.
     */
    public function handleFeedback(string $input, ?int $lastMessageId = null): array
    {
        $inputTrimmed = trim($input);
        $inputLower = strtolower($inputTrimmed);

        // 1. Check explicit slash commands
        if ($inputLower === '/good') {
            $this->logFeedback('good', $inputTrimmed, $lastMessageId);
            return [
                'handled' => true,
                'response' => "ATOM:\nThank you! I will keep delivering responses in this style."
            ];
        }

        if ($inputLower === '/bad') {
            $this->logFeedback('bad', $inputTrimmed, $lastMessageId);
            return [
                'handled' => true,
                'response' => "ATOM:\nUnderstood. I will adjust my approach. What specifically was wrong?"
            ];
        }

        if (strpos($inputLower, '/correct') === 0) {
            $correctionText = trim(substr($inputTrimmed, 8));
            $this->logFeedback('correct', $correctionText, $lastMessageId);
            
            // Try to extract correction preference or store it
            if (!empty($correctionText)) {
                $this->profile->setPreference('last_correction', $correctionText, 'user_correction', 1.0);
            }

            return [
                'handled' => true,
                'response' => "ATOM:\nCorrection logged. I will adjust my responses based on: \"$correctionText\""
            ];
        }

        // 2. Parse natural language feedback
        $naturalPreference = $this->parseNaturalFeedback($inputLower);
        if ($naturalPreference !== null) {
            $this->profile->setPreference(
                $naturalPreference['key'],
                $naturalPreference['value'],
                'user_correction',
                1.0
            );
            $this->logFeedback('natural', $inputTrimmed, $lastMessageId);
            return [
                'handled' => true,
                'response' => "ATOM:\nI have updated my preference: {$naturalPreference['key']} is now set to '{$naturalPreference['value']}'."
            ];
        }

        return ['handled' => false];
    }

    /**
     * Logs raw feedback in database.
     */
    public function logFeedback(string $type, ?string $text, ?int $associatedMessageId): void
    {
        if ($this->connection === null || !$this->connection->isConnected() || $this->sessionId === null) {
            return;
        }

        $pdo = $this->connection->getPdo();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO atom_feedback (session_id, feedback_type, feedback_text, associated_message_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$this->sessionId, $type, $text, $associatedMessageId]);
        } catch (\PDOException $e) {
            // Degrade silently
        }
    }

    private function parseNaturalFeedback(string $input): ?array
    {
        if (preg_match('/explain\s+shorter/i', $input) || preg_match('/shorter\s+next\s+time/i', $input) || preg_match('/shorter\s+answers/i', $input) || preg_match('/be\s+brief/i', $input) || preg_match('/shorter/i', $input)) {
            return ['key' => 'preferred_response_length', 'value' => 'short'];
        }

        if (preg_match('/use\s+simple\s+english/i', $input) || preg_match('/explain\s+simply/i', $input) || preg_match('/too\s+complicated/i', $input) || preg_match('/simple\s+english/i', $input)) {
            return ['key' => 'communication_style', 'value' => 'simple_english'];
        }

        if (preg_match('/give\s+example/i', $input) || preg_match('/include\s+code/i', $input) || preg_match('/show\s+code/i', $input) || preg_match('/give\s+example\s+also/i', $input)) {
            return ['key' => 'coding_preferences', 'value' => 'practical_examples_preferred'];
        }

        if (preg_match('/this\s+solution\s+worked/i', $input) || preg_match('/this\s+worked/i', $input)) {
            return ['key' => 'last_solution_status', 'value' => 'successful'];
        }

        if (preg_match('/this\s+answer\s+is\s+good/i', $input) || preg_match('/answer\s+is\s+good/i', $input)) {
            return ['key' => 'communication_style', 'value' => 'satisfactory'];
        }

        if (preg_match('/don\'t\s+explain\s+like\s+this/i', $input) || preg_match('/explain\s+differently/i', $input)) {
            return ['key' => 'communication_style', 'value' => 'alternative_explanation_style'];
        }

        return null;
    }
}
