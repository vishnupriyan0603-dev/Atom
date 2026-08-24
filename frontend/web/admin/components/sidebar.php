<!-- Collapsible Admin Sidebar Template Component -->
<aside id="adminSidebar" class="w-64 bg-[#0c0f14] border-r border-[#1e2838] flex flex-col justify-between shrink-0 transition-all duration-300 z-40">
  <div>
    <!-- Logo area -->
    <div class="h-16 px-6 flex items-center gap-3 border-b border-[#1e2838]">
      <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center font-black text-white shadow shadow-emerald-500/10">A</div>
      <span class="text-lg font-bold tracking-tight text-white sidebar-label">ATOM CONTROL</span>
    </div>

    <!-- Navigation List -->
    <nav class="mt-6 px-4 space-y-1">
      <a href="/admin" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
        <span class="sidebar-label">Dashboard</span>
      </a>

      <div class="pt-4 pb-1">
        <span class="px-4 text-[10px] font-bold tracking-wider uppercase sidebar-label" style="color:#A78BFA;">AI Brain</span>
      </div>

      <a href="/admin/brain" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
        <span class="sidebar-label">Personal Brain</span>
      </a>

      <a href="/admin/multimodal" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
        <span class="sidebar-label">Voice &amp; Vision</span>
      </a>

      <a href="/admin/daemon" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        <span class="sidebar-label">Proactive Daemon</span>
      </a>

      <a href="/admin/desktop" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        <span class="sidebar-label">Desktop Automation</span>
      </a>

      <div class="pt-4 pb-1">
        <span class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase sidebar-label">Brain Data</span>
      </div>

      <a href="/admin/knowledge" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
        <span class="sidebar-label">Knowledge Items</span>
      </a>

      <a href="/admin/documents" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
        <span class="sidebar-label">Documents / PDFs</span>
      </a>

      <a href="/admin/training" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
        <span class="sidebar-label">Training Examples</span>
      </a>

      <div class="pt-4 pb-1">
        <span class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase sidebar-label">AI & Models</span>
      </div>

      <a href="/admin/providers" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        <span class="sidebar-label">Providers</span>
      </a>

      <a href="/admin/playground" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        <span class="sidebar-label">Playground</span>
      </a>

      <a href="/admin/lsp" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
        <span class="sidebar-label">IDE Protocol (LSP)</span>
      </a>

      <a href="/admin/analytics" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
        <span class="sidebar-label">Observability</span>
      </a>

      <a href="/admin/agents" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        <span class="sidebar-label">Agent Orchestration</span>
      </a>

      <a href="/admin/workflows" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        <span class="sidebar-label">Autonomous Workflows</span>
      </a>

      <a href="/admin/evaluations" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"></path></svg>
        <span class="sidebar-label">Agent Evaluation</span>
      </a>

      <a href="/admin/routing" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
        <span class="sidebar-label">Adaptive Routing</span>
      </a>





      <div class="pt-4 pb-1">
        <span class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase sidebar-label">System & Security</span>
      </div>

      <a href="/admin/approvals" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
        <span class="sidebar-label">Human Approvals</span>
      </a>

      <a href="/admin/governance" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        <span class="sidebar-label">Governance Control</span>
      </a>


      <a href="/admin/jobs" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        <span class="sidebar-label">Background Jobs</span>
      </a>

      <a href="/admin/skills" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a2 2 0 002 2h1a2 2 0 110 4h-1a2 2 0 00-2 2v1a2 2 0 11-4 0v-1a2 2 0 00-2-2H7a2 2 0 110-4h1a2 2 0 002-2V4z"></path></svg>
        <span class="sidebar-label">Plugins & Skills</span>
      </a>

      <a href="/admin/settings" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:bg-[#1e2735] text-gray-400 hover:text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span class="sidebar-label">Settings</span>
      </a>
    </nav>
  </div>

  <!-- Bottom Toggle Collapsed State & Back button -->
  <div class="p-4 border-t border-[#1e2838] space-y-2">
    <a href="/chat" class="flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-[#11151c] text-emerald-400 hover:bg-[#16202e] border border-[#1e2838] transition-all">
      &larr; Chat Interface
    </a>
    <button onclick="toggleSidebar()" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-[10px] text-gray-500 hover:text-white hover:bg-[#11151c]">
      <span id="collapseIcon">&larr;</span> <span class="sidebar-label">Collapse Menu</span>
    </button>
  </div>
</aside>
