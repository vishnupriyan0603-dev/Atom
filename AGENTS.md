# ATOM Agent Instructions

## Knowledge Base
- `.antigravity/` - Unified AI Engineering Knowledge Base (principles, rules, UI, database, API, deployment, security)
- `.antigravity/skills/` - Technology-specific skills (php, codeigniter, mysql, javascript, bootstrap, api, security)

## Workflow
Always follow this workflow for every task (see `docs/svgAtom_strict_rules.md`):
1. Analyze - Read all relevant files before making changes
2. Plan - Determine files to modify, identify side effects
3. Branch - Create task branch (`feature/*`, `fix/*`, `refactor/*`, `docs/*`, `test/*`)
4. Backup - Backup affected files
5. Implement - Make targeted changes following project standards
6. Test - Verify PHPUnit, C# build, and feature acceptance criteria
7. Verify UI & DB - Check visual consistency and query correctness
8. Security Review - Validate input, escape output, check auth
9. Final Review - Confirm backwards compatibility

See `.antigravity/workflow.md` and `docs/svgAtom_strict_rules.md` for detailed descriptions.

## Loading Strategy
- PHP tasks: `.antigravity/workflow.md`, `.antigravity/skills/php.md`, `.antigravity/skills/codeigniter.md`, `.antigravity/project.md`, `.antigravity/rules.md`, `.antigravity/personal_metadata.md`
- UI tasks: `.antigravity/skills/bootstrap.md`, `.antigravity/skills/javascript.md`, `.antigravity/ui.md`, `.antigravity/personal_metadata.md`
- Database tasks: `.antigravity/skills/mysql.md`, `.antigravity/database.md`, `.antigravity/personal_metadata.md`
- Deployment: `.antigravity/skills/ubuntu.md`, `.antigravity/skills/apache.md`, `.antigravity/skills/cron.md`, `.antigravity/deployment.md`, `.antigravity/personal_metadata.md`
- Security: `.antigravity/security.md`, `.antigravity/personal_metadata.md`
- API: `.antigravity/skills/api.md`, `.antigravity/api.md`, `.antigravity/personal_metadata.md`

## Project Rules
- Follow `docs/svgAtom_strict_rules.md` strictly (Understand first. Change second. Verify third. Merge last.)
- Never change UI unless requested
- Never rename database columns or tables
- Never change existing APIs or remove functionality
- Always preserve backward compatibility
- Modify only requested modules
- Keep code modular, avoid duplicate logic
- Reuse existing functions whenever possible
- Validate every input, escape every output
- Use prepared statements / query builder
- Handle errors gracefully
- Never hardcode secrets
- Voice mode must use hold-to-talk (push-to-talk): audio is only captured while the user actively holds the mic control; releasing it stops capture immediately and triggers transcription. Never implement always-on/continuous listening.
- Temp audio buffers created during a hold-to-talk session exist only for that session's transcription — discard/clear them once transcription completes or the hold is released without sending; never persist raw mic audio beyond that.
- Cross-client parity: ATOM has four viewers — CLI/terminal (`atom.php`), Web frontend + admin (`frontend/web/`), Mobile (`mobile/`), Desktop WPF (`src/PersonalAIAssistant/`). A bug fix or behavior change must produce the same corrected result on every viewer, not just the one where it was noticed.
  - Fix shared logic at its source (`src/`, `backend/app/`) so the REST API (`/api/v1/*`) — which Web and Mobile both consume — and the CLI/Desktop (which use `src/` classes directly) all inherit the fix automatically.
  - If a bug is UI-only (rendering/formatting), check whether the same UI bug exists on the other three surfaces before calling the fix done; if it does, fix it on all of them in the same task, not just the one requested.
  - Never patch one client with a workaround that papers over a bug that actually lives in the shared backend/`src/` layer.
- Provider/model config parity: `LLM_PROVIDER`, `LLM_MODEL`, and the per-provider keys (`GROQ_*`, `GEMINI_*`, `OPENAI_*`, `ANTHROPIC_*`) in the root `.env` are the single source of truth, loaded via `Atom\Config\Config::load()`. CLI (`atom.php`), the Web dashboard (`frontend/web/bootstrap.php`), and the backend REST API (`backend/app/Services/AiChatService.php`, used by both Web and Mobile) all already read this same file — verify a provider/model change is reflected identically across all three (`/status` + `/provider` in CLI, "Active Provider" on the Web dashboard, and the model actually used to answer a chat request) before calling the change done.
- No orphaned duplicate implementations: before adding a new engine/class, check whether an existing one already does the same job (`grep` for similar class names/purposes in `src/`). If two implementations of the same thing exist (e.g. `Chunker` vs `NeuralDocumentChunker`), don't leave the better one wired only into an unrelated demo/API endpoint while the real pipeline keeps using the worse one — connect the better implementation to where it actually matters, with a regression check proving it's behavior-equivalent on the existing common case before relying on it for the uncommon case it improves.
- Documentation stays in sync with capability: any new command, feature, or changed behavior must update `docs/ATOM_User_Manual_Guide.md` in the **same task**, not as a follow-up — this restates that doc's own Section 16 "Step 5: Update the Manual Guide", which exists but was being skipped. If a command is added to the CLI (`src/CLI/CommandRouter.php`) or a UI panel is added/changed (Web, Admin, Mobile, Desktop), add or update its entry in the manual before calling the task done. A feature a user can't find documented is a feature that isn't really shipped.
  - Known gap: the Desktop app (WPF, `src/PersonalAIAssistant/`) does **not** read `.env` — it stores its own provider API keys in a local SQLite `Settings` table via `SettingsService`. A `.env` provider/model change will not reach Desktop today. Do not silently rewrite Desktop's config source to "fix" this — `Core AI model configuration` is a protected area (see `docs/svgAtom_strict_rules.md` §14); bridging Desktop to the shared config requires explicit approval first.

## Test Commands
- Backend tests: `cd backend && php -d extension=intl -d extension=sqlite3 -d extension=pdo_sqlite vendor/bin/phpunit --testdox`
- Desktop solution build: `dotnet build PersonalAIAssistant.sln`
