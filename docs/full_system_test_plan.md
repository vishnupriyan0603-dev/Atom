# ATOM — Full System Test Plan

Status: **PLAN ONLY — not yet executed.**
Owner: to be assigned before execution.
Related rules: `docs/svgAtom_strict_rules.md`, `AGENTS.md`.

This plan defines how to verify that ATOM actually works end-to-end, across every
surface the project ships (CLI, backend API, Brain engines, knowledge/RAG, voice,
web/admin, mobile, desktop). It does not run anything — execution is a separate,
explicitly-approved step per branch/task rules.

---

## 1. Current known state (baseline)

- `report.text` (2026-08-23) recorded a run of only **16 tests** (Database, Session,
  Health, KnowledgeGraph, Provider, SelfImprovement).
- `backend/tests/unit/` currently contains **276 test files**, and
  `backend/phpunit.dist.xml` points its `App` testsuite at the whole `./tests`
  directory — so a full run should discover far more than 16 tests today.
- Conclusion: the last report is **stale/partial**. Step 2 below (full backend run)
  must happen before any "ATOM works" claim is trusted again.

---

## 2. Backend — PHPUnit (CodeIgniter 4)

**Goal**: confirm every unit/integration test in `backend/tests/` passes, not just
the 16 previously reported.

Command:
```
cd backend && php -d extension=intl -d extension=sqlite3 -d extension=pdo_sqlite vendor/bin/phpunit --testdox
```

Checks:
- Total discovered test count matches (or exceeds) the 276 files in `tests/unit/`
  plus `tests/database/` and `tests/session/`.
- 0 Errors, 0 Failures. Skipped tests are individually justified (e.g. provider
  not configured), not silently accepted.
- Spot-check the "Security Pass" test families (`*SecurityPassTest.php`,
  ~40+ files) since they gate the phase-by-phase engines added over 167 commits.
- Coverage report (`build/logs/clover.xml` / `html`) reviewed for any core module
  (Brain, Security, Knowledge) sitting near 0% coverage.

## 3. CLI smoke test (`php atom.php`)

Manual walk-through of the primary interactive loop, one command family at a time:

| Area | Commands | Expected |
|---|---|---|
| Bootstrapping | `/status`, `/help` | Workspace path, file counts, MySQL connectivity all report correctly |
| Project awareness | `/project`, `/files`, `/search <query>` | Accurate tree/listing, no ignored dirs (git/vendor) leaking |
| File tools | `/read`, `/php-lint`, `/create`, `/patch` | Sandbox boundary enforced (WorkspaceGuard), backups written, lint rollback on failure |
| Memory | `/memory`, `remember that ...`, `forget memory <id>`, `remember solution: ...` | Entries persist in MySQL, `forget` actually removes the row |
| Knowledge/RAG | `/knowledge import <pdf>`, `/knowledge ask <query>` | Dedup by hash (no repeat originals), citations returned with page numbers |
| Learning | `/learning`, `/learning gaps`, `/learning topic <name>`, `/learning history` | Progress bars and topic data reflect actual stored state |
| Collaboration/provider | `/collaboration`, `/provider mode <local\|balanced\|collaborative>` | Mode switch actually changes routing behavior on next prompt |
| Voice | `/brain:voice on\|off`, `/voice:speak`, `/voice:voices`, `/voice:duplex`, `/voice:wake`, `/voice:interrupt` | See Section 6 (voice-specific plan) |

## 4. Brain engine behavioral tests

These exercise logic, not just unit assertions — run as scripted conversations
through the CLI (or a small PHP harness instantiating the engine directly):

- **Identity/session recall** (`AtomRelationshipEngine`): say a name early in a
  session (`"I'm Vishnu"`), then later ask `"what's my name?"` — must answer
  correctly without re-asking. Also verify topic continuity (short follow-ups
  like `"why"`, `"how much"`) resolve against the last active subject, and that
  correction phrases (`"no, I mean X"`) actually switch the active subject.
- **Goal planner / DAG decomposition** (`AtomGoalPlannerEngine`): multi-step goal
  produces an ordered, dependency-correct plan.
- **Self-improvement** (`SelfImprovementEngine` + `HumanApprovalGate`): flaw
  detection logs an evaluation, sandbox experiment runs isolated, and nothing is
  promoted to production behavior without explicit human approval.
- **Situation reasoner / calculators**: sample real-world calculation prompts
  return numerically correct results, not just well-formatted text.

## 5. Security review pass

- `WorkspaceGuard`: attempt path traversal (`../../etc/passwd`-style) and confirm
  it's rejected.
- `SecretRedactor`: feed sample API keys/passwords through `/read` and chat and
  confirm masking in both terminal output and stored logs.
- `HumanApprovalGate`: confirm no self-improvement change reaches "active" status
  without a recorded approval action.
- Confirm `.env` / secrets are never echoed by any command output.

## 6. Voice mode — hold-to-talk verification

New rule (see `AGENTS.md`): voice mode must be **hold-to-talk**, never
always-on/continuous listening, and temp audio buffers must not persist beyond
the transcription of one held session.

Checks once a real capture UI exists (today `VoiceEngine` is text-mode fallback
only — see its docblock, "Phase 23 delivers the TEXT-MODE FALLBACK only"):
- Audio capture starts only on control press, stops immediately on release.
- No capture occurs while the control is not held (no background/open-mic
  recording).
- Temp audio file/buffer is deleted (or never written to disk) once transcription
  completes, and also on a held-then-released-without-sending cancel path.
- `formatForVoice()` markdown-stripping still behaves correctly (fenced code,
  bold/italic, headings, links, lists, blockquotes all stripped, plain text
  preserved) — this part is already implemented and covered by
  `SpeechSynthesizerTest`/`AudioTranscriberTest`, just needs re-confirming after
  any capture-flow change.

## 7. Web frontend & admin panel

- Dashboard (`frontend/web/`) loads, conversation view sends/receives via
  `/api/v1/chat`.
- RAG documents explorer lists imported knowledge correctly.
- Memory modifier panels reflect the same data the CLI `/memory` shows.
- Admin panel (`frontend/web/admin/`) safety-gate widgets correctly reflect
  pending self-improvement approvals.

## 8. Mobile (Flutter)

- App builds for at least one target (Android or iOS simulator).
- Chat input, PDF indexing status, and home stats screens load without crashing
  and hit the same `/api/v1/*` endpoints as the web client.

## 9. Desktop (.NET/WPF)

Command:
```
dotnet build PersonalAIAssistant.sln
```
- 0 build errors (NuGet warnings acceptable, note count).
- Manually open `SelfImprovementView.xaml` and confirm it renders and reflects
  live approval-gate state.

## 10. REST API surface

For each endpoint in `README.md` §6, verify status code + shape:
`POST /auth/login`, `GET /user/profile`, `POST /chat`, `GET /memory`,
`GET /knowledge`, `POST /knowledge/upload`, `GET /learning`, `GET /workspace`.
Include one authenticated and one unauthenticated call per protected endpoint.

## 11. Exit criteria

A run is "PASSED" only if:
- Backend PHPUnit: 0 failures/errors, skip list justified.
- CLI smoke test: every command in Section 3 exercised at least once, output
  matches expected behavior.
- Brain behavioral tests (Section 4) pass, including the identity-recall case.
- Security checks (Section 5) all pass — any failure here blocks release
  regardless of other results (per `AGENTS.md` protected-areas rule).
- .NET build succeeds.
- Findings recorded in a report following the same format as `report.text`
  (module → command → result), so results stay comparable over time.

## 12. Out of scope for this plan

- Load/performance testing of the many speculative "phase" engines (zk-rollup,
  distributed rate limiter mesh, etc.) — these are unit-tested but not part of
  ATOM's actual product surface (CLI/API/UI) today; flag as a separate task if
  they need functional verification.
- Real microphone hardware testing — blocked until a real capture UI exists
  (see Section 6 baseline note).
