# ⚛️ ATOM — Autonomous Personal AI Development Assistant & Enterprise Operating System

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7%2B-EF4223?style=flat-square&logo=codeigniter&logoColor=white)](https://codeigniter.com/)
[![.NET](https://img.shields.io/badge/.NET-9.0%20WPF-512BD4?style=flat-square&logo=dotnet&logoColor=white)](https://dotnet.microsoft.com/)
[![Flutter](https://img.shields.io/badge/Flutter-3.x%20Mobile-02569B?style=flat-square&logo=flutter&logoColor=white)](https://flutter.dev/)
[![Tests](https://img.shields.io/badge/PHPUnit-1%2C429%20Passing-brightgreen?style=flat-square&logo=checkmarx&logoColor=white)](backend/phpunit.dist.xml)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

**ATOM** is an enterprise-grade, local-first, privacy-minded **Personal AI Development Assistant and Autonomous Operating System**. It unifies a persistent CLI command loop, a reactive PHP CodeIgniter 4 REST API, long-term MySQL/SQLite memory, a local PDF/Document technical knowledge base (RAG), autonomous desktop and system automation (ARC), an autonomous Enterprise DevOps governance suite, and four synchronized client viewers built with strict cross-client parity.

---

## 📑 Table of Contents
1. [Core Capabilities](#1-core-capabilities)
2. [Unified Client Viewers (Cross-Client Parity)](#2-unified-client-viewers-cross-client-parity)
3. [System Architecture](#3-system-architecture)
4. [Autonomous Robotic Controller (ARC)](#4-autonomous-robotic-controller-arc)
5. [Autonomous Enterprise DevOps & SRE Suite](#5-autonomous-enterprise-devops--sre-suite)
6. [Multi-Model Provider Architecture](#6-multi-model-provider-architecture)
7. [Directory Structure](#7-directory-structure)
8. [Getting Started & Installation](#8-getting-started--installation)
9. [Usage & Slash Commands Reference](#9-usage--slash-commands-reference)
10. [Voice Mode & Safety Guarantees](#10-voice-mode--safety-guarantees)
11. [Testing & Quality Verification](#11-testing--quality-verification)
12. [Documentation & Strict Standards](#12-documentation--strict-standards)

---

## 1. Core Capabilities

- **Autonomous Coding & Patching**: Safely inspects, navigates, and modifies codebases with dry-run diff previews, pre-write automated backups, AST security linting, and instant rollbacks on syntax failure.
- **Hybrid RAG Knowledge Base**: High-performance semantic chunking, cosine similarity, TF-IDF vector retrieval, and MySQL FULLTEXT search over ingested technical PDFs and documentation.
- **Long-Term Memory & Self-Learning**: Persistent MySQL/SQLite storage of developer preferences, architectural rules, past bugs, solution playbooks, and continuous topic knowledge graphs.
- **Autonomous Desktop & System Automation (ARC)**: Deterministic, visible on-screen window management, OS process control, port diagnostics, and safe system execution guarded by multi-level permission gates.
- **14 Autonomous DevOps Governors**: Automated end-to-end GitOps deployments, IaC blueprint generation, multi-cloud orchestration, zero-downtime database migrations, canary telemetry auditing, and self-healing runtime recovery.

---

## 2. Unified Client Viewers (Cross-Client Parity)

ATOM provides **four synchronized client viewers**. All clients share common business logic from `src/` and consume the unified `/api/v1/*` REST API:

| Client Viewer | Platform / Technology | Primary Location | Key Features |
| :--- | :--- | :--- | :--- |
| **Terminal CLI** | PHP 8.2+ Console | `atom.php` | Low-latency REPL loop, `/` slash commands, real-time streaming, interactive debugging. |
| **Web Dashboard & Admin Studio** | Modern PHP / HTML5 / Three.js | `frontend/web/` | 3D Neural Brain visualization (72 synapses), RAG explorer, memory manager, mobile APK hub. |
| **Desktop Assistant** | C# .NET 9 WPF & HTA Shell | `src/PersonalAIAssistant/`<br>`frontend/desktop/` | Fluent Dark/Cyberpunk theme, on-screen ARC automation, real-time push-to-talk voice HUD. |
| **Mobile Application** | Flutter 3.x & Android native | `mobile/` | Android APK build, mobile PWA companion (`frontend/web/mobile_app.php`), responsive chat. |

---

## 3. System Architecture

```text
                                  USER
                                   │
      ┌────────────────────────────┼───────────────────────────┐
      ▼                            ▼                           ▼
Terminal CLI                Web Admin & Studio          Desktop WPF / HTA
(atom.php)                  (frontend/web/)            (src/PersonalAIAssistant/)
      │                            │                           │
      └────────────────────────────┼───────────────────────────┘
                                   │
                                   ▼
                    CodeIgniter 4 REST API Layer
                            (/api/v1/*)
                                   │
                                   ▼
                         ATOM BRAIN CONTROLLER
                      (src/Brain/AtomBrain.php)
                                   │
          ┌────────────────────────┼────────────────────────┐
          │                        │                        │
          ▼                        ▼                        ▼
   Knowledge & RAG          Memory & History         Context Builder
 (PDF / Vector Stores)     (MySQL / SQLite)       (Workspace & Profiles)
          │                        │                        │
          └────────────────────────┼────────────────────────┘
                                   │
                                   ▼
                       UNIVERSAL MODEL GATEWAY
             (Gemini · Groq · OpenAI · Anthropic · Ollama)
                                   │
                                   ▼
                         RESPONSE PARSER & TOOLS
                                   │
          ┌────────────────────────┴────────────────────────┐
          ▼                                                 ▼
   WORKSPACE GUARD                                 ARC & DEVOPS SUITE
(File Sandboxing / Patching)                     (System Automation & SRE)
```

---

## 4. Autonomous Robotic Controller (ARC)

The **ARC (Autonomous Robotic Controller)** engine provides deterministic, policy-governed automation for local development machines:

- **ARC ONE (`ArcOneGitController`)**: Autonomous Git workflow management — branch creation, status diffing, atomic staging, semantic commits, rollback execution, and merge resolution.
- **ARC TWO (`ArcTwoDesktopController`)**: Deterministic desktop automation. Launches and interacts with native Windows desktop applications (Notepad, VS Code, Google Chrome, media players) with strict **Foreground Visible Execution** via Win32 `ShowWindowAsync` and `SetForegroundWindow`.
- **ARC System Dev (`ArcSystemDevController`)**: Low-level OS process management, listening socket analysis, developer toolchain discovery, and service control.
- **System Permission Gate (`SystemPermissionGate`)**: Fine-grained risk classification (Levels 0–3) enforcing human-in-the-loop authorization before executing privileged system actions.

---

## 5. Autonomous Enterprise DevOps & SRE Suite

ATOM includes **14 autonomous governance engines** providing complete Site Reliability Engineering capabilities:

1. **`GitOpsDeploymentEngine`**: Declarative state reconciliation, commit-triggered pipelines, and drift detection.
2. **`IaCBlueprintGenerator`**: Automated generation of valid Terraform blueprints, Docker Compose topologies, and Kubernetes manifests.
3. **`MultiCloudDeployOrchestrator`**: Unified multi-cloud deployment abstraction spanning AWS, GCP, and Azure.
4. **`ZeroDowntimeSchemaMigrator`**: Online schema migration runner utilizing shadow tables, triggers, and zero downtime.
5. **`ChaosFaultInjectionEngine`**: Controlled chaos engineering injecting network latency, packet loss, and CPU load spikes.
6. **`SyntheticLoadTestingEngine`**: Automated traffic generation, step-load concurrency ramps, and latency SLA auditing.
7. **`CanaryTelemetryAnalyzer`**: Metric-driven canary evaluations with automated baseline comparison and instant rollback gates.
8. **`SelfHealingRuntimeGovernor`**: Autonomous crash-loop monitoring, health probe evaluations, and self-healing runbooks.
9. **`ContinuousBenchmarkGovernor`**: Performance regression prevention tracking latency, memory footprints, and CPU usage.
10. **`DatabaseQueryOptimizerEngine`**: Automated slow-query log capture, `EXPLAIN` query analysis, and index synthesis.
11. **`ServiceMeshRoutingGovernor`**: Intelligent traffic shifting, circuit breakers, rate limits, and weighted routing.
12. **`ContainerSecurityScanner`**: Static vulnerability scanning for Dockerfiles and container image configurations.
13. **`AutomatedSecretRotator`**: Zero-downtime rotation and validation of database credentials, API tokens, and JWT keys.
14. **`TlsCertificateGovernor`**: Continuous TLS/SSL certificate lifecycle tracking, SAN verification, and expiration alerts.

---

## 6. Multi-Model Provider Architecture

ATOM treats the root `.env` as the single source of truth (`Atom\Config\Config::load()`), dynamically consumed by CLI, Web, Desktop, and Backend REST services:

```ini
# Core LLM Provider (gemini | groq | openai | anthropic | ollama)
LLM_PROVIDER=gemini
LLM_MODEL=gemini-2.5-flash
LLM_API_KEY=your-api-key-here
LLM_API_URL=https://generativelanguage.googleapis.com/v1beta

# Alternative Provider Keys
# GROQ_API_KEY=gsk_...
# OPENAI_API_KEY=sk-...
# ANTHROPIC_API_KEY=sk-ant-...

# Database Persistence
DB_HOST=localhost
DB_NAME=atom_assistant
DB_USER=root
DB_PASSWORD=
DB_PORT=3306
```

---

## 7. Directory Structure

```text
Atom/
├── atom.php                       # Interactive CLI entrypoint (PSR-4 autoloader)
├── PersonalAIAssistant.sln        # Desktop WPF Visual Studio Solution
├── backend/                       # CodeIgniter 4 REST API & Service Layer
│   ├── app/
│   │   ├── Config/                # Routes, Filters, and Environment configurations
│   │   ├── Controllers/Api/       # REST API endpoints (Chat, Auth, Health, RAG)
│   │   └── Services/              # Backend services (AiChatService, AuthService)
│   ├── tests/unit/                # 1,429 automated PHPUnit tests
│   └── composer.json              # Backend dependencies
├── frontend/
│   ├── web/                       # Web Dashboard & Admin Studio
│   │   ├── admin/                 # Admin Studio & 3D Neural Visualizer
│   │   ├── mobile_app.php         # Mobile PWA web application
│   │   └── mobile_download.php    # Standalone APK download landing page
│   └── desktop/                   # Desktop HTA Client & 3D Audio Scripts
├── mobile/                        # Flutter Mobile Application
│   ├── android/                   # Native Android wrapper & build config
│   └── lib/                       # Flutter screens and API service layer
├── src/                           # Shared Core AI & System Engines
│   ├── Arc/                       # ARC One (Git), Two (Desktop), System Dev, Master
│   ├── Brain/                     # Neural Brain, Context Builder, Speech Synthesizer
│   ├── CLI/                       # Terminal UI, Application Loop, Command Router
│   ├── DevOps/                    # 14 Autonomous Enterprise DevOps Governors
│   ├── Knowledge/                 # PDF extraction, chunking, and semantic RAG
│   ├── LLM/                       # Gemini, Groq, OpenAI, Anthropic Provider adapters
│   ├── Memory/                    # Long-term memory manager and graph store
│   ├── Security/                  # WorkspaceGuard, SecretRedactor, SystemPermissionGate
│   └── Tools/                     # File tools, ARC tools, DevOps tools
├── docs/                          # Comprehensive Architectural & User Documentation
│   ├── ATOM_Phases_Architecture_Roadmap.md # Master Roadmap (Phases 1-110+)
│   ├── ATOM_User_Manual_Guide.md           # Complete User & Operator Manual
│   └── svgAtom_strict_rules.md             # Core Architectural Invariants & Rules
└── storage/                       # Backups, ingested RAG documents, and audit logs
```

---

## 8. Getting Started & Installation

### Prerequisites
- **PHP**: 8.2 or higher (extensions: `intl`, `sqlite3`, `pdo_sqlite`, `curl`, `mbstring`, `mysqli`)
- **Composer**: 2.x
- **Database**: MySQL 8.x / MariaDB or SQLite
- **Desktop Client**: Windows 10/11 with .NET 9.0 SDK installed
- **Mobile Client (Optional)**: Flutter 3.x and Android SDK

### Setup Steps

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/vishnupriyan0603-dev/Atom.git
   cd Atom
   ```

2. **Install Backend Dependencies**:
   ```bash
   cd backend
   composer install
   cd ..
   ```

3. **Configure Environment**:
   Copy `.env.example` to `.env` and fill in your database credentials and preferred AI provider API key.

4. **Initialize Database**:
   Import `database/schema.sql` into your MySQL instance or run SQLite migrations:
   ```bash
   mysql -u root -p atom_assistant < database/schema.sql
   ```

5. **Launch ATOM Services**:
   - **All-in-One Launcher**: Run `start-all.bat` or `atom.bat` (Select `[A]`).
   - **CLI Mode**: Run `php atom.php`.
   - **Backend API**: Run `cd backend && php spark serve --port 8080`.
   - **Desktop WPF App**: Open `PersonalAIAssistant.sln` in Visual Studio or run `dotnet run --project src/PersonalAIAssistant`.

---

## 9. Usage & Slash Commands Reference

Inside the interactive CLI (`php atom.php`), use natural language prompts or dedicated slash commands:

### Workspace & Project Navigation
- `/status` — Display active workspace path, indexed file counts, and database health.
- `/project` — Render an interactive directory tree of the project.
- `/files` — List indexed source files excluding vendor/cache directories.
- `/read <file>` — Safely read file content with automatic credential redacting.
- `/search <query>` — Search codebase file paths and source lines.
- `/php-lint <file>` — Run compile-time validation syntax check (`php -l`).
- `/create <file>; <content>` — Create a new file within the sandbox safely.
- `/patch <file>; <find>; <replace>` — Apply incremental edits with automated backup and rollback on lint errors.

### Memory & Learning Knowledge
- `/memory` — Inspect stored developer preferences, architectural rules, and solutions.
- `/knowledge import <path>` — Ingest a technical document/PDF into the RAG vector store.
- `/knowledge ask <query>` — Query matching citations, formulas, and document sections.
- `/learning` — View overall knowledge absorption progress and topic matrix.
- `/provider mode <local|balanced|collaborative>` — Change active AI provider routing mode.

### ARC & DevOps Control
- `/arc status` — Inspect active desktop and git automation controllers.
- `/arc desktop open <app>` — Open application visibly in the foreground.
- `/devops status` — Audit health of the 14 autonomous SRE governors.
- `/devops check` — Run real-time security, benchmark, and drift checks.

---

## 10. Voice Mode & Safety Guarantees

### Hold-to-Talk (Push-to-Talk) Invariant
Voice mode strictly implements **Hold-to-Talk (Push-to-Talk)**. Audio is only captured while the user actively holds down the physical or UI microphone trigger. Releasing it immediately terminates recording and purges temporary audio buffers from memory. Continuous or always-on listening is strictly prohibited.

### Foreground Visible Display Rule
All desktop applications, browsers, documents, and media players triggered by ATOM launch **strictly into the visible foreground** (`ShowWindowAsync` + `SetForegroundWindow` / `SwitchToThisWindow`). Silent or hidden background execution of UI applications is prohibited.

---

## 11. Testing & Quality Verification

ATOM maintains an exhaustive automated test suite to ensure complete backward compatibility and architectural stability:

### 1. Backend PHPUnit Suite (1,429 Tests)
Run all backend unit and integration tests:
```powershell
cd backend
php -d extension=intl -d extension=sqlite3 -d extension=pdo_sqlite vendor/bin/phpunit
```
> **Current Status**: **1,429 tests passing**, 5,472 assertions, **0 failures**.

### 2. Desktop WPF Client Build
Verify the desktop assistant solution compiles without errors:
```powershell
dotnet build PersonalAIAssistant.sln
```
> **Current Status**: **Build succeeded (0 Errors)**.

---

## 12. Documentation & Strict Standards

Detailed architectural guides and operating manuals are located in `docs/`:
- [`ATOM_User_Manual_Guide.md`](docs/ATOM_User_Manual_Guide.md) — Comprehensive user manual, presentation guide, and command catalog.
- [`ATOM_Phases_Architecture_Roadmap.md`](docs/ATOM_Phases_Architecture_Roadmap.md) — Complete 110+ phase development roadmap.
- [`svgAtom_strict_rules.md`](docs/svgAtom_strict_rules.md) — Core strict rules, invariants, and architectural guidelines.
- [`AGENTS.md`](AGENTS.md) — Autonomous agent workflows and parity conventions.

---

## 📄 License
This project is open-source software licensed under the [MIT License](LICENSE).
