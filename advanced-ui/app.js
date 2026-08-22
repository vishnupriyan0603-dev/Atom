/* ATOM Advanced Neural UI - Application Logic */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initChatConsole();
    initRAGUploader();
    initKnowledgeGraph();
    initTelemetryStream();
});

// Navigation Handling
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

// Chat Console & Simulated Neural Responses
function initChatConsole() {
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const chatHistory = document.getElementById('chatHistory');
    const chipBtns = document.querySelectorAll('.chip-btn');

    function sendMessage(text) {
        if (!text.trim()) return;

        // User Message
        appendMessage('user', 'You', text);
        chatInput.value = '';

        // Simulated AI Response with streaming delay
        setTimeout(() => {
            const simulatedResponses = [
                `**ATOM RAG Engine**: Retrieved context from \`docs/ATOM_User_Manual_Guide.md\` (Similarity: 0.94).\n\nHere is what I found:\n\`\`\`php\n$ragService = new \\Atom\\Knowledge\\HybridRAG();\n$results = $ragService->search("$text", limit: 5);\n\`\`\`\nThe vector database search completed in **14ms**.`,
                `**Knowledge Graph Query**: Extracted 3 Subject-Predicate-Object triples.\n- \`(ATOM Engine, uses, CodeIgniter 4)\`\n- \`(Knowledge Base, indexed_with, SQLite Vector)\`\n- \`(Self-Learning Sandbox, gate_type, Human Authorization)\`\n\nHow would you like to process these triples?`,
                `**A/B Sandbox Test Result**: Analyzed performance logs. Memory usage optimized by **18.4%** across 500 benchmark queries.`
            ];

            const responseText = simulatedResponses[Math.floor(Math.random() * simulatedResponses.length)];
            appendMessage('ai', 'ATOM AI Core', responseText);
        }, 800);
    }

    sendBtn.addEventListener('click', () => sendMessage(chatInput.value));
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage(chatInput.value);
    });

    chipBtns.forEach(chip => {
        chip.addEventListener('click', () => {
            sendMessage(chip.innerText);
        });
    });
}

function appendMessage(sender, name, content) {
    const chatHistory = document.getElementById('chatHistory');
    const msgDiv = document.createElement('div');
    msgDiv.className = `chat-message ${sender}`;

    const formattedContent = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-family: monospace;">$1</code>')
        .replace(/```(\w+)?\n([\s\S]*?)\n```/g, '<div class="code-block">$2</div>');

    msgDiv.innerHTML = `
        <div class="msg-avatar">${sender === 'user' ? 'U' : 'AI'}</div>
        <div class="msg-bubble">
            <div style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 4px;">${name} • Just now</div>
            <div>${formattedContent}</div>
        </div>
    `;

    chatHistory.appendChild(msgDiv);
    chatHistory.scrollTop = chatHistory.scrollHeight;
}

// RAG Uploader Simulation
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

// Live Metrics Simulation
function initTelemetryStream() {
    setInterval(() => {
        const cpuEl = document.getElementById('cpuMetric');
        const latencyEl = document.getElementById('latencyMetric');

        if (cpuEl) {
            const randomCpu = (8 + Math.random() * 6).toFixed(1);
            cpuEl.innerText = `${randomCpu}%`;
        }

        if (latencyEl) {
            const randomLatency = Math.floor(110 + Math.random() * 40);
            latencyEl.innerText = `${randomLatency} ms`;
        }
    }, 3000);
}
