/**
 * ATOM AI Assistant - Bilingual Voice Engine (English & Tamil)
 * Features:
 * - English & Tamil Speech Synthesis with DSP pitch/rate tuning
 * - Automatic Bilingual Language Detection (Script & Phoneme analysis)
 * - Hold-to-Talk (Push-to-Talk) with Zero-Persistence Memory Buffers
 * - Synchronized 3D Avatar & 16-Band Audio Spectrum Visualizer
 */

(function () {
    var voiceEngine = {
        languageMode: 'auto', // 'auto', 'en-US', 'ta-IN'
        activeVoiceProfile: 'atom-cyber-neural',
        activeTamilProfile: 'ta-IN-Ben10-Heroic',
        autoSpeak: false,
        isHoldingMic: false,
        recognition: null,
        synth: window.speechSynthesis || null,
        availableVoices: [],
        currentUtterance: null,
        lastDetectedLang: 'en-US'
    };

    // Bilingual Voice Personas & Acoustic DSP Multipliers
    var VOICE_PROFILES = {
        // --- English AI Personas ---
        'atom-cyber-neural': {
            name: 'ATOM Cyber Neural (Unique English AI)',
            lang: 'en-US',
            pitch: 1.06,
            rate: 1.02,
            preferredNames: ['Natural', 'Neural', 'Google US', 'Samantha', 'Zira', 'David', 'English']
        },
        'atom-jarvis-studio': {
            name: 'ATOM Jarvis Studio (Deep Executive Male)',
            lang: 'en-US',
            pitch: 0.85,
            rate: 0.95,
            preferredNames: ['George', 'Guy', 'Daniel', 'David', 'Male', 'Google UK English Male']
        },
        'atom-nova-synth': {
            name: 'ATOM Nova Synthetic (Natural Female AI)',
            lang: 'en-US',
            pitch: 1.14,
            rate: 1.05,
            preferredNames: ['Jenny', 'Sonia', 'Victoria', 'Karen', 'Google UK English Female', 'Female']
        },
        'atom-quantum-core': {
            name: 'ATOM Quantum Core (Resonant Matrix)',
            lang: 'en-US',
            pitch: 0.92,
            rate: 1.10,
            preferredNames: ['Ravi', 'Alex', 'Microsoft', 'Google']
        },

        // --- Tamil (தமிழ்) AI Personas ---
        'ta-IN-Ben10-Heroic': {
            name: 'ATOM Ben 10 Heroic (தமிழ் Heroic Reference)',
            lang: 'ta-IN',
            pitch: 1.18,
            rate: 1.18,
            preferredNames: ['Tamil', 'ta-IN', 'ta', 'Valluvar', 'Google தமிழ்', 'Latha', 'Mukta Malar']
        },
        'ta-IN-Valluvar-Wisdom': {
            name: 'ATOM Valluvar Wisdom (தமிழ் Executive/Calm)',
            lang: 'ta-IN',
            pitch: 0.90,
            rate: 0.95,
            preferredNames: ['Tamil', 'ta-IN', 'ta', 'Valluvar', 'Google தமிழ்']
        },
        'ta-IN-Ananya-Neural': {
            name: 'ATOM Ananya Neural (தமிழ் Conversational Female)',
            lang: 'ta-IN',
            pitch: 1.12,
            rate: 1.05,
            preferredNames: ['Tamil', 'ta-IN', 'ta', 'Google தமிழ்', 'Latha']
        }
    };

    // Load available browser voices
    function loadVoices() {
        if (!voiceEngine.synth) return;
        voiceEngine.availableVoices = voiceEngine.synth.getVoices() || [];
    }

    if (voiceEngine.synth) {
        loadVoices();
        if (voiceEngine.synth.onvoiceschanged !== undefined) {
            voiceEngine.synth.onvoiceschanged = loadVoices;
        }
    }

    // Helper: Detect whether text strictly contains Tamil Unicode characters
    function isTamilText(text) {
        if (!text) return false;
        // Strictly check for Tamil Unicode block: U+0B80 to U+0BFF
        var tamilPattern = /[\u0B80-\u0BFF]/;
        if (tamilPattern.test(text)) return true;

        // Check for specific standalone Tamil greeting words ONLY with word boundaries
        var wholeWordPattern = /\b(vanakkam|nandri|sollunga|vaanga|ponga)\b/i;
        return wholeWordPattern.test(text);
    }

    // Best matching Voice selector with strict language isolation
    function findBestVoice(profileKey, forceLang) {
        var profile = VOICE_PROFILES[profileKey] || VOICE_PROFILES['atom-cyber-neural'];
        var targetLang = forceLang || profile.lang || 'en-US';
        var voices = [];
        if (voiceEngine.synth) {
            voices = voiceEngine.synth.getVoices() || [];
        }
        if (!voices.length) {
            voices = voiceEngine.availableVoices || [];
        }
        if (!voices.length) return null;

        var isTamilTarget = (targetLang === 'ta-IN' || targetLang.indexOf('ta') === 0);

        if (isTamilTarget) {
            // Filter strictly to Tamil voices
            var tamilVoices = voices.filter(function (v) {
                var l = (v.lang || '').toLowerCase();
                var n = (v.name || '').toLowerCase();
                return l.indexOf('ta') === 0 || n.indexOf('tamil') !== -1 || n.indexOf('தமிழ்') !== -1;
            });

            if (tamilVoices.length > 0) {
                for (var i = 0; i < profile.preferredNames.length; i++) {
                    var s = profile.preferredNames[i].toLowerCase();
                    for (var j = 0; j < tamilVoices.length; j++) {
                        if (tamilVoices[j].name.toLowerCase().indexOf(s) !== -1) {
                            return tamilVoices[j];
                        }
                    }
                }
                return tamilVoices[0];
            }
            // If no Tamil voice exists on browser, fallback to any available
            return voices[0] || null;
        } else {
            // STRICTLY ENGLISH VOICES ONLY (Never pick Spanish, Hindi, French, etc.)
            var englishVoices = voices.filter(function (v) {
                var l = (v.lang || '').toLowerCase();
                return l.indexOf('en') === 0 || l.indexOf('en_') === 0 || l.indexOf('en-') === 0;
            });

            if (englishVoices.length > 0) {
                // 1. Try specific preferred voice name keywords on English voices ONLY
                for (var pi = 0; pi < profile.preferredNames.length; pi++) {
                    var search = profile.preferredNames[pi].toLowerCase();
                    if (search === 'english' || search === 'natural' || search === 'neural') continue; // Skip generic tags
                    for (var ei = 0; ei < englishVoices.length; ei++) {
                        var vName = englishVoices[ei].name.toLowerCase();
                        if (vName.indexOf(search) !== -1) {
                            return englishVoices[ei];
                        }
                    }
                }

                // 2. High-quality default English voice ranking
                var topEnPicks = ['david', 'zira', 'mark', 'george', 'guy', 'samantha', 'jenny', 'aria', 'google us', 'google uk', 'hazel', 'catherine'];
                for (var tp = 0; tp < topEnPicks.length; tp++) {
                    for (var ej = 0; ej < englishVoices.length; ej++) {
                        if (englishVoices[ej].name.toLowerCase().indexOf(topEnPicks[tp]) !== -1) {
                            return englishVoices[ej];
                        }
                    }
                }

                // 3. Match en-US / en-GB default or first English voice
                for (var k = 0; k < englishVoices.length; k++) {
                    if (englishVoices[k].default || englishVoices[k].lang === 'en-US' || englishVoices[k].lang === 'en_US') {
                        return englishVoices[k];
                    }
                }

                return englishVoices[0];
            }

            // Fallback: any voice in the system that starts with en
            for (var f = 0; f < voices.length; f++) {
                if ((voices[f].lang || '').toLowerCase().indexOf('en') === 0) {
                    return voices[f];
                }
            }

            return voices[0] || null;
        }
    }

    // Bilingual AI Speech Synthesis Engine
    function speakText(text, onComplete) {
        if (!voiceEngine.synth) {
            if (onComplete) onComplete();
            return;
        }

        stopSpeaking();

        // Ensure browser voices are loaded
        if (!voiceEngine.availableVoices.length) {
            voiceEngine.availableVoices = voiceEngine.synth.getVoices() || [];
        }

        // Clean markdown syntax for natural vocalization
        var clean = text
            .replace(/```[\s\S]*?```/g, 'Code block omitted.')
            .replace(/`([^`]+)`/g, '$1')
            .replace(/[*_#~]/g, '')
            .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
            .replace(/•/g, '')
            .trim();

        if (!clean) {
            if (onComplete) onComplete();
            return;
        }

        // Determine language routing (Auto-Detect or explicit override)
        var detectedIsTamil = false;
        if (voiceEngine.languageMode === 'ta-IN') {
            detectedIsTamil = true;
        } else if (voiceEngine.languageMode === 'en-US') {
            detectedIsTamil = false;
        } else {
            // 'auto' mode: check if text actually contains Tamil characters
            detectedIsTamil = isTamilText(clean);
        }

        voiceEngine.lastDetectedLang = detectedIsTamil ? 'ta-IN' : 'en-US';

        var activeKey = detectedIsTamil ? voiceEngine.activeTamilProfile : voiceEngine.activeVoiceProfile;
        var profile = VOICE_PROFILES[activeKey] || (detectedIsTamil ? VOICE_PROFILES['ta-IN-Ben10-Heroic'] : VOICE_PROFILES['atom-cyber-neural']);
        
        var utter = new SpeechSynthesisUtterance(clean);
        var voice = findBestVoice(activeKey, detectedIsTamil ? 'ta-IN' : 'en-US');
        if (voice) {
            utter.voice = voice;
            utter.lang = voice.lang || (detectedIsTamil ? 'ta-IN' : 'en-US');
        } else {
            utter.lang = detectedIsTamil ? 'ta-IN' : 'en-US';
        }

        utter.pitch = profile.pitch;
        utter.rate = profile.rate;
        utter.volume = 1.0;

        utter.onstart = function () {
            if (window.atom3D && window.atom3D.setSpeaking) {
                window.atom3D.setSpeaking(true);
            }
        };

        utter.onend = function () {
            if (window.atom3D && window.atom3D.setSpeaking) {
                window.atom3D.setSpeaking(false);
            }
            voiceEngine.currentUtterance = null;
            if (onComplete) onComplete();
        };

        utter.onerror = function () {
            if (window.atom3D && window.atom3D.setSpeaking) {
                window.atom3D.setSpeaking(false);
            }
            voiceEngine.currentUtterance = null;
            if (onComplete) onComplete();
        };

        voiceEngine.currentUtterance = utter;
        voiceEngine.synth.speak(utter);
    }

    function stopSpeaking() {
        if (voiceEngine.synth && voiceEngine.synth.speaking) {
            voiceEngine.synth.cancel();
        }
        if (window.atom3D && window.atom3D.setSpeaking) {
            window.atom3D.setSpeaking(false);
        }
        voiceEngine.currentUtterance = null;
    }

    // -------------------------------------------------------------
    // HOLD-TO-TALK (Push-to-Talk) Audio Capture Engine
    // Captured ONLY while user physically holds down the mic button.
    // Zero-continuous recording. Temp buffers discarded on release.
    // -------------------------------------------------------------
    function setupHoldToTalk() {
        var micBtn = document.getElementById('btnHoldToTalk');
        var input = document.getElementById('msgInput');
        if (!micBtn) return;

        var SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition || null;
        var hasPromptedPermission = false;

        // Pre-flight one-time microphone permission request
        function ensureMicPermission(onGranted) {
            try {
                if (window.localStorage && window.localStorage.getItem('atom_mic_permission') === 'granted') {
                    if (onGranted) onGranted();
                    return;
                }
            } catch (e) {}

            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && !hasPromptedPermission) {
                hasPromptedPermission = true;
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (stream) {
                        try {
                            if (window.localStorage) window.localStorage.setItem('atom_mic_permission', 'granted');
                        } catch (err) {}
                        // Immediately stop tracks to respect zero-persistence rule
                        stream.getTracks().forEach(function (track) { track.stop(); });
                        if (onGranted) onGranted();
                    })
                    .catch(function (err) {
                        console.log('Mic permission notice:', err);
                        if (onGranted) onGranted();
                    });
            } else {
                if (onGranted) onGranted();
            }
        }

        function startCapture(e) {
            if (e && e.preventDefault) e.preventDefault();
            if (voiceEngine.isHoldingMic) return;

            // Trigger permission check on first interaction
            ensureMicPermission();

            voiceEngine.isHoldingMic = true;
            micBtn.className = 'btn btn-mic active';
            
            var langLabel = (voiceEngine.languageMode === 'ta-IN' || (voiceEngine.languageMode === 'auto' && voiceEngine.lastDetectedLang === 'ta-IN')) ? 'தமிழ் (Tamil)' : 'English';
            micBtn.innerHTML = '<span class="mic-pulse"></span> Listening [' + langLabel + '] (Release to Send)';

            if (window.atom3D && window.atom3D.setSpeaking) {
                window.atom3D.setSpeaking(true); // Animate visualizer while listening
            }

            if (!SpeechRec) {
                if (window.showToast) window.showToast('Speech recognition not supported in this browser mode');
                return;
            }

            try {
                if (voiceEngine.recognition) {
                    try { voiceEngine.recognition.abort(); } catch (err) {}
                }

                voiceEngine.recognition = new SpeechRec();
                voiceEngine.recognition.continuous = false;
                voiceEngine.recognition.interimResults = true;

                // Set language based on active mode
                var recLang = 'en-US';
                if (voiceEngine.languageMode === 'ta-IN') {
                    recLang = 'ta-IN';
                } else if (voiceEngine.languageMode === 'auto') {
                    recLang = (voiceEngine.lastDetectedLang === 'ta-IN') ? 'ta-IN' : 'en-US';
                }
                voiceEngine.recognition.lang = recLang;

                var finalTranscript = '';

                voiceEngine.recognition.onresult = function (event) {
                    var interim = '';
                    for (var i = event.resultIndex; i < event.results.length; i++) {
                        var transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript;
                        } else {
                            interim += transcript;
                        }
                    }
                    if (input) {
                        input.value = finalTranscript || interim;
                    }
                };

                voiceEngine.recognition.onerror = function (event) {
                    console.log('Speech capture notice:', event.error);
                };

                voiceEngine.recognition.onend = function () {
                    discardTemporaryBuffers();
                };

                voiceEngine.recognition.start();
            } catch (ex) {
                console.error('Mic start error:', ex);
            }
        }

        function stopCapture(e) {
            if (e && e.preventDefault) e.preventDefault();
            if (!voiceEngine.isHoldingMic) return;

            voiceEngine.isHoldingMic = false;
            micBtn.className = 'btn btn-mic';
            micBtn.innerHTML = '🎙️ Hold to Speak';

            if (window.atom3D && window.atom3D.setSpeaking) {
                window.atom3D.setSpeaking(false);
            }

            if (voiceEngine.recognition) {
                try {
                    voiceEngine.recognition.stop();
                } catch (err) {}
            }

            // Immediately send transcribed text and clear buffers
            setTimeout(function () {
                discardTemporaryBuffers();
                if (input && input.value && input.value.trim()) {
                    if (window.sendMsg) {
                        window.sendMsg();
                    }
                }
            }, 300);
        }

        // Temporary audio buffer cleanup
        function discardTemporaryBuffers() {
            if (voiceEngine.recognition) {
                try { voiceEngine.recognition = null; } catch (e) {}
            }
        }

        // Mouse Events
        micBtn.addEventListener('mousedown', startCapture);
        window.addEventListener('mouseup', function () {
            if (voiceEngine.isHoldingMic) stopCapture();
        });

        // Touch Events for Mobile / Tablet
        micBtn.addEventListener('touchstart', startCapture, { passive: false });
        micBtn.addEventListener('touchend', stopCapture, { passive: false });
        micBtn.addEventListener('touchcancel', stopCapture, { passive: false });

        // Optional pre-check on hover / focus
        micBtn.addEventListener('mouseenter', function () {
            ensureMicPermission();
        });
    }

    // Initialize UI elements
    function initVoiceUI() {
        setupHoldToTalk();

        // Voice Language selector in settings
        var langSelect = document.getElementById('settingVoiceLang');
        if (langSelect) {
            langSelect.value = voiceEngine.languageMode;
            langSelect.onchange = function () {
                voiceEngine.languageMode = this.value;
                updateLangBadges();
                var msg = (this.value === 'ta-IN') ? 'குரல் மொழி: தமிழ் (Tamil)' : ((this.value === 'en-US') ? 'Voice Language: English' : 'Voice Language: Auto-Detect (Bilingual)');
                if (window.showToast) window.showToast(msg);
            };
        }

        // Voice Profile selector in settings
        var voiceSelect = document.getElementById('settingVoice');
        if (voiceSelect) {
            voiceSelect.value = voiceEngine.activeVoiceProfile;
            voiceSelect.onchange = function () {
                if (this.value.indexOf('ta-IN') === 0) {
                    voiceEngine.activeTamilProfile = this.value;
                    voiceEngine.languageMode = 'ta-IN';
                    if (langSelect) langSelect.value = 'ta-IN';
                } else {
                    voiceEngine.activeVoiceProfile = this.value;
                }
                updateLangBadges();
                if (window.showToast) window.showToast('Voice Persona set to: ' + this.options[this.selectedIndex].text);
                
                // Sample greeting with selected persona
                if (this.value.indexOf('ta-IN') === 0) {
                    speakText('வணக்கம் விச்சு! ஆட்டம் தமிழ் குரல் தயார்!');
                } else {
                    speakText('Hello Vichu! ATOM voice persona set to ' + this.options[this.selectedIndex].text);
                }
            };
        }

        // Auto-speak toggle in settings
        var autoSpeakToggle = document.getElementById('toggleAutoSpeak');
        if (autoSpeakToggle) {
            autoSpeakToggle.checked = voiceEngine.autoSpeak;
            autoSpeakToggle.onchange = function () {
                voiceEngine.autoSpeak = this.checked;
                if (window.showToast) window.showToast('Auto-Speak ' + (this.checked ? 'Enabled' : 'Disabled'));
            };
        }

        updateLangBadges();
    }

    // Update Quick Language Badge
    function updateLangBadges() {
        var badge = document.getElementById('quickLangBadge');
        if (!badge) return;
        if (voiceEngine.languageMode === 'ta-IN') {
            badge.textContent = 'தமிழ்';
            badge.className = 'lang-pill active-ta';
            badge.title = 'Active Language: Tamil (Click to toggle)';
        } else if (voiceEngine.languageMode === 'en-US') {
            badge.textContent = 'EN';
            badge.className = 'lang-pill active-en';
            badge.title = 'Active Language: English (Click to toggle)';
        } else {
            badge.textContent = 'EN/தமிழ்';
            badge.className = 'lang-pill active-auto';
            badge.title = 'Active Language: Bilingual Auto-Detect (Click to toggle)';
        }
    }

    function toggleQuickLang() {
        if (voiceEngine.languageMode === 'auto') {
            voiceEngine.languageMode = 'ta-IN';
        } else if (voiceEngine.languageMode === 'ta-IN') {
            voiceEngine.languageMode = 'en-US';
        } else {
            voiceEngine.languageMode = 'auto';
        }
        var langSelect = document.getElementById('settingVoiceLang');
        if (langSelect) langSelect.value = voiceEngine.languageMode;
        updateLangBadges();
        var msg = (voiceEngine.languageMode === 'ta-IN') ? 'Language: தமிழ் (Tamil)' : ((voiceEngine.languageMode === 'en-US') ? 'Language: English' : 'Language: Auto-Detect (Bilingual)');
        if (window.showToast) window.showToast(msg);
    }

    // Expose Global Voice Engine API
    window.atomVoice = {
        speak: speakText,
        stop: stopSpeaking,
        setProfile: function (p) {
            if (VOICE_PROFILES[p]) {
                if (p.indexOf('ta-IN') === 0) {
                    voiceEngine.activeTamilProfile = p;
                } else {
                    voiceEngine.activeVoiceProfile = p;
                }
            }
        },
        setLanguage: function (l) {
            if (l === 'auto' || l === 'en-US' || l === 'ta-IN') {
                voiceEngine.languageMode = l;
                updateLangBadges();
            }
        },
        toggleLanguage: toggleQuickLang,
        getLanguage: function () {
            return voiceEngine.languageMode;
        },
        getProfile: function () {
            return voiceEngine.activeVoiceProfile;
        },
        getProfiles: function () {
            return VOICE_PROFILES;
        },
        isAutoSpeak: function () {
            return voiceEngine.autoSpeak;
        },
        isTamil: isTamilText
    };

    window.toggleVoiceLang = toggleQuickLang;

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initVoiceUI();
    } else {
        window.addEventListener('DOMContentLoaded', initVoiceUI);
    }
})();
