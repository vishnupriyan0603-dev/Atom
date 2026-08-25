<?php
// ATOM Collapsible & Optimized Admin Sidebar Component
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
?>
<aside id="adminSidebar" class="w-64 h-screen sticky top-0 bg-[#0c0f14] border-r border-[#1e2838] flex flex-col justify-between shrink-0 transition-all duration-300 z-40 select-none">
  
  <!-- Fixed Header: Logo & Instant Search Filter -->
  <div class="p-4 border-b border-[#1e2838] shrink-0">
    <div class="flex items-center gap-3 mb-3">
      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-white shadow-lg shadow-emerald-500/20">A</div>
      <div class="sidebar-label">
        <span class="text-sm font-bold tracking-tight text-white block">ATOM CONTROL</span>
        <span class="text-[10px] text-emerald-400 font-semibold uppercase tracking-wider">v2.0 • 52 Phases</span>
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
    
    <!-- 0. Core Cockpit -->
    <a href="<?= $getAdminUrl('command_center') ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all <?= $isActive('command_center') ? 'bg-gradient-to-r from-indigo-900/80 to-purple-900/80 border border-indigo-500/50 text-white shadow-md shadow-indigo-500/20' : 'text-indigo-300 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
      <span class="sidebar-label">🌟 Command Center (50)</span>
    </a>

    <a href="<?= $getAdminUrl('distributed_tracing') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold transition-all <?= $isActive('distributed_tracing') ? 'bg-[#1e2735] text-cyan-400 font-bold border-l-2 border-cyan-400' : 'text-cyan-300 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">🌌 Distributed Tracing (60)</span>
    </a>

    <a href="<?= $getAdminUrl('dashboard') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('dashboard') ? 'bg-[#1e2735] text-emerald-400 font-bold border-l-2 border-emerald-400' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
      <span class="sidebar-label">Overview Dashboard</span>
    </a>

    <!-- Category 1: AI Brain & Multi-Modal Swarm -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-purple-400">AI Brain &amp; Swarm</span>
    </div>

    <a href="<?= $getAdminUrl('swarm') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('swarm') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      <span class="sidebar-label">Swarm Orchestrator (41)</span>
    </a>

    <a href="<?= $getAdminUrl('voice_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('voice_studio') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
      <span class="sidebar-label">Ben 10 Tamil Voice</span>
    </a>

    <a href="<?= $getAdminUrl('voice_duplex_stream') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('voice_duplex_stream') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
      <span class="sidebar-label">Live Formant Shifter (46)</span>
    </a>

    <a href="<?= $getAdminUrl('acoustic_filter') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('acoustic_filter') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
      <span class="sidebar-label">Acoustic Noise Filter (58)</span>
    </a>

    <a href="<?= $getAdminUrl('vision_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vision_studio') ? 'bg-[#1e2735] text-blue-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
      <span class="sidebar-label">Neural Vision &amp; OCR (42)</span>
    </a>

    <a href="<?= $getAdminUrl('brain') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('brain') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
      <span class="sidebar-label">Personal Brain</span>
    </a>

    <a href="<?= $getAdminUrl('planning') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('planning') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm4 0h8M8 11h8M8 15h4"></path></svg>
      <span class="sidebar-label">GoT Planning</span>
    </a>

    <!-- Category 2: Engineering, AST & Refactoring -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-cyan-400">Engineering &amp; Code</span>
    </div>

    <a href="<?= $getAdminUrl('code_modernizer') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('code_modernizer') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">AST Modernizer (47)</span>
    </a>

    <a href="<?= $getAdminUrl('performance_profiler') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('performance_profiler') ? 'bg-[#1e2735] text-amber-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">Performance Profiler (51)</span>
    </a>

    <a href="<?= $getAdminUrl('dead_code_pruner') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('dead_code_pruner') ? 'bg-[#1e2735] text-purple-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"></path></svg>
      <span class="sidebar-label">Dead Code Pruner (54)</span>
    </a>

    <a href="<?= $getAdminUrl('dependency_graph') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('dependency_graph') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
      <span class="sidebar-label">Dependency Graph (43)</span>
    </a>

    <a href="<?= $getAdminUrl('refactoring') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('refactoring') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
      <span class="sidebar-label">Refactoring Studio</span>
    </a>

    <a href="<?= $getAdminUrl('cicd') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('cicd') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
      <span class="sidebar-label">CI/CD &amp; Testing</span>
    </a>

    <a href="<?= $getAdminUrl('openapi_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('openapi_studio') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
      <span class="sidebar-label">OpenAPI &amp; SDKs (57)</span>
    </a>

    <!-- Category 3: Security & Zero-Trust -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-pink-400">Security &amp; Zero-Trust</span>
    </div>

    <a href="<?= $getAdminUrl('pqc_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('pqc_studio') ? 'bg-[#1e2735] text-pink-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span class="sidebar-label">Post-Quantum Crypto (45)</span>
    </a>

    <a href="<?= $getAdminUrl('abac_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('abac_studio') ? 'bg-[#1e2735] text-blue-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span class="sidebar-label">ABAC Firewall (48)</span>
    </a>

    <a href="<?= $getAdminUrl('rate_limiter') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('rate_limiter') ? 'bg-[#1e2735] text-rose-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <span class="sidebar-label">Rate Limiter (56)</span>
    </a>

    <a href="<?= $getAdminUrl('vault') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vault') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
      <span class="sidebar-label">ZK Vault (AES-256)</span>
    </a>

    <a href="<?= $getAdminUrl('rbac') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('rbac') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
      <span class="sidebar-label">Enterprise RBAC</span>
    </a>

    <!-- Category 4: Data, Storage & Vector Hub -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-emerald-400">Data &amp; Storage</span>
    </div>

    <a href="<?= $getAdminUrl('vector_index') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('vector_index') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">HNSW Vector Index (44)</span>
    </a>

    <a href="<?= $getAdminUrl('query_optimizer') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('query_optimizer') ? 'bg-[#1e2735] text-cyan-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm4 0h8M8 11h8M8 15h4"></path></svg>
      <span class="sidebar-label">SQL Index Optimizer (52)</span>
    </a>

    <a href="<?= $getAdminUrl('query_load_simulator') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('query_load_simulator') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">Query Load Simulator (61)</span>
    </a>

    <a href="<?= $getAdminUrl('knowledge') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('knowledge') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
      <span class="sidebar-label">Knowledge Items</span>
    </a>

    <!-- Category 5: Automation & Network -->
    <div class="nav-category pt-3 pb-1">
      <span class="px-3 text-[10px] font-bold tracking-wider uppercase sidebar-label text-indigo-400">Automation &amp; Edge</span>
    </div>

    <a href="<?= $getAdminUrl('cron_scheduler') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('cron_scheduler') ? 'bg-[#1e2735] text-indigo-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      <span class="sidebar-label">Distributed Cron (49)</span>
    </a>

    <a href="<?= $getAdminUrl('webhook_hub') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('webhook_hub') ? 'bg-[#1e2735] text-sky-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span class="sidebar-label">Event Webhook Hub (53)</span>
    </a>

    <a href="<?= $getAdminUrl('federated_learning') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('federated_learning') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
      <span class="sidebar-label">Federated Learning (55)</span>
    </a>

    <a href="<?= $getAdminUrl('release_studio') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('release_studio') ? 'bg-[#1e2735] text-teal-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
      <span class="sidebar-label">Semantic Release (59)</span>
    </a>

    <a href="<?= $getAdminUrl('webrtc_mesh') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('webrtc_mesh') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
      <span class="sidebar-label">P2P Edge Mesh</span>
    </a>

    <a href="<?= $getAdminUrl('predictive_analytics') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('predictive_analytics') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
      <span class="sidebar-label">Predictive Brain</span>
    </a>

    <a href="<?= $getAdminUrl('settings') ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $isActive('settings') ? 'bg-[#1e2735] text-emerald-400 font-bold' : 'text-gray-400 hover:text-white hover:bg-[#161c28]' ?>">
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
      <span class="sidebar-label">Settings</span>
    </a>

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
