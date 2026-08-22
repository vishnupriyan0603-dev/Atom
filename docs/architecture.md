# Atom AI - System Architecture

This document describes the high-level architecture of Atom AI.

## Architecture Flow

```
                 USER — VICHU
                       │
                       ▼
                 ATOM CLI / UI
                       │
                       ▼
                 Intent Router
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
       Memory       Knowledge      Tools
          │            │            │
          └────────────┼────────────┘
                       ▼
                Context Builder
                       │
                       ▼
               AI Provider Layer
                       │
                ┌──────┴──────┐
                ▼             ▼
             Gemini       Future Models
                              │
                        Local LLM / APIs
                       │
                       ▼
                    Response
                       │
                       ▼
                Memory Manager
```

## Architectural Components

1. **Atom CLI / UI**: The terminal command-line interface (`atom.php`) that hosts the user session.
2. **Intent Router**: Identifies whether the input is a command, a direct memory/ RAG instruction, or general conversation.
3. **Context Builder**: Combines system instructions, the personal profile, current project stats, long term memories, session details, and RAG knowledge chunks.
4. **AI Provider Layer**: Abstract interface (`LLMInterface` & `ModelInterface`) separating Atom from any single provider, making it easy to swap Gemini for other local/private models later.
5. **Memory Manager**: Controls the multi-layered memory retrieval and storage process.
