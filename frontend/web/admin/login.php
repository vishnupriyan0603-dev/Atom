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
      <div class="flex rounded-xl bg-[#080a0d] border border-[#1e2838] p-1 text-xs font-bold">
        <button id="tabLogin" onclick="switchMode('login')" class="flex-1 py-2 rounded-lg bg-emerald-500/15 text-emerald-400">SIGN IN</button>
        <button id="tabRegister" onclick="switchMode('register')" class="flex-1 py-2 rounded-lg text-gray-500">CREATE ACCOUNT</button>
      </div>

      <div class="space-y-4">
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Email</label>
          <input type="email" id="email" autocomplete="email"
            class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm focus:outline-none focus:border-emerald-500/50">
        </div>
        <div>
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Password</label>
          <input type="password" id="password" autocomplete="current-password"
            class="w-full mt-1 px-4 py-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-sm focus:outline-none focus:border-emerald-500/50">
        </div>
        <div id="nameField" class="hidden">
          <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Display Name</label>
          <input type="text" id="name" autocomplete="name"
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
      <a href="/" class="hover:text-gray-400">&#8592; Back to Atom Assistant</a>
    </p>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    var ATOM_API = 'http://localhost:8080/api';
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
      btn.textContent = 'Please wait...';

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
          window.location.href = next && next.indexOf('/login') === -1 ? next : '/admin/index.php';
        } else {
          showError(json.message || 'Authentication failed.');
        }
      } catch (e) {
        showError('Cannot reach the backend. Make sure the API server is running.');
      } finally {
        btn.disabled = false;
        btn.textContent = mode === 'login' ? 'SIGN IN' : 'CREATE ACCOUNT';
      }
    }

    // Enter key submits
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') submitAuth();
    });
  </script>
</body>
</html>
