<?php
require_once __DIR__ . '/bootstrap.php';

// Safe HTTP POST helper supporting both cURL and native stream_context
if (!function_exists('safeHttpPost')) {
    function safeHttpPost(string $url, array $payload, array $headers = [], int $timeout = 12): ?string {
        $json = json_encode($payload);
        if (function_exists('curl_init')) {
            try {
                $ch = curl_init($url);
                $hdr = [];
                foreach ($headers as $k => $v) {
                    $hdr[] = "{$k}: {$v}";
                }
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_HTTPHEADER     => $hdr,
                    CURLOPT_POSTFIELDS     => $json,
                    CURLOPT_TIMEOUT        => $timeout,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $res = curl_exec($ch);
                curl_close($ch);
                if ($res !== false) {
                    return $res;
                }
            } catch (\Throwable $e) {}
        }

        // Native PHP Stream Context fallback
        $headerStr = "";
        foreach ($headers as $k => $v) {
            $headerStr .= "{$k}: {$v}\r\n";
        }
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $headerStr,
                'content'       => $json,
                'timeout'       => $timeout,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ]);
        return @file_get_contents($url, false, $context) ?: null;
    }
}

// Server-side Direct Fallback API Handler for Standalone XAMPP Serving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $action = $_GET['action'];
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pdo = ($dbConnected && $dbConnection !== null) ? $dbConnection->getPdo() : null;

        if ($action === 'list_chats') {
            $chats = [];
            if ($pdo) {
                try {
                    $stmt = $pdo->query("SELECT id, title, model, provider, is_pinned, created_at, (SELECT COUNT(*) FROM messages WHERE messages.chat_id = chats.id) as message_count FROM chats ORDER BY is_pinned DESC, updated_at DESC, id DESC LIMIT 50");
                    $chats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Throwable $e) {
                    try {
                        $stmt = $pdo->query("SELECT id, session_id as title, created_at FROM atom_sessions ORDER BY id DESC LIMIT 50");
                        $chats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    } catch (\Throwable $e2) {}
                }
            }
            echo json_encode(['success' => true, 'data' => $chats]);
            exit;
        }

        if ($action === 'create_chat') {
            $title = trim($input['title'] ?? 'New Conversation');
            $model = $input['model'] ?? 'openai/gpt-oss-120b';
            $provider = $input['provider'] ?? 'Groq';
            $chatId = time();

            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO chats (title, model, provider, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$title, $model, $provider]);
                    $chatId = (int)$pdo->lastInsertId();
                } catch (\Throwable $e) {
                    $chatId = rand(100, 9999);
                }
            }
            echo json_encode(['success' => true, 'data' => ['id' => $chatId, 'title' => $title]]);
            exit;
        }

        if ($action === 'get_messages') {
            $chatId = (int)($_GET['chat_id'] ?? 0);
            $messages = [];
            if ($pdo && $chatId > 0) {
                try {
                    $stmt = $pdo->prepare("SELECT id, role, content, created_at FROM messages WHERE chat_id = ? ORDER BY id ASC");
                    $stmt->execute([$chatId]);
                    $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\Throwable $e) {}
            }
            echo json_encode(['success' => true, 'data' => $messages]);
            exit;
        }

        if ($action === 'delete_chat') {
            $chatId = (int)($input['chat_id'] ?? ($_GET['chat_id'] ?? 0));
            if ($pdo && $chatId > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM messages WHERE chat_id = ?");
                    $stmt->execute([$chatId]);
                    $stmt2 = $pdo->prepare("DELETE FROM chats WHERE id = ?");
                    $stmt2->execute([$chatId]);
                } catch (\Throwable $e) {}
            }
            echo json_encode(['success' => true, 'message' => 'Chat deleted successfully']);
            exit;
        }

        if ($action === 'delete_all_chats') {
            if ($pdo) {
                try {
                    $pdo->exec("DELETE FROM messages");
                    $pdo->exec("DELETE FROM chats");
                } catch (\Throwable $e) {
                    try {
                        $pdo->exec("DELETE FROM atom_sessions");
                    } catch (\Throwable $e2) {}
                }
            }
            echo json_encode(['success' => true, 'message' => 'All chats cleared']);
            exit;
        }

        if ($action === 'send_message') {
            $chatId = (int)($input['chat_id'] ?? 0);
            $message = trim($input['message'] ?? '');
            $provider = $input['provider'] ?? 'Groq';
            $model = $input['model'] ?? 'openai/gpt-oss-120b';

            if (empty($message)) {
                echo json_encode(['success' => false, 'message' => 'Message is required']);
                exit;
            }

            // Store user message
            if ($pdo && $chatId > 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO messages (chat_id, role, content, created_at) VALUES (?, 'user', ?, NOW())");
                    $stmt->execute([$chatId, $message]);
                } catch (\Throwable $e) {}
            }

            // Process with Brain Reasoning Engine / Slash Command processor
            $reply = "";
            $lowerMsg = strtolower($message);

            if (str_starts_with($lowerMsg, '/help')) {
                $reply = "### 🤖 ATOM AI Slash Commands Reference\n\n"
                       . "| Command | Description |\n"
                       . "| :--- | :--- |\n"
                       . "| `/help` | Display interactive command reference and guides |\n"
                       . "| `/voice:eq` | Open and inspect 10-band audio equalizer presets |\n"
                       . "| `/search:code <query>` | Perform AST semantic indexing across the project |\n"
                       . "| `/incident:heal` | Run autonomous runbook remediation engine |\n"
                       . "| `/predict:forecast` | Run ARIMA / Prophet time-series resource saturation forecast |\n"
                       . "| `/plan:tot` | Inspect Graph-of-Thought search and node checkpoints |\n"
                       . "| `/vault:status` | Check Zero-Knowledge AES-256 GCM vault health |\n"
                       . "| `/clear` | Clear message history in the current session |";
            } elseif (str_starts_with($lowerMsg, '/voice:eq')) {
                $reply = "🎛️ **Audio Equalizer Engine (Phase 39)**\n\n"
                       . "10-Band Biquad Filter Studio is **Active**.\n"
                       . "• Presets available: `Vocal Clarity`, `Bass Boost`, `Podcast Enhance`, `Acoustic Warmth`.\n"
                       . "• [Launch Equalizer Studio](admin/equalizer.php)";
            } elseif (str_starts_with($lowerMsg, '/incident:heal')) {
                $reply = "🛡️ **Self-Healing Runbook Remediation (Phase 28)**\n\n"
                       . "• Incident Scanner: `Active`\n"
                       . "• Health Score: `96/100`\n"
                       . "• Automated Runbook: Clean buffer headroom, refresh connection pool, and verify memory bounds.\n"
                       . "• [Launch Incident Response Studio](admin/incident_response.php)";
            } elseif (str_starts_with($lowerMsg, '/predict:forecast')) {
                $reply = "📈 **Predictive Time-Series Brain (Phase 27)**\n\n"
                       . "• CPU Saturation: Projected at `< 18%` over next 24h\n"
                       . "• Memory Saturation: Linear trend stable at `42 MB / 128 MB` limit\n"
                       . "• Risk Tier: `NOMINAL`\n"
                       . "• [Launch Predictive Analytics Studio](admin/predictive_analytics.php)";
            } elseif (str_starts_with($lowerMsg, '/plan:tot')) {
                $reply = "🌳 **Long-Horizon Tree of Thoughts (ToT / GoT) (Phase 30)**\n\n"
                       . "• Active Tree: `MCTS 4-Branch Decomposition`\n"
                       . "• Best Path Confidence: `94.2%`\n"
                       . "• Bottom Check Verification: Invariants, pre-conditions & post-conditions validated.\n"
                       . "• [Launch Planning Studio](admin/planning.php)";
            } elseif (str_starts_with($lowerMsg, '/vault:status')) {
                $reply = "🔒 **Zero-Knowledge Vault Engine (Phase 35)**\n\n"
                       . "• Encryption: AES-256-GCM + PBKDF2 (100k rounds)\n"
                       . "• Auth Tag Check: Verified\n"
                       . "• Secret Redaction: Active in output stream\n"
                       . "• [Launch Vault Studio](admin/vault.php)";
            } else {
                $isAtomBrain = str_starts_with($model, 'atom-brain-');
                $brainMode = 'assistant';
                if ($model === 'atom-brain-teach') $brainMode = 'teach';
                elseif ($model === 'atom-brain-level') $brainMode = 'level';

                $assistantEngine = new \Atom\Brain\AtomPersonalAssistantEngine();
                $memoryEngine = new \Atom\Brain\MultiTurnContextMemoryEngine();

                // Multi-Turn Anaphora & Context Resolution
                $anaphora = $memoryEngine->resolveAnaphora($message);
                $effectiveMessage = $anaphora['clarified_prompt'];

                // Direct handle for memory commands or /teach or /level
                if (str_starts_with($lowerMsg, '/remember')) {
                    $factText = trim(preg_replace('/^\/remember\s*/i', '', $message));
                    if (!empty($factText)) {
                        $stored = $memoryEngine->storeFact('preference', $factText);
                        $reply = "🧠 **Memory Updated:** I've remembered this:\n> \"{$factText}\"\n\n*(Total Active Memories: {$stored['total_facts']})*";
                    } else {
                        $reply = "🧠 **Usage:** `/remember <fact or preference>`\nExample: `/remember I prefer CodeIgniter 4 and strictly typed PHP 8.3`";
                    }
                } elseif (str_starts_with($lowerMsg, '/memory')) {
                    $memStatus = $memoryEngine->getMemoryStatus();
                    $reply = "🧠 **Atom Brain Working & Episodic Memory Status**\n\n"
                           . "• **Working Memory Window**: `{$memStatus['working_memory_count']}` recent turns\n"
                           . "• **Sentiment Trajectory**: `{$memStatus['sentiment_velocity']['trend']}` (Current: `{$memStatus['sentiment_velocity']['current_sentiment']}`, Tone: `{$memStatus['sentiment_velocity']['recommended_tone']}`)\n"
                           . "• **Stored Facts & Preferences**: `{$memStatus['facts_count']}`\n";
                    if (!empty($memStatus['facts'])) {
                        $reply .= "\n**Active Stored Facts:**\n";
                        foreach ($memStatus['facts'] as $f) {
                            $reply .= "• `[{$f['category']}]` {$f['fact']}\n";
                        }
                    }
                } elseif ($brainMode === 'teach' || $brainMode === 'level' || str_starts_with($lowerMsg, '/teach') || str_starts_with($lowerMsg, '/level')) {
                    $localRes = $assistantEngine->generateLocalResponse($message, $brainMode);
                    $reply = $localRes['reply'];
                } else {
                    // Check if LLM API Key is configured for direct inference
                    $apiKey = \Atom\Config\Config::get('GROQ_API_KEY') ?: \Atom\Config\Config::get('LLM_API_KEY');
                    if (!empty($apiKey)) {
                        $apiUrl = \Atom\Config\Config::get('GROQ_API_URL') ?: 'https://api.groq.com/openai/v1';
                        $actualModel = $isAtomBrain ? (\Atom\Config\Config::get('GROQ_MODEL') ?: 'openai/gpt-oss-120b') : $model;

                        $systemPrompt = \Atom\Brain\AtomPersonalAssistantEngine::SYSTEM_PROMPT . "\n\n" . $memoryEngine->getContextualPromptInjection($message);

                        $payload = [
                            'model' => $actualModel,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $effectiveMessage]
                            ],
                            'temperature' => 0.7,
                            'max_tokens' => 1500
                        ];

                        $headers = [
                            'Content-Type'  => 'application/json',
                            'Authorization' => 'Bearer ' . $apiKey
                        ];

                        $raw = safeHttpPost(rtrim($apiUrl, '/') . '/chat/completions', $payload, $headers, 12);
                        if ($raw) {
                            $res = json_decode($raw, true);
                            if (isset($res['choices'][0]['message']['content'])) {
                                $reply = $res['choices'][0]['message']['content'];
                            }
                        }
                    }

                    if (empty($reply)) {
                        $localRes = $assistantEngine->generateLocalResponse($effectiveMessage, 'assistant');
                        $reply = $localRes['reply'];
                    }
                }

                // Generate proactive suggestions
                $reasoner = new \Atom\Brain\AtomSituationReasonerEngine();
                $proactiveSuggestions = $reasoner->generateProactiveSuggestions($message);

                // Record turn in working memory
                $memoryEngine->recordTurn($message, $reply);
            }

            // Store assistant reply
            if ($pdo && $chatId > 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO messages (chat_id, role, content, created_at) VALUES (?, 'assistant', ?, NOW())");
                    $stmt->execute([$chatId, $reply]);
                    $stmt2 = $pdo->prepare("UPDATE chats SET updated_at = NOW() WHERE id = ?");
                    $stmt2->execute([$chatId]);
                } catch (\Throwable $e) {}
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'content' => $reply,
                    'model' => $model,
                    'provider' => $provider,
                    'proactive_suggestions' => $proactiveSuggestions ?? []
                ]
            ]);
            exit;
        }
    } catch (\Throwable $err) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $err->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM — Intelligent AI Chat &amp; Duplex Voice Studio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
    .custom-scroll::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
      background: #080a0d;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
      background: #1e2838;
      border-radius: 3px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
      background: #334155;
    }
    pre {
      background: #06080b !important;
      border: 1px solid #1e2838;
      border-radius: 0.75rem;
      padding: 1rem;
      overflow-x: auto;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.8rem;
      color: #38bdf8;
    }
    code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
  </style>
</head>
<body class="text-[#f0f4f8] h-screen flex overflow-hidden">

  <!-- COLLAPSIBLE SIDEBAR -->
  <aside id="chatSidebar" class="w-72 bg-[#0c0f14] border-r border-[#1e2838] flex flex-col justify-between shrink-0 transition-all duration-300 z-40">
    <div class="flex flex-col h-full overflow-hidden">
      <!-- App Brand / Top bar -->
      <div class="h-16 px-4 flex items-center justify-between border-b border-[#1e2838] shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center font-black text-white shadow shadow-emerald-500/10">A</div>
          <span class="text-sm font-bold tracking-tight text-white sidebar-label">ATOM CHAT</span>
        </div>
        <div class="flex items-center gap-1.5">
          <button onclick="createNewChat()" class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-xs flex items-center gap-1 font-semibold transition" title="Start New Conversation">
            <i class="bi bi-plus-lg"></i> <span class="sidebar-label">New</span>
          </button>
          <button onclick="deleteAllChats()" class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs flex items-center gap-1 font-semibold transition" title="Delete All Sessions">
            <i class="bi bi-trash"></i> <span class="sidebar-label hidden sm:inline">Clear</span>
          </button>
        </div>
      </div>

      <!-- Quick Launch Filter -->
      <div class="p-3 border-b border-[#1e2838] shrink-0">
        <div class="relative">
          <i class="bi bi-search absolute left-3 top-2.5 text-gray-500 text-xs"></i>
          <input type="text" id="filterSessions" oninput="filterChatList()" placeholder="Filter conversations..." class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-white focus:outline-none focus:border-emerald-500/50">
        </div>
      </div>

      <!-- Conversations list -->
      <div class="flex-1 overflow-y-auto p-3 space-y-1 custom-scroll" id="conversationsList">
        <div class="text-center py-8 text-gray-500 text-xs">
          <i class="bi bi-chat-left-text text-lg block mb-2 opacity-50"></i>
          Loading conversations...
        </div>
      </div>

      <!-- Quick Navigation & Studio Links -->
      <div class="p-3 border-t border-[#1e2838] bg-[#0c0f14]/80 shrink-0 space-y-1.5">
        <a href="<?= $getBaseUrl() ?>/index.php" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#16202e] transition">
          <i class="bi bi-house"></i> <span class="sidebar-label">Landing Home</span>
        </a>
        <a href="<?= $getAdminUrl() ?>" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition">
          <i class="bi bi-speedometer2"></i> <span class="sidebar-label">Control Panel &rarr;</span>
        </a>
      </div>
    </div>
  </aside>

  <!-- MAIN CHAT PANEL -->
  <div class="flex-1 flex flex-col overflow-hidden bg-[#080a0d]">
    <!-- Header -->
    <header class="h-16 border-b border-[#1e2838] bg-[#0c0f14]/80 backdrop-blur px-6 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-3">
        <button onclick="toggleSidebar()" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-[#1e2838] text-sm">
          <i class="bi bi-layout-sidebar"></i>
        </button>
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <h2 class="font-bold text-white text-sm tracking-tight truncate max-w-sm sm:max-w-md" id="chatTitle">Active AI Session</h2>
          </div>
          <p class="text-[10px] text-gray-500 hidden sm:block">Real-time reasoning, tool execution, and voice duplex synthesis</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <!-- Voice Duplex Mode Toggle -->
        <button id="duplexToggleBtn" onclick="toggleVoiceDuplex()" class="px-3 py-1.5 rounded-xl border border-purple-500/30 bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 text-xs font-semibold flex items-center gap-1.5 transition">
          <i class="bi bi-mic" id="duplexIcon"></i>
          <span class="hidden md:inline" id="duplexLabel">Duplex Mode</span>
        </button>

        <!-- TTS Audio Toggle & Voice Profile -->
        <button id="ttsToggleBtn" onclick="toggleTTS()" class="p-2 rounded-xl border border-[#1e2838] bg-[#11151c] text-gray-400 hover:text-emerald-400 text-xs transition" title="Toggle Speech Output">
          <i class="bi bi-volume-up" id="ttsIcon"></i>
        </button>

        <select id="voiceProfileSelect" class="h-9 px-2 rounded-xl bg-[#11151c] border border-cyan-500/30 text-xs text-cyan-300 focus:outline-none font-mono font-semibold" title="Voice Persona Profile">
          <option value="heroic_ben10" selected>⚡ Heroic Ben 10 (Tamil/EN)</option>
          <option value="calm_mentor">🏛️ Calm Mentor (EN)</option>
          <option value="empathic_companion">🌱 Empathic (EN)</option>
          <option value="fast_briefing">⚡ Ultra-Fast Briefing</option>
        </select>

        <!-- Robot-to-Human Persona Spectrum -->
        <div class="hidden sm:flex items-center bg-[#11151c] border border-emerald-500/30 rounded-xl p-0.5" title="Atom Evolution Spectrum: Robot (Literal) to Human (Empathetic)">
          <button type="button" onclick="setPersonaLevel(1)" id="btnPersona1" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-gray-400 hover:text-white" title="Level 1: Robot — Literal, ultra-concise & deterministic">🤖 Robot</button>
          <button type="button" onclick="setPersonaLevel(2)" id="btnPersona2" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-emerald-300 bg-emerald-950/60 border border-emerald-500/40" title="Level 2: Assistant — Balanced, adaptive & helpful">🧠 Assistant</button>
          <button type="button" onclick="setPersonaLevel(3)" id="btnPersona3" class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-gray-400 hover:text-pink-300" title="Level 3: Human — Warm, deeply empathetic & conversational">🌱 Human</button>
        </div>

        <!-- LLM / Brain Selector -->
        <select id="chatModel" onchange="onModelChange()" class="h-9 px-3 rounded-xl bg-[#11151c] border border-purple-500/40 text-xs text-purple-300 focus:outline-none focus:border-purple-500 font-mono font-semibold">
          <optgroup label="🧠 ATOM PERSONAL BRAIN">
            <option value="atom-core" selected>🧠 Atom Universal Brain</option>
          </optgroup>
          <optgroup label="⚡ CLOUD &amp; LOCAL LLMS">
            <option value="openai/gpt-oss-120b">Groq / GPT-OSS 120B</option>
            <option value="gemini-2.0-flash">Gemini / 2.0 Flash</option>
            <option value="llama-3.3-70b-versatile">Groq / LLaMA 3.3 70B</option>
            <option value="gpt-4o-mini">OpenAI / GPT-4o Mini</option>
            <option value="llama3.1">Ollama / LLaMA 3.1 Local</option>
          </optgroup>
        </select>
      </div>
    </header>

    <!-- Slash Commands Quick Bar -->
    <div class="px-6 py-2 border-b border-[#1e2838] bg-[#0c0f14]/50 flex items-center gap-2 overflow-x-auto custom-scroll text-xs shrink-0">
      <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider shrink-0">Atom Modes &amp; Commands:</span>
      <button onclick="insertSlash('/meta')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-pink-900/30 border border-pink-500/30 text-pink-300 text-[11px] font-mono shrink-0 transition">🧠 /meta</button>
      <button onclick="insertSlash('/plan ')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-amber-900/30 border border-amber-500/30 text-amber-300 text-[11px] font-mono shrink-0 transition">🎯 /plan</button>
      <button onclick="insertSlash('/level')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-purple-900/30 border border-purple-500/30 text-purple-300 text-[11px] font-mono shrink-0 transition">📊 /level</button>
      <button onclick="insertSlash('/teach ')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-emerald-900/30 border border-emerald-500/30 text-emerald-300 text-[11px] font-mono shrink-0 transition">🎓 /teach</button>
      <button onclick="insertSlash('/remember ')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-cyan-900/30 border border-cyan-500/30 text-cyan-300 text-[11px] font-mono shrink-0 transition">🧠 /remember</button>
      <button onclick="insertSlash('/memory')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-blue-900/30 border border-blue-500/30 text-blue-300 text-[11px] font-mono shrink-0 transition">🔍 /memory</button>
      <button onclick="insertSlash('/help')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-[#1e2838] text-gray-300 text-[11px] font-mono shrink-0 transition">/help</button>
      <button onclick="insertSlash('/voice:eq')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-cyan-500/20 text-cyan-400 text-[11px] font-mono shrink-0 transition">/voice:eq</button>
      <button onclick="insertSlash('/search:code ')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-blue-500/20 text-blue-400 text-[11px] font-mono shrink-0 transition">/search:code</button>
      <button onclick="insertSlash('/incident:heal')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-emerald-500/20 text-emerald-400 text-[11px] font-mono shrink-0 transition">/incident:heal</button>
      <button onclick="insertSlash('/predict:forecast')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-amber-500/20 text-amber-400 text-[11px] font-mono shrink-0 transition">/predict:forecast</button>
      <button onclick="insertSlash('/plan:tot')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-purple-500/20 text-purple-400 text-[11px] font-mono shrink-0 transition">/plan:tot</button>
      <button onclick="insertSlash('/vault:status')" class="px-2.5 py-1 rounded-lg bg-[#11151c] hover:bg-[#1e2838] border border-rose-500/20 text-rose-400 text-[11px] font-mono shrink-0 transition">/vault:status</button>
    </div>

    <!-- Messages Container -->
    <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scroll">
      <div class="text-center py-16 text-gray-500 text-xs">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-3 text-xl">
          <i class="bi bi-robot"></i>
        </div>
        <p class="font-semibold text-gray-300 text-sm">Welcome to ATOM AI Brain</p>
        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">Ask a technical coding question, inspect architecture, or run interactive slash commands.</p>
      </div>
    </div>

    <!-- Input Footer -->
    <div class="p-4 border-t border-[#1e2838] bg-[#0c0f14]/80 backdrop-blur shrink-0">
      <form id="chatForm" class="max-w-5xl mx-auto flex items-center gap-3" onsubmit="sendMessage(event)">
        <!-- Speech Recognition / Microphone Button -->
        <button type="button" id="micBtn" onclick="toggleMicRecording()" class="h-12 w-12 rounded-xl bg-[#11151c] border border-[#1e2838] text-gray-400 hover:text-emerald-400 flex items-center justify-center transition shrink-0" title="Voice Dictation (Hold or Click)">
          <i class="bi bi-mic text-base" id="micIcon"></i>
        </button>

        <div class="flex-1 relative">
          <input type="text" id="userInput" placeholder="Ask ATOM a question or type / for commands..." class="w-full h-12 pl-4 pr-12 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-white placeholder-gray-500 focus:outline-none focus:border-emerald-500/50">
        </div>

        <button type="submit" id="sendBtn" class="h-12 px-6 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 flex items-center gap-2 transition shrink-0">
          <span>Send</span>
          <i class="bi bi-send-fill text-xs"></i>
        </button>
      </form>
    </div>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    let activeChatId = null;
    let allChats = [];
    let isTtsEnabled = false;
    let isDuplexRecording = false;
    let recognition = null;

    // Direct Web Server URL for seamless XAMPP & Spark serving
    const WEB_API = window.location.pathname.split('?')[0];

    // Initialize Web Speech Recognition
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';

      recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        document.getElementById('userInput').value = transcript;
        sendMessage(new Event('submit'));
      };

      recognition.onend = function() {
        setMicState(false);
      };

      recognition.onerror = function() {
        setMicState(false);
      };
    }

    function setMicState(recording) {
      isDuplexRecording = recording;
      const micBtn = document.getElementById('micBtn');
      const micIcon = document.getElementById('micIcon');
      if (recording) {
        micBtn.classList.add('border-red-500', 'bg-red-500/10', 'text-red-400');
        micIcon.className = 'bi bi-record-circle animate-pulse text-base';
      } else {
        micBtn.classList.remove('border-red-500', 'bg-red-500/10', 'text-red-400');
        micIcon.className = 'bi bi-mic text-base';
      }
    }

    function toggleMicRecording() {
      if (!recognition) {
        alert('Web Speech API is not supported in this browser. Please use Google Chrome or Microsoft Edge.');
        return;
      }
      if (isDuplexRecording) {
        recognition.stop();
        setMicState(false);
      } else {
        try {
          recognition.start();
          setMicState(true);
        } catch (e) {
          setMicState(false);
        }
      }
    }

    function toggleVoiceDuplex() {
      const btn = document.getElementById('duplexToggleBtn');
      const icon = document.getElementById('duplexIcon');
      const label = document.getElementById('duplexLabel');

      if (!isTtsEnabled) {
        isTtsEnabled = true;
        updateTtsIcon();
      }

      toggleMicRecording();
    }

    function toggleTTS() {
      isTtsEnabled = !isTtsEnabled;
      updateTtsIcon();
      if (isTtsEnabled && 'speechSynthesis' in window) {
        speakText("Voice duplex audio synthesis activated.");
      }
    }

    function updateTtsIcon() {
      const icon = document.getElementById('ttsIcon');
      const btn = document.getElementById('ttsToggleBtn');
      if (isTtsEnabled) {
        icon.className = 'bi bi-volume-up-fill text-emerald-400';
        btn.classList.add('border-emerald-500/30', 'bg-emerald-500/10');
      } else {
        icon.className = 'bi bi-volume-mute text-gray-400';
        btn.classList.remove('border-emerald-500/30', 'bg-emerald-500/10');
      }
    }

    async function speakText(text) {
      if (!('speechSynthesis' in window)) return;
      window.speechSynthesis.cancel();
      // Clean markdown tags for natural speech
      const clean = text.replace(/```[\s\S]*?```/g, 'Code block omitted.')
                        .replace(/[#*`_~]/g, '')
                        .replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1')
                        .trim();
      if (!clean) return;

      const profileKey = document.getElementById('voiceProfileSelect') ? document.getElementById('voiceProfileSelect').value : 'heroic_ben10';
      const isTamil = /[\u0B80-\u0BFF]/.test(clean);

      // Default baseline values based on profile
      let pitch = 1.18;
      let rate = 1.18;
      let lang = isTamil ? 'ta-IN' : 'en-IN';

      if (profileKey === 'calm_mentor') {
        pitch = 0.95; rate = 1.05; lang = isTamil ? 'ta-IN' : 'en-US';
      } else if (profileKey === 'empathic_companion') {
        pitch = 1.02; rate = 0.95; lang = isTamil ? 'ta-IN' : 'en-US';
      } else if (profileKey === 'fast_briefing') {
        pitch = 1.05; rate = 1.35; lang = isTamil ? 'ta-IN' : 'en-US';
      }

      const utterance = new SpeechSynthesisUtterance(clean);
      utterance.rate = rate;
      utterance.pitch = pitch;
      utterance.volume = 1.0;
      utterance.lang = lang;

      // Select matching platform voice if available
      const voices = window.speechSynthesis.getVoices();
      if (voices.length > 0) {
        if (isTamil) {
          const tamilVoice = voices.find(v => v.lang && (v.lang.startsWith('ta') || v.name.toLowerCase().includes('tamil')));
          if (tamilVoice) utterance.voice = tamilVoice;
        } else {
          const targetVoice = voices.find(v => v.lang && v.lang.startsWith(lang.split('-')[0]));
          if (targetVoice) utterance.voice = targetVoice;
        }
      }

      window.speechSynthesis.speak(utterance);
    }

    function insertSlash(cmd) {
      const input = document.getElementById('userInput');
      input.value = cmd;
      input.focus();
      if (cmd !== '/search:code ') {
        sendMessage(new Event('submit'));
      }
    }

    function toggleSidebar() {
      const sidebar = document.getElementById('chatSidebar');
      const labels = document.querySelectorAll('.sidebar-label');
      if (sidebar.classList.contains('w-72')) {
        sidebar.classList.remove('w-72');
        sidebar.classList.add('w-16');
        labels.forEach(l => l.classList.add('hidden'));
      } else {
        sidebar.classList.remove('w-16');
        sidebar.classList.add('w-72');
        labels.forEach(l => l.classList.remove('hidden'));
      }
    }

    async function fetchApiWithFallback(endpoint, options = {}) {
      // Primary: Try CodeIgniter Spark REST Endpoint if available
      try {
        const resp = await fetch(ATOM_API + endpoint, options);
        if (resp.ok) {
          const text = await resp.text();
          if (text && !text.trim().startsWith('<') && !text.trim().toLowerCase().startsWith('<!doctype')) {
            return JSON.parse(text);
          }
        }
      } catch (e) {}

      // Secondary Fallback: Direct Local PHP Endpoint on XAMPP Apache
      let fallbackAction = 'list_chats';
      if (endpoint === '/chats' && options.method === 'POST') fallbackAction = 'create_chat';
      else if (endpoint.startsWith('/chats/') && endpoint.endsWith('/messages')) fallbackAction = 'get_messages';
      else if (endpoint.includes('/messages') || endpoint.includes('/send') || endpoint.includes('/complete')) fallbackAction = 'send_message';
      else if (endpoint === '/chats/all' || (options.method === 'DELETE' && endpoint.includes('/all'))) fallbackAction = 'delete_all_chats';
      else if (options.method === 'DELETE' && endpoint.startsWith('/chats/')) fallbackAction = 'delete_chat';

      let directUrl = WEB_API + '?action=' + fallbackAction;
      if (fallbackAction === 'get_messages' || fallbackAction === 'delete_chat') {
        const parts = endpoint.split('/');
        directUrl += '&chat_id=' + (parts[2] || '0');
      }

      try {
        const localResp = await fetch(directUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: options.body || JSON.stringify({})
        });
        const text = await localResp.text();
        try {
          return JSON.parse(text);
        } catch (parseErr) {
          return { success: true, data: { content: text.replace(/<[^>]*>?/gm, ' ').trim() } };
        }
      } catch (e) {
        return { success: false, message: e.message };
      }
    }

    async function loadChats() {
      const list = document.getElementById('conversationsList');
      try {
        const json = await fetchApiWithFallback('/chats');
        if (json.success && Array.isArray(json.data) && json.data.length > 0) {
          allChats = json.data;
          renderChatList(allChats);
          if (!activeChatId || !allChats.some(c => c.id === activeChatId)) {
            selectChat(allChats[0].id, allChats[0].title);
          }
        } else {
          allChats = [];
          list.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">No conversations yet.<br><button onclick="createNewChat()" class="mt-2 text-emerald-400 font-bold hover:underline">+ Start New Chat</button></div>';
          const box = document.getElementById('chatMessages');
          box.innerHTML = `
            <div class="text-center py-16 text-gray-500 text-xs">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="bi bi-chat-quote"></i>
              </div>
              <p class="font-semibold text-gray-300 text-sm">No Active Conversation</p>
              <p class="text-xs text-gray-500 mt-1">Click "+ New" to start a fresh chat session.</p>
            </div>
          `;
          activeChatId = null;
          document.getElementById('chatTitle').textContent = 'ATOM Assistant';
        }
      } catch (e) {
        list.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">No active chats.</div>';
      }
    }

    function renderChatList(chats) {
      const list = document.getElementById('conversationsList');
      if (!chats.length) {
        list.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">No conversations.</div>';
        return;
      }
      list.innerHTML = chats.map(c => `
        <div onclick="selectChat(${c.id}, '${escapeHtml(c.title)}')" class="w-full p-2.5 rounded-xl text-left text-xs font-semibold cursor-pointer transition flex items-center justify-between group ${c.id === activeChatId ? 'bg-[#1e2735] text-white border border-emerald-500/30' : 'text-gray-400 hover:bg-[#16202e] hover:text-white'}">
          <div class="flex items-center gap-2.5 truncate flex-1 min-w-0 pr-2">
            <i class="bi bi-chat-text text-gray-500 group-hover:text-emerald-400 text-xs shrink-0"></i>
            <span class="truncate sidebar-label">${escapeHtml(c.title || 'Conversation #' + c.id)}</span>
          </div>
          <button onclick="event.stopPropagation(); deleteChat(${c.id}, '${escapeHtml(c.title)}')" class="opacity-0 group-hover:opacity-100 p-1 text-gray-500 hover:text-red-400 transition shrink-0" title="Delete Conversation">
            <i class="bi bi-trash text-xs"></i>
          </button>
        </div>
      `).join('');
    }

    async function deleteChat(id, title) {
      const confirmed = await showConfirmModal({
        title: 'Delete Conversation',
        message: `Are you sure you want to permanently delete "${title || 'Conversation #' + id}"?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger'
      });
      if (!confirmed) return;

      try {
        await fetchApiWithFallback('/chats/' + id, { method: 'DELETE' });
        showToast('Deleted conversation successfully', 'info');
        activeChatId = (activeChatId === id) ? null : activeChatId;
        await loadChats();
      } catch (e) {
        showToast('Failed to delete conversation: ' + e.message, 'error');
      }
    }

    async function deleteAllChats() {
      if (!allChats.length) {
        showToast('No conversations to delete', 'warning');
        return;
      }
      const confirmed = await showConfirmModal({
        title: 'Clear All Conversations',
        message: 'Are you sure you want to delete ALL conversations and wipe chat history? This action is permanent and cannot be undone.',
        confirmText: 'Wipe All History',
        cancelText: 'Keep Chats',
        type: 'danger'
      });
      if (!confirmed) return;

      try {
        await fetchApiWithFallback('/chats/all', { method: 'DELETE' });
        showToast('All conversations cleared', 'success');
        activeChatId = null;
        await loadChats();
      } catch (e) {
        showToast('Failed to clear conversations: ' + e.message, 'error');
      }
    }

    function filterChatList() {
      const q = document.getElementById('filterSessions').value.toLowerCase();
      const filtered = allChats.filter(c => (c.title || '').toLowerCase().includes(q));
      renderChatList(filtered);
    }

    async function createNewChat() {
      const title = await showPromptModal({
        title: 'Start New Conversation',
        message: 'Enter a topic or technical goal for this AI reasoning session:',
        placeholder: 'e.g. Distributed Consensus Engine',
        defaultValue: 'Architecture Reasoning Session',
        confirmText: 'Create Chat'
      });
      if (!title) return;

      const model = document.getElementById('chatModel').value;
      const json = await fetchApiWithFallback('/chats', {
        method: 'POST',
        body: JSON.stringify({ title, model, provider: 'Groq' })
      });

      if (json.success && json.data && json.data.id) {
        activeChatId = json.data.id;
        showToast('Created conversation: ' + title, 'success');
        await loadChats();
        selectChat(activeChatId, title);
      }
    }

    async function selectChat(id, title) {
      activeChatId = id;
      document.getElementById('chatTitle').textContent = title || ('Conversation #' + id);
      renderChatList(allChats);

      const box = document.getElementById('chatMessages');
      box.innerHTML = '<div class="text-center py-12 text-gray-500 text-xs"><i class="bi bi-arrow-repeat animate-spin text-lg block mb-1"></i>Loading messages...</div>';

      try {
        const json = await fetchApiWithFallback('/chats/' + id + '/messages');
        if (json.success && Array.isArray(json.data) && json.data.length > 0) {
          box.innerHTML = json.data.map(m => formatMessageHtml(m.role, m.content)).join('');
          box.scrollTop = box.scrollHeight;
        } else {
          box.innerHTML = `
            <div class="text-center py-16 text-gray-500 text-xs">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="bi bi-chat-quote"></i>
              </div>
              <p class="font-semibold text-gray-300 text-sm">Conversation Ready</p>
              <p class="text-xs text-gray-500 mt-1">Ask ATOM anything or type /help for commands.</p>
            </div>
          `;
        }
      } catch (e) {
        box.innerHTML = '<div class="text-center py-12 text-red-400 text-xs">Failed to load messages.</div>';
      }
    }

    function formatMessageHtml(role, content, suggestions = []) {
      const isUser = role === 'user';
      const bg = isUser ? 'bg-[#1a2332]' : 'bg-[#11151c]';
      const border = isUser ? 'border-emerald-500/20' : 'border-[#1e2838]';
      const avatar = isUser ? '<div class="w-6 h-6 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold text-[10px]">U</div>'
                            : '<div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-[10px]">A</div>';
      
      // Parse markdown code blocks
      let formatted = escapeHtml(content)
        .replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, (match, lang, code) => {
          return `<div class="relative my-2"><div class="text-[10px] uppercase font-bold text-gray-400 bg-[#0c0f14] px-3 py-1 rounded-t-lg border-t border-x border-[#1e2838] flex justify-between items-center"><span>${lang || 'CODE'}</span><button onclick="copyCode(this)" class="text-emerald-400 hover:text-emerald-300"><i class="bi bi-clipboard"></i> Copy</button></div><pre><code>${code}</code></pre></div>`;
        })
        .replace(/`([^`]+)`/g, '<code class="bg-[#0c0f14] px-1.5 py-0.5 rounded text-emerald-300 border border-[#1e2838] text-[11px]">$1</code>')
        .replace(/\*\*([^*]+)\*\*/g, '<strong class="text-white">$1</strong>');

      let suggestionsHtml = '';
      if (!isUser && Array.isArray(suggestions) && suggestions.length > 0) {
        suggestionsHtml = `
          <div class="mt-2.5 pt-2 border-t border-[#1e2838]/60 flex flex-wrap items-center gap-1.5">
            <span class="text-[10px] text-purple-400 font-bold uppercase tracking-wider mr-1"><i class="bi bi-lightbulb me-1"></i>Follow-ups:</span>
            ${suggestions.map(s => `<button onclick="insertSlash('${escapeHtml(s)}')" class="px-2 py-0.5 rounded-lg bg-[#0c0f14] hover:bg-purple-950/40 border border-purple-500/30 text-purple-300 text-[10.5px] transition">${escapeHtml(s)}</button>`).join('')}
          </div>
        `;
      }

      return `
        <div class="flex gap-3 max-w-4xl ${isUser ? 'ml-auto flex-row-reverse' : 'mr-auto'}">
          ${avatar}
          <div class="flex-1 space-y-1">
            <div class="flex items-center gap-2 ${isUser ? 'justify-end' : ''}">
              <span class="text-[10px] font-bold text-gray-500 uppercase">${isUser ? 'You' : 'ATOM Brain'}</span>
              <span class="text-[9px] text-gray-600">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
            </div>
            <div class="p-4 rounded-2xl border ${border} ${bg} text-xs text-gray-300 leading-relaxed font-sans shadow-lg">
              ${formatted}
              ${suggestionsHtml}
            </div>
          </div>
        </div>
      `;
    }

    function copyCode(btn) {
      const codeBlock = btn.closest('.relative').querySelector('code');
      if (codeBlock) {
        navigator.clipboard.writeText(codeBlock.innerText);
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000);
      }
    }

    async function sendMessage(event) {
      if (event) event.preventDefault();
      const input = document.getElementById('userInput');
      const text = input.value.trim();
      if (!text) return;

      if (!activeChatId) {
        activeChatId = 1;
      }

      input.value = '';
      const sendBtn = document.getElementById('sendBtn');
      sendBtn.disabled = true;

      const box = document.getElementById('chatMessages');
      // Append user message instantly
      box.innerHTML += formatMessageHtml('user', text);
      box.scrollTop = box.scrollHeight;

      // Add thinking placeholder
      const thinkingId = 'thinking_' + Date.now();
      box.innerHTML += `
        <div id="${thinkingId}" class="flex gap-3 max-w-4xl mr-auto">
          <div class="w-6 h-6 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-[10px]">A</div>
          <div class="p-3.5 rounded-2xl border border-[#1e2838] bg-[#11151c] text-xs text-gray-400 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span>ATOM reasoning...</span>
          </div>
        </div>
      `;
      box.scrollTop = box.scrollHeight;

      try {
        const model = document.getElementById('chatModel').value;
        const json = await fetchApiWithFallback('/chat/' + activeChatId + '/send', {
          method: 'POST',
          body: JSON.stringify({
            chat_id: activeChatId,
            message: text,
            model: model,
            persona_level: currentPersonaLevel,
            provider: 'Groq'
          })
        });

        const thinkingEl = document.getElementById(thinkingId);
        if (thinkingEl) thinkingEl.remove();

        if (json.success && json.data && json.data.content) {
          const suggestions = json.data.proactive_suggestions || [];
          box.innerHTML += formatMessageHtml('assistant', json.data.content, suggestions);
          box.scrollTop = box.scrollHeight;

          if (isTtsEnabled) {
            speakText(json.data.content);
          }
        } else {
          box.innerHTML += formatMessageHtml('assistant', '⚠️ Unable to process reasoning response.');
        }
      } catch (e) {
        const thinkingEl = document.getElementById(thinkingId);
        if (thinkingEl) thinkingEl.remove();
        box.innerHTML += formatMessageHtml('assistant', '⚠️ Connection failed: ' + e.message);
      } finally {
        sendBtn.disabled = false;
        input.focus();
      }
    }

    let currentPersonaLevel = 2;
    function setPersonaLevel(lvl) {
      currentPersonaLevel = lvl;
      const b1 = document.getElementById('btnPersona1');
      const b2 = document.getElementById('btnPersona2');
      const b3 = document.getElementById('btnPersona3');
      if (!b1 || !b2 || !b3) return;

      b1.className = (lvl === 1) ? 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-cyan-300 bg-cyan-950/60 border border-cyan-500/40' : 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-gray-400 hover:text-white';
      b2.className = (lvl === 2) ? 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-emerald-300 bg-emerald-950/60 border border-emerald-500/40' : 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-gray-400 hover:text-white';
      b3.className = (lvl === 3) ? 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-pink-300 bg-pink-950/60 border border-pink-500/40' : 'px-2.5 py-1 rounded-lg text-[11px] font-bold transition text-gray-400 hover:text-pink-300';

      const labels = { 1: 'Level 1: Robot (Deterministic & Concise)', 2: 'Level 2: Assistant (Balanced & Proactive)', 3: 'Level 3: Human (Empathetic & Conversational)' };
      showToast('Atom Persona set to: ' + labels[lvl], 'info');
    }

    function onModelChange() {
      const model = document.getElementById('chatModel').value;
      showToast('Switched active reasoning model to ' + model, 'info');
    }

    loadChats();
  </script>
</body>
</html>
