using System;
using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;
using AtomAssistant.Services;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class ChatPageViewModel : ObservableObject
    {
        private readonly ChatService _chatService;
        private readonly ILogger<ChatPageViewModel> _logger;
        private long _activeChatId;
        private string _activeModel = "Gemini 3.6 Flash";

        [ObservableProperty]
        private ObservableCollection<MessageItem> messages = new();

        [ObservableProperty]
        private ObservableCollection<string> availableModels = new()
        {
            "Gemini 3.6 Flash",
            "Groq (OSS-120B)",
            "OpenAI GPT-4o",
            "Ollama (Local)",
            "Llama.cpp (Local)"
        };

        [ObservableProperty]
        private string inputText = string.Empty;

        [ObservableProperty]
        private bool isStreaming;

        [ObservableProperty]
        private int pendingApprovalsCount;

        [ObservableProperty]
        private MessageItem? selectedMessage;

        public ChatPageViewModel(ChatService chatService, ILogger<ChatPageViewModel> logger)
        {
            _chatService = chatService;
            _logger = logger;
            AddWelcomeMessage();
        }

        private void AddWelcomeMessage()
        {
            Messages.Add(new MessageItem
            {
                Content = "Greetings Vishnupriyan! I am ATOM Desktop Assistant with integrated Model Gateway, Memory 2.0, and Human Approval security gate.",
                IsUser = false,
                Timestamp = DateTime.Now
            });
        }

        public void SetActiveModel(string model)
        {
            _activeModel = model;
        }

        [RelayCommand]
        private async Task LaunchAgentTask(string objective)
        {
            if (string.IsNullOrWhiteSpace(objective)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { objective }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                await client.PostAsync("http://localhost:8080/api/v1/agents/tasks", content);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not launch agent task");
            }
        }

        [RelayCommand]
        private async Task ExecuteWorkflow(int workflowId)
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { input = new { objective = "Desktop workflow dispatch" } }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                await client.PostAsync($"http://localhost:8080/api/v1/workflows/{workflowId}/execute", content);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not execute workflow {WorkflowId}", workflowId);
            }
        }

        [RelayCommand]
        private async Task LaunchSwarm(string objective)
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { objective }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                await client.PostAsync("http://localhost:8080/api/v1/swarms", content);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not launch swarm for objective {Objective}", objective);
            }
        }

        [RelayCommand]
        private async Task LaunchEvaluationRun(string targetType)
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { dataset_id = 1, target_type = targetType, target_id = "1" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                await client.PostAsync("http://localhost:8080/api/v1/evaluations/runs", content);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not launch evaluation run for target {TargetType}", targetType);
            }
        }

        [RelayCommand]
        private async Task InspectAdaptiveRouting()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/routing/candidates");
                _logger.LogInformation("Routing candidates retrieved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not inspect routing candidates");
            }
        }

        [RelayCommand]
        private async Task InspectGovernancePolicies()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/governance/policies");
                _logger.LogInformation("Governance policies retrieved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not inspect governance policies");
            }
        }

        // ── Phase 23 — Personal AI Brain Commands ─────────────────────────────

        /// <summary>Inspect the Brain's current state: environment, context, personality, voice mode.</summary>
        [RelayCommand]
        private async Task InspectBrainStatus()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/brain/status");
                _logger.LogInformation("Brain status retrieved: {StatusCode}", response.StatusCode);
                if (response.IsSuccessStatusCode)
                {
                    var json = await response.Content.ReadAsStringAsync();
                    _logger.LogDebug("Brain status payload: {Json}", json);
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not retrieve Brain status");
            }
        }

        /// <summary>Reset the Brain's active context thread for a fresh conversation.</summary>
        [RelayCommand]
        private async Task ResetContext()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.PostAsync("http://localhost:8080/api/v1/brain/reset-context", null);
                _logger.LogInformation("Brain context reset: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not reset Brain context");
            }
        }

        // ── Phase 24 — Multi-Modal Voice & Vision Commands ────────────────────

        /// <summary>Synthesize speech audio for the given text message.</summary>
        [RelayCommand]
        private async Task SynthesizeVoice(string text)
        {
            if (string.IsNullOrWhiteSpace(text)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { text, voice = "en-IN-Standard-A" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/voice/synthesize", content);
                _logger.LogInformation("Voice synthesized: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not synthesize voice");
            }
        }

        /// <summary>Analyze an image or screenshot with multi-modal vision.</summary>
        [RelayCommand]
        private async Task AnalyzeImage(string base64Image)
        {
            if (string.IsNullOrWhiteSpace(base64Image)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { image_base64 = base64Image, task_type = "screenshot_debug" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/vision/analyze", content);
                _logger.LogInformation("Image analyzed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not analyze image");
            }
        }

        // ── Phase 25 — Proactive Daemon & Briefing Commands ───────────────────

        /// <summary>Inspect proactive daemon status, pulse counts, and workspace health.</summary>
        [RelayCommand]
        private async Task InspectDaemonStatus()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/daemon/status");
                _logger.LogInformation("Daemon status retrieved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not retrieve daemon status");
            }
        }

        /// <summary>Generate a fresh daily morning/evening briefing.</summary>
        [RelayCommand]
        private async Task GenerateBriefing()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { type = "morning" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/daemon/briefing/generate", content);
                _logger.LogInformation("Briefing generated: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not generate briefing");
            }
        }

        // ── Phase 26 — Developer IDE Protocol (LSP) Commands ──────────────────

        /// <summary>Inspect language server protocol capabilities and triggers.</summary>
        [RelayCommand]
        private async Task InspectLspCapabilities()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/lsp/capabilities");
                _logger.LogInformation("LSP capabilities retrieved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not retrieve LSP capabilities");
            }
        }

        /// <summary>Execute AST refactoring transformation on code snippet.</summary>
        [RelayCommand]
        private async Task RefactorCode(string code)
        {
            if (string.IsNullOrWhiteSpace(code)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var content = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { code, action = "format_syntax" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/lsp/refactor", content);
                _logger.LogInformation("Code refactored: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not refactor code");
            }
        }

        // ── Phase 27 — Desktop Automation & OS Sidecar Commands ───────────────

        /// <summary>Inspect native OS sidecar telemetry, active window, and power.</summary>
        [RelayCommand]
        private async Task InspectDesktopStatus()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var response = await client.GetAsync("http://localhost:8080/api/v1/desktop/status");
                _logger.LogInformation("Desktop status retrieved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not retrieve desktop status");
            }
        }

        /// <summary>Analyze clipboard buffer text for proactive AI actions.</summary>
        [RelayCommand]
        private async Task AnalyzeClipboard(string content)
        {
            if (string.IsNullOrWhiteSpace(content)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { content }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/desktop/clipboard/analyze", reqContent);
                _logger.LogInformation("Clipboard analyzed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not analyze clipboard");
            }
        }

        // ── Phase 28 — Real-Time WebSocket & Sync Commands ───────────────────

        /// <summary>Register WPF desktop peer with synchronization hub.</summary>
        [RelayCommand]
        private async Task RegisterSyncPeer()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new {
                        device_id = "desktop_wpf_master",
                        client_type = "desktop_wpf",
                        device_name = "ATOM Desktop (WPF)"
                    }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/sync/register", reqContent);
                _logger.LogInformation("Desktop peer registered: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not register sync peer");
            }
        }

        /// <summary>Broadcast real-time message event to all peers.</summary>
        [RelayCommand]
        private async Task BroadcastSyncEvent(string eventName, string message)
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new {
                        @event = string.IsNullOrWhiteSpace(eventName) ? "desktop:ping" : eventName,
                        payload = new { message, source = "desktop_wpf" }
                    }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/sync/broadcast", reqContent);
                _logger.LogInformation("Sync event broadcasted: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not broadcast sync event");
            }
        }

        // ── Phase 29 — Autonomous Testing & CI/CD Commands ────────────────────

        /// <summary>Trigger multi-stage CI/CD pipeline pass.</summary>
        [RelayCommand]
        private async Task TriggerCiPipeline()
        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new {
                        stages = new[] { "lint", "unit_tests", "security_scan", "coverage_check", "build_check" }
                    }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/cicd/pipeline/trigger", reqContent);
                _logger.LogInformation("CI/CD pipeline triggered: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not trigger CI/CD pipeline");
            }
        }

        /// <summary>Generate PHPUnit test suite for given source class.</summary>
        [RelayCommand]
        private async Task GenerateTests(string code)
        {
            if (string.IsNullOrWhiteSpace(code)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { code, class_name = "TargetComponent" }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/cicd/test/generate", reqContent);
                _logger.LogInformation("Tests generated: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not generate tests");
            }
        }

        // ── Phase 30 — Long-Horizon Planning & Graph-of-Thought Commands ──────

        /// <summary>Decomposes a complex goal into a hierarchical DAG task structure.</summary>
        [RelayCommand]
        private async Task DecomposeGoal(string goal)
        {
            if (string.IsNullOrWhiteSpace(goal)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { goal, max_depth = 3 }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/planning/decompose", reqContent);
                _logger.LogInformation("Goal decomposed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not decompose goal");
            }
        }

        /// <summary>Executes and verifies a specific plan step node.</summary>
        [RelayCommand]
        private async Task ExecutePlanStep(string parameters)
        {
            if (string.IsNullOrWhiteSpace(parameters)) return;
            var parts = parameters.Split(' ', 2);
            if (parts.Length < 2) return;
            var treeId = parts[0];
            var nodeId = parts[1];
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { tree_id = treeId, node_id = nodeId }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/planning/execute-step", reqContent);
                _logger.LogInformation("Plan step executed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not execute plan step");
            }
        }

        // ── Phase 31 — Mathematical & Algorithmic Computation Commands ────────

        /// <summary>Solves an algebraic equation with step-by-step derivation.</summary>
        [RelayCommand]
        private async Task SolveEquation(string equation)
        {
            if (string.IsNullOrWhiteSpace(equation)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { equation }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/compute/solve", reqContent);
                _logger.LogInformation("Equation solved: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not solve equation");
            }
        }

        /// <summary>Analyzes code snippet Big-O time and space complexity.</summary>
        [RelayCommand]
        private async Task AnalyzeComplexity(string code)
        {
            if (string.IsNullOrWhiteSpace(code)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { code }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/compute/complexity", reqContent);
                _logger.LogInformation("Complexity analyzed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not analyze complexity");
            }
        }

        // ── Phase 32 — Sandboxed Plugin Marketplace Commands ───────────────────

        /// <summary>Installs a verified plugin from the marketplace.</summary>
        [RelayCommand]
        private async Task InstallPlugin(string pluginId)
        {
            if (string.IsNullOrWhiteSpace(pluginId)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { id = pluginId }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/marketplace/install", reqContent);
                _logger.LogInformation("Plugin installed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not install plugin");
            }
        }

        /// <summary>Executes a sandboxed plugin capability method.</summary>
        [RelayCommand]
        private async Task ExecuteSandboxedPlugin(string method)
        {
            if (string.IsNullOrWhiteSpace(method)) return;
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var reqContent = new System.Net.Http.StringContent(
                    System.Text.Json.JsonSerializer.Serialize(new { method, @params = new { } }),
                    System.Text.Encoding.UTF8,
                    "application/json"
                );
                var response = await client.PostAsync("http://localhost:8080/api/v1/marketplace/execute", reqContent);
                _logger.LogInformation("Plugin capability executed: {StatusCode}", response.StatusCode);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not execute plugin capability");
            }
        }

        // ─────────────────────────────────────────────────────────────────────

        [RelayCommand]
        private async Task CheckPendingApprovals()






        {
            try
            {
                using var client = new System.Net.Http.HttpClient();
                var res = await client.GetAsync("http://localhost:8080/api/v1/approvals?status=pending");
                if (res.IsSuccessStatusCode)
                {
                    var json = await res.Content.ReadAsStringAsync();
                    if (json.Contains("\"data\":["))
                    {
                        PendingApprovalsCount = json.Split("\"id\":").Length - 1;
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Could not reach ATOM approval endpoint");
            }
        }

        public async Task LoadChat(long chatId)
        {
            _activeChatId = chatId;
            Messages.Clear();
            try
            {
                var history = await _chatService.GetChatHistory((int)chatId);
                foreach (var msg in history)
                {
                    Messages.Add(new MessageItem
                    {
                        Content = msg.Content,
                        IsUser = msg.Role == "user",
                        Timestamp = msg.CreatedAt
                    });
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to load chat {ChatId}", chatId);
                AddWelcomeMessage();
            }
        }

        [RelayCommand]
        private async Task Send()
        {
            if (string.IsNullOrWhiteSpace(InputText))
                return;

            var userText = InputText.Trim();

            var userMessage = new MessageItem
            {
                Content = userText,
                IsUser = true,
                Timestamp = DateTime.Now
            };

            Messages.Add(userMessage);
            InputText = string.Empty;

            if (_activeChatId == 0)
            {
                Chat chat;
                try
                {
                    chat = await _chatService.CreateChat("New Chat", _activeModel);
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Failed to create chat");
                    return;
                }
                _activeChatId = chat.Id;
            }

            IsStreaming = true;
            var assistantMessage = new MessageItem
            {
                Content = string.Empty,
                IsUser = false,
                Timestamp = DateTime.Now,
                IsStreaming = true
            };

            Messages.Add(assistantMessage);

            try
            {
                var messages = await _chatService.SendMessage((int)_activeChatId, userText, _activeModel);

                var response = string.Empty;
                foreach (var msg in messages)
                {
                    if (msg.Role == "assistant")
                    {
                        response = msg.Content;
                        break;
                    }
                }

                for (int i = 0; i < response.Length; i++)
                {
                    assistantMessage.Content += response[i];
                    if (i % 5 == 0) await Task.Delay(1);
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to send message");
                assistantMessage.Content = "Error: " + ex.Message;
            }

            assistantMessage.IsStreaming = false;
            IsStreaming = false;
        }

        [RelayCommand]
        private void Stop()
        {
            IsStreaming = false;
            if (Messages.Count > 0)
            {
                var lastMessage = Messages[^1];
                lastMessage.IsStreaming = false;
            }
        }

        [RelayCommand]
        private void Attach()
        {
            try
            {
                var dialog = new Microsoft.Win32.OpenFileDialog
                {
                    Filter = "Supported Files (*.txt;*.md;*.json;*.cs;*.php;*.js;*.html;*.css)|*.txt;*.md;*.json;*.cs;*.php;*.js;*.html;*.css|All Files (*.*)|*.*",
                    Title = "Attach Context File to ATOM Chat"
                };

                if (dialog.ShowDialog() == true)
                {
                    var fileContent = System.IO.File.ReadAllText(dialog.FileName);
                    var fileName = System.IO.Path.GetFileName(dialog.FileName);
                    InputText = $"[Attachment: {fileName}]\n{fileContent}\n\n{InputText}";
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to attach file");
            }
        }

        [RelayCommand]
        private void ClearChat()
        {
            Messages.Clear();
            _activeChatId = 0;
            AddWelcomeMessage();
        }

        [RelayCommand]
        private void VoiceInput() { }

        [RelayCommand]
        private void AddImage() { }

        [RelayCommand]
        private void CopyCode(MessageItem message)
        {
            if (message != null)
            {
                System.Windows.Clipboard.SetText(message.Content);
            }
        }

        [RelayCommand]
        private void EditMessage(MessageItem message)
        {
            if (message != null)
            {
                InputText = message.Content;
                Messages.Remove(message);
            }
        }

        [RelayCommand]
        private void Regenerate(MessageItem message)
        {
            if (message != null && !message.IsUser)
            {
                var index = Messages.IndexOf(message);
                if (index > 0)
                {
                    Messages.Remove(message);
                    var userMsg = Messages[index - 1];
                    _ = Send();
                }
            }
        }

        [RelayCommand]
        private void DeleteMessage(MessageItem message)
        {
            if (message != null)
            {
                Messages.Remove(message);
            }
        }

        [RelayCommand]
        private void ExportMessage(MessageItem message)
        {
            if (message != null)
            {
                try
                {
                    System.Windows.Clipboard.SetText(message.Content);
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Failed to export message");
                }
            }
        }
    }
}
