<!-- Shared Header/Top Navigation block with profile status indicators and system alert center -->
<header class="h-16 border-b border-[#1e2838] bg-[#0c0f14]/80 backdrop-blur sticky top-0 z-30 px-8 flex items-center justify-between">
  <div class="flex items-center gap-4">
    <button onclick="toggleSidebarMobile()" class="md:hidden text-gray-400 hover:text-white p-2">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>
    <div class="relative max-w-xs hidden sm:block">
      <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </span>
      <input type="text" id="globalSearchInput" placeholder="Search Brain (Ctrl + K)..." class="w-64 pl-9 pr-4 py-1.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs focus:outline-none focus:border-emerald-500/50 text-[#f0f4f8]">
    </div>
  </div>

  <div class="flex items-center gap-6">
    <div class="flex items-center gap-2">
      <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
      <span class="text-xs font-semibold text-emerald-400 tracking-wide uppercase sm:inline hidden">Online Mode</span>
    </div>

    <!-- Active Profile Display -->
    <div class="flex items-center gap-3 border-l border-[#1e2838] pl-6">
      <div class="text-right hidden md:block">
        <div class="text-xs font-bold text-white">Vichu</div>
        <div class="text-[9px] font-semibold text-gray-500 uppercase tracking-wider">Owner Profile</div>
      </div>
      <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center font-bold text-emerald-400">V</div>
    </div>
  </div>
</header>

<!-- Global search overlay modal container -->
<div id="searchOverlay" class="fixed inset-0 bg-[#080a0d]/80 backdrop-blur-sm z-50 hidden flex justify-center pt-24 px-4">
  <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl w-full max-w-2xl max-h-[500px] flex flex-col overflow-hidden shadow-2xl">
    <div class="p-4 border-b border-[#1e2838] flex items-center gap-3">
      <span class="text-gray-400 text-lg">&telrec;</span>
      <input type="text" id="overlaySearchInput" placeholder="Type key concept or command to search..." class="w-full bg-transparent text-[#f0f4f8] focus:outline-none placeholder-gray-500 text-sm">
      <button onclick="closeSearchOverlay()" class="text-xs text-gray-500 hover:text-white px-2 py-1 rounded bg-[#080a0d] border border-[#1e2838]">ESC</button>
    </div>
    <div id="searchResults" class="flex-1 overflow-y-auto p-4 space-y-2 text-sm text-gray-400">
      <div class="text-center py-8 text-gray-500 text-xs">Start typing to query ATOM's knowledge records, documents, or memories...</div>
    </div>
  </div>
</div>
