/* ATOM Combined Unified Web Application Script */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initChatConsole();
    initRAGUploader();
    initKnowledgeGraph();
    initTelemetryStream();
    initStatsCounter();
});

// Tab Navigation Handling
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const viewPanels = document.querySelectorAll('.view-panel');

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetTab = item.getAttribute('data-tab');

            navItems.forEach(nav => nav.classList.remove('active'));
            viewPanels.forEach(panel => panel.classList.remove('active'));

            item.classList.add('active');
            const activePanel = document.getElementById(`view-${targetTab}`);
            if (activePanel) {
                activePanel.classList.add('active');
            }
        });
    });
}

// Chat Console Logic
function initChatConsole() {
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatHistory = document.getElementById('chatHistory');
    const chipBtns = document.querySelectorAll('.chip-btn');

    function sendMessage(text) {
        if (!text || !text.trim()) return;

        appendMessage('user', 'Vichu', text);
        if (chatInput) chatInput.value = '';

        setTimeout(() => {
            let simulatedResponse = '';
            const lower = text.toLowerCase();

            if (lower.includes('rag') || lower.includes('pdf') || lower.includes('document')) {
                simulatedResponse = `**ATOM RAG Engine**: Retrieved context from \`docs/ATOM_User_Manual_Guide.md\` (Similarity: 0.96).\n\n\`\`\`php\n$ragService = new \\Atom\\Knowledge\\HybridRAG();\n$results = $ragService->search("$text", limit: 5);\n\`\`\`\nVector search completed in **14ms**.`;
            } else if (lower.includes('graph') || lower.includes('triple') || lower.includes('knowledge')) {
                simulatedResponse = `**Knowledge Graph Query**: Extracted 3 SPO Triples:\n- \`(ATOM Engine, uses, CodeIgniter 4)\`\n- \`(Vichu, role, PHP Full-Stack Developer)\`\n- \`(Self-Learning Sandbox, gate_type, Human Authorization)\``;
            } else if (lower.includes('model') || lower.includes('health') || lower.includes('gemini')) {
                simulatedResponse = `**System Health Check**: Active provider <code>Gemini 3.6 Flash</code>. Latency: **118ms**, CPU: **9.4%**, Memory: **3.4 GB**.`;
            } else {
                simulatedResponse = `Hello **Vichu**! I am **ATOM**, your Personal AI Assistant configured for modern PHP & Full-Stack development. How can I assist you with your project today?`;
            }

            appendMessage('ai', 'ATOM AI Core', simulatedResponse);
        }, 700);
    }

    if (sendBtn && chatInput) {
        sendBtn.addEventListener('click', () => sendMessage(chatInput.value));
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage(chatInput.value);
        });
    }

    chipBtns.forEach(chip => {
        chip.addEventListener('click', () => {
            sendMessage(chip.innerText);
        });
    });
}

function appendMessage(sender, name, content) {
    const chatHistory = document.getElementById('chatHistory');
    if (!chatHistory) return;

    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-message ${sender}`;

    const formattedContent = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-family: monospace;">$1</code>')
        .replace(/```(\w+)?\n([\s\S]*?)\n```/g, '<div style="background: #060911; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 12px; font-family: monospace; color: #38bdf8; margin-top: 8px; font-size: 0.85rem;">$2</div>');

    msgDiv.innerHTML = `
        <div class="msg-avatar">${sender === 'user' ? 'V' : 'AI'}</div>
        <div class="msg-bubble">
            <div style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 4px;">${name} • Just now</div>
            <div>${formattedContent}</div>
        </div>
    `;

    chatHistory.appendChild(msgDiv);
    chatHistory.scrollTop = chatHistory.scrollHeight;
}

// RAG PDF Drag & Drop Simulation
function initRAGUploader() {
    const dropzone = document.getElementById('pdfDropzone');
    const fileInput = document.getElementById('fileInput');

    if (!dropzone || !fileInput) return;

    dropzone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            simulateUpload(e.target.files[0].name);
        }
    });
}

function simulateUpload(filename) {
    const statusText = document.getElementById('uploadStatusText');
    if (!statusText) return;

    statusText.innerHTML = `⚡ Ingesting <strong>${filename}</strong>... Chunking & Extracting Triples...`;
    
    setTimeout(() => {
        statusText.innerHTML = `✅ Successfully indexed <strong>${filename}</strong> (128 vectors added to SQLite database).`;
        addDocToTable(filename, '128 Vectors', 'Active');
    }, 1500);
}

function addDocToTable(name, vectors, status) {
    const tableBody = document.getElementById('docTableBody');
    if (!tableBody) return;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><i class="fa-regular fa-file-pdf" style="color: #ff5252; margin-right: 8px;"></i>${name}</td>
        <td><span class="badge-tag">${vectors}</span></td>
        <td><span style="color: #00e676;"><i class="fa-solid fa-circle-check"></i> ${status}</span></td>
        <td>${new Date().toLocaleTimeString()}</td>
    `;
    tableBody.prepend(tr);
}

// Knowledge Graph Node Selection
function initKnowledgeGraph() {
    const nodes = document.querySelectorAll('.node-group');
    const detailPanel = document.getElementById('tripleDetail');

    nodes.forEach(node => {
        node.addEventListener('click', () => {
            const label = node.getAttribute('data-label');
            const rel = node.getAttribute('data-rel') || 'Connected to Neural Hub';
            if (detailPanel) {
                detailPanel.innerHTML = `
                    <div style="font-size: 0.95rem; font-weight: 600; color: #00f2fe;">Node Details: ${label}</div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 6px;">Relationship: <em>${rel}</em></div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">Triple Vector ID: #SPO-77492-X</div>
                `;
            }
        });
    });
}

// Telemetry & Metrics Simulation
function initTelemetryStream() {
    setInterval(() => {
        const cpuEl = document.getElementById('cpuMetric');
        const latencyEl = document.getElementById('latencyMetric');

        if (cpuEl) {
            const randomCpu = (8 + Math.random() * 5).toFixed(1);
            cpuEl.innerText = `${randomCpu}%`;
        }

        if (latencyEl) {
            const randomLatency = Math.floor(115 + Math.random() * 35);
            latencyEl.innerText = `${randomLatency} ms`;
        }
    }, 3000);
}

// Animated Stat Counter
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
