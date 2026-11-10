# ATOM Admin Navigation Guide

This guide explains the entries in the sidebar at `frontend/web/admin/`. Use it to find an administrative panel, understand the kind of task it supports, and navigate ATOM's management surfaces efficiently.

## Navigation Architecture Overview

To eliminate visual clutter and present a fast, purposeful control interface, the ATOM Admin sidebar is organized into two primary layers:

1. **Primary Operational Cockpit (Visible by Default)**: Contains the essential daily management surfaces for the ATOM AI Assistant platform, organized into 6 functional categories (Cockpit, AI Brain & Cognition, Knowledge & RAG, Multi-Modal Studios, Automation & Extensions, and Security & System).
2. **🧪 Milestone Studios & Labs (Collapsible Drawer)**: Holds experimental, milestone-specific phase studios (Phases 41 through 103). Collapsed by default to keep the interface uncluttered, it automatically expands when an administrator searches for or navigates to any milestone studio.

### Quick Find & Dynamic Filtering

- **Quick find menu (`#sidebarSearchInput`)**: Filters the navigation list in real time as you type. Typing any keyword (e.g. `pqc`, `fuzzer`, `swarm`, `approvals`) automatically opens the Milestone Labs drawer if a matching studio is contained within it.
- **Collapse Menu**: Minimizes the sidebar width from 256px to 80px for a focused widescreen workflow, showing clean icon badges.
- **Chat Interface**: Quick transition back to ATOM's active cognitive chat interface.

---

## 1. Primary Operational Cockpit

These 24 core modules form the operational backbone of the ATOM platform:

### Cockpit & Overview

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **Overview Dashboard** | `index.php` | Central telemetry hub, active provider switcher, real-time KPI metrics, and 3D Neural Particle Brain visualization. |
| **🌟 Command Center** | `command_center.php` | Autonomous multi-modal subsystem crossbar, master command dispatcher, and platform sentinel telemetry. |
| **Observability & Analytics** | `analytics.php` | Token consumption velocity, model latency percentiles, error rate monitoring, and query volume tracking. |

### AI Brain & Cognition

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **Personal Brain Core** | `brain.php` | Cognitive assistant architecture, dynamic memory graph, learning matrix, and personality tuning. |
| **AI Model Providers** | `providers.php` | Unified management of active cloud and local LLM backends (OpenAI, Anthropic, Gemini, Groq, Ollama, LM Studio). |
| **Model Playground** | `playground.php` | Interactive prompt engineering, model comparison, temperature tuning, and raw token streaming inspector. |
| **Autonomous Agents** | `agents.php` | Autonomous agent lifecycle runner, state machine transitions, execution budget caps, and goal tracking. |
| **Swarm Orchestrator** | `swarm.php` | Multi-agent collaborative consensus engine, task decomposition, and agent proposal synthesis. |
| **🛡️ Human Approvals** | `approvals.php` | Human-in-the-loop safety gate for tool executions, sensitive file modifications, and privileged system actions. |

### Knowledge & RAG

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **Knowledge Items (RAG)** | `knowledge.php` | Real-time RAG document search, category filters, vector hash validation, and knowledge chunk inspection. |
| **Document Ingestion** | `documents.php` | Multi-format document uploader (PDF, Markdown, Source Code), semantic chunking, and indexation queue. |
| **Vector Search Index** | `vector_index.php` | High-dimensional embedding similarity search, cosine distance metrics, and vector index health diagnostics. |

### Multi-Modal Studios

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **ATOM Voice Studio** | `voice_studio.php` | Real-time Tamil and English speech synthesis, Ben 10 voice profiles, pitch/rate controls, and audio spectrum display. |
| **Neural Vision & OCR** | `vision_studio.php` | Multi-modal visual understanding, screenshot debugging, diagram parsing, and UI-to-code synthesis. |
| **🌐 Web Crawler Studio** | `web_crawler_studio.php` | Phase 103 autonomous recursive link extractor, DOM parser, SSRF defense filter, and secret redaction engine. |

### Automation & Extensions

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **Plugins & Skills** | `skills.php` | Tool-calling plugin registry, custom function schemas, skill activation, and permission bounds. |
| **Plugin Marketplace** | `marketplace.php` | Ecosystem catalog for discovering, installing, and managing 1-click modular extensions. |
| **Automation Workflows** | `workflows.php` | Multi-step trigger-action workflow engine, DAG validator, and conditional execution chains. |
| **Background Jobs** | `jobs.php` | Asynchronous job queue, worker lifecycle, exponential backoff retries, and failed execution logs. |
| **Scheduled Cron** | `cron_scheduler.php` | Distributed task scheduler, cron expression manager, and cluster execution status. |

### Security & System

| Menu item | Route | Purpose & Key Capabilities |
|---|---|---|
| **ZK Vault (AES-256)** | `vault.php` | Zero-knowledge encrypted secret storage, cryptographic key derivation, and tamper-evident audit trees. |
| **Enterprise RBAC** | `rbac.php` | Role-based and attribute-based access control policies, tenant workspace scopes, and authorization tokens. |
| **AST Modernizer** | `code_modernizer.php` | Abstract syntax tree PHP modernizer, automated OWASP security patcher, and legacy code transformer. |
| **Desktop Companion** | `desktop.php` | Native WPF desktop client pairing, local server IPC bridge, and window management controls. |
| **Platform Settings** | `settings.php` | Single-tenant owner profile, active theme styling, system-wide defaults, and environment inspection. |

---

## 2. 🧪 Milestone Studios & Labs (Phases 41–103)

Accessible via the collapsible `🧪 Milestone Studios & Labs` drawer in the sidebar or directly via route. These modules provide specialized diagnostic, experimental, or milestone-specific simulation environments:

### Audio & Voice DSP Labs
- **Formant Shifter (Phase 46)** (`voice_duplex_stream.php`): Formant manipulation for streamed voice with real-time interval analysis.
- **Acoustic Filter (Phase 58)** (`acoustic_filter.php`): Spectral noise cancellation, hum removal, and signal-to-noise measurement.
- **Voice Harmonizer (Phase 62)** (`voice_harmonizer.php`): Multi-voice layered harmony generator and scale-locked pitch tracking.
- **Voice Emotion (Phase 68)** (`audio_emotion.php`): Speech sentiment classification and emotional tone modulation.
- **Natural Dialogue (Phase 69)** (`dialogue_studio.php`): Conversational turn-taking and conversational teaching controls.
- **Stem Separator (Phase 73)** (`audio_stems.php`): Frequency-domain separation of mixed audio into vocal and accompaniment stems.
- **Pitch Harmonizer (Phase 78)** (`pitch_studio.php`): Real-time autotune correction and microtonal harmony generation.
- **Audio Compressor (Phase 88)** (`compressor_studio.php`): Dynamic range compression, peak limiting, and threshold visualization.
- **Audio Spatializer (Phase 94)** (`spatializer_studio.php`): 3D binaural spatial audio panning and HRTF simulation.

### Cognitive & Vision Labs
- **Video Segmenter (Phase 82)** (`video_studio.php`): Automated scene-change detection and keyframe extraction.
- **Code Chunker (Phase 76)** (`code_chunker.php`): Semantic AST code splitter and call-tree generator.
- **Metacognition (Phase 80)** (`metacognition_studio.php`): Self-reflective reasoning analyzer and cognitive loop optimizer.
- **Cost Governor (Phase 89)** (`governor_studio.php`): Model budget enforcement, quota exhaustion mitigation, and cost matrices.
- **Vector Studio (Phase 96)** (`vector_studio.php`): Similarity metrics, distance ranking, and high-dimensional vector diagnostics.
- **GoT Planning** (`planning.php`): Graph-of-Thoughts tree visualization and branching evaluation canvas.
- **Century Finale (Phase 100 🏆)** (`century_studio.php`): Milestone 100 commemorative super-agent dispatch matrix.

### Engineering & AST Labs
- **Performance Profiler (Phase 51)** (`performance_profiler.php`): Code complexity analyzer and O(N²) loop bottleneck detection.
- **Dead Code Pruner (Phase 54)** (`dead_code_pruner.php`): Unreachable code detection and AST-based dead code elimination.
- **Dependency Graph (Phase 43)** (`dependency_graph.php`): Class coupling visualizer and cyclic dependency detector.
- **Refactoring Studio** (`refactoring.php`): Automated code smell remediation and AST transformation testing.
- **PSR-12 Linter (Phase 63)** (`code_linter.php`): PHP code standard validation and automated fixer preview.
- **CI/CD & Testing** (`cicd.php`): Autonomous test generation, self-correction pipeline, and build runners.
- **OpenAPI & SDKs (Phase 57)** (`openapi_studio.php`): Dynamic route introspection and client SDK generation.
- **Document Studio (Phase 84)** (`document_studio.php`): Markdown-to-PDF rendering and print layout previews.

### Security & Zero-Trust Labs
- **Post-Quantum Crypto (Phase 45)** (`pqc_studio.php`): Kyber KEM and Dilithium post-quantum signature verification.
- **ABAC Firewall (Phase 48)** (`abac_studio.php`): Context-aware attribute-based access policy evaluation engine.
- **Rate Limiter (Phase 56)** (`rate_limiter.php`): Token bucket rate governor and retry-after header verification.
- **Geo-Fence Firewall (Phase 64)** (`geofence_studio.php`): Geographic coordinate bounding and IP range perimeter filtering.
- **API Schema Fuzzer (Phase 74)** (`api_fuzzer.php`): Automated boundary value injection and security mutation fuzzer.
- **GraphQL Guard (Phase 83)** (`graphql_studio.php`): Query depth calculation and recursive complexity analyzer.
- **ZKP Verifier (Phase 91)** (`zkp_studio.php`): Non-interactive zero-knowledge proof generation and validation.
- **Token Mesh (Phase 99)** (`ratelimit_mesh_studio.php`): Distributed rate-limiting synchronization across peer nodes.

### Data & Storage Labs
- **SQL Optimizer (Phase 52)** (`query_optimizer.php`): Missing index recommendations and query plan optimization.
- **Query Load Simulator (Phase 61)** (`query_load_simulator.php`): High-concurrency synthetic database workload simulator.
- **Schema Drift Sync (Phase 65)** (`schema_drift.php`): Discrepancy detector between defined models and live SQL tables.
- **Cache Invalidator (Phase 70)** (`cache_studio.php`): Tag-based cache invalidation and eviction strategy benchmarks.
- **SQL Query Explainer (Phase 72)** (`query_explainer.php`): EXPLAIN visualizer with cost-based index suggestions.
- **Connection Pool (Phase 79)** (`pool_studio.php`): Database connection pool governor and starvation monitors.
- **Shard Router (Phase 87)** (`shard_studio.php`): Consistent hash ring shard allocator and partition rebalancer.
- **Stream ETL (Phase 93)** (`etl_studio.php`): Continuous extract-transform-load pipeline with schema mapping.
- **Schema Migrations (Phase 98)** (`migration_studio.php`): Dynamic DDL migration planner and safe rollback manager.

### Network & Edge Infrastructure Labs
- **Distributed Tracing (Phase 60)** (`distributed_tracing.php`): Distributed span timings, context propagation, and bottleneck identification.
- **Canary Governor (Phase 71)** (`canary_studio.php`): Progressive traffic split router with automated health rollbacks.
- **Circuit Mesh (Phase 85)** (`circuit_studio.php`): Dynamic circuit breaker with trip/half-open state machines.
- **Reverse Proxy (Phase 86)** (`proxy_studio.php`): Upstream load balancer with round-robin and least-connection routing.
- **Stream Framer (Phase 90)** (`stream_compressor_studio.php`): Real-time chunked compression and frame integrity checking.
- **Event Mesh (Phase 92)** (`event_mesh_studio.php`): Multi-channel topic broker with fan-out pub-sub delivery.
- **Webhook DLQ (Phase 97)** (`webhook_dlq_studio.php`): Dead-letter queue with exponential backoff replay governor.
- **Wasm Sandbox (Phase 101)** (`wasm_studio.php`): Isolated WebAssembly runtime with gas metering and memory bounds.
- **CQRS Ledger (Phase 102)** (`event_sourcing_studio.php`): Append-only event store with historical state time-travel.
- **Feature Flags (Phase 77)** (`feature_flags.php`): Dynamic toggle flags with tenant-level overrides.
- **Flag Rollouts (Phase 95)** (`rollout_studio.php`): Percentage-based feature rollout governor and A/B split evaluator.
- **Chaos Mesh (Phase 81)** (`chaos_studio.php`): Controlled fault injection, latency injection, and partition resilience simulator.
- **Event Webhooks (Phase 53)** (`webhook_hub.php`): HMAC-SHA256 signed event delivery engine.
- **Federated Learning (Phase 55)** (`federated_learning.php`): Distributed model weight aggregator with differential privacy.
- **Semantic Release (Phase 59)** (`release_studio.php`): Conventional commit log analyzer and semver versioning generator.
- **P2P Edge Mesh** (`webrtc_mesh.php`): WebRTC mesh peer discovery, SDP offer/answer signaling, and ICE management.
- **WebRTC Transfer (Phase 66)** (`webrtc_transfer.php`): Peer-to-peer chunked file transfer with SHA-256 integrity checks.
- **IoT Watchdog (Phase 75)** (`iot_studio.php`): Fleet device telemetry ingest and anomaly detector.
- **Latency Heatmap (Phase 67)** (`latency_heatmap.php`): Inter-service response time matrix and SLA threshold monitor.
- **Predictive Brain** (`predictive_analytics.php`): Holt-Winters exponential smoothing forecaster for system telemetry.
