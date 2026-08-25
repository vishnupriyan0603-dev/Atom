<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;

/**
 * NaturalDialogueOrchestratorEngine — Phase 69
 * Natural, human-like dialogue orchestrator with multi-tone emotional adaptation,
 * gentle English learning cues, 3-tier teaching explanations, and trade-off reasoning.
 */
class NaturalDialogueOrchestratorEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Process a natural conversational turn.
     *
     * @param string $userInput User prompt or message
     * @param array $context Optional conversation context (e.g. user name, topic)
     * @return array Conversational payload with tone, response, and optional English tips
     */
    public function processTurn(string $userInput, array $context = []): array
    {
        $rawText = trim($this->redactor->redact($userInput));
        if (empty($rawText)) {
            return [
                'success' => false,
                'error' => 'Input message cannot be empty',
                'tone' => 'neutral',
                'response' => 'Hello! How can I help you today?',
            ];
        }

        $detectedTone = $this->detectEmotionalTone($rawText);
        $englishTip = $this->analyzeEnglishGuidance($rawText);
        $isGreeting = $this->isGreeting($rawText);

        if ($isGreeting) {
            $response = $this->generateNaturalGreeting($rawText, $context);
        } else {
            $response = $this->generateConversationalResponse($rawText, $detectedTone, $context);
        }

        return [
            'success' => true,
            'input' => $rawText,
            'detected_tone' => $detectedTone,
            'is_greeting' => $isGreeting,
            'response' => $response,
            'english_tip' => $englishTip,
        ];
    }

    /**
     * Detect user's emotional state (Happy, Frustrated, Confused, Playful, Neutral).
     */
    public function detectEmotionalTone(string $text): string
    {
        $lower = mb_strtolower($text);

        if (preg_match('/(angry|broken|fail|error|frustrated|annoyed|terrible|bad|stuck)/i', $lower)) {
            return 'frustrated';
        }
        if (preg_match('/(confused|dont understand|do not understand|i am lost|cannot understand|so confusing)/i', $lower)) {
            return 'confused';
        }
        if (preg_match('/(haha|lol|funny|joke|kidding|fun)/i', $lower)) {
            return 'playful';
        }
        if (preg_match('/(great|awesome|happy|wonderful|yay|love|thanks|thank you|good)/i', $lower)) {
            return 'happy';
        }

        return 'neutral';
    }

    /**
     * Checks if input is an everyday greeting.
     */
    public function isGreeting(string $text): bool
    {
        $clean = trim(mb_strtolower($text), "!.? \t\n\r");
        return (bool) preg_match('/\b(hi|hello|hey|good\s+(morning|afternoon|evening|day)|greetings|howdy|sup)\b/i', $clean);
    }

    /**
     * Generate warm, natural greeting without robotic boilerplate.
     */
    public function generateNaturalGreeting(string $text, array $context = []): string
    {
        $name = !empty($context['user_name']) ? " " . $context['user_name'] : "";
        $greetings = [
            "Hi{$name}! It's great to hear from you. What are you working on today?",
            "Hello{$name}! How's your day going? I'm ready to help with whatever you need.",
            "Hey{$name}! Good to see you. How can I lend a hand today?",
        ];

        return $greetings[abs(crc32($text)) % count($greetings)];
    }

    /**
     * Formats 3-tier pedagogical explanation: (1) Simple Explanation, (2) Concrete Example, (3) Practical Advice.
     */
    public function structureTeachingExplanation(string $concept, string $explanation, string $example, string $practicalAdvice): array
    {
        return [
            'concept' => $concept,
            'tier_1_simple_explanation' => $explanation,
            'tier_2_concrete_example' => $example,
            'tier_3_practical_advice' => $practicalAdvice,
            'formatted_markdown' => "### Understanding {$concept}\n\n**1. Simple Explanation:**\n{$explanation}\n\n**2. Example:**\n{$example}\n\n**3. Practical Advice:**\n{$practicalAdvice}",
        ];
    }

    /**
     * Synthesize structured trade-off decision with pros/cons and a clear recommendation.
     */
    public function synthesizeDecisionRecommendation(string $topic, array $options, string $recommendedOption, string $rationale): array
    {
        return [
            'topic' => $topic,
            'options' => $options,
            'recommended_option' => $recommendedOption,
            'rationale' => $rationale,
        ];
    }

    /**
     * Analyze text for helpful English improvement hints.
     */
    public function analyzeEnglishGuidance(string $text): ?array
    {
        $lower = mb_strtolower($text);

        // Example: "I am use two provider" -> "I am using two providers"
        if (preg_match('/\bam use\b/i', $lower)) {
            return [
                'original' => 'am use',
                'suggestion' => 'am using',
                'explanation' => "A more natural way to phrase continuous action is 'I am using'.",
            ];
        }

        if (preg_match('/\bhow it work\b/i', $lower)) {
            return [
                'original' => 'how it work',
                'suggestion' => 'how it works / how does it work',
                'explanation' => "In standard English, we usually ask 'How does it work?' or say 'how it works'.",
            ];
        }

        return null;
    }

    private function generateConversationalResponse(string $text, string $tone, array $context = []): string
    {
        return match ($tone) {
            'frustrated' => "I understand that this can be frustrating. Let's walk through this step by step and get it solved cleanly.",
            'confused' => "Let me break this down simply so it's easy and intuitive to follow.",
            'playful' => "Haha, I like that! Let's dive in and have some fun with it.",
            'happy' => "That's fantastic! Glad everything is moving smoothly. Let's keep the momentum going!",
            default => "I'm right here with you. Let's look into this.",
        };
    }
}
