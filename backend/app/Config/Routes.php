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
$routes->get('chat', 'Home::chat');
$routes->get('admin.php', 'Home::serve/admin.php');
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

    // Knowledge Documents management
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
});







