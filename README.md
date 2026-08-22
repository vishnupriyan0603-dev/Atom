# ATOM — Personal AI PHP Development Assistant

ATOM is a local-first, privacy-minded Personal AI Development Assistant designed to run completely on your local machine. It combines a persistent CLI command loop, long-term MySQL database memory, local PDF technical knowledge base (RAG), and secure sandbox file editing tools to aid PHP developers in building and debugging Core PHP websites, CRMs, ERPs, and billing systems.

---

## 1. Core Architecture
ATOM is built around six major components coordinating through a single entry point:

```text
                     USER
                       │
                       ▼
                  TERMINAL CLI
                       │
                       ▼
                 INTENT ROUTER
                       │
                       ▼
                  ATOM BRAIN
                       │
          ┌────────────┼────────────┐
          │            │            │
       PROJECT       MEMORY      KNOWLEDGE
    (Scanner/Search) (MySQL DB)  (PDF RAG)
          │            │            │
          └────────────┼────────────┘
                       │
                       ▼
                CONTEXT BUILDER
                       │
                       ▼
                  LLM PROVIDER
                       │
                       ▼
               RESPONSE PARSER
                       │
                 if action needed
                       │
                       ▼
               WORKSPACE GUARD
                       │
                       ▼
                CONTROLLED TOOL
```

---

## 2. Directory Structure

```text
atom/
│
├── atom.php                 # Dynamic CLI entry script (includes Custom PSR-4 Autoloader)
│
├── bin/
│   ├── atom.bat             # Windows CLI launcher script
│   └── atom                 # Unix bash CLI launcher script
│
├── config/
│   └── config.php           # Dynamic .env configuration loader
│
├── database/
│   └── schema.sql           # Database schema tables setup script
│
├── src/
│   ├── Brain/
│   │   ├── AtomBrain.php       # Orchestrates loop routing and offline fallbacks
│   │   ├── ContextBuilder.php  # Packages prompt messages with workspace context
│   │   ├── IntentDetector.php  # Analyzes user prompt categories
│   │   └── ResponseParser.php  # Extracts JSON tool requests from LLM text
│   │
│   ├── CLI/
│   │   ├── Application.php     # Dynamic workspace directory detector and shell loop
│   │   └── CommandRouter.php   # Routes commands and natural conversation history
│   │
│   ├── Database/
│   │   └── Connection.php      # Soft-fallback PDO MySQL connection manager
│   │
│   ├── LLM/
│   │   ├── LLMInterface.php    # standard provider contract
│   │   ├── OpenAIProvider.php  # cURL OpenAI/Compatible (Ollama, LM Studio) provider
│   │   └── GeminiProvider.php  # Direct Google Gemini API connector
│   │
│   ├── Project/
│   │   ├── ProjectScanner.php  # Indexes files, ignoring git/vendor/compiler folders
│   │   └── CodeSearch.php      # High-speed codebase grep matching utility
│   │
│   ├── Knowledge/
│   │   ├── PdfExtractor.php    # Decodes and unzips PDF text content streams
│   │   ├── Chunker.php         # Breaks text into sentence-aligned overlapping chunks
│   │   ├── DocumentImporter.php# Copies original PDFs and writes text segments to database
│   │   └── KnowledgeSearch.php # Performs MySQL FULLTEXT matching (with LIKE fallback)
│   │
│   ├── Memory/
│   │   └── MemoryManager.php   # Handles database logs, user preferences, and solutions
│   │
│   ├── Tools/
│   │   ├── ToolInterface.php   # Unified tool interface contract
│   │   ├── ReadFileTool.php    # Safely reads file text inside sandbox
│   │   ├── SearchCodeTool.php  # Scans filenames and contents for search terms
│   │   ├── PhpLintTool.php     # Compiles target PHP script with php -l
│   │   ├── CreateFileTool.php  # Creates a new file securely
│   │   ├── PatchFileTool.php   # Applies search-replace diffs with rollbacks on lint failures
│   │   └── ToolManager.php     # Registry and dispatcher of active tools
│   │
│   └── Security/
│       ├── WorkspaceGuard.php  # Restricts path operations to active workspace
│       ├── FilePolicy.php      # Allowed file extension filter rules
│       └── SecretRedactor.php  # Regex masking for API keys, passwords, and tokens
│
├── storage/
│   ├── backups/             # Pre-write backup copies
│   ├── knowledge/           # Ingested original PDF library
│   ├── cache/               # Search indexes and temporary cache
│   └── logs/                # Audit logs (redacted)
│
└── .env                     # Target configurations (API keys & DB settings)
```

---

## 3. Setup & Configuration

1. **Environment Config**: Create a `.env` file in the root directory following this template:
   ```ini
    # LLM Provider Configuration (openai or gemini)
    LLM_PROVIDER=openai
    LLM_MODEL=gpt-4o-mini
    LLM_API_KEY=your-api-key-here
    LLM_API_URL=https://api.openai.com/v1
    
    # Or for Google Gemini configuration:
    # LLM_PROVIDER=gemini
    # LLM_MODEL=gemini-flash-latest
    # LLM_API_KEY=AQ.Ab8RN...
    # LLM_API_URL=https://generativelanguage.googleapis.com/v1beta

    # MySQL Database Configuration
    DB_HOST=localhost
    DB_NAME=atom_assistant
    DB_USER=root
    DB_PASSWORD=
    DB_PORT=3306
    ```
2. **Initialize Schema**: Import `database/schema.sql` to register the MySQL tables.

---

## 4. Usage Command Reference

Start the interactive console loop:
```bash
php atom.php
```

### Slash Commands
* `/help` — Display help instructions.
* `/status` — Check active workspace path, file counts, and MySQL connectivity state.
* `/project` — Render a visual tree of the current project directory.
* `/files` — List scanned non-ignored source files.
* `/read <file>` — Read file content safely (masks credentials automatically).
* `/search <query>` — Search codebase filenames and lines.
* `/php-lint <file>` — Run compile validation checks using `php -l`.
* `/create <file>; <content>` — Create a new file securely.
* `/patch <file>; <search>; <replace>` — Apply incremental edits with pre-write backups, dry-run diff review, and lint check rollbacks.
* `/memory` — Print long-term user preferences and settings.
* `/history` — List persistent conversation transcripts.
* `/knowledge import <path>` — Ingest a PDF guide into the library database.
* `/knowledge ask <query>` — Query matching quotes and page citations.
* `/learning` — Print overall knowledge status, progress bars, and topic list.
* `/learning gaps` — Display priority areas needing study/knowledge.
* `/learning topic <name>` — Inspect specific details and references for a topic.
* `/learning history` — List recent topic modifications and user corrections.
* `/collaboration` — Show active Gemini role parameters and request resolution ratios.
* `/provider mode <local|balanced|collaborative>` — Change active AI provider routing mode.
* `/clear` — Clear the terminal display.
* `/exit` — Quit the shell loop.

### Conversational Memory Keywords
ATOM automatically processes memory requests inside standard conversation entries:
* `remember that <preference>` — Record long-term guidelines (e.g. `remember that I prefer prepared statements`).
* `forget memory <id>` — Delete a stored memory row by its database ID.
* `remember solution: problem=<summary>; cause=<reason>; fix=<solution>` — Save solved coding issues.

---

## 5. Web Frontend & Mobile Client
ATOM includes two modern visual client layers communicating with the REST API:

1. **Web Frontend** (`frontend/web/`): A responsive developer operating dashboard, supporting conversations, RAG documents explorer, memory modifier panels, and collaboration control charts.
2. **Flutter Mobile Application** (`mobile/`): Feature-first mobile shell allowing chat inputs, PDF indexing status checks, and home stats navigation.

---

## 6. Shared REST API (`/api/v1/`)
Secure token endpoints mapped in CodeIgniter 4 backend:
* `POST /api/v1/auth/login`
* `GET  /api/v1/user/profile`
* `POST /api/v1/chat`
* `GET  /api/v1/memory`
* `GET  /api/v1/knowledge`
* `POST /api/v1/knowledge/upload`
* `GET  /api/v1/learning`
* `GET  /api/v1/workspace`

