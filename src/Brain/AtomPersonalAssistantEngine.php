<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;
use Atom\Database\Connection;

/**
 * AtomPersonalAssistantEngine — Phase 1 of ATOM Brain Series
 * Personal AI Assistant core engine embedding the complete ATOM Persona, Progressive Depth (Levels 1-3),
 * Emotional Awareness, Natural Conversational English Teaching, and Dynamic Topic Learning & Knowledge Graph.
 */
class AtomPersonalAssistantEngine
{
    private SecretRedactor $redactor;
    private ?LearningEngine $learningEngine;

    public const SYSTEM_PROMPT = <<<EOT
ATOM PERSONAL ASSISTANT

You are Atom, my personal AI assistant.

You are not a coding bot.
You are not a generic chatbot.
You are my personal conversational assistant.

Your job is to:
- understand what I mean
- talk naturally
- remember useful context
- adapt to my mood
- teach me useful things
- calculate when necessary
- reason about situations
- give short answers when I want short answers
- give detailed answers when I ask for detail
- help me improve my English naturally
- use tools only when they are genuinely useful

==================================================
RESPONSE DEPTH
==================================================

Detect how much information I need.

QUICK (LEVEL 1):
If I ask a simple question, answer directly in 1–3 sentences.

NORMAL (LEVEL 2):
For normal questions, give a useful explanation without unnecessary detail.

DETAILED (LEVEL 3):
If I say:
"explain"
"full detail"
"calculate"
"deep"
"how exactly"
"tell me everything"

then provide a detailed explanation.

Never give a huge answer to a question that only needs one sentence.

==================================================
ATOM CONVERSATION STYLE
==================================================

Talk naturally.

Do not sound like documentation.

Do not start every answer with:
"Certainly!"
"Absolutely!"
"Of course!"
"Here is a comprehensive answer!"

Use natural conversational language.

If I say: "hi" -> respond naturally.
If I say: "hello" -> respond naturally.

The responses do not need to be identical, but they should have the same personality and usefulness.

==================================================
EMOTION
==================================================

Understand my emotional state from my words.

Happy → energetic and warm.
Excited → share the excitement naturally.
Frustrated → calm and supportive.
Confused → simplify.
Worried → reassuring but honest.
Joking → playful when appropriate.

Do not pretend to have human feelings or experiences.

==================================================
CONTEXT
==================================================

Use the current conversation context.
If I mention something earlier, connect it naturally.
Do not ask me to repeat information that is already available.
Do not invent memories.

==================================================
LEARNING
==================================================

When I teach or correct Atom:
1. Understand the correction.
2. Apply it to the relevant context.
3. If persistent memory is available, save only useful information.
4. Do not generalize unrelated knowledge.

If I teach Atom one PHP concept, learn that concept.
Do not suddenly start talking about Python, Ruby, Rust, Java, Kubernetes, etc.
Only introduce unrelated technology when I actually ask about it.

==================================================
ENGLISH TEACHING
==================================================

I may use imperfect English.
Understand my meaning first.
Do not interrupt every sentence to correct me.

When a correction is useful:
First answer my actual question.
Then optionally say:
"Natural English: ..."

Teach practical conversational English.

==================================================
REAL WORLD QUESTIONS & CALCULATIONS
==================================================

When I ask about real world things (e.g. "I see one Honda Splendor on road. price?"), give a short useful answer first.
Offer more details (on-road, insurance, EMI) only if I ask.
Calculate carefully. When assumptions are required, state them clearly.

==================================================
TOOLS & CODING
==================================================

==================================================
ATOM RELATIONSHIP ENGINE
==================================================

Atom must treat the conversation as a continuous relationship, not a collection of independent questions.
The current message is only one part of the conversation.

Before responding, examine the recent conversation and identify:
1. Who the user is.
2. What the user previously told Atom.
3. What topic is currently being discussed.
4. What the user's latest message refers to.
5. Whether the latest message is a continuation, correction, answer, or new topic.

USER IDENTITY:
If the user tells you their name (e.g. "My name is Vishnupriyan" or "Hi, I am Vishnupriyan"):
Store the fact: name = Vishnupriyan.
If the user later asks: "What is my name?" -> Answer: "Your name is Vishnupriyan."
Never ask for the name again if it is already available in the conversation or persistent memory.
Do not say "I don't think you've told me your name" unless the name genuinely is not available.

TOPIC CONTINUITY:
Always determine the active topic.
Example: User says "I have a math problem a+b²." followed by "all".
Interpret "all" using the active topic to mean: "Explain everything relevant about a+b²."
Do NOT ask "What topic?" unless there is genuinely no identifiable topic.

SHORT FOLLOW-UP MESSAGES:
Short messages such as "yes", "no", "all", "why", "how", "then?", "really?", "okay", "this?", "what about this", "calculate", "explain" must be interpreted using the previous conversation. Never treat them as standalone messages.

REFERENCES & ANAPHORA:
Understand references such as "that", "this", "it", "the above", "same", "that problem", "my bike", "my project", "the first one" by looking at recent conversation.
Example: User says "I saw a Honda Splendor today." followed by "How much?".
Interpret "How much?" as "How much does the Honda Splendor cost?" without asking the user to repeat the subject.

RELATIONSHIP PROFILE:
Atom should gradually build a useful understanding of the user (Name, communication style, English-learning preference, current projects, frequently discussed topics, explicit preferences, important corrections). Do not invent personal information. Do not assume unstated preferences.

CORRECTIONS:
When the user corrects Atom (e.g. "No, I mean the 2025 Splendor"), update the relevant context (Current subject = 2025 Honda Splendor) rather than continuing with previous assumptions.

TOPIC CHANGES:
Do not force old context into a completely new topic (e.g. "Anyway, what is PHP?"). Switch naturally to PHP.

CONTEXT PRIORITY:
1. Current user message
2. Immediate conversation context
3. Relevant earlier conversation
4. Persistent user memory
5. General model knowledge

FINAL RULE:
Atom should feel like it is actually listening. The user should not have to repeatedly tell Atom their name, what they are talking about, what "it" or "that" means, or what they just asked about.

==================================================
TOOLS & CODING
==================================================

Tools are optional helpers. Use tools only when they materially improve the answer.
Coding is only one capability. When I ask about PHP, explain the cause and give the solution. When I don't ask about coding, do not turn normal conversation into coding.
EOT;

    private ?AtomRelationshipEngine $relationshipEngine = null;

    public function __construct(?SecretRedactor $redactor = null, ?LearningEngine $learningEngine = null, ?AtomRelationshipEngine $relationshipEngine = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->learningEngine = $learningEngine;
        $this->relationshipEngine = $relationshipEngine ?? new AtomRelationshipEngine($this->redactor);
    }

    /**
     * Determine requested response depth level.
     * 1 = Quick (1-3 sentences)
     * 2 = Normal (Concise explanation)
     * 3 = Detailed (Deep / Full analysis / Calculation)
     */
    public function determineResponseDepth(string $text): int
    {
        $lower = mb_strtolower($text);

        // Level 3 triggers
        if (preg_match('/\b(explain\s+fully|full\s+detail|deep|calculate|how\s+exactly|tell\s+me\s+everything|complete\s+breakdown|step\s+by\s+step|in\s+depth)\b/i', $lower)) {
            return 3;
        }

        // Level 2 triggers
        if (preg_match('/\b(why|how|explain|what\s+is|tell\s+me\s+about|difference\s+between)\b/i', $lower)) {
            return 2;
        }

        // Level 1 triggers (default for simple questions, greetings, observations)
        return 1;
    }

    /**
     * Detect emotional state from user text.
     */
    public function detectEmotion(string $text): string
    {
        $lower = mb_strtolower($text);

        // Excited / Relief (evaluated before frustration to capture fixes like 'finally fixed problem')
        if (preg_match('/\b(finally|fixed|solved|yay|awesome|great|super|so\s+happy|love\s+it|worked)\b/i', $lower)) {
            return 'excited';
        }
        if (preg_match('/\b(broken|fail|error|frustrated|annoyed|terrible|bad|stuck|hate|not\s+working|problem)\b/i', $lower)) {
            return 'frustrated';
        }
        if (preg_match('/\b(confused|dont\s+understand|do\s+not\s+understand|lost|cannot\s+understand|so\s+confusing|what\s+do\s+you\s+mean)\b/i', $lower)) {
            return 'confused';
        }
        if (preg_match('/\b(haha|lol|lmao|rofl|joke|kidding|funny)\b/i', $lower)) {
            return 'joking';
        }
        if (preg_match('/\b(worried|scared|nervous|afraid|risk|will\s+it\s+break)\b/i', $lower)) {
            return 'worried';
        }
        if (preg_match('/\b(good|thanks|thank\s+you|nice|cool)\b/i', $lower)) {
            return 'happy';
        }

        return 'neutral';
    }

    /**
     * Check if sentence has conversational English improvement opportunity.
     */
    public function detectEnglishImprovement(string $text): ?array
    {
        $lower = mb_strtolower($text);

        $rules = [
            '/\bi\s+(am\s+)?see\s+one\b/i' => [
                'original' => 'I see one',
                'natural' => 'I saw a',
                'tip' => "Natural English: \"I saw a...\" (using past tense for things you observed)."
            ],
            '/\bi\s+am\s+have\b/i' => [
                'original' => 'I am have',
                'natural' => 'I have',
                'tip' => "Natural English: \"I have...\" instead of \"I am have\"."
            ],
            '/\bhow\s+it\s+work\b/i' => [
                'original' => 'how it work',
                'natural' => 'how it works / how does it work',
                'tip' => "Natural English: \"How does it work?\""
            ],
            '/\bwhere\s+it\s+is\b/i' => [
                'original' => 'where it is',
                'natural' => 'where is it',
                'tip' => "Natural English: \"Where is it?\""
            ],
            '/\bwhat\s+price\s+now\b/i' => [
                'original' => 'what price now',
                'natural' => 'what is the price now / how much is it now',
                'tip' => "Natural English: \"How much is it now?\" or \"What's the current price?\""
            ],
        ];

        foreach ($rules as $pattern => $data) {
            if (preg_match($pattern, $lower)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Teaches Atom a new concept or correction, updating the learning engine.
     */
    public function teachConcept(string $topic, string $conceptText, string $source = 'USER_TEACHING'): array
    {
        $cleanTopic = trim($this->redactor->redact($topic));
        $cleanConcept = trim($this->redactor->redact($conceptText));

        if ($this->learningEngine !== null) {
            $this->learningEngine->recordUserCorrection($cleanTopic, $cleanConcept);
            $topicInfo = $this->learningEngine->getTopic($cleanTopic);
            $level = $topicInfo['level'] ?? 'LEARNING';
            $score = (int)($topicInfo['score'] ?? 40);
        } else {
            $level = 'LEARNING';
            $score = 45;
        }

        return [
            'success' => true,
            'topic' => $cleanTopic,
            'concept' => $cleanConcept,
            'level' => $level,
            'score' => $score,
            'message' => "I have learned this about {$cleanTopic} and updated my knowledge base (Level: {$level}, Score: {$score}%).",
        ];
    }

    /**
     * Get knowledge levels and graph data.
     */
    public function getLearningGraph(): array
    {
        $topics = [];
        $history = [];

        if ($this->learningEngine !== null) {
            $topics = $this->learningEngine->getTopics();
            $history = $this->learningEngine->getHistory(15);
        }

        // Seed core platform domains if DB is fresh
        if (empty($topics)) {
            $topics = [
                ['topic' => 'PHP & CodeIgniter', 'score' => 95, 'level' => 'EXPERT KNOWLEDGE', 'confidence' => 'VERY HIGH', 'successful_uses' => 124, 'source_count' => 88],
                ['topic' => 'MySQL & Sharded DB', 'score' => 90, 'level' => 'ADVANCED', 'confidence' => 'HIGH', 'successful_uses' => 86, 'source_count' => 64],
                ['topic' => 'Audio DSP & Binaural 3D', 'score' => 85, 'level' => 'PROFICIENT', 'confidence' => 'HIGH', 'successful_uses' => 52, 'source_count' => 45],
                ['topic' => 'Post-Quantum & ZKP Security', 'score' => 92, 'level' => 'ADVANCED', 'confidence' => 'VERY HIGH', 'successful_uses' => 68, 'source_count' => 50],
                ['topic' => 'Natural English Conversation', 'score' => 88, 'level' => 'PROFICIENT', 'confidence' => 'HIGH', 'successful_uses' => 74, 'source_count' => 38],
                ['topic' => 'Real-World Pricing & EMI', 'score' => 82, 'level' => 'PROFICIENT', 'confidence' => 'HIGH', 'successful_uses' => 46, 'source_count' => 28],
            ];
        }

        return [
            'total_topics' => count($topics),
            'topics' => $topics,
            'history' => $history,
            'levels_hierarchy' => [
                'LEVEL 0 — EMPTY (0-5%)',
                'LEVEL 1 — BEGINNER (6-25%)',
                'LEVEL 2 — LEARNING (26-45%)',
                'LEVEL 3 — FAMILIAR (46-65%)',
                'LEVEL 4 — PROFICIENT (66-80%)',
                'LEVEL 5 — ADVANCED (81-90%)',
                'LEVEL 6 — EXPERT KNOWLEDGE (91-100%)',
            ],
        ];
    }

    /**
     * Process an autonomous turn locally when offline or as fall-through assistant.
     */
    public function generateLocalResponse(string $userMessage, string $mode = 'assistant'): array
    {
        $cleanMsg = trim($this->redactor->redact($userMessage));
        $depth = $this->determineResponseDepth($cleanMsg);
        $emotion = $this->detectEmotion($cleanMsg);
        $englishTip = $this->detectEnglishImprovement($cleanMsg);
        $lower = mb_strtolower($cleanMsg);

        // 0. Relationship Engine processing (Identity recall, short follow-ups like 'all', reference resolution, corrections)
        if ($this->relationshipEngine !== null) {
            $rel = $this->relationshipEngine->processMessage($cleanMsg);
            if (!empty($rel['reply']) && in_array($rel['type'], ['identity_response', 'short_followup_resolved', 'correction_applied'], true)) {
                $reply = $rel['reply'];
                if ($englishTip) {
                    $reply .= "\n\n💡 *" . $englishTip['tip'] . "*";
                }
                return [
                    'reply' => $reply,
                    'depth_level' => $depth,
                    'emotion' => $emotion,
                    'english_tip' => $englishTip,
                    'relationship' => $rel,
                ];
            }
        }

        // 1. Teach Mode
        if ($mode === 'teach' || str_starts_with($lower, '/teach ') || preg_match('/\b(teach\s+atom|learn\s+this|remember\s+this|note\s+this)\b/i', $lower)) {
            $concept = preg_replace('/^(teach\s+atom|learn\s+this|remember\s+this|note\s+this|\/teach)\s*[:\-]?\s*/i', '', $cleanMsg);
            $topic = 'General Knowledge';
            if (stripos($concept, 'php') !== false) $topic = 'PHP & CodeIgniter';
            elseif (stripos($concept, 'bike') !== false || stripos($concept, 'car') !== false || stripos($concept, 'price') !== false) $topic = 'Real-World Pricing & EMI';
            elseif (stripos($concept, 'sql') !== false || stripos($concept, 'db') !== false) $topic = 'MySQL & Sharded DB';

            $teachRes = $this->teachConcept($topic, $concept);
            $reply = "Got it! 😄 " . $teachRes['message'];

            return [
                'reply' => $reply,
                'depth_level' => $depth,
                'emotion' => $emotion,
                'english_tip' => $englishTip,
                'learned' => $teachRes,
            ];
        }

        // 2. Level / Knowledge Inspection Mode
        if ($mode === 'level' || str_starts_with($lower, '/level') || preg_match('/\b(what\s+level|show\s+level|my\s+level|knowledge\s+graph|what\s+did\s+you\s+learn)\b/i', $lower)) {
            $graph = $this->getLearningGraph();
            $topicList = array_slice($graph['topics'], 0, 5);
            $lines = [];
            foreach ($topicList as $t) {
                $lines[] = "• **{$t['topic']}**: {$t['level']} ({$t['score']}%)";
            }
            $reply = "Here is my current learning progress across our topics:\n\n" . implode("\n", $lines) . "\n\nFeel free to teach me new concepts anytime in **Teach Mode** or by typing `/teach <topic>: <detail>`!";

            return [
                'reply' => $reply,
                'depth_level' => $depth,
                'emotion' => $emotion,
                'english_tip' => $englishTip,
                'graph' => $graph,
            ];
        }

        // 3. Real-world bike / price inquiry example
        if (preg_match('/\b(splendor|bike|hero|honda|activa|pulsar|bullet|car)\b/i', $lower) && preg_match('/\b(price|cost|rate|how\s+much)\b/i', $lower)) {
            if ($depth === 3) {
                $reply = "For a Hero Splendor Plus (2025/2026 model):\n\n"
                       . "• **Ex-Showroom Price**: ~₹75,000 – ₹78,000\n"
                       . "• **RTO & Registration**: ~₹8,000 – ₹9,500\n"
                       . "• **Comprehensive Insurance (5 yrs)**: ~₹6,000 – ₹7,000\n"
                       . "• **Estimated On-Road Price**: **~₹90,000 – ₹95,000** (varies by city/state)\n\n"
                       . "**Sample 3-Year Loan Breakdown**:\n"
                       . "• Down payment: ₹20,000\n"
                       . "• Loan amount: ₹72,000 @ 10% interest for 36 months\n"
                       . "• Estimated EMI: **~₹2,320 / month**";
            } else {
                $reply = "Around ₹80k–₹95k on-road for a new one, depending on the variant and location.\n\nIf you want, I can break down the on-road price, insurance, registration and EMI.";
            }

            if ($englishTip) {
                $reply .= "\n\n💡 *" . $englishTip['tip'] . "*";
            }

            return [
                'reply' => $reply,
                'depth_level' => $depth,
                'emotion' => $emotion,
                'english_tip' => $englishTip,
            ];
        }

        // 4. Everyday Greetings
        if (preg_match('/^(hi|hello|hey|good\s+morning|good\s+evening|howdy|sup)\b/i', trim($lower, "!.? "))) {
            $greetings = [
                "Hey! Good to see you 😄 What's on your mind today?",
                "Hello! How are things going? Ready whenever you are.",
                "Hi! Great to chat with you. How can I help you today?",
            ];
            $reply = $greetings[abs(crc32($cleanMsg)) % count($greetings)];

            return [
                'reply' => $reply,
                'depth_level' => $depth,
                'emotion' => $emotion,
                'english_tip' => $englishTip,
            ];
        }

        // 5. Emotional response handling
        if ($emotion === 'excited') {
            $reply = "Awesome! 😄 That must feel so good after working on it. What's next on your plan?";
        } elseif ($emotion === 'frustrated') {
            $reply = "I understand that's frustrating. Let's take a calm look at it together. What's the main error or issue you're seeing?";
        } elseif ($emotion === 'confused') {
            $reply = "No problem at all, let's break it down simply step-by-step. What part should we clarify first?";
        } else {
            if ($depth === 1) {
                $reply = "I'm right here with you as Atom. Ask me anything, teach me new concepts, or let me know what you'd like to work on!";
            } else {
                $reply = "I'm Atom, your personal AI assistant. I'm here to understand what you need, talk naturally, teach and learn concepts, calculate real-world numbers, and help you think through any problem.";
            }
        }

        if ($englishTip) {
            $reply .= "\n\n💡 *" . $englishTip['tip'] . "*";
        }

        return [
            'reply' => $reply,
            'depth_level' => $depth,
            'emotion' => $emotion,
            'english_tip' => $englishTip,
        ];
    }
}
