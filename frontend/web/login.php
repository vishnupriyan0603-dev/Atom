<?php
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM — Sign In &amp; Account Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
</head>
<body class="text-[#f0f4f8] min-h-screen flex flex-col justify-between p-6">

  <!-- Top bar -->
  <header class="max-w-6xl w-full mx-auto flex items-center justify-between py-4">
    <a href="<?= $getBaseUrl() ?>/index.php" class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center font-black text-white shadow shadow-emerald-500/10">A</div>
      <span class="text-xl font-bold tracking-tight text-white">ATOM <span class="text-emerald-400 text-xs font-semibold px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 ml-1">AUTH</span></span>
    </a>
    <div class="flex items-center gap-4 text-xs font-semibold text-gray-400">
      <a href="<?= $getBaseUrl() ?>/chat.php" class="hover:text-emerald-400 transition">Chat Interface</a>
      <a href="<?= $getAdminUrl() ?>" class="hover:text-emerald-400 transition">Admin Panel &rarr;</a>
    </div>
  </header>

  <!-- Login Card -->
  <main class="flex-1 flex items-center justify-center my-8">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 mb-4 text-emerald-400 text-2xl font-black">
          <i class="bi bi-shield-lock"></i>
        </div>
        <h1 class="text-2xl font-black tracking-tight text-white">Welcome Back</h1>
        <p class="text-xs text-gray-500 mt-1">Sign in to access your ATOM AI brain, chat sessions, and control services</p>
      </div>

      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-8 shadow-2xl space-y-5">
        <div class="flex rounded-xl bg-[#080a0d] border border-[#1e2838] p-1 text-xs font-bold">
          <button id="tabLogin" onclick="switchMode('login')" class="flex-1 py-2 rounded-lg bg-emerald-500/15 text-emerald-400">SIGN IN</button>
          <button id="tabRegister" onclick="switchMode('register')" class="flex-1 py-2 rounded-lg text-gray-500">CREATE ACCOUNT</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Email Address</label>
            <input type="email" id="email" autocomplete="email" placeholder="admin@atom.local"
              class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm text-white placeholder-gray-600 focus:outline-none focus:border-emerald-500/50">
          </div>
          <div>
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password</label>
            <input type="password" id="password" autocomplete="current-password" placeholder="••••••••"
              class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm text-white placeholder-gray-600 focus:outline-none focus:border-emerald-500/50">
          </div>
          <div id="nameField" class="hidden">
            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Full Name</label>
            <input type="text" id="name" autocomplete="name" placeholder="Administrator"
              class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm text-white placeholder-gray-600 focus:outline-none focus:border-emerald-500/50">
          </div>
        </div>

        <button id="submitBtn" onclick="submitAuth()"
          class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black text-sm transition-all shadow-lg shadow-emerald-500/10">
          SIGN IN
        </button>

        <div id="errorBox" class="hidden text-xs text-red-400 bg-red-500/10 border border-red-500/30 rounded-xl px-3.5 py-2.5"></div>
      </div>

      <div class="flex items-center justify-between text-xs text-gray-500 mt-6 px-2">
        <a href="<?= $getBaseUrl() ?>/index.php" class="hover:text-gray-400 flex items-center gap-1">
          <i class="bi bi-arrow-left"></i> Home
        </a>
        <a href="<?= $getBaseUrl() ?>/chat.php" class="text-emerald-400 hover:text-emerald-300 font-semibold">
          Open Chat &rarr;
        </a>
      </div>
    </div>
  </main>

  <footer class="text-center text-[11px] text-gray-600 py-4">
    ATOM Intelligent Assistant &copy; 2026. Secure Access Protocol.
  </footer>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    var mode = 'login';

    function switchMode(m) {
      mode = m;
      document.getElementById('tabLogin').className = 'flex-1 py-2 rounded-lg ' + (m === 'login' ? 'bg-emerald-500/15 text-emerald-400' : 'text-gray-500');
      document.getElementById('tabRegister').className = 'flex-1 py-2 rounded-lg ' + (m === 'register' ? 'bg-emerald-500/15 text-emerald-400' : 'text-gray-500');
      document.getElementById('nameField').classList.toggle('hidden', m !== 'register');
      document.getElementById('submitBtn').textContent = m === 'login' ? 'SIGN IN' : 'CREATE ACCOUNT';
      document.getElementById('errorBox').classList.add('hidden');
    }

    function showError(msg) {
      var box = document.getElementById('errorBox');
      box.textContent = msg;
      box.classList.remove('hidden');
    }

    async function submitAuth() {
      var email = document.getElementById('email').value.trim();
      var password = document.getElementById('password').value;
      var name = document.getElementById('name').value.trim();

      if (!email || !password) {
        showError('Email and password are required.');
        return;
      }

      var btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.textContent = 'Authenticating...';

      try {
        var resp = await fetch(ATOM_API + '/auth/' + mode, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(mode === 'login' ? { email: email, password: password } : { email: email, password: password, name: name })
        });
        var json = await resp.json();

        if (json.success && json.token) {
          setAuthToken(json.token, json.user && json.user.email);
          var next = new URLSearchParams(window.location.search).get('next');
          var fallback = '<?= $getBaseUrl() ?>/chat.php';
          window.location.href = next && next.indexOf('/login') === -1 ? next : fallback;
        } else {
          showError(json.message || 'Authentication failed. Please verify your credentials.');
        }
      } catch (e) {
        // Direct local fallback mode for dev
        setAuthToken('dev_token_local', email);
        var next = new URLSearchParams(window.location.search).get('next');
        window.location.href = next && next.indexOf('/login') === -1 ? next : '<?= $getBaseUrl() ?>/chat.php';
      } finally {
        btn.disabled = false;
        btn.textContent = mode === 'login' ? 'SIGN IN' : 'CREATE ACCOUNT';
      }
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') submitAuth();
    });
  </script>
</body>
</html>
