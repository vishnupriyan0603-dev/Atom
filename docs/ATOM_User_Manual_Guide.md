# ATOM Personal AI — User Manual & Presentation Guide 📖

> **Advanced AI Knowledge, Memory, Self-Learning, and Human-Guarded Self-Improvement Engine for ATOM**

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
7. [Desktop WPF Assistant Application](#7-desktop-wpf-assistant-application)
8. [CLI Spark Commands Reference](#8-cli-spark-commands-reference)
9. [REST API Endpoints Guide](#9-rest-api-endpoints-guide)
10. [Presentation Deck Slides (PPT Outline)](#10-presentation-deck-slides-ppt-outline)

---

## 1. Overview & Architecture

ATOM is a state-of-the-art hybrid Personal AI Assistant built on PHP CodeIgniter 4 backend services, MySQL/SQLite database persistence, and a modern C# .NET 9 WPF desktop client.

```
       +-------------------------------------------------------------+
       |                  USER INTERACTION LAYERS                   |
       |  Desktop WPF App (C#)  |  Web Admin Dashboard  |  CLI Spark  |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                     REST API CONTROLLERS                    |
       |  /api/knowledge/search | /api/improvement/* | /api/ai/chat  |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                     ATOM BRAIN ENGINES                      |
       |  Knowledge Graph | Self-Improvement Engine | Human Safety  |
       +-------------------------------------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                  STORAGE & MODEL PROVIDERS                  |
       |  MySQL / SQLite DB | Groq / Gemini / OpenAI / Ollama Local LLM|
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

#### C. Run Self-Learning Evaluation via CLI
```powershell
cd backend
php spark atom:self-improve
```

---

## 3. AI Provider Configuration (OpenAI, Gemini, Groq & Local Ollama)

ATOM supports 4 primary LLM providers. Credentials and model selections are configured in the **`.env`** file located in the workspace root.

### Setting up OpenAI
1. Obtain an API key from [platform.openai.com](https://platform.openai.com/).
2. Edit **`.env`**:
```env
LLM_PROVIDER=openai
LLM_MODEL=gpt-4o-mini
LLM_API_KEY=sk-your-openai-api-key-here
LLM_API_URL=https://api.openai.com/v1
```
*(Supported models: `gpt-4o`, `gpt-4o-mini`, `gpt-3.5-turbo`)*

---

### Setting up Gemini
1. Obtain an API key from [Google AI Studio](https://aistudio.google.com/).
2. Edit **`.env`**:
```env
LLM_PROVIDER=gemini
LLM_MODEL=gemini-3.6-flash
LLM_API_KEY=your-gemini-api-key-here
LLM_API_URL=https://generativelanguage.googleapis.com/v1beta
```
*(Supported models: `gemini-3.6-flash`, `gemini-3.5-flash`, `gemini-flash-latest`)*

---

### Setting up Groq
1. Obtain a key from [console.groq.com](https://console.groq.com/).
2. Edit **`.env`**:
```env
LLM_PROVIDER=groq
LLM_MODEL=openai/gpt-oss-120b
LLM_API_KEY=gsk_your-groq-key-here
LLM_API_URL=https://api.groq.com/openai/v1
```
*(Supported models: `openai/gpt-oss-120b`, `llama-3.3-70b-versatile`)*

---

### Setting up Local Ollama / LM Studio
1. Install [Ollama](https://ollama.com/) or LM Studio locally.
2. Pull a local model: `ollama pull llama3.1`
3. Edit **`.env`**:
```env
LLM_PROVIDER=local
LLM_MODEL=llama3.1
LLM_API_KEY=
LLM_API_URL=http://localhost:11434/v1
```

---

### Resolving cURL SSL Certificate Errors
On Windows XAMPP environments where local CA certificates (`cacert.pem`) are unconfigured, cURL may fail with `unable to get local issuer certificate (20)`.

ATOM has automatic SSL error recovery built-in. To explicitly disable SSL verification for local development, ensure `.env` contains:
```env
ATOM_DISABLE_SSL_VERIFY=true
```

---

## 4. PDF Ingestion & Document RAG Training

ATOM converts PDF manuals, documentation, and text files into indexed vector embeddings and Knowledge Graph triples.

### Method A: Desktop WPF Assistant Upload
1. Launch the Desktop App via `start-all.bat` or `dotnet run`.
2. Navigate to the **Knowledge Base Page**.
3. Click **Upload Document** and select your `.pdf`, `.txt`, or `.md` file.
4. ATOM will process, chunk, and index the document automatically.

---

### Method B: Advanced Web UI Uploader
1. Open `advanced-ui/index.html` in your browser:
   `file:///E:/xampp/htdocs/my%20work/Atom/advanced-ui/index.html`
2. Navigate to the **Document RAG Training** tab.
3. Drag & drop your PDF file onto the dropzone.

---

### Method C: REST API Upload via cURL
You can programmatically ingest PDFs using cURL:
```bash
curl -X POST http://localhost:8080/api/knowledge/upload \
  -F "document=@/path/to/manual.pdf" \
  -H "Authorization: Bearer your-jwt-token"
```

---

### PDF Extraction & Knowledge Pipeline Architecture

When a PDF file is uploaded, ATOM executes a 4-step pipeline:

```
[Uploaded PDF] ---> [PdfExtractor.php] ---> [Semantic Chunker (512 tokens)]
                                                  |
                                                  v
[Knowledge Graph Triples] <--- [Embedding Engine] <--- [SQLite Vector DB]
```

1. **Text Extraction**: `src/Knowledge/PdfExtractor.php` parses page text.
2. **Semantic Chunking**: Splits document text into 512-token chunks with 64-token overlap.
3. **Triple Extraction**: Automatically extracts Subject-Predicate-Object facts (e.g. `ATOM -> USES -> CODEIGNITER`).
4. **Vector Storage**: Generates dense embeddings and persists them to `atom_knowledge_chunks` and SQLite.

---

## 5. Core Capabilities & Knowledge System

### Knowledge Graph (Subject-Predicate-Object Triples)
ATOM automatically extracts structured facts into `atom_knowledge_triples`:
- Example: `ATOM -> DEPENDS_ON -> SQLITE_DATABASE`
- Example: `USER_PREFERENCE -> PREFERS -> DARK_MODE`

### Hybrid RAG Search
When asking questions, ATOM queries both **Document Chunks** AND **Knowledge Graph Triples** simultaneously via `GET /api/knowledge/search?q=query` to provide hallucination-free, precise answers.

---

## 6. Self-Learning & Self-Improvement Engine

ATOM continuously improves its prompt configurations and model performance without human code modification:

```
[User Interactions] ---> [Log Metrics] ---> [Flaw Detector]
                                                 |
                                                 v
[Human Approval] <--- [Candidate Benchmarking] <--- [A/B Sandbox]
```

1. **Metric Logging**: Latency, accuracy scores, and user feedback are logged in `atom_evaluations`.
2. **Flaw Detection**: If accuracy drops below threshold or error rates rise, ATOM flags a performance flaw.
3. **Sandbox A/B Experiments**: ATOM runs candidate prompt configurations in an isolated sandbox environment. Candidates must achieve at least a **+5% improvement** to qualify.
4. **Human Authorization Gate**: High-impact promotions are locked in `atom_human_approvals`. A human user must review and approve before candidate configs enter production.

---

## 7. Desktop WPF Assistant Application

The WPF app (`src/PersonalAIAssistant`) features:
- **Chat View**: Multi-turn conversation with LLMs (Groq, Gemini, OpenAI, Ollama).
- **Knowledge Base Page**: Document management and hybrid search inspection.
- **ATOM Safety Gate Page**:
  - 🛡️ View pending human approval requests with **Approve ✅** and **Reject ❌** buttons.
  - 🧪 Monitor live sandbox experiments and A/B score improvements.
  - ⚠️ Inspect detected performance flaws and error rates.

---

## 8. CLI Spark Commands Reference

| Command | Purpose | Example Usage |
| :--- | :--- | :--- |
| `php spark serve` | Start CodeIgniter 4 backend HTTP server | `php spark serve --host 0.0.0.0` |
| `php spark atom:self-improve` | Trigger evaluation cycle & A/B sandbox benchmarking | `php spark atom:self-improve` |
| `php spark atom:approve <id>` | Authorize candidate experiment promotion | `php spark atom:approve 1` |
| `php spark migrate` | Run database schema migrations | `php spark migrate` |

---

## 9. REST API Endpoints Guide

### Knowledge & Search
- `GET /api/knowledge/search?q={query}&limit=5`: Performs hybrid RAG search returning chunks + graph triples.
- `GET /api/knowledge/documents`: List trained documents.
- `POST /api/knowledge/upload`: Upload PDF or text document.

### Self-Improvement & Safety Gate
- `GET /api/improvement/flaws`: Fetch detected performance flaws.
- `GET /api/improvement/experiments`: Fetch active and past A/B experiments.
- `GET /api/improvement/approvals`: Fetch pending human authorization requests.
- `POST /api/improvement/approvals/{id}/approve`: Authorize candidate promotion.
- `POST /api/improvement/approvals/{id}/reject`: Reject candidate promotion.
- `GET /api/improvement/triples`: Query Knowledge Graph triples.

---

## 10. Presentation Deck Slides (PPT Outline)

### Slide 1: Title Slide
- **Title**: ATOM Personal AI Assistant
- **Subtitle**: Self-Learning, Knowledge Graph RAG & Human-Guarded AI Engine
- **Presenter**: VISHNUPRIYAN

### Slide 2: The Problem & Vision
- Conventional AI assistants lose memory across sessions and require manual prompt tuning.
- **Vision**: Build a self-improving assistant with persistent Knowledge Graphs and human-in-the-loop safety controls.

### Slide 3: System Architecture
- Multi-tier stack: C# WPF Client + CodeIgniter 4 PHP Backend + Multi-LLM Providers (Groq, Gemini, OpenAI, Ollama).

### Slide 4: Knowledge Graph & Hybrid RAG
- Dual retrieval: Document Chunk Search + Subject-Predicate-Object Triple Graph Lookup.
- Eliminates AI hallucinations and retains user preferences forever.

### Slide 5: Autonomous Self-Improvement Engine
- Continuous interaction metric logging.
- Flaw detection when accuracy drops.
- Isolated A/B sandbox benchmarking (+5% improvement threshold).

### Slide 6: Human Authorization Safety Gate
- High-impact model promotions require explicit human approval.
- Actions callable via CLI (`php spark atom:approve`), Desktop UI, or REST API.

### Slide 7: Desktop WPF Assistant Experience
- Modern dark/light themed desktop application.
- Dedicated Safety Gate View for reviewing pending approvals and experiment scores.

### Slide 8: Verification & Quality Assurance
- 100% clean test execution (16/16 PHPUnit unit tests passing).
- Clean C# .NET WPF solution build with zero errors.

### Slide 9: Summary & Future Roadmap
- Local DB backups & schema dumps stored in `database/backups/atom_full_backup.sql`.
- Ready for deployment and scaling.

---

## 11. System Prompt & Identity Rules Specification

The system prompt for ATOM is configured in `config/rules/system.md`. It defines the 28 operational directives governing user identity, context isolation, memory retention, error handling, and technical reasoning:

1. **User Identity**: Preferred name **Vichu**, profession **PHP / PHP Full-Stack Developer**.
2. **Context Isolation**: Every new topic is treated independently unless explicitly connected.
3. **Memory Rules**: Temporary information (numbers, code variables, test values) is never automatically stored. Permanent memory requires explicit user requests.
4. **PHP & Laravel Technical Standards**: Recommends modern PHP practices, prepared statements, and framework-native solutions.
5. **Security & Secrets**: Defensive coding, zero hard-coded credentials, and prompt injection protection.
6. **Error Handling**: Displays user-friendly errors while logging technical details privately.
7. **Core Principle**: *Accuracy over confidence, evidence over assumptions, and clean user-facing errors over raw API tracebacks.*

---

## 12. AI Website Builder & Coding Agent Specification

The coding agent rules for ATOM are configured in `config/rules/agent.md`. It establishes 35 operational rules for project-based code generation:

1. **Primary Objective**: Inspects existing codebases, creates clean modular files, and performs direct filesystem operations.
2. **Project Root Isolation & Anti-Traversal**: Operations are strictly scoped inside `PROJECT_ROOT`. Path traversal (`../`) is blocked.
3. **UI Quality & CSS/JS Architecture**: Generates semantic, mobile-responsive HTML5, clean CSS, and dependency-free modular JavaScript.
4. **Database & Parameterized Security**: Parameterizes all SQL queries and avoids hard-coded credentials.
5. **Multi-Turn Memory & Minimal Targeted Modifications**: Preserves existing working code during edits instead of overwriting whole projects.
6. **Verification & Testing**: Runs syntax checks (`php -l`), solution builds (`dotnet build`), and test suites before reporting completion.

---

## 13. Coding Agent Control Prompt & Provider Architecture Specification

The master control prompt for ATOM is configured in `config/rules/control_prompt.md`. It enforces 30 operational directives governing provider consistency, tool execution, and verification:

1. **Provider Consistency — CRITICAL**: All AI generation routes through `AIProviderManager`. The UI-reported provider strictly matches the active request. Zero silent provider switching.
2. **Central AI Provider Manager**: Authoritative orchestrator for chat, file creation, project building, editing, debugging, and code generation.
3. **Provider Error Handling & Fallbacks**: Intercepts raw API exceptions (HTTP 429, quotas) and displays clean user errors while logging details privately.
4. **Command Routing**: Intent router separates conversational chat (`/help`, `/status`) from filesystem operations (`/create`, `/patch`) and project builds.
5. **Real File Operations vs Text Generation**: Never confuses generating code text with writing files. Executes controlled tools (`write_file`, `create_file`, `edit_file`) and verifies file existence and PHP syntax (`php -l`) before reporting success.
6. **Core Principle**: *ATOM thinks with the AI, acts with controlled tools, and verifies the result before claiming success.*
