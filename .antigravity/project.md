# Project Overview

- **Project Name**: Atom AI Assistant
- **Description**: Full-stack AI assistant platform with PHP/CodeIgniter backend API, .NET WPF desktop clients, and HTA desktop shell. Features chat with multiple AI providers, knowledge base, file management, plugin system, and sync capabilities.
- **Framework**: CodeIgniter 4.7+ (PHP 8.2+)
- **PHP Version**: 8.2+
- **Database**: MySQL/MariaDB (backend), SQLite (desktop clients)
- **Environment**: Development
- **Repository**: (local)
- **Server**: Apache / built-in PHP server
- **Domain**: localhost

## Architecture

- **Backend API**: RESTful API at `backend/public/` with JWT auth, CORS, and service layer pattern
- **Desktop v1**: .NET 9 WPF (`src/PersonalAIAssistant/`) with MVVM, DI, SQLite
- **Desktop v2**: .NET 9 WPF (`AtomAssistant/`) with SQLite, sync, knowledge base
- **Desktop Shell**: HTA (`frontend/desktop/Atom.hta`) lightweight browser-based UI
- **Web Frontend**: HTML5/CSS3/JS dark-themed web client (`frontend/web/`) connected to CodeIgniter REST API, featuring live completion with typing indicators, chat file attachments (`.txt`, `.md`, `.code`), Document RAG drag-and-drop ingestion, and Knowledge Graph SPO visualizer.

## AI Providers

- **Cloud**: OpenAI (GPT-4.1, GPT-4o), Anthropic (Claude), Google (Gemini), DeepSeek, Groq, Mistral, OpenRouter, Azure OpenAI
- **Local**: Ollama, LM Studio, GPT4All, llama.cpp

## Database

- `atom_assistant` (MySQL) - backend: chats, messages, users, ai_models, prompts, notes, settings, knowledge_items, file_records, plugins
- `assistant.db` (SQLite) - desktop v1: Chats, ChatMessages, Settings, Prompts
- `atomassistant.db` (SQLite) - desktop v2: Chats, Messages, UserSettings, AiModels, Prompts, PluginInfo, KnowledgeItems, FileRecords, Notes
