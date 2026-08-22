/* ATOM Web Portal Application Script */

document.addEventListener('DOMContentLoaded', () => {
    initInteractiveConsole();
    initStatsCounter();
});

// Interactive AI Console Demo
function initInteractiveConsole() {
    const consoleInput = document.getElementById('consoleInput');
    const sendBtn = document.getElementById('consoleSendBtn');
    const consoleBody = document.getElementById('consoleBody');
    const promptChips = document.querySelectorAll('.chip-prompt');

    function sendQuery(queryText) {
        if (!queryText.trim()) return;

        // User bubble
        appendConsoleMessage('user', 'You', queryText);
        if (consoleInput) consoleInput.value = '';

        // Simulated AI response
        setTimeout(() => {
            let aiReply = '';
            const lower = queryText.toLowerCase();

            if (lower.includes('rag') || lower.includes('pdf') || lower.includes('document')) {
                aiReply = `<strong>ATOM RAG Search</strong>: Found 2 chunks in <code>docs/ATOM_User_Manual_Guide.md</code> (Similarity: 0.96).\n\n<code>PdfExtractor.php</code> parses documents into 512-token chunks and vector embeddings.`;
            } else if (lower.includes('graph') || lower.includes('triple') || lower.includes('knowledge')) {
                aiReply = `<strong>Knowledge Graph Lookup</strong>: Found 3 SPO Triples:\n- <code>(ATOM, depends_on, SQLite)</code>\n- <code>(Vichu, role, PHP Full-Stack Developer)</code>\n- <code>(Self-Learning, mode, Human Safety Gate)</code>`;
            } else if (lower.includes('model') || lower.includes('gemini') || lower.includes('openai')) {
                aiReply = `<strong>Multi-Provider Switcher</strong>: Connected to <code>Gemini 3.6 Flash</code> (Fallback: Local Ollama Llama 3.1). Latency: <strong>112ms</strong>.`;
            } else {
                aiReply = `Hello <strong>Vichu</strong>! I am <strong>ATOM</strong>, your Personal AI Assistant with integrated Hybrid RAG, Knowledge Graph Triples, and Human Safety Gate controls. How can I assist you with your PHP / Full-Stack project today?`;
            }

            appendConsoleMessage('ai', 'ATOM AI Core', aiReply);
        }, 700);
    }

    if (sendBtn && consoleInput) {
        sendBtn.addEventListener('click', () => sendQuery(consoleInput.value));
        consoleInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendQuery(consoleInput.value);
        });
    }

    promptChips.forEach(chip => {
        chip.addEventListener('click', () => {
            sendQuery(chip.innerText);
        });
    });
}

function appendConsoleMessage(sender, name, htmlContent) {
    const consoleBody = document.getElementById('consoleBody');
    if (!consoleBody) return;

    const div = document.createElement('div');
    div.className = `console-msg ${sender}`;

    div.innerHTML = `
        <div class="console-avatar">${sender === 'user' ? 'U' : 'AI'}</div>
        <div class="console-bubble">
            <div style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 4px;">${name} • Just now</div>
            <div>${htmlContent.replace(/\n/g, '<br>')}</div>
        </div>
    `;

    consoleBody.appendChild(div);
    consoleBody.scrollTop = consoleBody.scrollHeight;
}

// Live Animated Counters
function initStatsCounter() {
    const stats = [
        { id: 'statChunks', end: 14290 },
        { id: 'statLatency', end: 124 },
        { id: 'statAccuracy', end: 99.4 }
    ];

    stats.forEach(s => {
        const el = document.getElementById(s.id);
        if (!el) return;

        let current = 0;
        const step = s.end / 40;
        const timer = setInterval(() => {
            current += step;
            if (current >= s.end) {
                current = s.end;
                clearInterval(timer);
            }
            el.innerText = s.id === 'statAccuracy' ? `${current.toFixed(1)}%` : Math.floor(current).toLocaleString();
        }, 30);
    });
}
