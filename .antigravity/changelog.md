# Changelog

## [Unreleased]

### Security
- Added server-side Admin page/action authorization with an HttpOnly, SameSite JWT cookie tied to the single Vichu database account; protected standalone Admin pages, removed synthetic user-ID-1 authentication fallbacks, rejected the known default JWT signing secret, and disabled public account registration for the single-user setup.
- Restricted the MyLife phone server bind address to a discovered RFC1918 IPv4 address or loopback, verified HMAC pairing proofs with a 60-second timestamp window, and required completed pairing plus the registered client identity for protected routes.
- Removed fabricated MyLife screen-time, battery, connectivity, and tracking values from summary/status responses; unavailable values now use null plus an explicit availability flag. Corrected the MyLife Server screen and documentation to state that SQLCipher is not active because mobile storage still uses standard sqflite and does not use the Keystore channel.
- Added Flutter tests for pairing proof acceptance, identity tampering, expired proofs, malformed signatures, and empty identity fields.

### Fixed
- Fixed Admin session sign-in token extraction in `session.php` to accept tokens across redundant channels (`Authorization`, `X-Authorization`, `X-Admin-Token` headers, `apache_request_headers()`, and JSON request payloads), preventing Apache/FastCGI authorization header stripping from causing "A valid API sign-in token is required" login errors.
- Added `.htaccess` rules in `frontend/web/` and `frontend/web/admin/` enabling `CGIPassAuth On` and mod_rewrite HTTP_AUTHORIZATION passthrough.
- Updated `AdminWebAccess::adminPath` and `issueCookie` to encode spaces in cookie path values and use `SameSite=Lax` for reliable post-login navigation.
- Fixed the Performance Profiler, AST Modernizer, and Dead Code Pruner pages by isolating embedded sample PHP tags using `htmlspecialchars()`-safe output, preventing runtime redeclaration and parse errors.
- Sanitized dynamic error interpolation in Web Admin Dashboard (`frontend/web/admin/index.php`) using `escapeHtml()`.
- Corrected test environment filesystem token permissions enabling CodeIgniter PHPUnit bootstrap and successful execution of the 1,450-test backend suite.
- Changed Web sign-in to use the wrapped API response, removed the fake development token fallback and sign-up controls, made chat redirect unauthenticated users to sign in, and escaped approval/agent API values before HTML rendering.
- Changed the self-healing runtime's PHP syntax check to launch PHP with an argument array, preserving temporary file paths with spaces and special characters on Windows.
- Removed simulated CPU, RAM, and disk figures from ARC hardware telemetry; unavailable OS queries now return null values with an explicit availability flag, and the CLI reports that state instead of displaying zeros.

### Added
- Added a plain-language guide for all 75 Web Admin sidebar destinations and linked it from the user manual.
- Implemented the signed PHP MyLife phone client and explicit `/mylife` CLI commands for pairing, reachability, device status, executive summaries, and supported module reads.
- Restricted MyLife CLI targets to local mDNS, loopback, and RFC1918 IPv4 hosts; added a standalone PHP client check suite and local `.env` template entries.
- Added a read-only `mylife_os` AI tool with real CLI approval, dynamic ContextBuilder discovery, one-read-per-turn enforcement, and no persistent chat/tool logging or cross-model learning for approved MyLife data.
- Changed high-risk tool approval to fail closed unless an application-side callback approves; model-generated `human_approved` values no longer authorize execution.
- Extended the CLI gate to every registered tool and displayed redacted action details before per-action typed approval; disabled session grants and natural-language access grants.
- Added authenticated, owner-scoped Brain tool approval requests with a 15-minute expiry, single-use execution, stored results, and a database migration for execution results.
- Added an explicit approval dialog to the authenticated Web Brain sandbox and in-memory JWT sign-in plus owner-scoped approval review to Mobile.

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
