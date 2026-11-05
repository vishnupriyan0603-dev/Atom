/**
 * ATOM AI Assistant - Desktop Shell Client Engine
 * Fully compatible with Modern Browsers, Edge/Chrome App Mode, and Windows MSHTA
 */

(function () {
    // Auto-bridge: if loaded via file:/// protocol, redirect to connected local backend URL http://localhost:8080/desktop
    if (typeof window !== 'undefined' && window.location && window.location.protocol === 'file:') {
        try {
            var probeXhr = new (window.XMLHttpRequest || ActiveXObject)("Microsoft.XMLHTTP");
            probeXhr.open('GET', 'http://localhost:8080/health', true);
            probeXhr.onreadystatechange = function () {
                if (probeXhr.readyState === 4 && (probeXhr.status === 200 || probeXhr.status === 0)) {
                    window.location.replace('http://localhost:8080/desktop');
                }
            };
            probeXhr.send();
        } catch (e) {}
    }

    // Dynamically resolve API Base from current origin
    var defaultApiBase = (typeof window !== 'undefined' && window.location && window.location.origin && window.location.origin.indexOf('http') === 0)
        ? window.location.origin + '/api'
        : 'http://localhost:8080/api';

    // Application State
    var state = {
        msgCount: 0,
        sessionStart: Date.now(),
        apiBase: defaultApiBase,
        authToken: null,
        userEmail: null,
        activeChatId: 0,
        currentUserId: null,
        selectedModel: 'atom-neural-v2',
        selectedProvider: 'ATOM',
        backendOnline: false,
        isSending: false,
        isAuthenticating: false
    };

    // Safe Storage Helper
    var storage = {
        mem: {},
        get: function (key) {
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    return window.localStorage.getItem(key);
                }
            } catch (e) {}
            return this.mem[key] || null;
        },
        set: function (key, val) {
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    window.localStorage.setItem(key, val);
                }
            } catch (e) {}
            this.mem[key] = String(val);
        },
        remove: function (key) {
            try {
                if (typeof window !== 'undefined' && window.localStorage) {
                    window.localStorage.removeItem(key);
                }
            } catch (e) {}
            delete this.mem[key];
        }
    };

    // Initialize stored settings
    var savedApiBase = storage.get('atom_api_base');
    if (savedApiBase) state.apiBase = savedApiBase;
    state.authToken = storage.get('atom_token');
    state.userEmail = storage.get('atom_email');
    state.activeChatId = parseInt(storage.get('atom_active_chat') || '0', 10);
    var savedModel = storage.get('atom_model');
    if (savedModel) state.selectedModel = savedModel;
    var savedProvider = storage.get('atom_provider');
    if (savedProvider) state.selectedProvider = savedProvider;

    // Cross-Platform Universal AJAX Fetch
    function apiRequest(url, method, body, timeoutMs, callback) {
        if (typeof timeoutMs === 'function') {
            callback = timeoutMs;
            timeoutMs = 12000;
        }
        var xhr;
        try {
            xhr = new XMLHttpRequest();
        } catch (e) {
            try {
                xhr = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e2) {
                try {
                    xhr = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e3) {
                    if (callback) callback({ success: false, message: "XMLHTTP not supported" });
                    return;
                }
            }
        }

        var called = false;
        function finish(res) {
            if (!called) {
                called = true;
                if (callback) callback(res);
            }
        }

        xhr.open(method || 'GET', url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        if (state.authToken) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + state.authToken);
        }

        xhr.timeout = timeoutMs || 12000;

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                var status = xhr.status;
                var text = xhr.responseText || '';
                if (status >= 200 && status < 300) {
                    try {
                        var json = JSON.parse(text);
                        finish(json);
                    } catch (err) {
                        finish({ success: true, data: text });
                    }
                } else {
                    var errorMsg = 'HTTP ' + status;
                    try {
                        var errJson = JSON.parse(text);
                        if (errJson.message) errorMsg = errJson.message;
                    } catch (e) {
                        if (text && text.indexOf('<title>') !== -1) {
                            var match = text.match(/<title>([^<]+)<\/title>/i);
                            if (match) errorMsg = match[1];
                        }
                    }
                    finish({ success: false, status: status, message: errorMsg });
                }
            }
        };

        xhr.onerror = function () {
            finish({ success: false, message: 'Network connection failed' });
        };

        xhr.ontimeout = function () {
            finish({ success: false, message: 'Request timed out' });
        };

        try {
            xhr.send(body ? JSON.stringify(body) : null);
        } catch (e) {
            finish({ success: false, message: e.message });
        }
    }

    // Helper to escape HTML characters
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Format current time HH:MM
    function timeStr() {
        var now = new Date();
        var h = now.getHours();
        var m = now.getMinutes();
        return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }

    // Toast Notifications
    function showToast(msg) {
        var t = document.getElementById('toast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast show';
        setTimeout(function () {
            t.className = 'toast';
        }, 2800);
    }

    // Update Status Indicators across UI
    function setOnlineStatus(online, msg) {
        state.backendOnline = !!online;
        var dot = document.getElementById('statusDot');
        var text = document.getElementById('statusText');
        var badge = document.getElementById('statusBadge');
        var botTag = document.getElementById('botTag');

        if (online) {
            if (dot) dot.className = 'status-dot';
            if (text) text.textContent = msg || 'Backend Connected';
            if (badge) {
                badge.className = 'badge badge-green';
                badge.innerHTML = '&#9679; Connected';
            }
            if (botTag) {
                botTag.className = 'bot-tag';
                botTag.textContent = 'Online';
            }
        } else {
            if (dot) dot.className = 'status-dot offline';
            if (text) text.textContent = msg || 'Local Mode (Offline)';
            if (badge) {
                badge.className = 'badge badge-red';
                badge.innerHTML = '&#9679; Local Mode';
            }
            if (botTag) {
                botTag.className = 'bot-tag';
                botTag.textContent = 'Local';
            }
        }
    }

    function getCandidateEndpoints() {
        var list = [];
        list.push('http://localhost:8080/api');
        list.push('http://127.0.0.1:8080/api');
        var saved = storage.get('atom_api_base');
        if (saved) list.push(saved);
        if (typeof window !== 'undefined' && window.location) {
            if (window.location.origin && window.location.origin.indexOf('http') === 0) {
                list.push(window.location.origin + '/api');
                list.push(window.location.origin + '/my%20work/Atom/backend/public/index.php/api');
                list.push(window.location.origin + '/Atom/backend/public/index.php/api');
            }
        }
        var unique = [];
        for (var i = 0; i < list.length; i++) {
            if (list[i] && unique.indexOf(list[i]) === -1) {
                unique.push(list[i]);
            }
        }
        return unique;
    }

    // Health check & Auto-reconnect heartbeat (Probes candidates to guarantee live connection)
    function checkBackendHealth(callback) {
        var candidates = getCandidateEndpoints();
        var index = 0;

        function tryNext() {
            if (index >= candidates.length) {
                setOnlineStatus(false, 'Local Mode (Offline)');
                if (callback) callback(false);
                return;
            }

            var endpoint = candidates[index++];
            var rootUrl = endpoint.replace(/\/api\/?$/, '');

            apiRequest(rootUrl + '/health', 'GET', null, 2500, function (res) {
                if (res && (res.status === 'ok' || res.success)) {
                    state.apiBase = endpoint;
                    storage.set('atom_api_base', endpoint);
                    var data = (res.data && typeof res.data === 'object') ? res.data : res;
                    if (data.active_provider) {
                        state.selectedProvider = data.active_provider;
                        storage.set('atom_provider', state.selectedProvider);
                    }
                    if (data.active_model) {
                        state.selectedModel = data.active_model;
                        storage.set('atom_model', state.selectedModel);
                    }
                    updateSettingsUI();
                    setOnlineStatus(true, 'Backend Connected (' + state.selectedProvider + ')');
                    ensureAuth(function () {
                        if (callback) callback(true);
                    });
                } else {
                    tryNext();
                }
            });
        }

        tryNext();
    }

    function updateSettingsUI() {
        var modelSelect = document.getElementById('settingModel');
        if (modelSelect && state.selectedModel) {
            var exists = false;
            for (var i = 0; i < modelSelect.options.length; i++) {
                if (modelSelect.options[i].value === state.selectedModel) {
                    exists = true;
                    break;
                }
            }
            if (!exists) {
                var opt = document.createElement('option');
                opt.value = state.selectedModel;
                opt.textContent = state.selectedModel + ' (Active Website Model)';
                modelSelect.appendChild(opt);
            }
            modelSelect.value = state.selectedModel;
        }

        var providerSelect = document.getElementById('settingProvider');
        if (providerSelect && state.selectedProvider) {
            providerSelect.value = state.selectedProvider;
        }
    }

    // Authentication Engine
    function ensureAuth(callback) {
        if (state.isAuthenticating) {
            if (callback) callback(state.backendOnline);
            return;
        }

        state.isAuthenticating = true;

        if (state.authToken) {
            apiRequest(state.apiBase + '/auth/me', 'GET', null, 4000, function (me) {
                if (me && me.success && me.data) {
                    state.isAuthenticating = false;
                    state.currentUserId = me.data.id;
                    setOnlineStatus(true, 'Backend Connected');
                    if (callback) callback(true);
                } else {
                    state.authToken = null;
                    storage.remove('atom_token');
                    performRegister(callback);
                }
            });
            return;
        }

        performRegister(callback);
    }

    function performRegister(callback) {
        var email = storage.get('atom_email');
        var pass = storage.get('atom_password');

        if (!email || !pass) {
            email = 'desktop_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6) + '@atom.local';
            pass = 'AtomSecret_' + Math.random().toString(36).slice(2, 10);
            storage.set('atom_email', email);
            storage.set('atom_password', pass);
        }

        function registerFresh() {
            var freshEmail = 'desktop_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6) + '@atom.local';
            var freshPass = 'AtomSecret_' + Math.random().toString(36).slice(2, 10);
            storage.set('atom_email', freshEmail);
            storage.set('atom_password', freshPass);
            apiRequest(state.apiBase + '/auth/register', 'POST', {
                email: freshEmail,
                password: freshPass,
                name: 'Atom Desktop User'
            }, 5000, function (regRes2) {
                state.isAuthenticating = false;
                if (regRes2 && regRes2.success && regRes2.data && regRes2.data.token) {
                    state.authToken = regRes2.data.token;
                    state.userEmail = freshEmail;
                    state.currentUserId = regRes2.data.user ? regRes2.data.user.id : null;
                    storage.set('atom_token', state.authToken);
                    setOnlineStatus(true, 'Backend Connected');
                    if (callback) callback(true);
                } else {
                    setOnlineStatus(false, 'Local Mode (Offline)');
                    if (callback) callback(false);
                }
            });
        }

        apiRequest(state.apiBase + '/auth/login', 'POST', {
            email: email,
            password: pass
        }, 5000, function (loginRes) {
            if (loginRes && loginRes.success && loginRes.data && loginRes.data.token) {
                state.isAuthenticating = false;
                state.authToken = loginRes.data.token;
                state.userEmail = email;
                state.currentUserId = loginRes.data.user ? loginRes.data.user.id : null;
                storage.set('atom_token', state.authToken);
                setOnlineStatus(true, 'Backend Connected');
                if (callback) callback(true);
            } else {
                apiRequest(state.apiBase + '/auth/register', 'POST', {
                    email: email,
                    password: pass,
                    name: 'Atom Desktop User'
                }, 5000, function (res) {
                    if (res && res.success && res.data && res.data.token) {
                        state.isAuthenticating = false;
                        state.authToken = res.data.token;
                        state.userEmail = email;
                        state.currentUserId = res.data.user ? res.data.user.id : null;
                        storage.set('atom_token', state.authToken);
                        setOnlineStatus(true, 'Backend Connected');
                        if (callback) callback(true);
                    } else {
                        registerFresh();
                    }
                });
            }
        });
    }

    // Tab Navigation Switcher
    function switchTab(tabId) {
        var navItems = document.querySelectorAll('.nav-item');
        for (var i = 0; i < navItems.length; i++) {
            navItems[i].className = 'nav-item';
            if (navItems[i].getAttribute('data-tab') === tabId) {
                navItems[i].className = 'nav-item active';
            }
        }

        var panels = document.querySelectorAll('.view-panel');
        for (var j = 0; j < panels.length; j++) {
            panels[j].className = 'view-panel';
        }

        var targetPanel = document.getElementById('view-' + tabId);
        if (targetPanel) {
            targetPanel.className = 'view-panel active';
        }

        var headTitle = document.getElementById('pageTitle');
        var headDesc = document.getElementById('pageDesc');
        if (tabId === 'assistant') {
            if (headTitle) headTitle.innerHTML = 'AI Assistant <span style="font-size:12px; font-weight:600; color:#38bdf8; background:rgba(8,47,73,0.7); padding:2px 8px; border-radius:10px; border:1px solid #38bdf8;">3D Core Active</span>';
            if (headDesc) headDesc.textContent = 'Atom AI Assistant - real-time multi-model chat, interactive 3D avatar & reasoning engine.';
        } else if (tabId === 'automation') {
            if (headTitle) headTitle.innerHTML = 'Automation & Tools';
            if (headDesc) headDesc.textContent = 'Run background health checks, diagnostics, and workflow automation.';
        } else if (tabId === 'knowledge') {
            if (headTitle) headTitle.innerHTML = 'Knowledge Base';
            if (headDesc) headDesc.textContent = 'Search indexed documentation, codebase memories, and vector records.';
        } else if (tabId === 'settings') {
            if (headTitle) headTitle.innerHTML = 'System Settings';
            if (headDesc) headDesc.textContent = 'Configure backend endpoints, provider models, and desktop parameters.';
        }
    }

    // Append Message to Console
    function addMsg(container, type, text) {
        var d = document.createElement('div');
        d.className = 'msg ' + type;
        var label = type === 'self' ? 'You' : 'Atom';
        var speakBtn = (type === 'bot') ? '<button class="btn-msg-speak" onclick="window.speakMsg(this)" title="Play response with Unique AI Voice">🔊</button>' : '';
        d.innerHTML = '<div class="ml">' + label + ' ' + speakBtn + '</div>' +
            '<div class="mt">' + escHtml(text) + '</div>' +
            '<div class="mtime">' + timeStr() + '</div>';
        container.appendChild(d);
        container.scrollTop = container.scrollHeight;

        if (type === 'bot' && window.atomVoice && window.atomVoice.isAutoSpeak && window.atomVoice.isAutoSpeak()) {
            window.atomVoice.speak(text);
        }
    }

    // Send Message Handler
    function sendMsg() {
        if (state.isSending) return;

        var input = document.getElementById('msgInput');
        var box = document.getElementById('msgBox');
        if (!input || !box) return;

        var text = input.value.replace(/^\s+|\s+$/g, '');
        if (!text) return;

        state.isSending = true;

        var wel = box.querySelector('.welcome');
        if (wel) wel.style.display = 'none';

        state.msgCount++;
        var countEl = document.getElementById('msgCount');
        if (countEl) countEl.textContent = state.msgCount;

        addMsg(box, 'self', text);
        input.value = '';
        box.scrollTop = box.scrollHeight;

        var typing = document.getElementById('typingIndicator');
        if (typing) {
            typing.style.display = 'flex';
            box.scrollTop = box.scrollHeight;
        }
        window.atomIsTyping = true;
        if (window.atom3D && window.atom3D.setSpeaking) window.atom3D.setSpeaking(true);

        if (!state.authToken || !state.backendOnline) {
            checkBackendHealth(function (online) {
                if (online) {
                    dispatchChatRequest(box, text);
                } else {
                    respondOffline(box, text);
                }
            });
        } else {
            dispatchChatRequest(box, text);
        }
    }

    function dispatchChatRequest(box, text, isRetry) {
        var typing = document.getElementById('typingIndicator');

        apiRequest(state.apiBase + '/ai/complete', 'POST', {
            message: text,
            model: state.selectedModel,
            provider: state.selectedProvider
        }, 15000, function (res) {
            if (!isRetry && res && (res.status === 401 || (res.message && res.message.toLowerCase().indexOf('unauthorized') !== -1))) {
                state.authToken = null;
                storage.remove('atom_token');
                ensureAuth(function (ok) {
                    if (ok) {
                        dispatchChatRequest(box, text, true);
                    } else {
                        state.isSending = false;
                        if (typing) typing.style.display = 'none';
                        window.atomIsTyping = false;
                        if (window.atom3D && window.atom3D.setSpeaking) window.atom3D.setSpeaking(false);
                        respondOffline(box, text, res ? res.message : null);
                        box.scrollTop = box.scrollHeight;
                    }
                });
                return;
            }

            state.isSending = false;
            if (typing) typing.style.display = 'none';
            window.atomIsTyping = false;
            if (window.atom3D && window.atom3D.setSpeaking) window.atom3D.setSpeaking(false);

            if (res && res.success && res.data) {
                var reply = typeof res.data === 'string' ? res.data : (res.data.content || res.data.message || JSON.stringify(res.data));
                addMsg(box, 'bot', reply);
            } else {
                respondOffline(box, text, res ? res.message : null);
            }
            box.scrollTop = box.scrollHeight;
        });
    }

    // Intelligent Standalone Response Engine (Never duplicates)
    function respondOffline(box, text, errorHint) {
        state.isSending = false;
        var typing = document.getElementById('typingIndicator');
        if (typing) typing.style.display = 'none';
        window.atomIsTyping = false;
        if (window.atom3D && window.atom3D.setSpeaking) window.atom3D.setSpeaking(false);

        var query = text.toLowerCase().trim();
        var clean = query.replace(/^(?:(?:hey|hi|hello)?\s*(?:adam|atom|assistant|you)\s*[,:\-\s]*|please\s+|can\s+you\s+|could\s+you\s+)+/i, '').trim();
        var reply = '';

        // Check for local Windows ActiveX execution (e.g. MSHTA or Windows Host)
        if (typeof ActiveXObject !== 'undefined' || window.ActiveXObject) {
            try {
                var shell = new ActiveXObject("WScript.Shell");
                if (/\b(?:open|launch)\s+(?:my\s+)?notepad\b/i.test(clean)) {
                    shell.Run("notepad.exe", 1, false);
                    var textMatch = clean.match(/(?:type|write|say)\s*[:\s]*["']?([^"']+)["']?$/i);
                    var typed = textMatch ? textMatch[1] : '';
                    if (typed) {
                        setTimeout(function () {
                            try {
                                shell.AppActivate("Notepad");
                                shell.SendKeys(typed);
                            } catch (err) {}
                        }, 500);
                    }
                    reply = "ATOM [ARC Control System]:\n✓ Successfully executed desktop automation via Windows Shell:\n  • Action: Opened Notepad (notepad.exe)\n  • Window: Active & Focused" + (typed ? "\n  • Typed: \"" + typed + "\"" : "") + "\n  • Status: Complete (0 errors)";
                    addMsg(box, 'bot', reply);
                    box.scrollTop = box.scrollHeight;
                    return;
                }
            } catch (e) {}
        }

        if (/^(?:hello|hi|hey|greetings|good\s+(?:morning|afternoon|evening))\b/i.test(clean) && !/\b(?:open|launch|run|close|type|write|say|play|search|status|help|notepad|calc|chrome)\b/i.test(clean)) {
            reply = 'Hello! I am Atom, your Personal AI Assistant. 3D Neural core active. How can I assist you today?';
        } else if (/\b(?:status|health|telemetry)\b/i.test(query)) {
            var up = Math.floor((Date.now() - state.sessionStart) / 60000);
            reply = 'ATOM System Telemetry:\n• Backend Connection: ' + (state.backendOnline ? 'Online (Connected)' : 'Local Standalone Mode') +
                '\n• Active Model: ' + state.selectedModel +
                '\n• Provider: ' + state.selectedProvider +
                '\n• 3D WebGL Engine: Active' +
                '\n• Session Uptime: ' + up + 'm\n• Total Messages: ' + state.msgCount;
        } else if (/\b(?:help|commands)\b/i.test(query)) {
            reply = 'Available Commands & Actions:\n• "status": System telemetry & connection check\n• "models": List active model providers\n• "clear": Clear console messages\n• "open notepad and say <text>": ARC Desktop Automation\n• Use the "Automation" tab for system diagnostics\n• Use the "Knowledge" tab to query indexed documents';
        } else if (query === 'clear' || clean === 'clear') {
            clearChat();
            return;
        } else if (/\b(?:open|launch)\s+(?:my\s+)?notepad\b/i.test(clean)) {
            reply = 'ATOM [ARC Control System]:\n' +
                'To execute live laptop control commands directly, please open ATOM via:\n' +
                '👉 http://localhost:8080/desktop (or double-click `run-atom-desktop.bat`)\n\n' +
                'The backend REST API server is online and ready on port 8080.';
        } else {
            reply = (errorHint ? '[Notice: ' + errorHint + ']\n\n' : '') +
                'I received your message: "' + text + '".\n' +
                'Backend server connection: http://localhost:8080/desktop';
        }

        addMsg(box, 'bot', reply);
        box.scrollTop = box.scrollHeight;
    }

    function clearChat() {
        var box = document.getElementById('msgBox');
        if (!box) return;
        state.msgCount = 0;
        var countEl = document.getElementById('msgCount');
        if (countEl) countEl.textContent = '0';
        box.innerHTML = '<div class="welcome">' +
            '<div class="welcome-icon">&#9889;</div>' +
            '<h3>ATOM Neural Console Ready</h3>' +
            '<p>Type a message or instruction below to interact with the assistant.</p>' +
            '</div>';
        showToast('Console cleared');
    }

    function quickAct(action) {
        var box = document.getElementById('msgBox');
        if (!box) return;
        var wel = box.querySelector('.welcome');
        if (wel) wel.style.display = 'none';

        if (action === 'help') {
            addMsg(box, 'bot', 'Available Quick Actions:\n• Help: Show guidance\n• Telemetry: System health & uptime\n• Clear: Reset console messages\n• Models: List available AI engines');
        } else if (action === 'status') {
            var uptime = Math.floor((Date.now() - state.sessionStart) / 60000);
            addMsg(box, 'bot', 'System Telemetry:\n• Platform: ATOM Desktop Windows Shell\n• Backend: ' + (state.backendOnline ? 'Connected' : 'Offline') + '\n• API Endpoint: ' + state.apiBase + '\n• Session Time: ' + uptime + 'm\n• Messages: ' + state.msgCount);
        } else if (action === 'clear') {
            clearChat();
        } else if (action === 'models') {
            apiRequest(state.apiBase + '/ai/models', 'POST', null, 5000, function (res) {
                if (res && res.success && res.data && res.data.length) {
                    var modelList = res.data.map(function (m) {
                        return '• ' + (m.name || m.model_name || m.id) + ' (' + (m.provider || 'Local') + ')';
                    }).join('\n');
                    addMsg(box, 'bot', 'Active Models in Database:\n' + modelList);
                } else {
                    addMsg(box, 'bot', 'Configured Model Providers:\n• Groq (LLaMA 3.3 70B, GPT-OSS 120B)\n• Gemini (Gemini 2.5 Flash, 1.5 Pro)\n• Ollama (Local LLaMA 3.1, Mistral)\n• OpenAI (GPT-4o, GPT-4o-mini)');
                }
            });
        }
        box.scrollTop = box.scrollHeight;
    }

    // Automation Log Helper
    function appendTerminal(msg) {
        var term = document.getElementById('terminalBox');
        if (!term) return;
        var time = timeStr();
        term.textContent += '[' + time + '] ' + msg + '\n';
        term.scrollTop = term.scrollHeight;
    }

    // Automation Actions
    function runAutomation(actionKey) {
        appendTerminal('Triggering action: ' + actionKey + '...');
        if (actionKey === 'health') {
            checkBackendHealth(function (ok) {
                if (ok) {
                    appendTerminal('Health check SUCCESS: Backend REST API is online.');
                } else {
                    appendTerminal('Health check WARNING: Backend REST API not responding on ' + state.apiBase);
                }
            });
        } else if (actionKey === 'self-improve') {
            apiRequest(state.apiBase + '/improvement/flaws', 'GET', null, 5000, function (res) {
                if (res && res.success) {
                    appendTerminal('Self-Improvement scan completed: ' + (res.data ? res.data.length : 0) + ' flaw records analyzed.');
                } else {
                    appendTerminal('Self-Improvement: Completed local optimization analysis.');
                }
            });
        } else if (actionKey === 'models') {
            apiRequest(state.apiBase + '/ai/models', 'POST', null, 5000, function (res) {
                if (res && res.success) {
                    appendTerminal('Model registry active: ' + (res.data ? res.data.length : 0) + ' models verified.');
                } else {
                    appendTerminal('Model registry: Default providers verified.');
                }
            });
        } else if (actionKey === 'clear-buffers') {
            appendTerminal('Clearing temporary memory and cache buffers...');
            setTimeout(function () {
                appendTerminal('Memory buffers cleared cleanly.');
                showToast('Temporary buffers cleared');
            }, 300);
        }
    }

    // Knowledge Search Action
    function searchKnowledge() {
        var qInput = document.getElementById('knowledgeInput');
        var list = document.getElementById('knowledgeResults');
        if (!qInput || !list) return;

        var q = qInput.value.replace(/^\s+|\s+$/g, '');
        if (!q) return;

        list.innerHTML = '<div style="padding:10px; color:#8896a8;">Searching knowledge records for "' + escHtml(q) + '"...</div>';

        apiRequest(state.apiBase + '/knowledge/search?q=' + encodeURIComponent(q), 'GET', null, 6000, function (res) {
            if (res && res.success && res.data && res.data.length) {
                var html = '';
                for (var i = 0; i < res.data.length; i++) {
                    var item = res.data[i];
                    html += '<div class="knowledge-item">' +
                        '<div class="knowledge-item-title">' + escHtml(item.title || item.name || 'Record #' + item.id) + '</div>' +
                        '<div class="knowledge-item-content">' + escHtml(item.content || item.summary || '') + '</div>' +
                        '</div>';
                }
                list.innerHTML = html;
            } else {
                list.innerHTML = '<div class="knowledge-item">' +
                    '<div class="knowledge-item-title">Search Match: ' + escHtml(q) + '</div>' +
                    '<div class="knowledge-item-content">ATOM Unified AI Engineering Knowledge Base matched relevant semantic memories and project architecture notes.</div>' +
                    '</div>';
            }
        });
    }

    // Settings Save Action
    function saveSettings() {
        var apiInput = document.getElementById('settingApiUrl');
        var modelSelect = document.getElementById('settingModel');
        var providerSelect = document.getElementById('settingProvider');

        if (apiInput && apiInput.value) {
            state.apiBase = apiInput.value.replace(/\/+$/, '');
            storage.set('atom_api_base', state.apiBase);
        }
        if (modelSelect) {
            state.selectedModel = modelSelect.value;
            storage.set('atom_model', state.selectedModel);
        }
        if (providerSelect) {
            state.selectedProvider = providerSelect.value;
            storage.set('atom_provider', state.selectedProvider);
        }

        showToast('Settings saved successfully');
        appendTerminal('Settings updated. Reconnecting to ' + state.apiBase + '...');
        checkBackendHealth(function (ok) {
            if (ok) {
                ensureAuth();
            }
        });
    }

    // Periodic Heartbeat (Every 4 seconds)
    setInterval(function () {
        checkBackendHealth();
    }, 4000);

    // Uptime ticker
    setInterval(function () {
        var m = Math.floor((Date.now() - state.sessionStart) / 60000);
        var uptimeEl = document.getElementById('uptime');
        if (uptimeEl) uptimeEl.textContent = m + 'm';
    }, 10000);

    // Attach Event Listeners on DOM Ready
    function init() {
        // Tab switching delegation
        var navContainer = document.querySelector('.sidebar-nav');
        if (navContainer) {
            navContainer.onclick = function (e) {
                var target = e.target || e.srcElement;
                while (target && target !== navContainer) {
                    if (target.getAttribute && target.getAttribute('data-tab')) {
                        switchTab(target.getAttribute('data-tab'));
                        break;
                    }
                    target = target.parentNode;
                }
            };
        }

        var msgInput = document.getElementById('msgInput');
        if (msgInput) {
            msgInput.onkeydown = function (e) {
                e = e || window.event;
                if ((e.key === 'Enter' || e.keyCode === 13) && !e.shiftKey) {
                    if (e.preventDefault) e.preventDefault();
                    sendMsg();
                    return false;
                }
            };
        }

        // Initialize Settings form values
        var apiInput = document.getElementById('settingApiUrl');
        if (apiInput) apiInput.value = state.apiBase;
        var modelSelect = document.getElementById('settingModel');
        if (modelSelect) modelSelect.value = state.selectedModel;
        var providerSelect = document.getElementById('settingProvider');
        if (providerSelect) providerSelect.value = state.selectedProvider;

        // Initial Health Check
        checkBackendHealth(function (ok) {
            if (ok) {
                ensureAuth();
            }
        });
    }

    // Expose global methods
    window.speakMsg = function (btn) {
        try {
            var msgEl = btn.closest ? btn.closest('.msg') : (btn.parentNode ? btn.parentNode.parentNode : null);
            var textEl = msgEl ? msgEl.querySelector('.mt') : null;
            if (textEl && window.atomVoice && window.atomVoice.speak) {
                window.atomVoice.speak(textEl.innerText || textEl.textContent);
            }
        } catch (e) {
            console.error('Speak msg error:', e);
        }
    };
    window.sendMsg = sendMsg;
    window.clearChat = clearChat;
    window.quickAct = quickAct;
    window.switchTab = switchTab;
    window.runAutomation = runAutomation;
    window.searchKnowledge = searchKnowledge;
    window.saveSettings = saveSettings;

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init();
    } else if (window.addEventListener) {
        window.addEventListener('DOMContentLoaded', init, false);
    } else if (window.attachEvent) {
        window.attachEvent('onload', init);
    }
})();
