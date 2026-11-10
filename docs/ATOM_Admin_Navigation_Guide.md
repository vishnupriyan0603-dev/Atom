# ATOM Admin Navigation Guide

This guide explains the entries in the sidebar at `frontend/web/admin/`. Use it to find a page and understand the kind of task it supports.

## How to read the menu

- The number in parentheses is the phase or milestone label shown by the sidebar. It is not a guarantee that a production service is deployed or enabled.
- Menu pages include dashboards, editors, diagnostics, and simulations. A page being present does not mean its feature is connected to a live production system. Check the page's status, results, and configured services before relying on an operation.
- **Quick find menu** filters the sidebar entries by their displayed text. **Collapse Menu** hides the labels to make more room. **Chat Interface** opens the regular ATOM chat page.

## Core cockpit

| Menu item | What it is for |
|---|---|
| 🌟 Command Center (50) | View the platform command and subsystem overview, including dispatcher and platform telemetry. |
| 🌌 Distributed Tracing (60) | Inspect trace context and timing across components to help find slow or failing parts of a request. |
| Overview Dashboard | See the main ATOM health and knowledge metrics, provider/model status, and the visual Brain overview. |

## AI Brain & Swarm

| Menu item | What it is for |
|---|---|
| Swarm Orchestrator (41) | Explore or run a multi-role task workflow that combines agent proposals and displays a consensus result. |
| Ben 10 Tamil Voice | Try the Tamil voice studio for speech synthesis and voice/acoustic profile controls. |
| Live Formant Shifter (46) | Adjust formant settings for streamed voice and hear how the voice character changes. |
| Acoustic Noise Filter (58) | Inspect or try noise filtering, hum removal, and signal-to-noise measurements. |
| Voice Harmonizer (62) | Work with pitch tracking, scale selection, and layered voice harmonies. |
| Voice Emotion (68) | Explore voice tone classification and speech-style adjustments for different moods. |
| Natural Dialogue (69) | Try conversational response styles, greetings, and the page's teaching/dialogue controls. |
| Stem Separator (73) | Explore separating an audio track into vocal and instrumental parts and recombining stems. |
| Pitch Harmonizer (78) | Inspect pitch detection and try scale-based tuning or multi-voice chord settings. |
| Audio Compressor (88) | Adjust audio compression and peak-limiting controls to manage loudness dynamics. |
| Audio Spatializer (94) | Try spatial audio settings such as stereo position, distance, and directional effects. |
| Neural Vision & OCR (42) | Work with image/visual extraction and the wireframe-to-interface preview tools. |
| Video Segmenter (82) | Inspect video scene-change detection and choose representative keyframes. |
| Personal Brain | Open the broader personal-brain and platform-orchestration workspace. |
| Code Chunker (76) | Split source code around classes and methods and inspect its symbols/dependency information. |
| Metacognition (80) | Review a reasoning or task plan for loops, redundant branches, and opportunities to simplify. |
| Cost Governor (89) | Inspect model-cost and budget controls, including budget thresholds and fallback options. |
| Vector Index (96) | Search or inspect vector data using similarity methods and metadata filters. |
| GoT Planning | Break a task into a graph of steps and compare or verify planning branches. |

## Engineering & Code

| Menu item | What it is for |
|---|---|
| AST Modernizer (47) | Inspect PHP syntax modernization suggestions and potential security fixes based on parsed code. |
| Performance Profiler (51) | Analyze code or query patterns for likely time, memory, and database performance bottlenecks. |
| Dead Code Pruner (54) | Find code that appears unused or unreachable so it can be reviewed for cleanup. |
| Dependency Graph (43) | Visualize code dependencies and look for cycles or tightly coupled components. |
| Refactoring Studio | Inspect code smells and explore safer transformations and architecture improvements. |
| PSR-12 Linter (63) | Check PHP style and preview or apply formatting-related fixes offered by the page. |
| CI/CD & Testing | Explore test-generation, failure diagnosis, and CI pipeline controls. |
| OpenAPI & SDKs (57) | Inspect API routes and generate an OpenAPI description or client SDK output. |
| Document Studio (84) | Prepare or preview paginated documents, including SVG and print-layout handling. |

## Security & Zero-Trust

| Menu item | What it is for |
|---|---|
| Post-Quantum Crypto (45) | Explore post-quantum key-exchange and signature examples or handshake checks. |
| ABAC Firewall (48) | Evaluate access decisions using subject, resource, action, and environment attributes. |
| Rate Limiter (56) | Inspect request quota and rate-limiting behavior, including retry timing. |
| Geo-Fence Firewall (64) | Review geographic and IP/CIDR access-policy controls. |
| API Schema Fuzzer (74) | Exercise API validation with generated boundary and malformed-input cases. |
| GraphQL Guard (83) | Inspect GraphQL query-depth and cost limits intended to reject overly expensive queries. |
| ZKP Verifier (91) | Try zero-knowledge proof verification examples and view their verification results. |
| Token Mesh (99) | Explore distributed token-bucket or rate-limit synchronization across nodes. |
| ZK Vault (AES-256) | Use the vault interface for encrypted data operations and inspect its security/audit status. |
| Enterprise RBAC | Review role-based access rules, scoped permissions, and authorization decisions. |

## Data & Storage

| Menu item | What it is for |
|---|---|
| HNSW Vector Index (44) | Explore approximate-nearest-neighbor indexing and vector-search behavior. |
| SQL Index Optimizer (52) | Inspect query/index recommendations and migration output for database tuning. |
| Query Load Simulator (61) | Simulate SQL traffic and compare latency percentiles or index scenarios. |
| Schema Drift Sync (65) | Compare expected and actual database schemas and review migration suggestions. |
| Cache Invalidator (70) | Inspect cache-tag invalidation and refresh policies. |
| SQL Query Explainer (72) | Review a query execution plan and identify scans or possible index improvements. |
| Connection Pool (79) | Inspect connection-pool sizing and behavior such as starvation or leaked handles. |
| Shard Router (87) | Explore shard assignment, topology, and entity-rebalancing plans. |
| Stream ETL (93) | Build or inspect extract-transform-filter-sink steps and rejected-record handling. |
| Schema Migrations (98) | Review migration and rollback planning for database schema changes. |
| Knowledge Items | Search, add, edit, or verify the RAG knowledge chunks stored by ATOM. |

## Automation & Edge

| Menu item | What it is for |
|---|---|
| Distributed Cron (49) | Inspect or schedule recurring jobs and their execution/failed-job status. |
| Canary Governor (71) | Explore gradual traffic rollout controls and rollback thresholds. |
| Circuit Mesh (85) | Inspect circuit-breaker states and failure thresholds for fast-fail behavior. |
| Reverse Proxy (86) | Configure or preview request routing strategies and upstream health checks. |
| Stream Framer (90) | Explore stream compression/framing options and integrity checks. |
| Event Mesh (92) | Inspect event subscriptions, fan-out, and consumer offsets. |
| Webhook DLQ (97) | Review failed webhook deliveries and retry or replay controls. |
| Wasm Sandbox (101) | Explore isolated WebAssembly execution limits and fault handling. |
| CQRS Ledger (102) | Inspect append-only event records and historical replay controls. |
| Feature Flags (77) | Manage or preview feature availability rules and kill switches. |
| Flag Rollouts (95) | Plan percentage-based or role-targeted feature rollouts. |
| Chaos Engineering (81) | Simulate selected failure conditions to inspect resilience and blast-radius controls. |
| Event Webhook Hub (53) | Inspect signed event delivery, retry behavior, and dead-letter handling. |
| Federated Learning (55) | Explore a federated-learning workflow and its aggregation/privacy controls. |
| Semantic Release (59) | Analyze conventional commits and preview a semantic version/changelog. |
| P2P Edge Mesh | Inspect peer-to-peer WebRTC signaling and connection status. |
| WebRTC Transfer (66) | Try peer-to-peer file-transfer controls and integrity verification. |
| IoT Watchdog (75) | Inspect sensor/fleet telemetry and anomaly-detection status. |
| Latency Heatmap (67) | Compare component latency buckets and locate potential service-level breaches. |
| Predictive Brain | Review resource forecasts and anomaly indicators derived from available metrics. |
| 🌐 Web Crawler (103) | Configure or inspect a crawler run and extracted page text, links, code, or tables. |

## Account and conversation

| Menu item | What it is for |
|---|---|
| Settings | Configure the available general, personalization, appearance, and optimization options. |
| Chat Interface | Open ATOM chat for regular conversations, tool approvals, and voice features supported by the current setup. |

## Practical notes

- Read each page's status and result before treating an operation as successful. Some pages are explicitly simulators or previews; other tools depend on credentials, services, permissions, and database configuration.
- Menu phase numbers are labels only. For user workflows and platform setup, see the [ATOM User Manual](ATOM_User_Manual_Guide.md).
- If a sidebar link does not load, use the page's route under `frontend/web/admin/`; the dashboard route is `index.php` and Chat Interface is `../chat.php` from the admin folder.
