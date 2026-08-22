<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — AI Playground</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
</head>
<body class="text-[#f0f4f8] h-screen flex overflow-hidden">

  <!-- COLLAPSIBLE SIDEBAR -->
  <?php include_once __DIR__ . '/components/sidebar.php'; ?>

  <!-- MAIN WORKSPACE CONTAINER -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- TOP BAR -->
    <?php include_once __DIR__ . '/components/topbar.php'; ?>

    <!-- CONTENT BODY -->
    <main class="flex-1 overflow-y-auto p-8 space-y-8">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black tracking-tight">AI PLAYGROUND</h1>
          <p class="text-xs text-gray-500 mt-1">Directly execute reasoning queries against different backend LLM model providers.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Settings pane -->
        <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-5 h-fit">
          <h3 class="font-bold text-white text-sm border-b border-[#1e2838] pb-3">Test Parameters</h3>
          <div class="space-y-4 text-xs">
            <div class="space-y-1">
              <label class="text-gray-500 font-semibold block">AI Provider</label>
              <select id="playProvider" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
                <option value="Groq">Groq</option>
                <option value="Gemini">Gemini</option>
                <option value="OpenAI">OpenAI</option>
                <option value="Ollama">Ollama (Local)</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="text-gray-500 font-semibold block">Model Name</label>
              <input type="text" id="playModel" value="openai/gpt-oss-120b" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50 font-mono">
            </div>
            <div class="space-y-1">
              <label class="text-gray-500 font-semibold block">Temperature</label>
              <input type="number" id="playTemp" value="0.7" step="0.1" min="0" max="1" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
            </div>
            <button onclick="runPlaygroundQuery()" id="runBtn" class="w-full py-3 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all">
              Run ATOM
            </button>
          </div>
        </div>

        <!-- Chat / response pane -->
        <div class="lg:col-span-2 flex flex-col gap-6">
          <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-4">
            <h3 class="font-bold text-white text-sm">User Prompt</h3>
            <textarea id="playPrompt" placeholder="Ask ATOM a technical question..." class="w-full h-32 p-4 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-white focus:outline-none focus:border-emerald-500/50 placeholder-gray-500 resize-none"></textarea>
          </div>

          <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-4 flex-1">
            <h3 class="font-bold text-white text-sm">Response Output</h3>
            <div id="playResponse" class="p-4 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-gray-400 font-mono h-64 overflow-y-auto whitespace-pre-wrap">Output will render here...</div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    async function runPlaygroundQuery() {
      const provider = document.getElementById('playProvider').value;
      const model = document.getElementById('playModel').value;
      const prompt = document.getElementById('playPrompt').value.trim();
      const output = document.getElementById('playResponse');
      const btn = document.getElementById('runBtn');

      if (!prompt) {
        showToast('Prompt cannot be empty', 'warning');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Generating response...';
      output.textContent = 'Contacting AI provider...';

      try {
        const json = await apiFetch('/ai/complete', {
          method: 'POST',
          body: JSON.stringify({ message: prompt, provider, model })
        });

        if (json.success && json.data) {
          output.textContent = json.data.content;
        } else {
          output.textContent = 'Error: ' + (json.message || 'Generation failed');
        }
      } catch (e) {
        output.textContent = 'Connection failed. Verify server configurations.';
      } finally {
        btn.disabled = false;
        btn.textContent = 'Run ATOM';
      }
    }
  </script>
</body>
</html>
