<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

// Root health check — returns JSON so frontend knows server is alive
$routes->get('health', 'Api\Health::index');
$routes->get('api', 'Api\Health::index');
$routes->get('api/health', 'Api\Health::index');

// Web frontend pages mapping
$routes->get('/', 'Home::index');
$routes->get('index.php', 'Home::index');
$routes->get('chat', 'Home::chat');
$routes->get('chat.php', 'Home::chat');
$routes->get('admin.php', 'Home::admin/index');
$routes->get('admin', 'Home::admin/index');
$routes->get('admin/(:any)', 'Home::admin/$1');
$routes->get('static/(:any)', 'Home::serve/$1');

// Auth routes (no auth required)
$routes->post('api/auth/register', 'Api\Auth::register');
$routes->post('api/auth/login', 'Api\Auth::login');

// Protected API routes
$routes->group('api', ['filter' => 'auth'], static function ($routes) {
    $routes->get('auth/me', 'Api\Auth::me');

    // AI operations (auth required — avoids anonymous LLM quota burn)
    $routes->post('ai/complete', 'Api\AiChat::complete');
    $routes->post('ai/models', 'Api\AiChat::listModels');

    // Knowledge Records & Documents management
    $routes->get('knowledge', 'Api\Knowledge::index');
    $routes->get('knowledge/(:num)', 'Api\Knowledge::show/$1');
    $routes->post('knowledge', 'Api\Knowledge::create');
    $routes->put('knowledge/(:num)', 'Api\Knowledge::update/$1');
    $routes->delete('knowledge/(:num)', 'Api\Knowledge::delete/$1');
    $routes->get('knowledge/documents', 'Api\Knowledge::documents');
    $routes->post('knowledge/upload', 'Api\Knowledge::upload');
    $routes->delete('knowledge/documents/(:num)', 'Api\Knowledge::deleteDocument/$1');
    $routes->post('knowledge/documents/(:num)/train', 'Api\Knowledge::trainDocument/$1');

    // Analytics / observability (admin)
    $routes->get('analytics/requests', 'Api\Analytics::requests');
    $routes->get('analytics/responses', 'Api\Analytics::responses');
    $routes->get('analytics/errors', 'Api\Analytics::errors');
    $routes->get('analytics/tool-logs', 'Api\Analytics::toolLogs');
    $routes->get('analytics/summary', 'Api\Analytics::summary');
    $routes->get('analytics/training-records', 'Api\Analytics::trainingRecords');
    $routes->delete('analytics/training-records/(:num)', 'Api\Analytics::deleteTrainingRecord/$1');
    $routes->get('analytics/learning-history', 'Api\Analytics::learningHistory');
    $routes->get('analytics/providers', 'Api\Analytics::providers');
    $routes->get('analytics/duplicates', 'Api\Analytics::duplicates');
    $routes->post('analytics/optimize-training', 'Api\Analytics::optimizeTraining');
    $routes->get('analytics/global-search', 'Api\Analytics::globalSearch');
    $routes->get('analytics/stream', 'Api\Analytics::streamTelemetry');

    // Self-Improvement & Knowledge Graph
    $routes->get('improvement/flaws', 'Api\SelfImprovement::flaws');
    $routes->get('improvement/experiments', 'Api\SelfImprovement::experiments');
    $routes->post('improvement/experiments', 'Api\SelfImprovement::createExperiment');
    $routes->get('improvement/approvals', 'Api\SelfImprovement::approvals');
    $routes->post('improvement/approvals/(:num)/approve', 'Api\SelfImprovement::approve/$1');
    $routes->post('improvement/approvals/(:num)/reject', 'Api\SelfImprovement::reject/$1');
    $routes->get('improvement/triples', 'Api\SelfImprovement::triples');

    // General Human Approval System API
    $routes->get('approvals', 'Api\Approval::list');
    $routes->post('approvals/create', 'Api\Approval::create');
    $routes->post('approvals/(:num)/approve', 'Api\Approval::approve/$1');
    $routes->post('approvals/(:num)/reject', 'Api\Approval::reject/$1');

    // Memory 2.0 Subsystem API
    $routes->get('memory', 'Api\Memory::list');
    $routes->post('memory', 'Api\Memory::create');
    $routes->put('memory/(:num)', 'Api\Memory::update/$1');
    $routes->delete('memory/(:num)', 'Api\Memory::delete/$1');
    $routes->post('memory/clear', 'Api\Memory::clear');

    // Background Job Queue System API
    $routes->get('jobs', 'Api\Jobs::list');
    $routes->post('jobs/dispatch', 'Api\Jobs::dispatch');
    $routes->post('jobs/process-next', 'Api\Jobs::processNext');
    $routes->post('jobs/(:num)/retry', 'Api\Jobs::retry/$1');
    $routes->post('jobs/(:num)/cancel', 'Api\Jobs::cancel/$1');

    // Plugin / Skill System API
    $routes->get('skills', 'Api\Skills::list');
    $routes->post('skills/(:segment)/enable', 'Api\Skills::enable/$1');
    $routes->post('skills/(:segment)/disable', 'Api\Skills::disable/$1');
    $routes->get('skills/history', 'Api\Skills::history');
    $routes->get('skills/(:segment)/history', 'Api\Skills::history/$1');

    // Unified Telemetry & Observability API
    $routes->get('telemetry/metrics', 'Api\Telemetry::metrics');
    $routes->get('telemetry/spans', 'Api\Telemetry::spans');

    // API Version 1 (v1) Versioned Endpoint Group
    $routes->group('v1', static function ($routes) {
        $routes->get('chats', 'Api\Chats::index');
        $routes->post('chats', 'Api\Chats::create');
        $routes->get('chats/(:num)', 'Api\Chats::show/$1');
        $routes->get('memory', 'Api\Memory::list');
        $routes->post('memory', 'Api\Memory::create');
        $routes->get('approvals', 'Api\Approval::list');
        $routes->get('jobs', 'Api\Jobs::list');
        $routes->get('skills', 'Api\Skills::list');
        $routes->get('telemetry/metrics', 'Api\Telemetry::metrics');
    });

    // Chats
    $routes->get('chats', 'Api\Chats::index');
    $routes->post('chats', 'Api\Chats::create');
    $routes->get('chats/(:num)', 'Api\Chats::show/$1');
    $routes->put('chats/(:num)', 'Api\Chats::update/$1');
    $routes->delete('chats/(:num)', 'Api\Chats::delete/$1');
    $routes->get('chats/(:num)/messages', 'Api\Chats::messages/$1');
    $routes->post('chats/(:num)/messages', 'Api\Chats::addMessage/$1');

    // Messages
    $routes->get('messages/(:num)', 'Api\Messages::show/$1');
    $routes->put('messages/(:num)', 'Api\Messages::update/$1');
    $routes->delete('messages/(:num)', 'Api\Messages::delete/$1');

    // Prompts
    $routes->get('prompts', 'Api\Prompts::index');
    $routes->post('prompts', 'Api\Prompts::create');
    $routes->get('prompts/(:num)', 'Api\Prompts::show/$1');
    $routes->put('prompts/(:num)', 'Api\Prompts::update/$1');
    $routes->delete('prompts/(:num)', 'Api\Prompts::delete/$1');

    // Notes
    $routes->get('notes', 'Api\Notes::index');
    $routes->post('notes', 'Api\Notes::create');
    $routes->get('notes/(:num)', 'Api\Notes::show/$1');
    $routes->put('notes/(:num)', 'Api\Notes::update/$1');
    $routes->delete('notes/(:num)', 'Api\Notes::delete/$1');

    // AI Models
    $routes->get('models', 'Api\AiModels::index');
    $routes->post('models', 'Api\AiModels::create');
    $routes->get('models/(:num)', 'Api\AiModels::show/$1');
    $routes->put('models/(:num)', 'Api\AiModels::update/$1');
    $routes->delete('models/(:num)', 'Api\AiModels::delete/$1');

    // Settings
    $routes->get('settings', 'Api\Settings::index');
    $routes->get('settings/(:any)', 'Api\Settings::show/$1');
    $routes->post('settings', 'Api\Settings::create');
    $routes->put('settings/(:any)', 'Api\Settings::update/$1');
    $routes->delete('settings/(:any)', 'Api\Settings::delete/$1');

    // Knowledge
    $routes->get('knowledge/search', 'Api\Knowledge::search');
    $routes->get('knowledge', 'Api\Knowledge::index');
    $routes->post('knowledge', 'Api\Knowledge::create');
    $routes->get('knowledge/(:num)', 'Api\Knowledge::show/$1');
    $routes->put('knowledge/(:num)', 'Api\Knowledge::update/$1');
    $routes->delete('knowledge/(:num)', 'Api\Knowledge::delete/$1');

    // Files
    $routes->get('files', 'Api\Files::index');
    $routes->post('files', 'Api\Files::create');
    $routes->get('files/(:num)', 'Api\Files::show/$1');
    $routes->delete('files/(:num)', 'Api\Files::delete/$1');

    // Plugins
    $routes->get('plugins', 'Api\Plugins::index');
    $routes->post('plugins', 'Api\Plugins::create');
    $routes->get('plugins/(:num)', 'Api\Plugins::show/$1');
    $routes->put('plugins/(:num)', 'Api\Plugins::update/$1');
    $routes->delete('plugins/(:num)', 'Api\Plugins::delete/$1');

    // AI Chat
    $routes->post('chat/(:num)/send', 'Api\AiChat::send/$1');
    $routes->post('chat/(:num)/preview', 'Api\AiChat::preview/$1');
    $routes->post('chat/(:num)/stream', 'Api\AiChat::stream/$1');

    // Sync
    $routes->get('sync', 'Api\Sync::pull');
    $routes->post('sync', 'Api\Sync::push');

    // Profile Management
    $routes->get('profile', 'Api\Profile::index');
    $routes->post('profile', 'Api\Profile::updateProfile');
    $routes->post('profile/biometrics', 'Api\Profile::updateBiometrics');
    $routes->post('profile/image', 'Api\Profile::uploadImage');
    $routes->post('profile/biometric-photo', 'Api\Profile::uploadBiometricPhoto');
    $routes->get('profile/export', 'Api\Profile::exportData');
    $routes->post('profile/wipe-conversations', 'Api\Profile::wipeConversations');
    $routes->post('profile/wipe-memories', 'Api\Profile::wipeMemories');
    $routes->post('profile/wipe-training', 'Api\Profile::wipeTraining');
    $routes->post('profile/wipe-knowledge', 'Api\Profile::wipeKnowledge');
    $routes->post('profile/wipe-face', 'Api\Profile::wipeFace');
    $routes->post('profile/reset', 'Api\Profile::resetPersonalization');
});

// Versioned v1 API routes mapping
$routes->post('api/v1/auth/login', 'Api\Auth::login');

$routes->group('api/v1', ['filter' => 'auth'], static function ($routes) {
    $routes->post('auth/logout', 'Api\Auth::logout');
    $routes->get('user/profile', 'Api\Profile::index');

    $routes->post('chat', 'Api\AiChat::complete');
    $routes->get('conversations', 'Api\Chats::index');
    $routes->get('conversations/(:num)', 'Api\Chats::show/$1');
    $routes->delete('conversations/(:num)', 'Api\Chats::delete/$1');

    $routes->get('memory', 'Api\Notes::index');
    $routes->get('memory/stats', 'Api\Settings::index');

    $routes->get('knowledge', 'Api\Knowledge::index');
    $routes->post('knowledge/upload', 'Api\Knowledge::create');
    $routes->delete('knowledge/(:num)', 'Api\Knowledge::delete/$1');

    $routes->get('learning', 'Api\Health::index');
    $routes->get('projects', 'Api\Files::index');
    $routes->get('workspace', 'Api\Files::index');
    $routes->get('provider/status', 'Api\AiModels::index');
    $routes->get('system/status', 'Api\Health::index');

    // Controlled Agent Orchestration Engine Routes
    $routes->post('agents/tasks', 'Api\Agents::createTask');
    $routes->get('agents/tasks', 'Api\Agents::getTasks');
    $routes->get('agents/tasks/(:num)', 'Api\Agents::getTask/$1');
    $routes->post('agents/tasks/(:num)/cancel', 'Api\Agents::cancelTask/$1');
    $routes->get('agents/tasks/(:num)/steps', 'Api\Agents::getTaskSteps/$1');
    $routes->get('agents/tasks/(:num)/stream', 'Api\Agents::streamTaskEvents/$1');

    // Knowledge Records & Documents Management
    $routes->get('knowledge', 'Api\Knowledge::index');
    $routes->get('knowledge/(:num)', 'Api\Knowledge::show/$1');
    $routes->post('knowledge', 'Api\Knowledge::create');
    $routes->put('knowledge/(:num)', 'Api\Knowledge::update/$1');
    $routes->delete('knowledge/(:num)', 'Api\Knowledge::delete/$1');
    $routes->get('knowledge/documents', 'Api\Knowledge::documents');
    $routes->post('knowledge/upload', 'Api\Knowledge::upload');

    // Autonomous Workflow Engine Routes
    $routes->get('workflows', 'Api\Workflows::getWorkflows');
    $routes->post('workflows', 'Api\Workflows::createWorkflow');
    $routes->post('workflows/(:num)/execute', 'Api\Workflows::executeWorkflow/$1');
    $routes->get('workflows/executions', 'Api\Workflows::getExecutions');
    $routes->get('workflows/executions/(:num)', 'Api\Workflows::getExecution/$1');
    $routes->get('workflows/executions/(:num)/stream', 'Api\Workflows::streamExecutionEvents/$1');

    // Multi-Agent Collaboration & Controlled Agent Swarm Engine Routes
    $routes->get('agents/definitions', 'Api\Swarms::getDefinitions');
    $routes->get('swarms', 'Api\Swarms::getSwarms');
    $routes->post('swarms', 'Api\Swarms::createSwarm');
    $routes->get('swarms/(:num)', 'Api\Swarms::getSwarm/$1');
    $routes->get('swarms/(:num)/stream', 'Api\Swarms::streamSwarmEvents/$1');

    // Agent Evaluation, Simulation & Continuous Improvement Platform Routes
    $routes->get('evaluations/datasets', 'Api\Evaluations::getDatasets');
    $routes->post('evaluations/datasets', 'Api\Evaluations::createDataset');
    $routes->get('evaluations/runs', 'Api\Evaluations::getRuns');
    $routes->post('evaluations/runs', 'Api\Evaluations::createRun');
    $routes->post('evaluations/compare', 'Api\Evaluations::compareCandidate');

    // Production Intelligence, Safety Governance & Adaptive Model / Agent Routing Routes
    $routes->get('routing/policies', 'Api\Routing::getPolicies');
    $routes->get('routing/candidates', 'Api\Routing::getCandidates');
    $routes->post('routing/select', 'Api\Routing::selectCandidate');
    $routes->get('routing/decisions', 'Api\Routing::getDecisions');

    // Unified Policy, Governance, Trust & Compliance Control Plane Routes
    $routes->get('governance/policies', 'Api\Governance::getPolicies');
    $routes->post('governance/policies/simulate', 'Api\Governance::simulatePolicy');
    $routes->get('governance/decisions', 'Api\Governance::getDecisions');
    $routes->post('governance/kill-switch', 'Api\Governance::toggleKillSwitch');

    // Phase 23 — Personal AI Brain Routes
    $routes->get('brain/status', 'Api\Brain::status');
    $routes->get('brain/context', 'Api\Brain::context');
    $routes->post('brain/reset-context', 'Api\Brain::resetContext');
    $routes->get('brain/intent', 'Api\Brain::classifyIntent');

    // Phase 24 — Multi-Modal Speech, Voice & Vision Routes
    $routes->post('voice/synthesize', 'Api\Voice::synthesize');
    $routes->post('voice/transcribe', 'Api\Voice::transcribe');
    $routes->get('voice/voices', 'Api\Voice::getVoices');
    $routes->post('voice/equalizer/apply', 'Api\Voice::applyEqualizer');
    $routes->get('voice/equalizer/presets', 'Api\Voice::getEqualizerPresets');
    $routes->post('voice/equalizer/curve', 'Api\Voice::getEqualizerCurve');
    $routes->get('voice/equalizer/state', 'Api\Voice::getEqualizerState');
    $routes->post('vision/analyze', 'Api\Vision::analyze');
    $routes->post('vision/screenshot-debug', 'Api\Vision::debugScreenshot');

    // Phase 25 — Proactive Daemon & Autonomous Life-Cycle Routes
    $routes->get('daemon/status', 'Api\Daemon::status');
    $routes->post('daemon/pulse', 'Api\Daemon::pulse');
    $routes->get('daemon/briefing', 'Api\Daemon::briefing');
    $routes->post('daemon/briefing/generate', 'Api\Daemon::generateBriefing');
    $routes->get('daemon/healing-log', 'Api\Daemon::healingLog');

    // Phase 26 — Developer IDE Protocol & LSP Routes
    $routes->post('lsp/rpc', 'Api\Lsp::rpc');
    $routes->post('lsp/complete', 'Api\Lsp::complete');
    $routes->post('lsp/hover', 'Api\Lsp::hover');
    $routes->post('lsp/refactor', 'Api\Lsp::refactor');
    $routes->get('lsp/capabilities', 'Api\Lsp::capabilities');

    // Phase 27 — Desktop Automation & Native OS Sidecar Routes
    $routes->get('desktop/status', 'Api\Desktop::status');
    $routes->post('desktop/clipboard/analyze', 'Api\Desktop::analyzeClipboard');
    $routes->post('desktop/window/focus', 'Api\Desktop::focusWindow');
    $routes->post('desktop/notify', 'Api\Desktop::notify');
    $routes->post('desktop/action', 'Api\Desktop::action');

    // Phase 28 — Real-Time WebSocket & Cross-Device Sync Routes
    $routes->get('sync/peers', 'Api\Sync::peers');
    $routes->post('sync/register', 'Api\Sync::register');
    $routes->post('sync/push', 'Api\Sync::push');
    $routes->post('sync/pull', 'Api\Sync::pull');
    $routes->post('sync/broadcast', 'Api\Sync::broadcast');

    // Phase 29 — Autonomous Test Generation & CI/CD Pipeline Routes
    $routes->post('cicd/test/generate', 'Api\Cicd::generateTest');
    $routes->post('cicd/test/run', 'Api\Cicd::runTests');
    $routes->post('cicd/repair', 'Api\Cicd::repair');
    $routes->get('cicd/pipelines', 'Api\Cicd::pipelines');
    $routes->post('cicd/pipeline/trigger', 'Api\Cicd::triggerPipeline');

    // Phase 30 — Long-Horizon Planning & Graph-of-Thought (GoT) Routes
    $routes->post('planning/decompose', 'Api\Planning::decompose');
    $routes->post('planning/search', 'Api\Planning::search');
    $routes->post('planning/execute-step', 'Api\Planning::executeStep');
    $routes->get('planning/tree/(:any)', 'Api\Planning::showTree/$1');
    $routes->post('planning/rollback', 'Api\Planning::rollback');

    // Phase 31 — Mathematical, Algorithmic & Symbolic Computation Routes
    $routes->post('compute/solve', 'Api\Computation::solve');
    $routes->post('compute/matrix', 'Api\Computation::matrix');
    $routes->post('compute/statistics', 'Api\Computation::statistics');
    $routes->post('compute/geometry', 'Api\Computation::geometry');
    $routes->post('compute/complexity', 'Api\Computation::complexity');

    // Phase 32 — Sandboxed Plugin Marketplace Routes
    $routes->get('marketplace/plugins', 'Api\Marketplace::index');
    $routes->post('marketplace/install', 'Api\Marketplace::install');
    $routes->post('marketplace/uninstall', 'Api\Marketplace::uninstall');
    $routes->post('marketplace/toggle', 'Api\Marketplace::toggle');
    $routes->post('marketplace/execute', 'Api\Marketplace::execute');

    // Phase 33 — Federated Zero-Knowledge Vault & Sync Routes
    $routes->post('vault/unlock', 'Api\Vault::unlock');
    $routes->post('vault/store', 'Api\Vault::store');
    $routes->post('vault/retrieve', 'Api\Vault::retrieve');
    $routes->get('vault/merkle-root', 'Api\Vault::merkleRoot');
    $routes->post('vault/sync-deltas', 'Api\Vault::syncDeltas');

    // Phase 34 — Real-Time Voice Duplex & Continuous Audio Routes
    $routes->post('voice/duplex/start', 'Api\VoiceDuplex::start');
    $routes->post('voice/duplex/chunk', 'Api\VoiceDuplex::chunk');
    $routes->post('voice/duplex/interrupt', 'Api\VoiceDuplex::interrupt');
    $routes->post('voice/duplex/emotion', 'Api\VoiceDuplex::emotion');
    $routes->get('voice/duplex/state', 'Api\VoiceDuplex::state');

    // Phase 35 — Autonomous Code Refactoring & Micro-Architecture Routes
    $routes->post('refactor/smells', 'Api\Refactoring::smells');
    $routes->post('refactor/transform', 'Api\Refactoring::transform');
    $routes->post('refactor/dependencies', 'Api\Refactoring::dependencies');
    $routes->post('refactor/verify', 'Api\Refactoring::verify');

    // Phase 36 — Enterprise Multi-Tenant RBAC & ABAC Routes
    $routes->post('rbac/tenant/create', 'Api\Rbac::createTenant');
    $routes->post('rbac/check', 'Api\Rbac::check');
    $routes->post('rbac/token/generate', 'Api\Rbac::generateToken');
    $routes->post('rbac/token/revoke', 'Api\Rbac::revokeToken');
    $routes->get('rbac/matrix', 'Api\Rbac::matrix');

    // Phase 37 — Distributed Edge Swarm & WebRTC P2P Mesh Routes
    $routes->post('webrtc/peer/register', 'Api\WebRtcMesh::registerPeer');
    $routes->post('webrtc/sdp/offer', 'Api\WebRtcMesh::sdpOffer');
    $routes->post('webrtc/sdp/answer', 'Api\WebRtcMesh::sdpAnswer');
    $routes->post('webrtc/ice/candidate', 'Api\WebRtcMesh::iceCandidate');
    $routes->post('webrtc/gossip/sync', 'Api\WebRtcMesh::gossipSync');
    $routes->get('webrtc/topology', 'Api\WebRtcMesh::topology');

    // Phase 38 — Predictive Forecasting & Time-Series Anomaly Routes
    $routes->post('predictive/forecast', 'Api\PredictiveAnalytics::forecast');
    $routes->post('predictive/anomalies', 'Api\PredictiveAnalytics::anomalies');
    $routes->post('predictive/saturation', 'Api\PredictiveAnalytics::saturation');
    $routes->post('predictive/decompose', 'Api\PredictiveAnalytics::decompose');

    // Phase 39 — Autonomous Semantic Code Search & Vector Embedding Routes
    $routes->post('search/query', 'Api\SemanticSearch::query');
    $routes->post('search/index', 'Api\SemanticSearch::index');
    $routes->post('search/embed', 'Api\SemanticSearch::embed');
    $routes->post('search/hybrid', 'Api\SemanticSearch::hybrid');
    $routes->get('search/stats', 'Api\SemanticSearch::stats');

    // Phase 40 — Autonomous Self-Healing Infrastructure & Incident Response Routes
    $routes->post('incident/classify', 'Api\IncidentResponse::classify');
    $routes->post('incident/remediate', 'Api\IncidentResponse::remediate');
    $routes->post('incident/circuit/record', 'Api\IncidentResponse::recordCircuit');
    $routes->post('incident/postmortem', 'Api\IncidentResponse::postMortem');
    $routes->get('incident/status', 'Api\IncidentResponse::status');

    // Phase 41 — Multi-Modal Swarm Orchestration, Audio DSP & Neural Knowledge Routes
    $routes->get('swarm/topology', 'Api\SwarmOrchestration::topology');
    $routes->post('swarm/plan', 'Api\SwarmOrchestration::plan');
    $routes->post('swarm/consensus', 'Api\SwarmOrchestration::consensus');
    $routes->post('swarm/synthesize', 'Api\SwarmOrchestration::synthesize');
    $routes->get('audio-dsp/graph', 'Api\SwarmOrchestration::dspGraph');
    $routes->post('audio-dsp/fft', 'Api\SwarmOrchestration::dspFft');
    $routes->post('neural-knowledge/chunk', 'Api\SwarmOrchestration::chunk');
    $routes->post('neural-knowledge/similarity', 'Api\SwarmOrchestration::similarity');

    // Voice Synthesis & Ben 10 Tamil Base Reference Voice Routes
    $routes->post('voice/synthesize', 'Api\Voice::synthesize');
    $routes->post('voice/transcribe', 'Api\Voice::transcribe');
    $routes->get('voice/voices', 'Api\Voice::getVoices');
    $routes->get('voice/reference/profile', 'Api\Voice::getReferenceProfile');
    $routes->post('voice/reference/analyze', 'Api\Voice::analyzeReference');
    $routes->post('voice/reference/synthesize', 'Api\Voice::synthesizeReference');
    $routes->post('voice/equalizer/apply', 'Api\Voice::applyEqualizer');
    $routes->get('voice/equalizer/presets', 'Api\Voice::getEqualizerPresets');
    $routes->post('voice/equalizer/curve', 'Api\Voice::getEqualizerCurve');
    $routes->get('voice/equalizer/state', 'Api\Voice::getEqualizerState');

    // Telemetry & Runtime Error Logging & Auto-Fix Routes
    $routes->get('telemetry/metrics', 'Api\Telemetry::metrics');
    $routes->get('telemetry/spans', 'Api\Telemetry::spans');
    $routes->get('telemetry/errors', 'Api\Telemetry::getErrors');
    $routes->post('telemetry/errors', 'Api\Telemetry::logError');
    $routes->post('telemetry/errors/diagnose', 'Api\Telemetry::diagnose');
    $routes->post('telemetry/errors/autofix', 'Api\Telemetry::autoFix');
    $routes->post('telemetry/errors/resolve', 'Api\Telemetry::resolveError');
    $routes->delete('telemetry/errors', 'Api\Telemetry::clearErrors');

    // Phase 42 — Multi-Modal Neural Vision, Code OCR & UI Synthesizer Routes
    $routes->post('vision/analyze', 'Api\Vision::analyze');
    $routes->post('vision/screenshot-debug', 'Api\Vision::debugScreenshot');
    $routes->post('vision/ocr/code', 'Api\Vision::ocrCode');
    $routes->post('vision/ui/synthesize', 'Api\Vision::synthesizeUi');
    $routes->post('vision/diagram/schema', 'Api\Vision::synthesizeSchema');
    $routes->get('vision/presets', 'Api\Vision::presets');

    // Phase 43 — Codebase Dependency Graph & Circular Reference Resolver Routes
    $routes->get('dependency/graph', 'Api\DependencyGraph::graph');
    $routes->post('dependency/scan', 'Api\DependencyGraph::scan');
    $routes->post('dependency/cycles', 'Api\DependencyGraph::cycles');
    $routes->post('dependency/decouple', 'Api\DependencyGraph::decouple');
    $routes->get('dependency/metrics', 'Api\DependencyGraph::metrics');

    // Phase 44 — Edge-Native HNSW Vector Index & Semantic Embedding Retrieval Routes
    $routes->get('vector/index/stats', 'Api\VectorSearch::stats');
    $routes->post('vector/index/insert', 'Api\VectorSearch::insert');
    $routes->post('vector/search', 'Api\VectorSearch::search');
    $routes->post('vector/embed', 'Api\VectorSearch::embed');
    $routes->delete('vector/index/clear', 'Api\VectorSearch::clear');

    // Phase 45 — Post-Quantum Cryptography & Zero-Trust Key Exchange Routes
    $routes->post('pqc/kem/keypair', 'Api\PostQuantum::keypair');
    $routes->post('pqc/kem/encapsulate', 'Api\PostQuantum::encapsulate');
    $routes->post('pqc/kem/decapsulate', 'Api\PostQuantum::decapsulate');
    $routes->post('pqc/sign', 'Api\PostQuantum::sign');
    $routes->post('pqc/verify', 'Api\PostQuantum::verify');
    $routes->post('pqc/handshake', 'Api\PostQuantum::handshake');

    // Phase 46 — Real-Time Voice Duplex Stream & Dynamic Formant Shifter Routes
    $routes->post('voice/stream/session/start', 'Api\VoiceStream::startSession');
    $routes->post('voice/stream/chunk', 'Api\VoiceStream::processChunk');
    $routes->post('voice/stream/formants/set', 'Api\VoiceStream::setFormants');
    $routes->get('voice/stream/session/stats', 'Api\VoiceStream::sessionStats');
    $routes->delete('voice/stream/session/stop', 'Api\VoiceStream::stopSession');

    // Phase 47 — Autonomous AST Code Modernizer & OWASP Security Auto-Patcher Routes
    $routes->post('modernizer/upgrade', 'Api\CodeModernizer::upgrade');
    $routes->post('modernizer/scan-security', 'Api\CodeModernizer::scanSecurity');
    $routes->post('modernizer/auto-patch', 'Api\CodeModernizer::autoPatch');
    $routes->get('modernizer/rules', 'Api\CodeModernizer::rules');

    // Phase 48 — Dynamic Attribute-Based Access Control (ABAC) & Zero-Trust Firewall Routes
    $routes->post('abac/evaluate', 'Api\AbacPolicy::evaluate');
    $routes->get('abac/policies', 'Api\AbacPolicy::listPolicies');
    $routes->post('abac/policies', 'Api\AbacPolicy::savePolicy');
    $routes->delete('abac/policies/(:segment)', 'Api\AbacPolicy::deletePolicy/$1');
    $routes->post('abac/simulate', 'Api\AbacPolicy::simulate');

    // Phase 49 — Distributed Edge Cron Scheduler & Raft Failover Hub Routes
    $routes->get('cron/jobs', 'Api\CronScheduler::listJobs');
    $routes->post('cron/jobs', 'Api\CronScheduler::createJob');
    $routes->post('cron/jobs/trigger', 'Api\CronScheduler::triggerJob');
    $routes->post('cron/lease/renew', 'Api\CronScheduler::renewLease');
    $routes->get('cron/cluster/status', 'Api\CronScheduler::clusterStatus');
    $routes->delete('cron/jobs/(:segment)', 'Api\CronScheduler::deleteJob/$1');

    // Phase 50 — Autonomous Multi-Modal Platform Command Center Routes
    $routes->get('command-center/platform-status', 'Api\CommandCenter::platformStatus');
    $routes->post('command-center/dispatch', 'Api\CommandCenter::dispatch');
    $routes->post('command-center/run-diagnostics', 'Api\CommandCenter::runDiagnostics');
    $routes->post('command-center/heal', 'Api\CommandCenter::heal');

    // Phase 51 — Autonomous AST Performance Profiler & Complexity Analyzer Routes
    $routes->post('profiler/analyze', 'Api\PerformanceProfiler::analyze');
    $routes->post('profiler/optimize', 'Api\PerformanceProfiler::optimize');
    $routes->get('profiler/metrics', 'Api\PerformanceProfiler::metrics');

    // Phase 52 — Autonomous SQL Query Index Optimizer & Migration Routes
    $routes->post('database/query-optimizer/analyze', 'Api\QueryOptimizer::analyze');
    $routes->post('database/query-optimizer/generate-migration', 'Api\QueryOptimizer::generateMigration');
    $routes->get('database/query-optimizer/rules', 'Api\QueryOptimizer::rules');

    // Phase 53 — Real-Time Webhook Dispatcher & HMAC-SHA256 Hub Routes
    $routes->get('webhooks/subscriptions', 'Api\WebhookHub::listSubscriptions');
    $routes->post('webhooks/subscriptions', 'Api\WebhookHub::createSubscription');
    $routes->post('webhooks/dispatch', 'Api\WebhookHub::dispatch');
    $routes->get('webhooks/dlq', 'Api\WebhookHub::listDlq');
    $routes->post('webhooks/dlq/replay', 'Api\WebhookHub::replayDlq');

    // Phase 54 — Autonomous AST Dead Code Eliminator Routes
    $routes->post('refactoring/dead-code/scan', 'Api\DeadCodePruner::scan');
    $routes->post('refactoring/dead-code/prune', 'Api\DeadCodePruner::prune');

    // Phase 55 — Zero-Knowledge Federated Learning Routes
    $routes->get('federated-learning/weights', 'Api\FederatedLearning::getWeights');
    $routes->post('federated-learning/aggregate', 'Api\FederatedLearning::aggregate');

    // Phase 56 — Multi-Tenant Zero-Trust Rate Limiter Routes
    $routes->post('rate-limiter/check', 'Api\RateLimiter::check');
    $routes->get('rate-limiter/metrics', 'Api\RateLimiter::metrics');

    // Phase 57 — Autonomous OpenAPI 3.1 & SDK Routes
    $routes->get('docs/openapi.json', 'Api\OpenApiSdk::openApiJson');
    $routes->post('docs/generate-sdk', 'Api\OpenApiSdk::generateSdk');

    // Phase 58 — Real-Time Audio Spectral Noise Filter Routes
    $routes->post('voice/filter/denoise', 'Api\AcousticFilter::denoise');
    $routes->get('voice/filter/presets', 'Api\AcousticFilter::presets');

    // Phase 59 — Autonomous Git VCS Semantic Release Routes
    $routes->post('vcs/release/analyze', 'Api\SemanticRelease::analyze');
    $routes->get('vcs/release/history', 'Api\SemanticRelease::history');

    // Phase 60 — Real-Time Distributed Tracing & OpenTelemetry Routes
    $routes->get('tracing/traces', 'Api\DistributedTracing::listTraces');
    $routes->post('tracing/spans/start', 'Api\DistributedTracing::startSpan');
    $routes->post('tracing/spans/end', 'Api\DistributedTracing::endSpan');

    // Phase 61 — Database Query Load Simulator Routes
    $routes->post('database/load-simulator/run', 'Api\QueryLoadSimulator::run');
    $routes->get('database/load-simulator/presets', 'Api\QueryLoadSimulator::presets');

    // Phase 62 — Real-Time Voice Harmonizer & Autotune Routes
    $routes->post('voice/harmonizer/correct-pitch', 'Api\VoiceHarmonizer::correctPitch');
    $routes->post('voice/harmonizer/generate-harmonies', 'Api\VoiceHarmonizer::generateHarmonies');
    $routes->get('voice/harmonizer/scales', 'Api\VoiceHarmonizer::scales');

    // Phase 63 — Autonomous AST Code Linter & PSR-12 Auto-Fixer Routes
    $routes->post('refactoring/linter/scan', 'Api\CodeLinter::scan');
    $routes->post('refactoring/linter/fix', 'Api\CodeLinter::fix');

    // Phase 64 — Zero-Trust IP Geolocation & Geo-Fencing Routes
    $routes->post('security/geofence/evaluate', 'Api\GeoFirewall::evaluate');
    $routes->post('security/geofence/lookup', 'Api\GeoFirewall::lookup');
    $routes->get('security/geofence/policy', 'Api\GeoFirewall::policy');

    // Phase 65 — Database Schema Drift Detector & Auto-Sync Routes
    $routes->post('database/schema/detect-drift', 'Api\SchemaDrift::detectDrift');
    $routes->post('database/schema/generate-migration', 'Api\SchemaDrift::generateMigration');

    // Phase 66 — WebRTC Peer Data Channel File Transfer Routes
    $routes->post('network/webrtc/transfer/prepare', 'Api\WebRtcTransfer::prepare');
    $routes->post('network/webrtc/transfer/ingest-chunk', 'Api\WebRtcTransfer::ingestChunk');
    $routes->post('network/webrtc/transfer/reassemble', 'Api\WebRtcTransfer::reassemble');

    // Phase 67 — Latency Heatmap & SLA Alert Routes
    $routes->get('telemetry/heatmap/matrix', 'Api\LatencyHeatmap::matrix');
    $routes->post('telemetry/heatmap/record', 'Api\LatencyHeatmap::record');
    $routes->get('telemetry/heatmap/sla', 'Api\LatencyHeatmap::sla');

    // Phase 68 — Audio Emotion & Acoustic Mood Routes
    $routes->post('voice/emotion/classify', 'Api\AudioEmotion::classify');
    $routes->get('voice/emotion/moods', 'Api\AudioEmotion::moods');

    // Phase 69 — Natural Conversational Dialogue & Persona Routes
    $routes->post('brain/dialogue/respond', 'Api\NaturalDialogue::respond');
    $routes->post('brain/dialogue/teach', 'Api\NaturalDialogue::teach');
    $routes->post('brain/dialogue/english-hint', 'Api\NaturalDialogue::englishHint');

    // Phase 70 Landmark — Distributed Cache Invalidation & Stampede Shield Routes
    $routes->get('database/cache/stats', 'Api\CacheInvalidator::stats');
    $routes->post('database/cache/set', 'Api\CacheInvalidator::setKey');
    $routes->post('database/cache/get', 'Api\CacheInvalidator::getKey');
    $routes->post('database/cache/invalidate-tag', 'Api\CacheInvalidator::invalidateTag');

    // Phase 71 — Canary Deployment & Traffic Split Routes
    $routes->get('infrastructure/canary/status', 'Api\CanaryGovernor::status');
    $routes->post('infrastructure/canary/route', 'Api\CanaryGovernor::route');
    $routes->post('infrastructure/canary/update-weights', 'Api\CanaryGovernor::updateWeights');
    $routes->post('infrastructure/canary/record-telemetry', 'Api\CanaryGovernor::recordTelemetry');

    // Phase 72 — Dynamic SQL Query Explainer & Index Synthesizer Routes
    $routes->post('database/explainer/analyze', 'Api\QueryExplainer::analyze');
    $routes->post('database/explainer/suggest-indexes', 'Api\QueryExplainer::suggestIndexes');

    // Phase 73 — Audio Vocal Isolator & Stem Separator Routes
    $routes->post('voice/stems/separate', 'Api\AudioStems::separate');
    $routes->post('voice/stems/mix', 'Api\AudioStems::mix');
    $routes->get('voice/stems/bands', 'Api\AudioStems::bands');

    // Phase 74 — API Schema Fuzzer & Vulnerability Scanner Routes
    $routes->post('testing/fuzzer/scan', 'Api\ApiFuzzer::scan');
    $routes->get('testing/fuzzer/payloads', 'Api\ApiFuzzer::payloads');

    // Phase 75 — Edge IoT Telemetry Ingest & Watchdog Routes
    $routes->get('network/iot/fleet-status', 'Api\IoTWatchdog::fleetStatus');
    $routes->post('network/iot/ingest', 'Api\IoTWatchdog::ingest');
    $routes->post('network/iot/register-device', 'Api\IoTWatchdog::registerDevice');

    // Phase 76 — Semantic Code Chunk Splitter & Call-Tree Indexer Routes
    $routes->post('brain/chunker/split', 'Api\CodeChunker::split');
    $routes->post('brain/chunker/call-tree', 'Api\CodeChunker::callTree');

    // Phase 77 — Feature Flags & Gradual Rollout Matrix Routes
    $routes->get('config/flags/list', 'Api\FeatureFlags::list');
    $routes->post('config/flags/evaluate', 'Api\FeatureFlags::evaluate');
    $routes->post('config/flags/set', 'Api\FeatureFlags::setFlag');

    // Phase 78 — Real-Time Pitch Corrector & Vocal Harmonizer Routes
    $routes->post('voice/pitch/autotune', 'Api\PitchHarmonizer::autotune');
    $routes->post('voice/pitch/harmonize', 'Api\PitchHarmonizer::harmonize');
    $routes->get('voice/pitch/scales', 'Api\PitchHarmonizer::scales');

    // Phase 79 — Database Connection Pool Governor Routes
    $routes->get('database/pool/status', 'Api\ConnectionPool::status');
    $routes->post('database/pool/lease', 'Api\ConnectionPool::lease');
    $routes->post('database/pool/release', 'Api\ConnectionPool::release');
    $routes->post('database/pool/reclaim-leaks', 'Api\ConnectionPool::reclaimLeaks');

    // Phase 80 Landmark — Metacognitive Reasoning & Thought-Graph Pruning Routes
    $routes->post('brain/metacognition/reflect', 'Api\MetacognitiveBrain::reflect');
    $routes->post('brain/metacognition/prune-graph', 'Api\MetacognitiveBrain::pruneGraph');
    $routes->get('brain/metacognition/metrics', 'Api\MetacognitiveBrain::metrics');

    // Phase 81 — Autonomous Chaos Engineering & Failure Injection Routes
    $routes->get('infrastructure/chaos/experiments', 'Api\ChaosGovernor::experiments');
    $routes->post('infrastructure/chaos/start', 'Api\ChaosGovernor::start');
    $routes->post('infrastructure/chaos/stop', 'Api\ChaosGovernor::stop');
    $routes->post('infrastructure/chaos/evaluate', 'Api\ChaosGovernor::evaluate');

    // Phase 82 — Video Keyframe Extractor & Scene Segmenter Routes
    $routes->post('vision/video/segment', 'Api\VideoSegmenter::segment');
    $routes->post('vision/video/keyframes', 'Api\VideoSegmenter::keyframes');
    $routes->get('vision/video/codecs', 'Api\VideoSegmenter::codecs');

    // Phase 83 — GraphQL Query Complexity Guard Routes
    $routes->post('api/graphql/analyze', 'Api\GraphQLGuard::analyze');
    $routes->get('api/graphql/budgets', 'Api\GraphQLGuard::budgets');

    // Phase 84 — Markdown-to-PDF Streaming Renderer Routes
    $routes->post('documents/render/pdf', 'Api\DocumentRenderer::renderPdf');
    $routes->get('documents/templates', 'Api\DocumentRenderer::templates');

    // Phase 85 — Dynamic Circuit Breaker & Fallback Mesh Routes
    $routes->get('infrastructure/circuit/services', 'Api\CircuitMesh::services');
    $routes->post('infrastructure/circuit/execute', 'Api\CircuitMesh::execute');
    $routes->post('infrastructure/circuit/reset', 'Api\CircuitMesh::reset');
});

// Public unauthenticated error ingest route (ensures client-side errors log even if session is expired)
$routes->post('api/telemetry/errors', 'Api\Telemetry::logError');


















































