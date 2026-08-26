# ATOM Personal AI — User Manual & Presentation Guide 📖

> **Advanced AI Knowledge, Memory, Self-Learning, Voice Duplex, and Human-Guarded Self-Improvement Engine for ATOM**

---

## Table of Contents
1. [Overview & Architecture](#1-overview--architecture)
2. [Quick Start & Application Launch](#2-quick-start--application-launch)
   - [All-in-One Quick Launcher](#all-in-one-quick-launcher)
   - [Manual Service Launch](#manual-service-launch)
3. [AI Provider Configuration (OpenAI, Gemini, Groq & Local Ollama)](#3-ai-provider-configuration-openai-gemini-groq--local-ollama)
   - [Setting up OpenAI](#setting-up-openai)
   - [Setting up Gemini](#setting-up-gemini)
   - [Setting up Groq](#setting-up-groq)
   - [Setting up Local Ollama / LM Studio](#setting-up-local-ollama--lm-studio)
   - [Resolving cURL SSL Certificate Errors](#resolving-curl-ssl-certificate-errors)
4. [PDF Ingestion & Document RAG Training](#4-pdf-ingestion--document-rag-training)
   - [Method A: Desktop WPF Assistant Upload](#method-a-desktop-wpf-assistant-upload)
   - [Method B: Advanced Web UI Uploader](#method-b-advanced-web-ui-uploader)
   - [Method C: REST API Upload via cURL](#method-c-rest-api-upload-via-curl)
   - [PDF Extraction & Knowledge Pipeline Architecture](#pdf-extraction--knowledge-pipeline-architecture)
5. [Core Capabilities & Knowledge System](#5-core-capabilities--knowledge-system)
   - [Knowledge Graph (Subject-Predicate-Object Triples)](#knowledge-graph-subject-predicate-object-triples)
   - [Hybrid RAG Search](#hybrid-rag-search)
6. [Self-Learning & Self-Improvement Engine](#6-self-learning--self-improvement-engine)
   - [Flaw Detection & Metric Logging](#flaw-detection--metric-logging)
   - [A/B Sandbox Experiments](#ab-sandbox-experiments)
   - [Human Authorization Safety Gate](#human-authorization-safety-gate)
   - [Web Admin Dashboard — 3D Neural Brain Model](#61-web-admin-dashboard--3d-neural-brain-model)
7. [Desktop WPF Assistant Application](#7-desktop-wpf-assistant-application)
8. [CLI Spark Commands Reference](#8-cli-spark-commands-reference)
9. [REST API Endpoints Guide](#9-rest-api-endpoints-guide)
10. [Presentation Deck Slides (PPT Outline)](#10-presentation-deck-slides-ppt-outline)
11. [System Prompt & Identity Rules Specification](#11-system-prompt--identity-rules-specification)
12. [AI Website Builder & Coding Agent Specification](#12-ai-website-builder--coding-agent-specification)
13. [Coding Agent Control Prompt & Provider Architecture](#13-coding-agent-control-prompt--provider-architecture)
14. [Interactive Chat & CLI Slash Commands Reference](#14-interactive-chat--cli-slash-commands-reference)
15. [Real-Time Voice Commands & Audio Duplex Manual](#15-real-time-voice-commands--audio-duplex-manual)
16. [How to Add & Update New Commands (Developer Guide)](#16-how-to-add--update-new-commands-developer-guide)

---

## 1. Overview & Architecture

ATOM is a state-of-the-art hybrid Personal AI Assistant built on PHP CodeIgniter 4 backend services, MySQL/SQLite database persistence, and a modern C# .NET 9 WPF desktop client.

```
       +-------------------------------------------------------------+
       |                  USER INTERACTION LAYERS                    |
       |  Desktop WPF App (C#)  |  Web Admin Dashboard  |  CLI Spark |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                     REST API CONTROLLERS                    |
       |  /api/knowledge/search | /api/improvement/* | /api/v1/voice |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                     ATOM BRAIN ENGINES                      |
       |  Knowledge Graph | Self-Improvement Engine | Voice Duplex   |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                  STORAGE & MODEL PROVIDERS                  |
       |  MySQL / SQLite DB | Groq / Gemini / OpenAI / Ollama Local  |
       +-------------------------------------------------------------+
```

---

## 2. Quick Start & Application Launch

### All-in-One Quick Launcher

To launch all services (Backend API, CLI Evaluation, and Desktop WPF Client) simultaneously in a single command:

#### Option 1: Run `start-all.bat`
```powershell
.\start-all.bat
```

#### Option 2: Run `atom.bat` Menu
```powershell
.\atom.bat
```
Select option **`[A]`** — *Start All Services (Backend + CLI + Desktop UI)*.

---

### Manual Service Launch

#### A. Start the Backend API Server
Open PowerShell in `E:\xampp\htdocs\my work\Atom`:
```powershell
cd backend
php spark serve --host 0.0.0.0
```
*(Backend HTTP API will run at `http://localhost:8080`)*

#### B. Launch Desktop WPF Assistant
```powershell
dotnet run --project src/PersonalAIAssistant/PersonalAIAssistant.csproj
```

---

## 3. AI Provider Configuration (OpenAI, Gemini, Groq & Local Ollama)

Configure `.env` in the repository root:

```ini
# LLM Default Configuration
LLM_PROVIDER=groq
LLM_MODEL=openai/gpt-oss-120b

# API Keys
GROQ_API_KEY=your_groq_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here
OPENAI_API_KEY=your_openai_api_key_here
OLLAMA_BASE_URL=http://127.0.0.1:11434
```

---

## 4. PDF Ingestion & Document RAG Training

ATOM supports multi-format PDF and document parsing with automatic semantic chunking, embedding generation, and Knowledge Graph triple extraction.

---

## 5. Core Capabilities & Knowledge System

- **Knowledge Graph**: Extracts (Subject $\rightarrow$ Predicate $\rightarrow$ Object) semantic triples.
- **Hybrid RAG Search**: Combines full-text cosine vector search with Knowledge Graph relational lookups.

---

## 6. Self-Learning & Self-Improvement Engine

1. **Flaw Detection**: Analyzes execution traces and pinpoints performance bottlenecks.
2. **A/B Sandbox Experiments**: Generates and benchmarks candidate prompt variants against baseline.
3. **Human Safety Gate**: Demands explicit human approval before deploying promoted configurations.

---

## 6.1 Web Admin Dashboard — 3D Neural Brain Model

`frontend/web/admin/index.php` renders a live, procedurally-generated 3D particle brain (no external 3D asset required):
- **Color** reflects the live Brain Health score (same value CLI `/status` shows) — red near 50%, amber mid, green near 100%.
- **Neuron count** scales with the actual `atom_document_chunks` row count.
- **Connecting lines** between nearby neurons are colored on a warm (close/strong) → cool (far/weak) gradient — a visual-only "connection strength" indicator, not tied to conversation sentiment.
- A subset of points pulse as "firing synapses" to suggest active recall.

---

## 7. Desktop WPF Assistant Application

The WPF app (`src/PersonalAIAssistant`) features:
- **Chat View**: Multi-turn conversation with LLMs.
- **Knowledge Base Page**: Document management and hybrid search inspection.
- **ATOM Safety Gate Page**: Review pending approvals and live sandbox experiments.

---

## 8. CLI Spark Commands Reference

| Command | Purpose | Example Usage |
| :--- | :--- | :--- |
| `php spark serve` | Start backend HTTP server | `php spark serve --host 0.0.0.0` |
| `php spark atom:self-improve` | Trigger evaluation cycle & A/B sandbox benchmarking | `php spark atom:self-improve` |
| `php spark atom:approve <id>` | Authorize candidate experiment promotion | `php spark atom:approve 1` |
| `php spark migrate` | Run database schema migrations | `php spark migrate` |

---

## 9. REST API Endpoints Guide

- `GET  /api/v1/voice/voices`: Retrieve voice presets.
- `POST /api/v1/voice/synthesize`: Synthesize text to speech.
- `POST /api/v1/voice/transcribe`: Transcribe spoken audio stream.
- `POST /api/v1/voice/equalizer/apply`: Configure 10-band DSP Equalizer profile.
- `GET  /api/v1/voice/equalizer/presets`: Retrieve curated acoustic presets.

---

## 10. Presentation Deck Slides (PPT Outline)

Outlines core system innovations, multi-modal capabilities, and autonomous workflows for stakeholder presentations.

---

## 11. System Prompt & Identity Rules Specification

The system prompt in `config/rules/system.md` enforces 28 operational directives governing user identity (**Vichu**), strict context isolation, and deterministic code generation.

---

## 12. AI Website Builder & Coding Agent Specification

Configured in `config/rules/agent.md` for project root scoping, parameterized SQL queries, and backward compatibility.

---

## 13. Coding Agent Control Prompt & Provider Architecture

Configured in `config/rules/control_prompt.md` enforcing zero silent provider switches and tool execution validation.

---

## 14. Interactive Chat & CLI Slash Commands Reference

ATOM supports a rich suite of interactive Slash Commands in both the Terminal CLI (`php atom.php`) and Web/Desktop Chat interfaces:

### 🎙️ Audio, Voice & Equalizer Commands
| Command | Description | Example |
| :--- | :--- | :--- |
| `/voice:wake <phrase>` | Test acoustic wake-word detector | `/voice:wake Hey Atom, run diagnostics` |
| `/voice:chunk <text>` | Feed simulated audio chunk into duplex pipeline | `/voice:chunk Hello Atom` |
| `/voice:interrupt` | Trigger immediate barge-in interruption signal | `/voice:interrupt` |
| `/voice:emotion` | Classify acoustic emotion from prosody signals | `/voice:emotion` |
| `/voice:eq [preset]` | Apply 10-band acoustic equalizer preset | `/voice:eq VOCAL_ENHANCE` |
| `/voice:eq:set <band> <gain>` | Adjust specific EQ band gain ($-12\text{ dB} \dots +12\text{ dB}$) | `/voice:eq:set 4 3.5` |
| `/voice:eq:presets` | List all 10 curated DSP equalizer profiles | `/voice:eq:presets` |

### 🛡️ Self-Healing & Incident Response Commands
| Command | Description | Example |
| :--- | :--- | :--- |
| `/incident:classify <msg>` | Classify error telemetry into SEV1–SEV4 severity | `/incident:classify database pool timeout` |
| `/incident:heal <runbook>` | Execute automated self-healing remediation runbook | `/incident:heal drain_connection_pool` |
| `/incident:circuit` | Inspect active 3-state Circuit Breaker status | `/incident:circuit` |
| `/incident:postmortem` | Generate automated RCA incident post-mortem | `/incident:postmortem` |

### 🔍 Semantic Code Search Commands
| Command | Description | Example |
| :--- | :--- | :--- |
| `/search:code <query>` | Natural language semantic repository search | `/search:code authentication token verification` |
| `/search:index <code>` | Segment and vectorize code chunk into in-memory store | `/search:index function verify() { ... }` |
| `/search:embed <text>` | Generate 64-D normalized vector embedding | `/search:embed database query pool` |
| `/search:stats` | View vector index dimension & memory metrics | `/search:stats` |

### 📈 Predictive Analytics & Time-Series Brain
| Command | Description | Example |
| :--- | :--- | :--- |
| `/predict:forecast` | Triple Exponential Holt-Winters forecasting | `/predict:forecast` |
| `/predict:anomaly` | Welford sliding-window Z-score anomaly detector | `/predict:anomaly` |
| `/predict:saturation` | Time-to-exhaustion resource saturation prediction | `/predict:saturation` |
| `/predict:decompose` | Additive Trend-Seasonality-Residual decomposition | `/predict:decompose` |

### 🏢 Enterprise Multi-Tenant RBAC & ABAC
| Command | Description | Example |
| :--- | :--- | :--- |
| `/rbac:check <perm>` | Evaluate active user RBAC/ABAC permission | `/rbac:check documents:write` |
| `/rbac:token:create <scopes>` | Issue cryptographically signed scoped API token | `/rbac:token:create search:read,voice:write` |
| `/rbac:token:validate <jwt>` | Validate token signature, expiry, and scope claims | `/rbac:token:validate eyJhbGciOi...` |
| `/rbac:tenants` | List tenant workspaces and active isolation state | `/rbac:tenants` |

### 🔀 AI Provider Switching Commands
Switch which AI provider answers your messages for the rest of the session — available identically in the Terminal CLI and as the model dropdown on the Web chat page (`frontend/web/chat.php`).

| Command | Description | Example |
| :--- | :--- | :--- |
| `/model` | Show the active session provider and live status (online/offline, key configured) of Groq and Gemini | `/model` |
| `/model <groq\|gemini\|atom>` | Switch the active provider for this session (no angle brackets — type the word itself, e.g. `/model gemini`) | `/model gemini` |
| `/provider set <groq\|gemini\|atom>` | Same as `/model` — full form of the same command | `/provider set atom` |
| `/provider test` | Ping the current primary provider and report latency | `/provider test` |
| `/provider mode <local\|balanced\|collaborative>` | Change the AI collaboration routing mode (separate from provider selection) | `/provider mode balanced` |

Selecting `atom` (Atom Universal Brain) doesn't pin to one vendor — it lets AtomBrain's own routing choose the best available provider per message, the same behavior as the "Atom Universal Brain" option in the Web chat dropdown.

### 🔐 Zero-Knowledge Vault & Differential Sync
| Command | Description | Example |
| :--- | :--- | :--- |
| `/vault:unlock [pass]` | Unlock AES-256-GCM encrypted vault session | `/vault:unlock my_secret_pass` |
| `/vault:store <key> <val>` | Encrypt and store confidential secret record | `/vault:store OPENAI_KEY sk-proj-123` |
| `/vault:get <key>` | Decrypt and retrieve secret payload | `/vault:get OPENAI_KEY` |
| `/vault:merkle` | Inspect cryptographic Merkle audit tree root & leaves | `/vault:merkle` |
| `/vault:sync` | Trigger peer anti-entropy differential sync | `/vault:sync` |

---

## 15. Real-Time Voice Commands & Audio Duplex Manual

ATOM incorporates an autonomous **Full-Duplex Audio & Voice Duplex Brain**:

### 1. Acoustic Wake-Word Engine
- **Supported Wake Triggers**: `"Hey Atom"`, `"Atom"`, `"OK Atom"`.
- **Phonetic Matching**: Utilizes Levenshtein soundex distance matching to reliably trigger even in noisy environments.
- **Wake Gating**: Audio frames prior to wake-word detection are buffered in a rolling 2-second memory window to ensure zero dropped speech phonemes.

### 2. Conversational Turn-Taking & Barge-In
- **Turn States**: `IDLE` $\rightarrow$ `USER_SPEAKING` $\rightarrow$ `THINKING` $\rightarrow$ `AI_SPEAKING`.
- **Barge-In**: If the user begins speaking while ATOM is outputting synthetic voice, an immediate interruption signal cancels the current audio stream and switches back to `USER_SPEAKING`.

### 3. Prosodic Emotion Classification
- Analyzes audio pitch ($F_0$ in Hz), root-mean-square energy (dB), speech rate (words/min), and pitch variance.
- Dynamically classifies user affective state (`NEUTRAL`, `JOYFUL`, `URGENT`, `FRUSTRATED`, `TIRED`) and modulates the TTS voice speed and response empathy accordingly.

### 4. 10-Band Parametric Equalizer DSP
- Real-time biquad audio filtering with 10 presets:
  - `SPEECH_CLARITY`: Boosts $1\text{kHz} - 4\text{kHz}$ intelligibility range while cutting low-end rumble.
  - `VOCAL_ENHANCE`: Smooth presence boost for voice recording and playback.
  - `NOISE_REDUCTION`: High-cut and low-cut filters eliminating ambient fan noise and electrical hum.

---

## 15.1 Phase 103: Autonomous Web Crawler & Recursive Link Extractor

ATOM includes an autonomous multi-hop web crawler and recursive link extractor for deep technical documentation research and knowledge grounding.

### Key Capabilities:
* **Recursive Breadth-First Crawling**: Multi-hop traversal with depth limiting ($1 \le \text{depth} \le 3$) and page quotas ($1 \le \text{pages} \le 20$).
* **DOM Content Extraction**: Parses titles, meta descriptions, headings (`H1`-`H6`), code blocks (`<pre><code>`), and markdown tables.
* **Noise Stripping**: Automatically removes `<script>`, `<style>`, `<noscript>`, `<nav>`, `<footer>`, `<header>`, and cookie banners.
* **SSRF Defense**: Strict blocking of loopback, link-local, and RFC 1918 private IP ranges (`127.0.0.0/8`, `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `169.254.0.0/16`).
* **Secret Redaction**: Integrates with `SecretRedactor` to protect credentials in crawled URLs and page text.
* **Admin Studio**: Dedicated interactive UI at `frontend/web/admin/web_crawler_studio.php` with live page rendering, code snippet inspector, and link graph explorer.

### REST API Endpoints:
* `POST /api/v1/search/crawler/crawl`: Dispatches a multi-hop recursive crawl.
* `POST /api/v1/search/crawler/extract`: Extracts structured content from raw HTML or single page.
* `GET /api/v1/search/crawler/status`: Returns crawler engine health and safety parameters.

---

## 16. How to Add & Update New Commands (Developer Guide)

Follow this 5-step workflow whenever creating or modifying Chat and Voice Commands in ATOM:

### Step 1: Register Command in CLI Router
Open `src/CLI/CommandRouter.php`:
1. Add the command name to the `match ($command)` or `switch ($command)` dispatch block:
   ```php
   case '/custom:action':
       $this->handleCustomAction($command, $args);
       return true;
   ```
2. Implement the private handler method:
   ```php
   private function handleCustomAction(string $command, string $args = ''): void
   {
       $this->ui->highlight("🚀 Executing Custom Action: {$args}");
       // Call underlying subsystem...
       $this->ui->writeLine("  Status: Complete");
       $this->ui->writeLine();
   }
   ```

### Step 2: Implement Backend API Controller Endpoint
Open `backend/app/Controllers/Api/` (or create a new controller) and register the route in `backend/app/Config/Routes.php`:
```php
// backend/app/Config/Routes.php
$routes->post('custom/action', 'Api\Custom::executeAction');
```

### Step 3: Connect Voice Intent Recognition
If the command should be callable via spoken voice:
1. Open `src/Voice/WakeWordDetector.php` or `ConversationalTurnTakingManager.php`.
2. Register the spoken voice phrase intent mapping in the voice parser.

### Step 4: Add Multi-Platform Client Relay Commands
- **Flutter Mobile**: Add API client method in `mobile/lib/services/api_service.dart`.
- **Desktop WPF**: Add `[RelayCommand]` in `AtomAssistant/ViewModels/Pages/ChatPageViewModel.cs`.

### Step 5: Update the Manual Guide
1. Document the command, arguments, and example in `docs/ATOM_User_Manual_Guide.md` under Section 14 or 15.
2. Re-export HTML / PDF documentation if required.
3. Add a PHPUnit test in `backend/tests/unit/` to verify automated test coverage.
