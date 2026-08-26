# ATOM Learning & Knowledge Subsystem — 50-Test Plan

Status: **PLAN ONLY — not yet executed.**
Scope: `src/Brain/LearningEngine.php`, `src/Knowledge/*.php` (KnowledgeSearch, KnowledgeGraph,
DocumentImporter, Chunker, PdfExtractor), the CLI `/learning` and `/knowledge` command families,
and the backing MySQL tables (`atom_learning_topics`, `atom_learning_history`, `atom_documents`,
`atom_document_chunks`, `atom_knowledge_triples`).

Related: `docs/full_system_test_plan.md` (whole-system plan); this document goes deep on the one
subsystem instead. Per `AGENTS.md`, this plan itself doesn't need a manual-guide entry — only
shipped features do — but any fix that comes out of running it does.

---

## A. LearningEngine — scoring & topic tracking (1-14)

1. `getLevelFromScore()` boundary values: 0, 5, 6, 25, 26, 45, 46, 65, 66, 80, 81, 90, 91, 100 — each must map to the documented `LEVEL n — NAME` band with no off-by-one.
2. `getTopics()` returns `[]` (not an error) when DB is disconnected.
3. `getTopics()` orders by `score DESC, topic ASC` — verify with topics of equal score.
4. `getTopic($name)` returns `null` for a topic that doesn't exist.
5. `getTopic($name)` on a hit includes computed `workspace_files` and `pdf_references`, not just raw DB columns.
6. `logHistory()` writes a row with the given topic/action/source/confidence; confirm default `confidence = 'MODERATE'` when omitted.
7. `getHistory($limit)` respects the limit and orders newest-first (`id DESC`).
8. `getGaps()` only returns topics with `score < 60`, capped at 5, ascending by score.
9. `updateTopicMetrics()` on a **new/unknown topic** (no existing row) — confirm it no-ops safely rather than erroring (current code `return`s if `!$row`; decide if this is the intended behavior or a gap — a topic that's never been seeded can never accrue metrics).
10. `updateTopicMetrics($topic, success: true)` increments `successful_uses` and recomputes score upward.
11. `updateTopicMetrics($topic, success: false)` increments `failed_uses` and applies the 8-point-per-failure penalty; verify score cannot go below 0.
12. Score formula ceiling: verify `coverageScore` caps at 40 (10+ sources), `usageScore` caps at 40 (10+ successes), total caps at 100.
13. `countWorkspaceReferences()` — **regression test for the fix just applied**: confirm the scan no longer descends into `.git`, `vendor`, `node_modules`, `cache`, `logs`, `uploads`, `obj`, `bin` (assert it completes in well under 1s on this 1300+ file workspace, and returns the same match count as before the fix for a term with no matches inside ignored dirs).
14. `recordUserCorrection()` logs a `USER_FEEDBACK` history entry at `HIGH` confidence and calls `updateTopicMetrics(topic, true, false)` — confirm both side effects happen atomically enough that one failing doesn't silently skip the other.

## B. KnowledgeSearch — RAG retrieval (15-26)

15. `search($query)` against `atom_document_chunks` — FULLTEXT match path returns ranked results for a term known to exist in the ingested PDF.
16. `search($query)` LIKE-fallback path (per README: "MySQL FULLTEXT matching with LIKE fallback") — force/verify the fallback triggers correctly when FULLTEXT is unavailable or returns nothing.
17. `search()` with a query containing SQL special characters (`%`, `_`, `"`, `'`) — confirm no SQL error and no injection (prepared statements only).
18. `search()` respects the `$limit` parameter (default 5) — request 1, 5, 20; verify counts.
19. `searchTriples($query)` returns matching Knowledge Graph triples for a subject/predicate/object substring.
20. `searchHybrid($query)` genuinely combines chunk search + triple search (not just one or the other) — verify with a query that only a triple would answer and one that only a chunk would answer.
21. `cosineSimilarity($vecA, $vecB)` — identical vectors → 1.0; orthogonal vectors → 0.0; a zero vector → no division-by-zero error.
22. `searchAdvancedRag()` respects `$topK` and `$similarityThreshold` — results below threshold are excluded; verify with an artificially high threshold (e.g. 0.99) returns empty.
23. Empty query string to any search method — confirm graceful empty-result handling, not an exception.
24. Query longer than any DB column limit — confirm truncation/handling doesn't throw a DB-level error.
25. Search with DB disconnected — confirm every method returns `[]` rather than fataling (matches the graceful-degradation pattern used elsewhere in this codebase).
26. Concurrent search load: fire ~20 rapid `search()` calls back-to-back — confirm no connection-pool exhaustion (single shared PDO connection per request, so this mainly guards against leaked prepared statements).

## C. KnowledgeGraph — triples (27-35)

27. `addTriple()` inserts a (subject, predicate, object) row with default confidence 0.95; verify stored value.
28. `addTriple()` with an explicit `$confidence` and `$sourceItemId` — verify both persist and `$sourceItemId` correctly links back to the source document/chunk.
29. `addTriple()` duplicate insert (same S-P-O twice) — decide and verify actual behavior: dedup, or intentional duplicate rows for confidence averaging?
30. `queryTriples()` with only `$subject` set — returns all triples for that subject regardless of predicate/object.
31. `queryTriples()` with all three of subject/predicate/object set — exact-match lookup.
32. `queryTriples()` with none set — either returns everything or is guarded against (verify which, and whether that's intentional given table size).
33. `extractTriplesFromText($text)` on a paragraph with clear "X is a Y" / "X has Y" patterns — verify plausible triples are extracted and the returned int matches the actual row count inserted.
34. `extractTriplesFromText()` on text with no extractable relationships — returns 0, no spurious triples.
35. `extractTriplesFromText()` on Tamil or mixed-script text — confirm it doesn't crash on multibyte input (relevant given the project's Tamil voice/knowledge work).

## D. DocumentImporter, Chunker, PdfExtractor — ingestion pipeline (36-45)

> **Update**: `DocumentImporter::import()` now chunks via `NeuralDocumentChunker` (header/code-boundary-aware),
> falling back to `Chunker` only if it returns nothing. Verified behavior-equivalent to the old plain
> `Chunker` on prose text (byte-identical output), and now splits along Markdown headers / code
> function-class boundaries instead of blind character-count slicing for structured documents.
> Test 39-41 below should be read as testing the shared `Chunker` fallback path; add a 39a covering
> `NeuralDocumentChunker::chunkDocument()`'s header-boundary detection directly.

36. `PdfExtractor::extract()` on a valid text-based PDF — returns non-empty extracted text.
37. `PdfExtractor::extract()` on a scanned/image-only PDF (no embedded text layer) — verify it fails gracefully (empty/error) rather than crashing, since there's no OCR step evident in this class.
38. `PdfExtractor::extract()` on a corrupted/truncated PDF file — graceful error, not a fatal.
39. `Chunker::chunk($text)` default params (800 size / 150 overlap) — verify chunk boundaries actually overlap by ~150 chars and no chunk exceeds ~800 chars mid-sentence in a way that breaks words unnecessarily.
40. `Chunker::chunk()` with `$chunkSize` smaller than `$overlap` — verify this doesn't infinite-loop or produce zero-length/negative-advance chunks.
41. `Chunker::chunk()` on text shorter than `$chunkSize` — returns exactly one chunk containing the whole text.
42. `DocumentImporter::import($path)` on a new PDF — confirm the file is copied into `storage/knowledge/originals/`, chunks land in `atom_document_chunks`, and a row appears in `atom_documents`.
43. `DocumentImporter::import()` on a **duplicate** file (same SHA-256 as one already imported) — per `report.text`'s documented fix, confirm it's deduplicated and does NOT create a second copy in `storage/knowledge/originals/`.
44. `DocumentImporter::import()` on a non-existent path — graceful error return, not a fatal/exception leaking a stack trace.
45. `DocumentImporter::import()` on an unsupported file type (e.g. `.exe` renamed to `.pdf`) — verify `FilePolicy`/extension checks actually reject it rather than attempting to parse garbage as PDF text.

## E. CLI command surface (46-50)

46. `/learning` (no args) — prints overall knowledge status, progress bars, topic list; verify numbers match what `getTopics()` returns directly.
47. `/learning gaps` — output matches `getGaps()` exactly (same topics, same order).
48. `/learning topic <name>` — for an existing topic, shows full detail including `workspace_files`/`pdf_references`; for a non-existent topic, shows a clear "not found" message rather than a blank/broken screen.
49. `/learning history` — shows recent entries in newest-first order, matching `getHistory()`.
50. `/knowledge import <path>` and `/knowledge ask <query>` end-to-end: import a small test PDF, then immediately ask a question answerable only from that PDF, and confirm the citation/page reference in the reply traces back to the just-imported document (proves the whole ingest → search → answer pipeline is actually wired together, not just each piece in isolation).

---

## Exit criteria

- All 50 cases produce the expected behavior described above, or a documented, deliberate deviation.
- Any case that fails gets a `AGENTS.md`-style report: `FAILED / Reason / Impact / Likely cause / Recommended fix` — not silently skipped.
- A fix arising from this plan updates `docs/ATOM_User_Manual_Guide.md` in the same task if it changes user-visible behavior, per the cross-client-parity and documentation-sync rules already in `AGENTS.md`.
