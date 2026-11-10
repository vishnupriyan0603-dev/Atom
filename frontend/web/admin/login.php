<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Sign In</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
  <link rel="stylesheet" href="css/admin-theme.css">
</head>
<body class="text-[#f0f4f8] min-h-screen flex items-center justify-center p-6">

  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 mb-4">
        <span class="text-emerald-400 text-2xl font-black">A</span>
      </div>
      <h1 class="text-2xl font-black tracking-tight">ATOM CONTROL</h1>
      <p class="text-xs text-gray-500 mt-1">Sign in to manage your personal AI assistant</p>
    </div>

    <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-8 shadow-2xl space-y-5">
      <div class="space-y-4">
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Username or Email</label>
          <input type="text" id="username" autocomplete="username"
            class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm focus:outline-none focus:border-emerald-500/50">
        </div>
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password</label>
          <input type="password" id="password" autocomplete="current-password"
            class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm focus:outline-none focus:border-emerald-500/50">
        </div>
      </div>

      <button id="submitBtn" onclick="submitAuth()"
        class="w-full py-3 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black font-black text-sm transition-all">
        SIGN IN
      </button>

      <div id="errorBox" class="hidden text-xs text-red-400 bg-red-500/10 border border-red-500/30 rounded-lg px-3 py-2"></div>
    </div>

    <p class="text-center text-[10px] text-gray-600 mt-6">
      <a href="<?= $getBaseUrl() ?>/index.php" class="hover:text-gray-400">&#8592; Back to Atom Assistant</a>
    </p>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    var ATOM_API = 'http://localhost:8080/api';
    function showError(msg) {
      var box = document.getElementById('errorBox');
      box.textContent = msg;
      box.classList.remove('hidden');
    }

    async function submitAuth() {
      var username = document.getElementById('username').value.trim();
      var password = document.getElementById('password').value;
      if (!username || !password) {
        showError('Username/email and password are required.');
        return;
      }

      var btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.textContent = 'Please wait...';

      try {
        var resp = await fetch(ATOM_API + '/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username: username, password: password })
        });
        var text = await resp.text();
        if (!text || text.trim().startsWith('<')) {
          throw new Error('ATOM login API returned an invalid response.');
        }
        var json = JSON.parse(text);
        if (!resp.ok || !json.success) {
          showError(json.message || 'Sign in failed. Check your username and password.');
          return;
        }

        // CodeIgniter wraps successful response data in `data`; also accept
        // the legacy flat shape for compatibility with older API deployments.
        var authData = json.data || json;
        if (!authData.token) {
          showError('Sign in succeeded but the API did not return a session token.');
          return;
        }

        setAuthToken(authData.token, authData.user && authData.user.email ? authData.user.email : username);
        var sessionResponse = await fetch('session.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Authorization': 'Bearer ' + authData.token }
        });
        var sessionText = await sessionResponse.text();
        var sessionResult = sessionText && !sessionText.trim().startsWith('<') ? JSON.parse(sessionText) : null;
        if (!sessionResponse.ok || !sessionResult || !sessionResult.success) {
          throw new Error((sessionResult && sessionResult.message) || 'Could not establish the secure Admin session.');
        }

        // Resolve the dashboard from the current login URL. This keeps the
        // redirect correct when ATOM is served from a nested or URL-encoded
        // XAMPP folder (for example, /my%20work/Atom/).
        var adminDirectory = window.location.pathname.replace(/login\.php$/i, '');
        var fallback = new URL('index.php', window.location.origin + adminDirectory).href;
        var destination = fallback;
        var next = new URLSearchParams(window.location.search).get('next');
        if (next) {
          try {
            var nextUrl = new URL(next, window.location.origin);
            if (nextUrl.origin === window.location.origin &&
                nextUrl.pathname.startsWith(adminDirectory) &&
                !/\/login(?:\.php)?$/i.test(nextUrl.pathname)) {
              destination = nextUrl.href;
            }
          } catch (e) {}
        }
        btn.textContent = 'Opening dashboard...';
        window.location.replace(destination);
      } catch (e) {
        clearAuthToken();
        showError(e && e.message ? e.message : 'Could not connect to the ATOM login API.');
      } finally {
        btn.disabled = false;
        btn.textContent = 'SIGN IN';
      }
    }

    // Enter key submits
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') submitAuth();
    });
  </script>
</body>
</html>
