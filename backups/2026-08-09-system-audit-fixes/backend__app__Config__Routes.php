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

// AI operations (no auth required for quick actions)
$routes->post('api/ai/complete', 'Api\AiChat::complete');
$routes->post('api/ai/models', 'Api\AiChat::listModels');

// Knowledge Documents management (Public API routes)
$routes->get('api/knowledge/documents', 'Api\Knowledge::documents');
$routes->post('api/knowledge/upload', 'Api\Knowledge::upload');
$routes->delete('api/knowledge/documents/(:num)', 'Api\Knowledge::deleteDocument/$1');
$routes->post('api/knowledge/documents/(:num)/train', 'Api\Knowledge::trainDocument/$1');

// Protected API routes
$routes->group('api', ['filter' => 'auth'], static function ($routes) {
    $routes->get('auth/me', 'Api\Auth::me');

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
$routes->group('api/v1', static function ($routes) {
    $routes->post('auth/login', 'Api\Auth::login');
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
});

