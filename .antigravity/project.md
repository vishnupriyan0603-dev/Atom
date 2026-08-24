# Project Overview

- **Project Name**: Atom AI Assistant Platform
- **Description**: Full-stack AI assistant platform with PHP 8.3/CodeIgniter 4 backend API, .NET 9 WPF desktop clients, Flutter mobile app, and Web Control Panel. Features chat with cloud & local offline LLM providers, SSE streaming, RAG vector embeddings, Knowledge Graph SPO triple editor, and Self-Learning A/B Sandbox.
- **Framework**: CodeIgniter 4.7+ (PHP 8.3+)
- **PHP Version**: 8.3+
- **Database**: MySQL/MariaDB (backend), SQLite (desktop clients & local storage)
- **Environment**: Development
- **Repository**: (local)
- **Server**: Apache / built-in PHP server
- **Domain**: localhost

## Architecture

- **Backend API**: RESTful API at `backend/public/` with JWT auth, CORS, SSE streaming (`POST api/chat/{id}/stream`), and service layer pattern.
- **Desktop v1**: .NET 9 WPF (`src/PersonalAIAssistant/`) with MVVM, DI, SQLite.
- **Desktop v2**: .NET 9 WPF (`AtomAssistant/`) with SQLite, sync, knowledge base.
- **Desktop Shell**: HTA (`frontend/desktop/Atom.hta`) lightweight browser-based UI.
- **Web Frontend**: Dark-themed HTML5/CSS3/JS Web UI (`frontend/web/`) connected to CodeIgniter REST API, featuring live completion with typing indicators, chat file attachments (`.txt`, `.md`, `.code`), Document RAG drag-and-drop ingestion, Knowledge Graph SPO triple editor, and A/B Sandbox human approval gate.
- **Web Admin Dashboard**: Control Panel (`frontend/web/admin/`) with active provider quick-switcher, real-time RAG category search, and observability metrics.
- **Flutter Mobile Client**: Cross-platform Flutter app (`mobile/lib/main.dart`) with model selector, memory profile viewer, and learning progress cards.

## AI Providers

- **Cloud**: OpenAI (GPT-4.1, GPT-4o), Anthropic (Claude 3.5), Google (Gemini 3.6 Flash), DeepSeek-R1, Groq (OSS-120B), Mistral, OpenRouter, Azure OpenAI.
- **Local Offline**: Ollama, LM Studio, GPT4All, llama.cpp with SSE token streaming.

## Database & Vector RAG

- `atom_assistant` (MySQL) - backend: chats, messages, users, ai_models, prompts, notes, settings, knowledge_items (with `embedding_json`, `token_count`, `vector_hash`, `chunk_index`), file_records, plugins.
- `assistant.db` (SQLite) - desktop v1: Chats, ChatMessages, Settings, Prompts.
- `atomassistant.db` (SQLite) - desktop v2: Chats, Messages, UserSettings, AiModels, Prompts, PluginInfo, KnowledgeItems, FileRecords, Notes.
