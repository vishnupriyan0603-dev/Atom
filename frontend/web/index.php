<?php
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM — Your Personal AI Brain</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
</head>
<body class="text-[#f0f4f8] min-h-screen flex flex-col justify-between overflow-x-hidden selection:bg-emerald-500/20 selection:text-emerald-400">

  <!-- Top Navigation Header -->
  <header class="border-b border-[#1e2838] bg-[#0c0f14]/80 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center font-black text-white shadow-lg shadow-emerald-500/10">A</div>
        <span class="text-xl font-bold tracking-tight text-white">ATOM <span class="text-emerald-400 text-xs font-semibold px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 ml-1">BRAIN</span></span>
      </div>
      <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-400">
        <a href="<?= $getBaseUrl() ?>/index.php" class="text-white hover:text-emerald-400 transition-colors">Home</a>
        <a href="<?= $getBaseUrl() ?>/chat.php" class="hover:text-emerald-400 transition-colors">Chat Interface</a>
        <a href="<?= $getBaseUrl() ?>/login.php" class="hover:text-emerald-400 transition-colors">Sign In</a>
        <a href="<?= $getAdminUrl() ?>" class="hover:text-emerald-400 transition-colors">Admin Panel</a>
      </nav>
      <div class="flex items-center gap-3">
        <a href="<?= $getBaseUrl() ?>/login.php" class="hidden sm:inline-flex items-center justify-center px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#11151c] hover:bg-[#16202e] border border-[#1e2838] text-gray-300 transition">
          Sign In
        </a>
        <a href="<?= $getBaseUrl() ?>/chat.php" class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/10 transition-all transform hover:-translate-y-0.5">
          Launch Chat
        </a>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-12 md:py-20 flex flex-col md:flex-row items-center justify-between gap-12">
    <div class="flex-1 space-y-8 text-center md:text-left">
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        SYSTEM OPERATIONAL — <?php echo esc($stats['active_provider']); ?>
      </div>
      <h1 class="text-5xl md:text-7xl font-black tracking-tight text-white leading-none">
        Your Personal <br>
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">AI Brain</span>
      </h1>
      <p class="text-lg md:text-xl text-gray-400 max-w-lg leading-relaxed">
        An intelligent control center that learns, remembers, refines, and optimizes coding knowledge directly from your project workspace.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center md:justify-start gap-4">
        <a href="<?= $getBaseUrl() ?>/chat.php" class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow-xl shadow-emerald-500/10 text-center transition-all transform hover:-translate-y-0.5">
          Start Conversation
        </a>
        <a href="<?= $getAdminUrl() ?>" class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-bold bg-[#11151c] hover:bg-[#171d26] text-white border border-[#1e2838] hover:border-emerald-500/30 text-center transition-all">
          Access Control Panel
        </a>
      </div>
    </div>

    <!-- Interactive AI Status Panel -->
    <div class="w-full md:w-[460px] bg-[#11151c] border border-[#1e2838] rounded-2xl p-8 relative overflow-hidden shadow-2xl">
      <div class="absolute -right-16 -top-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl"></div>
      
      <div class="flex items-center justify-between border-b border-[#1e2838] pb-6 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
          <span class="font-bold tracking-tight text-white">ATOM CORE STATUS</span>
        </div>
        <span class="text-xs text-gray-500">v1.0.0</span>
      </div>

      <div class="space-y-4 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Active Provider</span>
          <span class="font-semibold text-emerald-400"><?php echo esc($stats['active_provider']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Active Model</span>
          <span class="font-semibold text-white max-w-[200px] truncate" title="<?php echo esc($stats['active_model']); ?>"><?php echo esc($stats['active_model']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Knowledge Records</span>
          <span class="font-bold text-white"><?php echo number_format($stats['knowledge_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Trained Examples</span>
          <span class="font-bold text-white"><?php echo number_format($stats['training_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Documents Processed</span>
          <span class="font-bold text-white"><?php echo number_format($stats['document_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-gray-400">Brain Health Score</span>
          <span class="font-bold text-emerald-400"><?php echo $stats['health_score']; ?>/100</span>
        </div>
      </div>

      <!-- Quick Canvas Network Visualizer -->
      <div class="mt-6 pt-4 border-t border-[#1e2838]">
        <canvas id="brainCanvas" class="w-full h-28 rounded-lg bg-[#080a0d] border border-[#16202e]"></canvas>
      </div>
    </div>
  </main>

  <!-- Feature Studio Quick Launch Grid -->
  <section class="max-w-7xl w-full mx-auto px-6 py-12 border-t border-[#1e2838]">
    <div class="mb-8">
      <h2 class="text-2xl font-black text-white tracking-tight">AI &amp; DSP STUDIOS</h2>
      <p class="text-xs text-gray-500 mt-1">Direct access to core AI reasoning, audio DSP, planning, and self-healing control modules.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <a href="<?= $getBaseUrl() ?>/chat.php" class="p-5 rounded-2xl bg-[#11151c] border border-[#1e2838] hover:border-emerald-500/50 transition group flex flex-col justify-between space-y-3">
        <div>
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
          </div>
          <h3 class="font-bold text-white text-sm group-hover:text-emerald-400 transition">Chat Interface</h3>
          <p class="text-xs text-gray-500 mt-1">Multi-turn AI reasoning with persistent context and LLM routing.</p>
        </div>
        <span class="text-[11px] font-bold text-emerald-400 flex items-center gap-1">Launch Chat &rarr;</span>
      </a>

      <a href="<?= $getAdminUrl('equalizer') ?>" class="p-5 rounded-2xl bg-[#11151c] border border-[#1e2838] hover:border-emerald-500/50 transition group flex flex-col justify-between space-y-3">
        <div>
          <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
          </div>
          <h3 class="font-bold text-white text-sm group-hover:text-cyan-400 transition">Audio Equalizer</h3>
          <p class="text-xs text-gray-500 mt-1">10-band parametric DSP equalizer with biquad filters and live curve.</p>
        </div>
        <span class="text-[11px] font-bold text-cyan-400 flex items-center gap-1">Open Studio &rarr;</span>
      </a>

      <a href="<?= $getAdminUrl('planning') ?>" class="p-5 rounded-2xl bg-[#11151c] border border-[#1e2838] hover:border-emerald-500/50 transition group flex flex-col justify-between space-y-3">
        <div>
          <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm4 0h8M8 11h8M8 15h4"></path></svg>
          </div>
          <h3 class="font-bold text-white text-sm group-hover:text-purple-400 transition">ToT Planning</h3>
          <p class="text-xs text-gray-500 mt-1">Hierarchical Graph-of-Thought search, step verification &amp; rollback.</p>
        </div>
        <span class="text-[11px] font-bold text-purple-400 flex items-center gap-1">Explore Tree &rarr;</span>
      </a>

      <a href="<?= $getAdminUrl('knowledge') ?>" class="p-5 rounded-2xl bg-[#11151c] border border-[#1e2838] hover:border-emerald-500/50 transition group flex flex-col justify-between space-y-3">
        <div>
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
          </div>
          <h3 class="font-bold text-white text-sm group-hover:text-amber-400 transition">Knowledge Records</h3>
          <p class="text-xs text-gray-500 mt-1">Manage, verify, and search RAG vector chunks and documentation.</p>
        </div>
        <span class="text-[11px] font-bold text-amber-400 flex items-center gap-1">Manage Records &rarr;</span>
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="border-t border-[#1e2838] bg-[#0c0f14] py-8 text-center text-xs text-gray-500">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
      <span>ATOM Control Room &copy; 2026. All rights reserved.</span>
      <div class="flex gap-4">
        <a href="<?= $getAdminUrl() ?>" class="hover:text-emerald-400">Dashboard</a>
        <a href="<?= $getBaseUrl() ?>/chat.php" class="hover:text-emerald-400">Chat</a>
        <a href="<?= $getAdminUrl('equalizer') ?>" class="hover:text-emerald-400">Equalizer</a>
        <a href="<?= $getAdminUrl('planning') ?>" class="hover:text-emerald-400">Planning</a>
      </div>
    </div>
  </footer>

  <script>
    // Canvas Simple Node-Network Visualizer for Hero Page
    const canvas = document.getElementById('brainCanvas');
    if (canvas) {
      const ctx = canvas.getContext('2d');
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;

      const nodes = [];
      for(let i=0; i<15; i++) {
        nodes.push({
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height,
          r: Math.random() * 3 + 1,
          dx: (Math.random() - 0.5) * 0.4,
          dy: (Math.random() - 0.5) * 0.4
        });
      }

      function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = 'rgba(16, 185, 129, 0.4)';
        ctx.strokeStyle = 'rgba(16, 185, 129, 0.08)';

        nodes.forEach(n => {
          n.x += n.dx;
          n.y += n.dy;
          if(n.x < 0 || n.x > canvas.width) n.dx *= -1;
          if(n.y < 0 || n.y > canvas.height) n.dy *= -1;

          ctx.beginPath();
          ctx.arc(n.x, n.y, n.r, 0, Math.PI*2);
          ctx.fill();
        });

        for(let i=0; i<nodes.length; i++) {
          for(let j=i+1; j<nodes.length; j++) {
            const dist = Math.hypot(nodes[i].x - nodes[j].x, nodes[i].y - nodes[j].y);
            if (dist < 60) {
              ctx.beginPath();
              ctx.moveTo(nodes[i].x, nodes[i].y);
              ctx.lineTo(nodes[j].x, nodes[j].y);
              ctx.stroke();
            }
          }
        }
        requestAnimationFrame(animate);
      }
      animate();
    }
  </script>
</body>
</html>
