<?php
// ATOM Collapsible & Streamlined Admin Sidebar Component
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$currentPage = basename($scriptName, '.php');
$inDirectAdminDir = (basename(dirname($scriptName)) === 'admin');

// Helper to resolve admin link paths
$getAdminUrl = function(string $route) use ($inDirectAdminDir) {
    if ($route === 'dashboard') {
        return $inDirectAdminDir ? 'index.php' : '/admin';
    }
    if ($route === 'chat') {
        return $inDirectAdminDir ? '../chat.php' : '/chat';
    }
    return $inDirectAdminDir ? ($route . '.php') : ('/admin/' . $route);
};

// Helper to check active state
$isActive = function(string $route) use ($currentPage) {
    if ($route === 'dashboard' && ($currentPage === 'index' || $currentPage === 'dashboard')) {
        return true;
    }
    return ($currentPage === $route);
};

// List of milestone phase lab pages to determine auto-expansion of Labs drawer
$labPages = [
    'voice_duplex_stream', 'acoustic_filter', 'voice_harmonizer', 'audio_emotion',
    'dialogue_studio', 'audio_stems', 'pitch_studio', 'compressor_studio',
    'spatializer_studio', 'video_studio', 'century_studio', 'code_chunker',
    'metacognition_studio', 'governor_studio', 'vector_studio', 'planning',
    'performance_profiler', 'dead_code_pruner', 'dependency_graph', 'refactoring',
    'code_linter', 'cicd', 'openapi_studio', 'document_studio', 'pqc_studio',
    'abac_studio', 'rate_limiter', 'geofence_studio', 'api_fuzzer', 'graphql_studio',
    'zkp_studio', 'ratelimit_mesh_studio', 'query_optimizer', 'query_load_simulator',
    'schema_drift', 'cache_studio', 'query_explainer', 'pool_studio', 'shard_studio',
    'etl_studio', 'migration_studio', 'distributed_tracing', 'canary_studio',
    'circuit_studio', 'proxy_studio', 'stream_compressor_studio', 'event_mesh_studio',
    'webhook_dlq_studio', 'wasm_studio', 'event_sourcing_studio', 'feature_flags',
    'rollout_studio', 'chaos_studio', 'webhook_hub', 'federated_learning',
    'release_studio', 'webrtc_mesh', 'webrtc_transfer', 'iot_studio',
    'latency_heatmap', 'predictive_analytics', 'voice_duplex', 'equalizer',
    'computation', 'daemon', 'lsp', 'routing', 'governance', 'sync',
    'incident_response', 'semantic_search', 'training', 'evaluations'
];
$isCurrentInLabs = in_array($currentPage, $labPages, true);
?>
<aside id="adminSidebar" class="w-64 h-screen sticky top-0 bg-[#0c0f14] border-r border-[#1e2838] flex flex-col justify-between shrink-0 transition-all duration-300 z-40 select-none">
  
  <!-- Fixed Header: Logo & Instant Search Filter -->
  <div class="p-4 border-b border-[#1e2838] shrink-0">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white shadow-lg shadow-emerald-500/20">A</div>
      <div class="sidebar-label">
        <span class="text-sm font-bold tracking-tight text-white block">ATOM CONTROL</span>
        <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-wider">v2.0 • Assistant Hub</span>
      </div>
    </div>

    <!-- Quick Search Filter Box -->
    <div class="relative sidebar-label">
      <input type="text" id="sidebarSearchInput" placeholder="Quick find menu..." oninput="filterSidebarMenu(this.value)"
             class="w-full bg-[#111620] text-gray-200 text-xs px-3 py-1.5 pl-8 rounded-lg border border-[#1e2838] focus:border-emerald-500 focus:outline-none transition-all placeholder:text-gray-600">
      <svg class="w-3.5 h-3.5 text-gray-500 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
    </div>
  </div>

  <!-- Scrollable Navigation List (Optimized Smooth Scrollbar) -->
  <div class="flex-1 overflow-y-auto overflow-x-hidden custom-sidebar-scroll px-3 py-4 space-y-1">
    
    <!-- 1. Cockpit & Overview -->
    <div class="nav-category pt-1 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-indigo-400">Cockpit &amp; Overview</span>
    </div>

    <a href="<?= $getAdminUrl('dashboard') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('dashboard') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
      <span class="sidebar-label">Overview Dashboard</span>
    </a>

    <a href="<?= $getAdminUrl('command_center') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition-all <?= $isActive('command_center') ? 'bg-gradient-to-r from-indigo-900/80 to-purple-900/80 border border-indigo-500/50 text-white shadow-md shadow-indigo-500/20' : 'text-indigo-300 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
      <span class="sidebar-label">🌟 Command Center</span>
    </a>

    <a href="<?= $getAdminUrl('analytics') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('analytics') ? 'bg-[#1e2735] text-cyan-400 font-bold border-l-2 border-cyan-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
      <span class="sidebar-label">Observability &amp; Analytics</span>
    </a>

    <!-- 2. AI Brain & Cognition -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-purple-400">AI Brain &amp; Cognition</span>
    </div>

    <a href="<?= $getAdminUrl('brain') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('brain') ? 'bg-[#1e2735] text-purple-400 font-bold border-l-2 border-purple-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
      <span class="sidebar-label">Personal Brain Core</span>
    </a>

    <a href="<?= $getAdminUrl('providers') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('providers') ? 'bg-[#1e2735] text-sky-400 font-bold border-l-2 border-sky-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
      <span class="sidebar-label">AI Model Providers</span>
    </a>

    <a href="<?= $getAdminUrl('playground') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('playground') ? 'bg-[#1e2735] text-amber-400 font-bold border-l-2 border-amber-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <span class="sidebar-label">Model Playground</span>
    </a>

    <a href="<?= $getAdminUrl('agents') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('agents') ? 'bg-[#1e2735] text-indigo-400 font-bold border-l-2 border-indigo-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      <span class="sidebar-label">Autonomous Agents</span>
    </a>

    <a href="<?= $getAdminUrl('swarm') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('swarm') ? 'bg-[#1e2735] text-purple-400 font-bold border-l-2 border-purple-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">Swarm Orchestrator</span>
    </a>

    <a href="<?= $getAdminUrl('approvals') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('approvals') ? 'bg-[#1e2735] text-rose-400 font-bold border-l-2 border-rose-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span class="sidebar-label">🛡️ Human Approvals</span>
    </a>

    <!-- 3. Knowledge & Vector RAG -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-emerald-400">Knowledge &amp; RAG</span>
    </div>

    <a href="<?= $getAdminUrl('knowledge') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('knowledge') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
      <span class="sidebar-label">Knowledge Items (RAG)</span>
    </a>

    <a href="<?= $getAdminUrl('documents') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('documents') ? 'bg-[#1e2735] text-sky-400 font-bold border-l-2 border-sky-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
      <span class="sidebar-label">Document Ingestion</span>
    </a>

    <a href="<?= $getAdminUrl('vector_index') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vector_index') ? 'bg-[#1e2735] text-teal-400 font-bold border-l-2 border-teal-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">Vector Search Index</span>
    </a>

    <!-- 4. Multi-Modal Studios -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-pink-400">Multi-Modal Studios</span>
    </div>

    <a href="<?= $getAdminUrl('voice_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('voice_studio') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
      <span class="sidebar-label">ATOM Voice Studio</span>
    </a>

    <a href="<?= $getAdminUrl('vision_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vision_studio') ? 'bg-[#1e2735] text-blue-400 font-bold border-l-2 border-blue-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
      <span class="sidebar-label">Neural Vision &amp; OCR</span>
    </a>

    <a href="<?= $getAdminUrl('web_crawler_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('web_crawler_studio') ? 'bg-[#1e2735] text-cyan-400 font-bold border-l-2 border-cyan-400' : 'text-cyan-300 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
      <span class="sidebar-label">🌐 Web Crawler Studio</span>
    </a>

    <!-- 5. Automation & Extensions -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-amber-400">Automation &amp; Extensions</span>
    </div>

    <a href="<?= $getAdminUrl('skills') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('skills') ? 'bg-[#1e2735] text-amber-400 font-bold border-l-2 border-amber-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a2 2 0 002 2h1a2 2 0 110 4h-1a2 2 0 00-2 2v1a2 2 0 11-4 0v-1a2 2 0 00-2-2H7a2 2 0 110-4h1a2 2 0 002-2V4z"></path></svg>
      <span class="sidebar-label">Plugins &amp; Skills</span>
    </a>

    <a href="<?= $getAdminUrl('marketplace') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('marketplace') ? 'bg-[#1e2735] text-pink-400 font-bold border-l-2 border-pink-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
      <span class="sidebar-label">Plugin Marketplace</span>
    </a>

    <a href="<?= $getAdminUrl('workflows') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('workflows') ? 'bg-[#1e2735] text-indigo-400 font-bold border-l-2 border-indigo-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
      <span class="sidebar-label">Automation Workflows</span>
    </a>

    <a href="<?= $getAdminUrl('jobs') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('jobs') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
      <span class="sidebar-label">Background Jobs</span>
    </a>

    <a href="<?= $getAdminUrl('cron_scheduler') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('cron_scheduler') ? 'bg-[#1e2735] text-indigo-400 font-bold border-l-2 border-indigo-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <span class="sidebar-label">Scheduled Cron</span>
    </a>

    <!-- 6. Security & System -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-emerald-400">Security &amp; System</span>
    </div>

    <a href="<?= $getAdminUrl('vault') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vault') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
      <span class="sidebar-label">ZK Vault (AES-256)</span>
    </a>

    <a href="<?= $getAdminUrl('rbac') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('rbac') ? 'bg-[#1e2735] text-pink-400 font-bold border-l-2 border-pink-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span class="sidebar-label">Enterprise RBAC</span>
    </a>

    <a href="<?= $getAdminUrl('code_modernizer') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('code_modernizer') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">AST Modernizer</span>
    </a>

    <a href="<?= $getAdminUrl('desktop') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('desktop') ? 'bg-[#1e2735] text-cyan-400 font-bold border-l-2 border-cyan-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
      <span class="sidebar-label">Desktop Companion</span>
    </a>

    <a href="<?= $getAdminUrl('settings') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('settings') ? 'bg-[#1e2735] text-gray-200 font-bold border-l-2 border-gray-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
      <span class="sidebar-label">Platform Settings</span>
    </a>

    <!-- 7. Collapsible Advanced Milestone Studios & Labs (41-103) -->
    <div class="pt-3 pb-1 border-t border-[#1e2838]/60 mt-3">
      <details id="labsDetails" class="group" <?= $isCurrentInLabs ? 'open' : '' ?>>
        <summary class="flex items-center justify-between px-3 py-1.5 rounded-lg text-[10px] font-bold tracking-wider uppercase cursor-pointer hover:bg-[#161c28] text-gray-400 hover:text-white transition-all select-none list-none">
          <span class="flex items-center gap-2 sidebar-label">
            <span class="text-amber-400">🧪</span>
            <span>Milestone Studios &amp; Labs</span>
          </span>
          <svg class="w-3.5 h-3.5 transform transition-transform group-open:rotate-180 text-gray-500 sidebar-label" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </summary>

        <div class="mt-2 space-y-1 pl-1 border-l border-[#1e2838]/80 ml-2">

          <!-- Audio & Signal Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-purple-400/80 sidebar-label">Audio &amp; Voice DSP</span>
          </div>
          <a href="<?= $getAdminUrl('voice_duplex_stream') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('voice_duplex_stream') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Formant Shifter (46)</span>
          </a>
          <a href="<?= $getAdminUrl('acoustic_filter') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('acoustic_filter') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Acoustic Filter (58)</span>
          </a>
          <a href="<?= $getAdminUrl('voice_harmonizer') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('voice_harmonizer') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Voice Harmonizer (62)</span>
          </a>
          <a href="<?= $getAdminUrl('audio_emotion') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('audio_emotion') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Voice Emotion (68)</span>
          </a>
          <a href="<?= $getAdminUrl('dialogue_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('dialogue_studio') ? 'bg-[#1e2735] text-blue-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Natural Dialogue (69)</span>
          </a>
          <a href="<?= $getAdminUrl('audio_stems') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('audio_stems') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Stem Separator (73)</span>
          </a>
          <a href="<?= $getAdminUrl('pitch_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('pitch_studio') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Pitch Harmonizer (78)</span>
          </a>
          <a href="<?= $getAdminUrl('compressor_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('compressor_studio') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Audio Compressor (88)</span>
          </a>
          <a href="<?= $getAdminUrl('spatializer_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('spatializer_studio') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Audio Spatializer (94)</span>
          </a>

          <!-- Advanced AI & Vision Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-cyan-400/80 sidebar-label">Cognitive &amp; Vision Labs</span>
          </div>
          <a href="<?= $getAdminUrl('video_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('video_studio') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Video Segmenter (82)</span>
          </a>
          <a href="<?= $getAdminUrl('code_chunker') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('code_chunker') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Code Chunker (76)</span>
          </a>
          <a href="<?= $getAdminUrl('metacognition_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('metacognition_studio') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Metacognition (80)</span>
          </a>
          <a href="<?= $getAdminUrl('governor_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('governor_studio') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Cost Governor (89)</span>
          </a>
          <a href="<?= $getAdminUrl('vector_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('vector_studio') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Vector Studio (96)</span>
          </a>
          <a href="<?= $getAdminUrl('planning') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('planning') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">GoT Planning</span>
          </a>
          <a href="<?= $getAdminUrl('century_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('century_studio') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-amber-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label font-bold text-amber-300">Century Finale (100) 🏆</span>
          </a>

          <!-- Engineering, Code & AST Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-blue-400/80 sidebar-label">Engineering &amp; AST</span>
          </div>
          <a href="<?= $getAdminUrl('performance_profiler') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('performance_profiler') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Performance Profiler (51)</span>
          </a>
          <a href="<?= $getAdminUrl('dead_code_pruner') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('dead_code_pruner') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Dead Code Pruner (54)</span>
          </a>
          <a href="<?= $getAdminUrl('dependency_graph') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('dependency_graph') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Dependency Graph (43)</span>
          </a>
          <a href="<?= $getAdminUrl('refactoring') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('refactoring') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Refactoring Studio</span>
          </a>
          <a href="<?= $getAdminUrl('code_linter') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('code_linter') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">PSR-12 Linter (63)</span>
          </a>
          <a href="<?= $getAdminUrl('cicd') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('cicd') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">CI/CD &amp; Testing</span>
          </a>
          <a href="<?= $getAdminUrl('openapi_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('openapi_studio') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">OpenAPI &amp; SDKs (57)</span>
          </a>
          <a href="<?= $getAdminUrl('document_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('document_studio') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Document Studio (84)</span>
          </a>

          <!-- Zero-Trust & Security Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-pink-400/80 sidebar-label">Security &amp; Zero-Trust Labs</span>
          </div>
          <a href="<?= $getAdminUrl('pqc_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('pqc_studio') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Post-Quantum Crypto (45)</span>
          </a>
          <a href="<?= $getAdminUrl('abac_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('abac_studio') ? 'bg-[#1e2735] text-blue-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">ABAC Firewall (48)</span>
          </a>
          <a href="<?= $getAdminUrl('rate_limiter') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('rate_limiter') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Rate Limiter (56)</span>
          </a>
          <a href="<?= $getAdminUrl('geofence_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('geofence_studio') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Geo-Fence Firewall (64)</span>
          </a>
          <a href="<?= $getAdminUrl('api_fuzzer') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('api_fuzzer') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">API Schema Fuzzer (74)</span>
          </a>
          <a href="<?= $getAdminUrl('graphql_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('graphql_studio') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">GraphQL Guard (83)</span>
          </a>
          <a href="<?= $getAdminUrl('zkp_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('zkp_studio') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">ZKP Verifier (91)</span>
          </a>
          <a href="<?= $getAdminUrl('ratelimit_mesh_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('ratelimit_mesh_studio') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Token Mesh (99)</span>
          </a>

          <!-- Data, Storage & Sharding Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-emerald-400/80 sidebar-label">Data &amp; Storage Labs</span>
          </div>
          <a href="<?= $getAdminUrl('query_optimizer') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('query_optimizer') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">SQL Optimizer (52)</span>
          </a>
          <a href="<?= $getAdminUrl('query_load_simulator') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('query_load_simulator') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Query Load Sim (61)</span>
          </a>
          <a href="<?= $getAdminUrl('schema_drift') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('schema_drift') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Schema Drift Sync (65)</span>
          </a>
          <a href="<?= $getAdminUrl('cache_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('cache_studio') ? 'bg-[#1e2735] text-red-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Cache Invalidator (70)</span>
          </a>
          <a href="<?= $getAdminUrl('query_explainer') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('query_explainer') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">SQL Query Explainer (72)</span>
          </a>
          <a href="<?= $getAdminUrl('pool_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('pool_studio') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Connection Pool (79)</span>
          </a>
          <a href="<?= $getAdminUrl('shard_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('shard_studio') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Shard Router (87)</span>
          </a>
          <a href="<?= $getAdminUrl('etl_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('etl_studio') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Stream ETL (93)</span>
          </a>
          <a href="<?= $getAdminUrl('migration_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('migration_studio') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Schema Migrations (98)</span>
          </a>

          <!-- Network, Edge & Infrastructure Labs -->
          <div class="nav-category pt-2 pb-0.5">
            <span class="px-2 text-[9px] font-bold uppercase tracking-wider text-indigo-400/80 sidebar-label">Network &amp; Edge Infrastructure</span>
          </div>
          <a href="<?= $getAdminUrl('distributed_tracing') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('distributed_tracing') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-cyan-300 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Distributed Tracing (60)</span>
          </a>
          <a href="<?= $getAdminUrl('canary_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('canary_studio') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Canary Governor (71)</span>
          </a>
          <a href="<?= $getAdminUrl('circuit_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('circuit_studio') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Circuit Mesh (85)</span>
          </a>
          <a href="<?= $getAdminUrl('proxy_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('proxy_studio') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Reverse Proxy (86)</span>
          </a>
          <a href="<?= $getAdminUrl('stream_compressor_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('stream_compressor_studio') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Stream Framer (90)</span>
          </a>
          <a href="<?= $getAdminUrl('event_mesh_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('event_mesh_studio') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Event Mesh (92)</span>
          </a>
          <a href="<?= $getAdminUrl('webhook_dlq_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('webhook_dlq_studio') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Webhook DLQ (97)</span>
          </a>
          <a href="<?= $getAdminUrl('wasm_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('wasm_studio') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Wasm Sandbox (101)</span>
          </a>
          <a href="<?= $getAdminUrl('event_sourcing_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('event_sourcing_studio') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">CQRS Ledger (102)</span>
          </a>
          <a href="<?= $getAdminUrl('feature_flags') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('feature_flags') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Feature Flags (77)</span>
          </a>
          <a href="<?= $getAdminUrl('rollout_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('rollout_studio') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Flag Rollouts (95)</span>
          </a>
          <a href="<?= $getAdminUrl('chaos_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('chaos_studio') ? 'bg-[#1e2735] text-orange-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Chaos Mesh (81)</span>
          </a>
          <a href="<?= $getAdminUrl('webhook_hub') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('webhook_hub') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Event Webhooks (53)</span>
          </a>
          <a href="<?= $getAdminUrl('federated_learning') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('federated_learning') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Federated Learning (55)</span>
          </a>
          <a href="<?= $getAdminUrl('release_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('release_studio') ? 'bg-[#1e2735] text-teal-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Semantic Release (59)</span>
          </a>
          <a href="<?= $getAdminUrl('webrtc_mesh') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('webrtc_mesh') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">P2P Edge Mesh</span>
          </a>
          <a href="<?= $getAdminUrl('webrtc_transfer') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('webrtc_transfer') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">WebRTC Transfer (66)</span>
          </a>
          <a href="<?= $getAdminUrl('iot_studio') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('iot_studio') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">IoT Watchdog (75)</span>
          </a>
          <a href="<?= $getAdminUrl('latency_heatmap') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('latency_heatmap') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Latency Heatmap (67)</span>
          </a>
          <a href="<?= $getAdminUrl('predictive_analytics') ?>" class="nav-item flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-[11px] <?= $isActive('predictive_analytics') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
            <span class="sidebar-label">Predictive Brain</span>
          </a>

        </div>
      </details>
    </div>

  </div>

  <!-- Sticky Bottom Controls & Collapse Toggle -->
  <div class="p-3 border-t border-[#1e2838] space-y-2 shrink-0 bg-[#090c10]">
    <a href="<?= $getAdminUrl('chat') ?>" class="flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-[#11151c] text-emerald-400 hover:bg-[#16202e] border border-[#1e2838] transition-all">
      <span>&larr;</span> <span class="sidebar-label">Chat Interface</span>
    </a>
    <button onclick="toggleSidebar()" class="w-full flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg text-[10px] text-gray-500 hover:text-white hover:bg-[#11151c] transition-colors">
      <span id="collapseIcon">&larr;</span> <span class="sidebar-label">Collapse Menu</span>
    </button>
  </div>
</aside>

<style>
/* Custom Sleek Dark Scrollbar for Sidebar */
.custom-sidebar-scroll {
  scrollbar-width: thin;
  scrollbar-color: #1e2838 transparent;
}
.custom-sidebar-scroll::-webkit-scrollbar {
  width: 5px;
}
.custom-sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb {
  background: #1e2838;
  border-radius: 9999px;
}
.custom-sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background: #334155;
}
</style>

<script>
function filterSidebarMenu(query) {
  const q = (query || '').toLowerCase().trim();
  const items = document.querySelectorAll('.nav-item');
  const categories = document.querySelectorAll('.nav-category');
  const labsDetails = document.getElementById('labsDetails');

  if (q && labsDetails) {
    labsDetails.open = true;
  } else if (!q && labsDetails) {
    // Restore initial state when query cleared
    labsDetails.open = <?= $isCurrentInLabs ? 'true' : 'false' ?>;
  }

  items.forEach(item => {
    const text = item.innerText.toLowerCase();
    if (!q || text.includes(q)) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });

  categories.forEach(cat => {
    cat.style.display = q ? 'none' : 'block';
  });
}

function toggleSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  const labels = document.querySelectorAll('.sidebar-label');
  const icon = document.getElementById('collapseIcon');
  if (sidebar.classList.contains('w-64')) {
    sidebar.classList.remove('w-64');
    sidebar.classList.add('w-20');
    labels.forEach(l => l.classList.add('hidden'));
    if (icon) icon.innerHTML = '&rarr;';
  } else {
    sidebar.classList.remove('w-20');
    sidebar.classList.add('w-64');
    labels.forEach(l => l.classList.remove('hidden'));
    if (icon) icon.innerHTML = '&larr;';
  }
}

function toggleSidebarMobile() {
  const sidebar = document.getElementById('adminSidebar');
  sidebar.classList.toggle('hidden');
}
</script>
