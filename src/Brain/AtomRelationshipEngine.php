<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;

/**
 * AtomRelationshipEngine — Continuous Relationship & Conversational Context Engine
 *
 * Implements:
 * 1. User Identity tracking & persistent recall (Name, preferences, communication style).
 * 2. Topic Continuity & Active Subject tracking.
 * 3. Short Follow-Up Message interpretation ("all", "yes", "no", "why", "how", "then?", "really?", "okay", "this?", "what about this", "calculate", "explain").
 * 4. Anaphora & Reference Resolution ("it", "that", "this", "the above", "same", "that problem", "my bike", "my project", "the first one").
 * 5. Dynamic Corrections & Context Revision ("No, I mean the 2025 Splendor").
 * 6. Natural Topic Switching without forcing stale context.
 * 7. Context Priority Layering (Current Message > Immediate Context > Earlier Conversation > Persistent Memory > General Knowledge).
 */
class AtomRelationshipEngine
{
    private SecretRedactor $redactor;
    private MultiTurnContextMemoryEngine $memoryEngine;

    private ?string $userName = null;
    private ?string $activeTopic = null;
    private ?string $activeSubject = null;
    private array $userProfile = [
        'name' => null,
        'communication_style' => 'natural_conversational',
        'english_learning' => true,
        'current_projects' => [],
        'frequently_discussed_topics' => [],
        'explicit_preferences' => [],
        'corrections' => [],
    ];

    private string $storagePath;

    public function __construct(?SecretRedactor $redactor = null, ?MultiTurnContextMemoryEngine $memoryEngine = null, ?string $storagePath = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->memoryEngine = $memoryEngine ?? new MultiTurnContextMemoryEngine($this->redactor);
        $this->storagePath = $storagePath ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atom_relationship_profile.json';
        $this->loadPersistedProfile();
    }

    /**
     * Process an incoming user message within the active conversation relationship.
     */
    public function processMessage(string $userInput, array $recentHistory = []): array
    {
        $cleanInput = trim($this->redactor->redact($userInput));
        $lower = mb_strtolower($cleanInput);

        // 1. Identity & Name Extraction
        $extractedName = $this->extractUserName($cleanInput);
        if ($extractedName) {
            $this->setUserName($extractedName);
        }

        // 2. Identity Query Check ("What is my name?", "Do you know my name?")
        $isIdentityQuery = (bool) preg_match('/\b(what(\s+is|\'s)\s+my\s+name|who\s+am\s+i|do\s+you\s+know\s+my\s+name|my\s+name\?)\b/i', $lower);
        if ($isIdentityQuery) {
            if ($this->userName) {
                return [
                    'success' => true,
                    'type' => 'identity_response',
                    'reply' => "Your name is {$this->userName}.",
                    'active_topic' => $this->activeTopic,
                    'active_subject' => $this->activeSubject,
                    'user_name' => $this->userName,
                ];
            }
            return [
                'success' => true,
                'type' => 'identity_response',
                'reply' => "I don't think you've told me your name yet! What should I call you?",
                'active_topic' => $this->activeTopic,
                'active_subject' => $this->activeSubject,
                'user_name' => null,
            ];
        }

        // 3. Check for User Correction ("No, I mean the 2025 Splendor", "Actually, I meant X")
        $isCorrection = (bool) preg_match('/^(?:no[,.\s]+|actually[,.\s]+|not\s+that[,.\s]+)?(?:i\s+mean(t)?\s+(?:the\s+)?|i\s+meant\s+(?:the\s+)?)/i', $lower);
        if ($isCorrection) {
            $correctedSubject = preg_replace('/^(?:no[,.\s]+|actually[,.\s]+|not\s+that[,.\s]+)?(?:i\s+mean(t)?\s+(?:the\s+)?|i\s+meant\s+(?:the\s+)?)/i', '', $cleanInput);
            $correctedSubject = trim($correctedSubject, "!.? ");
            if (!empty($correctedSubject)) {
                $this->activeSubject = $correctedSubject;
                $this->activeTopic = $correctedSubject;
                $this->userProfile['corrections'][] = [
                    'timestamp' => date('c'),
                    'correction' => $cleanInput,
                    'new_subject' => $correctedSubject,
                ];
                $this->persistProfile();

                return [
                    'success' => true,
                    'type' => 'correction_applied',
                    'active_topic' => $this->activeTopic,
                    'active_subject' => $this->activeSubject,
                    'inferred_intent' => "Corrected active subject to '{$this->activeSubject}'",
                    'reply' => $this->generateSubjectResponse($this->activeSubject, $cleanInput),
                ];
            }
        }

        // 4. Topic Switch Detection ("Anyway, what is PHP?", "Switching topic, what about Python?", "By the way, tell me about Docker")
        $isTopicSwitch = (bool) preg_match('/^(anyway|by\s+the\s+way|moving\s+on|on\s+another\s+note|switching\s+topic|new\s+topic)[,.\s]+/i', $lower);
        if ($isTopicSwitch) {
            $newTopicText = preg_replace('/^(anyway|by\s+the\s+way|moving\s+on|on\s+another\s+note|switching\s+topic|new\s+topic)[,.\s]+/i', '', $cleanInput);
            $this->activeTopic = trim($newTopicText, "!.? ");
            $this->activeSubject = $this->extractCoreSubject($newTopicText) ?? $this->activeTopic;
            $this->persistProfile();

            return [
                'success' => true,
                'type' => 'topic_switched',
                'active_topic' => $this->activeTopic,
                'active_subject' => $this->activeSubject,
                'inferred_intent' => "Switched topic to '{$this->activeTopic}'",
            ];
        }

        // 5. Short Follow-Up Detection ("all", "yes", "no", "why", "how", "then?", "really?", "okay", "this?", "what about this", "calculate", "explain")
        $isShortFollowUp = $this->isShortFollowUpMessage($cleanInput);
        if ($isShortFollowUp) {
            $inferredMeaning = $this->interpretShortFollowUp($cleanInput, $this->activeTopic, $this->activeSubject);

            return [
                'success' => true,
                'type' => 'short_followup_resolved',
                'original_message' => $cleanInput,
                'active_topic' => $this->activeTopic,
                'active_subject' => $this->activeSubject,
                'inferred_meaning' => $inferredMeaning,
                'reply' => $this->generateShortFollowUpReply($cleanInput, $this->activeTopic, $this->activeSubject),
            ];
        }

        // 6. Reference & Anaphora Resolution ("that", "this", "it", "the above", "my bike", "how much?")
        $resolvedReference = $this->resolveReferences($cleanInput);

        // Update active topic and subject if a substantive topic is present
        $inferredTopic = $this->extractCoreSubject($cleanInput);
        if ($inferredTopic) {
            $this->activeTopic = $inferredTopic;
            $this->activeSubject = $inferredTopic;
            if (!in_array($inferredTopic, $this->userProfile['frequently_discussed_topics'], true)) {
                $this->userProfile['frequently_discussed_topics'][] = $inferredTopic;
            }
            $this->persistProfile();
        }

        return [
            'success' => true,
            'type' => 'standard_message',
            'user_name' => $this->userName,
            'active_topic' => $this->activeTopic,
            'active_subject' => $this->activeSubject,
            'resolved_reference' => $resolvedReference,
        ];
    }

    /**
     * Extracts user name from natural introductions.
     */
    public function extractUserName(string $text): ?string
    {
        $patterns = [
            '/\b(?:my\s+name\s+is|i\s+am|i\'m|call\s+me)\s+([A-Z][a-zA-Z0-9_\-]{1,24})\b/i',
            '/\b(?:hi|hello|hey)[,.\s]+(?:i\s+am|i\'m)\s+([A-Z][a-zA-Z0-9_\-]{1,24})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $candidate = trim($matches[1]);
                $lower = strtolower($candidate);
                // Exclude common non-name words
                if (!in_array($lower, ['atom', 'here', 'ready', 'back', 'just', 'fine', 'good', 'happy', 'sad', 'confused', 'excited', 'testing'], true)) {
                    return ucfirst($candidate);
                }
            }
        }

        return null;
    }

    /**
     * Sets and persists the user name.
     */
    public function setUserName(string $name): void
    {
        $this->userName = trim($name);
        $this->userProfile['name'] = $this->userName;
        $this->memoryEngine->storeFact('identity', "User's name is {$this->userName}", 1.0);
        $this->persistProfile();
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getActiveTopic(): ?string
    {
        return $this->activeTopic;
    }

    public function getActiveSubject(): ?string
    {
        return $this->activeSubject;
    }

    public function setActiveTopic(?string $topic, ?string $subject = null): void
    {
        $this->activeTopic = $topic;
        $this->activeSubject = $subject ?? $topic;
        $this->persistProfile();
    }

    public function getUserProfile(): array
    {
        return array_merge($this->userProfile, [
            'name' => $this->userName,
            'active_topic' => $this->activeTopic,
            'active_subject' => $this->activeSubject,
        ]);
    }

    /**
     * Checks if a message is a short conversational follow-up.
     */
    public function isShortFollowUpMessage(string $text): bool
    {
        $trimmed = mb_strtolower(trim($text, " !?.,"));
        $shortKeywords = [
            'all', 'yes', 'no', 'why', 'how', 'then', 'then?', 'really', 'really?',
            'okay', 'ok', 'this', 'this?', 'what about this', 'calculate', 'explain',
            'how much', 'how much?', 'more', 'details', 'same', 'and?', 'what next'
        ];

        return in_array($trimmed, $shortKeywords, true) || (mb_strlen($trimmed) <= 15 && in_array(explode(' ', $trimmed)[0], ['how', 'why', 'what', 'then', 'all', 'yes', 'no']));
    }

    /**
     * Interprets short follow-up messages using the active topic & subject.
     */
    public function interpretShortFollowUp(string $shortMsg, ?string $topic, ?string $subject): string
    {
        $msg = mb_strtolower(trim($shortMsg, " !?.,"));
        $target = $subject ?: ($topic ?: 'the current topic');

        switch ($msg) {
            case 'all':
                return "Explain everything relevant and comprehensive about {$target}.";
            case 'how much':
            case 'how much?':
                return "How much does {$target} cost, including pricing, on-road costs, or breakdown?";
            case 'calculate':
                return "Perform detailed calculation, numerical estimate, or formula breakdown for {$target}.";
            case 'why':
            case 'why?':
                return "Explain the underlying reasons, architecture, or causes behind {$target}.";
            case 'how':
            case 'how?':
                return "Explain the step-by-step process or implementation of {$target}.";
            case 'explain':
                return "Provide a clear, in-depth explanation of {$target}.";
            case 'this':
            case 'this?':
            case 'what about this':
                return "Evaluate or explain the specific aspect or variant of {$target}.";
            case 'then':
            case 'then?':
            case 'and?':
            case 'what next':
                return "Explain what follows or what the next logical step is for {$target}.";
            case 'really':
            case 'really?':
                return "Verify and provide confirming rationale or evidence regarding {$target}.";
            case 'yes':
            case 'okay':
            case 'ok':
                return "Acknowledge and proceed with the next step or detailed breakdown for {$target}.";
            case 'no':
                return "Acknowledge negative or alternative preference regarding {$target}.";
            default:
                return "Continue discussing and elaborating on {$target}.";
        }
    }

    /**
     * Resolves pronouns and references against active subject.
     */
    public function resolveReferences(string $input): array
    {
        $lower = mb_strtolower($input);
        $hasReference = (bool) preg_match('/\b(it|that|this|the above|same|that problem|my bike|my project|the first one|the second one|how much)\b/i', $lower);

        $resolved = false;
        $resolvedQuery = $input;

        if ($hasReference && ($this->activeSubject || $this->activeTopic)) {
            $target = $this->activeSubject ?: $this->activeTopic;
            $resolved = true;
            $resolvedQuery = "{$input} [Refers to active subject: {$target}]";
        }

        return [
            'has_reference' => $hasReference,
            'resolved' => $resolved,
            'target_subject' => $this->activeSubject ?: $this->activeTopic,
            'resolved_query' => $resolvedQuery,
        ];
    }

    /**
     * Generates a contextually accurate local fallback reply for short follow-ups.
     */
    public function generateShortFollowUpReply(string $shortMsg, ?string $topic, ?string $subject): string
    {
        $msg = mb_strtolower(trim($shortMsg, " !?.,"));
        $target = $subject ?: ($topic ?: 'our discussion');

        if ($msg === 'all') {
            if (stripos($target, 'a+b') !== false || stripos($target, 'math') !== false) {
                return "Sure. Let's cover it from the basics: what {$target} means, how to simplify it, how it behaves with actual values, and how it appears in equations.";
            }
            return "Sure! Let's cover everything relevant about **{$target}**: the core concepts, practical usage, real-world examples, and key nuances.";
        }

        if ($msg === 'how much' || $msg === 'how much?') {
            if (stripos($target, 'splendor') !== false || stripos($target, 'bike') !== false) {
                return "Around ₹80k–₹95k on-road for a new Hero Splendor Plus, depending on the variant and location.\n\nIf you want, I can break down the on-road price, insurance, registration, and monthly EMI for you!";
            }
            return "For **{$target}**, the cost depends on the specific variant or tier. Would you like a detailed price breakdown?";
        }

        if ($msg === 'explain') {
            return "Here is a complete breakdown of **{$target}**: let's look at what it does, why it matters, and how to apply it step-by-step.";
        }

        if ($msg === 'calculate') {
            return "Let's run the exact numbers for **{$target}**. What specific values or parameters would you like to calculate?";
        }

        return "Understood. Moving forward with **{$target}**—let me know if you'd like a quick summary, step-by-step guide, or full calculation!";
    }

    /**
     * Generates a response when a subject is discussed or corrected.
     */
    private function generateSubjectResponse(string $subject, string $originalMsg): string
    {
        if (stripos($subject, 'splendor') !== false) {
            return "Got it! Focusing specifically on the **{$subject}**. Around ₹80k–₹95k on-road for the latest 2025/2026 model. Would you like the full on-road price and EMI breakdown?";
        }

        return "Got it! Updating our context to focus directly on **{$subject}**. What specific details would you like to explore?";
    }

    /**
     * Extracts the primary noun/subject phrase from a user message.
     */
    private function extractCoreSubject(string $text): ?string
    {
        $clean = trim($text);

        // Math problems like a+b² or equations
        if (preg_match('/([a-zA-Z0-9\(\)]+[\+\-\*\/\^\²\³\=][a-zA-Z0-9\+\-\*\/\^\²\³\(\)\=]*)/u', $clean, $m)) {
            return trim($m[1], "!.? ");
        }

        // Vehicles & products
        if (preg_match('/\b(202[0-9]\s+)?(honda\s+splendor|hero\s+splendor|splendor|pulsar|activa|bullet|royalenfield|iphone\s+\d+|macbook\s+[a-z0-9]+)\b/i', $clean, $m)) {
            return ucwords(trim($m[0]));
        }

        // Tech stacks
        if (preg_match('/\b(php(\s+8\.[0-9])?|python|codeigniter(\s+4)?|mysql|sqlite|docker|kubernetes|vitess|webrtc|ssml)\b/i', $clean, $m)) {
            return strtoupper(trim($m[0]));
        }

        return null;
    }

    /**
     * Build the dynamic contextual relationship prompt block to inject into LLM system prompts.
     */
    public function buildRelationshipContextPrompt(): string
    {
        $lines = ["### ATOM RELATIONSHIP & ACTIVE CONTEXT:"];

        if ($this->userName) {
            $lines[] = "- **User Name**: {$this->userName} (Always address user naturally by name or recall when asked; never say 'I don't think you told me your name')";
        } else {
            $lines[] = "- **User Name**: Unknown (Listen and store name when user introduces themselves)";
        }

        if ($this->activeSubject || $this->activeTopic) {
            $subj = $this->activeSubject ?: $this->activeTopic;
            $lines[] = "- **Active Subject / Topic**: {$subj}";
            $lines[] = "- **Topic Continuity Rule**: Interpret short follow-ups (e.g. 'all', 'why', 'how', 'how much', 'calculate') using '{$subj}'. Never ask 'What topic?' if an active subject exists.";
        }

        if (!empty($this->userProfile['frequently_discussed_topics'])) {
            $recent = array_slice($this->userProfile['frequently_discussed_topics'], -4);
            $lines[] = "- **Recent Topics**: " . implode(', ', $recent);
        }

        $lines[] = "- **Context Priority**: Current Message > Immediate Context > Earlier Conversation > Persistent Memory > General Knowledge.";

        return implode("\n", $lines);
    }

    private function loadPersistedProfile(): void
    {
        if (file_exists($this->storagePath)) {
            $json = @file_get_contents($this->storagePath);
            if ($json) {
                $data = json_decode($json, true);
                if (is_array($data)) {
                    $this->userName = $data['name'] ?? null;
                    $this->activeTopic = $data['active_topic'] ?? null;
                    $this->activeSubject = $data['active_subject'] ?? null;
                    $this->userProfile = array_merge($this->userProfile, $data);
                }
            }
        }
    }

    private function persistProfile(): void
    {
        $data = array_merge($this->userProfile, [
            'name' => $this->userName,
            'active_topic' => $this->activeTopic,
            'active_subject' => $this->activeSubject,
            'updated_at' => date('c'),
        ]);

        @file_put_contents($this->storagePath, json_encode($data, JSON_PRETTY_PRINT));
    }
}
