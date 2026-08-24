# Changelog

## [Unreleased]

### Added
- Added local LLM streaming endpoint (`POST api/chat/{id}/stream`) with Server-Sent Events (SSE) and token-by-token cURL parsing in `AtomLocalModel.php` & `AiChat.php`.
- Added interactive Web Admin Dashboard controls (`frontend/web/admin/`): Provider activation switcher button & real-time RAG category search/filtering in `knowledge.php`.
- Created CodeIgniter 4 database migration (`2026-08-24-000001_AddVectorEmbeddingsToKnowledge.php`) adding `embedding_json`, `token_count`, `vector_hash`, and `chunk_index` columns to knowledge chunk tables.
- Added `cosineSimilarity()` vector similarity calculation method in `KnowledgeSearch.php`.
- Expanded `OwnerProfileManager.php` with `getProfileSummaryPrompt()` system prompt formatter and `updatePreferences()` helper.
- Enhanced Self-Learning Engine & Safety Gate view (`view-learning`) in Web UI (`frontend/web/index.html` & `combined.js`) with A/B model benchmark cards, human authorization approval queue table, and experiment trigger controls.
- Enhanced Flutter Mobile Client (`mobile/lib/main.dart`) with AI model selector dropdown, thinking indicators, personal profile memory cards, and learning progress bars.
- Added comprehensive Security & Auth unit test suite (`backend/tests/unit/AuthTest.php`) verifying password hashing, XSS input escaping, and Bearer token parsing (20/20 tests passing).
- Added chat file attachment functionality with attachment preview badge in web frontend.
- Added animated AI typing indicator during response completion.
- Connected RAG document dropzone to `POST /api/v1/knowledge/upload` endpoint.

### Changed
- Integrated live backend completion API calls (`POST /api/chat/1/preview`) into `frontend/web/js/combined.js`.
- Enhanced code block formatting and syntax highlighting in web frontend.

---

## [1.0.0] - 2026-08-24

### Added
- Initial Atom AI Assistant core architecture release.
