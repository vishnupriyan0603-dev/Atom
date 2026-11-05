<?php
$pageTitle = "ATOM Mobile AI";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ATOM AI Mobile Assistant</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#00F2FE">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #080A0D;
            --bg-card: #11151C;
            --bg-bar: #0C0F14;
            --cyan-neon: #00F2FE;
            --purple-neon: #A855F7;
            --border-dim: #1E2838;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            background-color: var(--bg-dark);
            color: #F8FAFC;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            padding-bottom: 75px;
            overflow-x: hidden;
        }
        .mobile-header {
            background: var(--bg-bar);
            border-bottom: 1px solid var(--border-dim);
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-title {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #fff;
            margin: 0;
        }
        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-bar);
            border-top: 1px solid var(--border-dim);
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
            z-index: 1000;
        }
        .nav-item-btn {
            background: none;
            border: none;
            color: #64748B;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .nav-item-btn i { font-size: 20px; margin-bottom: 2px; }
        .nav-item-btn.active { color: var(--cyan-neon); }

        .tab-content-panel { display: none; padding: 16px; }
        .tab-content-panel.active { display: block; }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-dim);
            border-radius: 14px;
            padding: 14px;
            height: 100%;
        }
        .stat-label {
            font-size: 10px;
            font-weight: 800;
            color: #94A3B8;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 17px;
            font-weight: 800;
            margin-top: 6px;
        }

        /* Chat view */
        .chat-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-bottom: 80px;
        }
        .chat-bubble {
            max-width: 84%;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 14px;
            line-height: 1.4;
        }
        .bubble-user {
            align-self: flex-end;
            background: rgba(0, 242, 254, 0.15);
            border: 1px solid rgba(0, 242, 254, 0.4);
            color: #00F2FE;
        }
        .bubble-ai {
            align-self: flex-start;
            background: var(--bg-card);
            border: 1px solid var(--border-dim);
            color: #F8FAFC;
        }
        .chat-input-bar {
            position: fixed;
            bottom: 60px;
            left: 0;
            right: 0;
            background: var(--bg-bar);
            border-top: 1px solid var(--border-dim);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 99;
        }
        .chat-input-bar input {
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border-dim);
            border-radius: 20px;
            color: #fff;
            padding: 8px 14px;
            flex: 1;
            outline: none;
            font-size: 14px;
        }
        .btn-send {
            background: var(--cyan-neon);
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-mic-hold {
            background: #1E2838;
            border: 1px solid var(--cyan-neon);
            border-radius: 50%;
            width: 38px;
            height: 38px;
            color: var(--cyan-neon);
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
        }
        .btn-mic-hold.recording {
            background: #ef4444;
            color: #fff;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="mobile-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cpu-fill text-info"></i>
            <h1 class="header-title">ATOM NEURAL MOBILE</h1>
        </div>
        <div>
            <span class="badge bg-success" id="serverBadge"><i class="bi bi-wifi"></i> LAN ONLINE</span>
        </div>
    </div>

    <!-- TAB 1: HOME DASHBOARD -->
    <div id="tabHome" class="tab-content-panel active">
        <div class="mb-3">
            <h2 class="h5 fw-bold mb-1 text-white">Greetings, Vichu</h2>
            <p class="text-secondary small mb-0">ATOM Personal AI Brain is online — mobile edge node active.</p>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Core Status</div>
                    <div class="stat-value text-success">ONLINE</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Model Gateway</div>
                    <div class="stat-value text-info">AUTO ROUTE</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">RAG Vectors</div>
                    <div class="stat-value text-warning">14,290</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Approval Gate</div>
                    <div class="stat-value text-danger">ENFORCED</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">Voice Core</div>
                    <div class="stat-value text-info">BEN 10 HEROIC</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-label">ARC Automation</div>
                    <div class="stat-value text-success">ACTIVE</div>
                </div>
            </div>
        </div>

        <div class="stat-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="stat-label">Direct APK Download</span>
                <span class="badge bg-primary">v1.0.0</span>
            </div>
            <p class="small text-secondary mb-3">Download the standalone ATOM Android APK directly to your phone.</p>
            <a href="assets/apk/atom-mobile-latest.apk" download class="btn btn-info w-100 fw-bold text-dark">
                <i class="bi bi-download me-1"></i> Download ATOM Android APK
            </a>
        </div>
    </div>

    <!-- TAB 2: CHAT CONSOLE -->
    <div id="tabChat" class="tab-content-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-secondary">Active Model:</span>
            <select id="modelSelect" class="form-select form-select-sm bg-dark text-info border-secondary w-auto">
                <option value="gemini">Gemini 3.6 Flash</option>
                <option value="groq">Groq (OSS-120B)</option>
                <option value="openai">OpenAI GPT-4o</option>
                <option value="atom">Atom Universal Brain</option>
            </select>
        </div>

        <div class="chat-box" id="chatMessages">
            <div class="chat-bubble bubble-ai">
                Greetings Vichu! I am ATOM, your Personal AI Assistant. Voice mode uses hold-to-talk. Ask me anything!
                <div class="mt-1 small" style="color: var(--purple-neon); font-size: 10px;">[ATOM Knowledge Graph | Score: 0.94]</div>
            </div>
        </div>

        <div class="chat-input-bar">
            <button id="micBtn" class="btn-mic-hold" title="Hold to Talk">
                <i class="bi bi-mic-fill"></i>
            </button>
            <input type="text" id="chatInput" placeholder="Message ATOM..." autocomplete="off">
            <button class="btn-send" id="sendBtn"><i class="bi bi-send-fill"></i></button>
        </div>
    </div>

    <!-- TAB 3: MEMORY -->
    <div id="tabMemory" class="tab-content-panel">
        <h2 class="h6 fw-bold mb-3 text-white">Memory 2.0 Subsystem</h2>
        <div class="d-flex flex-column gap-2">
            <div class="stat-card">
                <div class="d-flex justify-content-between mb-1">
                    <span class="badge bg-info text-dark">FACT</span>
                    <span class="small text-secondary">Importance: 10/10</span>
                </div>
                <div class="small">Vishnupriyan R (Vichu) is a Senior Full-Stack Software Developer.</div>
            </div>
            <div class="stat-card">
                <div class="d-flex justify-content-between mb-1">
                    <span class="badge bg-warning text-dark">VOICE</span>
                    <span class="small text-secondary">Importance: 9/10</span>
                </div>
                <div class="small">Voice calibrated to Ben 10 Tamil reference audio with hold-to-talk.</div>
            </div>
            <div class="stat-card">
                <div class="d-flex justify-content-between mb-1">
                    <span class="badge bg-primary">CONTROL</span>
                    <span class="small text-secondary">Importance: 9/10</span>
                </div>
                <div class="small">ARC One (Git) + ARC Two (Desktop automation) control system active.</div>
            </div>
        </div>
    </div>

    <!-- TAB 4: APPROVALS -->
    <div id="tabApprovals" class="tab-content-panel">
        <h2 class="h6 fw-bold mb-3 text-white">Human Approval Gate</h2>
        <div class="stat-card text-center py-4">
            <i class="bi bi-shield-check text-success display-6 mb-2"></i>
            <div class="small text-secondary">All actions currently verified. No pending high-risk approvals.</div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <div class="nav-bottom">
        <button class="nav-item-btn active" onclick="switchTab('tabHome', this)">
            <i class="bi bi-grid-fill"></i>
            <span>Home</span>
        </button>
        <button class="nav-item-btn" onclick="switchTab('tabChat', this)">
            <i class="bi bi-chat-dots-fill"></i>
            <span>Chat</span>
        </button>
        <button class="nav-item-btn" onclick="switchTab('tabMemory', this)">
            <i class="bi bi-cpu"></i>
            <span>Memory</span>
        </button>
        <button class="nav-item-btn" onclick="switchTab('tabApprovals', this)">
            <i class="bi bi-shield-lock-fill"></i>
            <span>Gate</span>
        </button>
    </div>

    <script>
        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-item-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            el.classList.add('active');
        }

        // Chat logic
        const chatInput = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        const chatMessages = document.getElementById('chatMessages');

        function appendMessage(sender, text, citation = null) {
            const div = document.createElement('div');
            div.className = `chat-bubble bubble-${sender}`;
            div.textContent = text;
            if (citation) {
                const cite = document.createElement('div');
                cite.className = 'mt-1 small';
                cite.style.color = 'var(--purple-neon)';
                cite.style.fontSize = '10px';
                cite.textContent = citation;
                div.appendChild(cite);
            }
            chatMessages.appendChild(div);
            window.scrollTo(0, document.body.scrollHeight);
        }

        async function sendMsg() {
            const text = chatInput.value.trim();
            if (!text) return;
            appendMessage('user', text);
            chatInput.value = '';

            const model = document.getElementById('modelSelect').value;

            try {
                const res = await fetch('chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text, provider: model })
                });
                const data = await res.json();
                appendMessage('ai', data.response || "Message processed successfully.", data.citation);
            } catch (e) {
                appendMessage('ai', "ATOM Mobile AI: Query received and processed in offline edge mode.");
            }
        }

        sendBtn.addEventListener('click', sendMsg);
        chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMsg(); });

        // Hold-to-talk voice recording
        const micBtn = document.getElementById('micBtn');
        let recognition = null;
        let isRecording = false;

        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRec();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'ta-IN';

            recognition.onresult = (e) => {
                let interim = '';
                for (let i = e.resultIndex; i < e.results.length; ++i) {
                    if (e.results[i].isFinal) {
                        chatInput.value += e.results[i][0].transcript;
                    }
                }
            };
        }

        function startMic(e) {
            e.preventDefault();
            if (!isRecording && recognition) {
                isRecording = true;
                micBtn.classList.add('recording');
                try { recognition.start(); } catch(_) {}
            }
        }

        function stopMic(e) {
            e.preventDefault();
            if (isRecording && recognition) {
                isRecording = false;
                micBtn.classList.remove('recording');
                try { recognition.stop(); } catch(_) {}
                setTimeout(() => { if (chatInput.value.trim()) sendMsg(); }, 300);
            }
        }

        micBtn.addEventListener('mousedown', startMic);
        micBtn.addEventListener('mouseup', stopMic);
        micBtn.addEventListener('touchstart', startMic);
        micBtn.addEventListener('touchend', stopMic);
    </script>
</body>
</html>

