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
        <a href="<?= $getAdminUrl() ?>" class="hover:text-emerald-400 transition-colors">Control Panel</a>
      </nav>
      <div>
        <a href="<?= $getBaseUrl() ?>/chat.php" class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow-lg shadow-emerald-500/10 transition-all transform hover:-translate-y-0.5">
          Launch Chat
        </a>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-12 md:py-24 flex flex-col md:flex-row items-center justify-between gap-12">
    <div class="flex-1 space-y-8 text-center md:text-left">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
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

      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Active Provider</span>
          <span class="text-sm font-semibold text-emerald-400"><?php echo esc($stats['active_provider']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Active Model</span>
          <span class="text-sm font-semibold text-white max-w-[200px] truncate" title="<?php echo esc($stats['active_model']); ?>"><?php echo esc($stats['active_model']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Knowledge records</span>
          <span class="text-sm font-bold text-white"><?php echo number_format($stats['knowledge_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Trained Examples</span>
          <span class="text-sm font-bold text-white"><?php echo number_format($stats['training_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Documents Processed</span>
          <span class="text-sm font-bold text-white"><?php echo number_format($stats['document_count']); ?></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-400">Brain Health Score</span>
          <span class="text-sm font-bold text-emerald-400"><?php echo $stats['health_score']; ?>/100</span>
        </div>
      </div>

      <!-- Quick Canvas Network Visualizer -->
      <div class="mt-8 pt-6 border-t border-[#1e2838]">
        <canvas id="brainCanvas" class="w-full h-32 rounded-lg bg-[#080a0d] border border-[#16202e]"></canvas>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="border-t border-[#1e2838] bg-[#0c0f14] py-8 text-center text-xs text-gray-500">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
      <span>ATOM Control Room &copy; 2026. All rights reserved.</span>
      <div class="flex gap-4">
        <a href="/admin/index.php" class="hover:text-emerald-400">Dashboard</a>
        <a href="/chat.php" class="hover:text-emerald-400">Chat</a>
      </div>
    </div>
  </footer>

  <script>
    // Canvas Simple Node-Network Visualizer for Hero Page
    const canvas = document.getElementById('brainCanvas');
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

      // Draw connections
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
  </script>
</body>
</html>
