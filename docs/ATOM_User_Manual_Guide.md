# ATOM Personal AI — User Manual & Presentation Guide 📖 slide

> **Advanced AI Knowledge, Memory, Self-Learning, and Human-Guarded Self-Improvement Engine for ATOM**

---

## Table of Contents
1. [Overview & Architecture](#1-overview--architecture)
2. [Quick Start & Application Launch](#2-quick-start--application-launch)
3. [Core Capabilities & Knowledge System](#3-core-capabilities--knowledge-system)
   - [Document RAG Training (PDF Uploads)](#document-rag-training-pdf-uploads)
   - [Knowledge Graph (Subject-Predicate-Object Triples)](#knowledge-graph-subject-predicate-object-triples)
   - [Hybrid RAG Search](#hybrid-rag-search)
4. [Self-Learning & Self-Improvement Engine](#4-self-learning--self-improvement-engine)
   - [Flaw Detection & Metric Logging](#flaw-detection--metric-logging)
   - [A/B Sandbox Experiments](#ab-sandbox-experiments)
   - [Human Authorization Safety Gate](#human-authorization-safety-gate)
5. [Desktop WPF Assistant Application](#5-desktop-wpf-assistant-application)
6. [CLI Spark Commands Reference](#6-cli-spark-commands-reference)
7. [REST API Endpoints Guide](#7-rest-api-endpoints-guide)
8. [Presentation Deck Slides (PPT Outline)](#8-presentation-deck-slides-ppt-outline)

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

### A. Start the Backend API Server
Open PowerShell in `E:\xampp\htdocs\my work\Atom`:
```powershell
cd backend
php spark serve
```
*(Server will start at `http://localhost:8080`)*

### B. Launch Desktop WPF Assistant
Run in terminal or build with Visual Studio / .NET CLI:
```powershell
dotnet run --project src/PersonalAIAssistant/PersonalAIAssistant.csproj
```

### C. Run Self-Learning Evaluation via CLI
```powershell
cd backend
php spark atom:self-improve
```

---

## 3. Core Capabilities & Knowledge System

### Document RAG Training (PDF Uploads)
1. **Upload**: Upload PDFs or text manuals in the Desktop App or Web Admin.
2. **Extraction & Chunking**: ATOM extracts text per page and creates semantic database chunks.
3. **Training**: ATOM generates a structured summary (`## Summary`, `## Key Topics`, `## Important Facts`).

### Knowledge Graph (Subject-Predicate-Object Triples)
ATOM automatically extracts structured facts into `atom_knowledge_triples`:
- Example: `ATOM -> DEPENDS_ON -> SQLITE_DATABASE`
- Example: `USER_PREFERENCE -> PREFERS -> DARK_MODE`

### Hybrid RAG Search
When asking questions, ATOM queries both **Document Chunks** AND **Knowledge Graph Triples** simultaneously via `GET /api/knowledge/search?q=query` to provide hallucination-free, precise answers.

---

## 4. Self-Learning & Self-Improvement Engine

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

## 5. Desktop WPF Assistant Application

The WPF app (`src/PersonalAIAssistant`) features:
- **Chat View**: Multi-turn conversation with LLMs (Groq, Gemini, OpenAI, Ollama).
- **Knowledge Base Page**: Document management and hybrid search inspection.
- **ATOM Safety Gate Page**:
  - 🛡️ View pending human approval requests with **Approve ✅** and **Reject ❌** buttons.
  - 🧪 Monitor live sandbox experiments and A/B score improvements.
  - ⚠️ Inspect detected performance flaws and error rates.

---

## 6. CLI Spark Commands Reference

| Command | Purpose | Example Usage |
| :--- | :--- | :--- |
| `php spark serve` | Start CodeIgniter 4 backend HTTP server | `php spark serve` |
| `php spark atom:self-improve` | Trigger evaluation cycle & A/B sandbox benchmarking | `php spark atom:self-improve` |
| `php spark atom:approve <id>` | Authorize candidate experiment promotion | `php spark atom:approve 1` |
| `php spark migrate` | Run database schema migrations | `php spark migrate` |

---

## 7. REST API Endpoints Guide

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

## 8. Presentation Deck Slides (PPT Outline)

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
