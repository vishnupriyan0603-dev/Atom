using System.Net.Http.Headers;
using System.Net.Http.Json;
using System.Runtime.CompilerServices;
using System.Text;
using System.Text.Json;
using System.Text.Json.Nodes;
using AtomAssistant.Models;
using Microsoft.Extensions.Configuration;

namespace AtomAssistant.Services
{
    public class CloudAiService : IAiProviderService
    {
        private readonly HttpClient _httpClient;
        private readonly IConfiguration _configuration;

        public CloudAiService(HttpClient httpClient, IConfiguration configuration)
        {
            _httpClient = httpClient;
            _configuration = configuration;
        }

        public async Task SendMessageAsync(string model, List<Message> history, Action<string> onStream)
        {
            var provider = _configuration["AI:Provider"] ?? "OpenAI";

            switch (provider)
            {
                case "OpenAI":
                    await SendOpenAiAsync(model, history, onStream);
                    break;
                case "Azure":
                    await SendAzureOpenAiAsync(model, history, onStream);
                    break;
                case "Anthropic":
                    await SendClaudeAsync(model, history, onStream);
                    break;
                case "Gemini":
                    await SendGeminiAsync(model, history, onStream);
                    break;
                case "DeepSeek":
                    await SendDeepSeekAsync(model, history, onStream);
                    break;
                case "Groq":
                    await SendGroqAsync(model, history, onStream);
                    break;
                case "OpenRouter":
                    await SendOpenRouterAsync(model, history, onStream);
                    break;
                default:
                    await SendOpenAiAsync(model, history, onStream);
                    break;
            }
        }

        public async Task<List<AiModel>> GetModelsAsync()
        {
            var models = new List<AiModel>();

            models.Add(new AiModel
            {
                Name = _configuration["AI:OpenAI:Model"] ?? "gpt-4o",
                Provider = "OpenAI",
                IsLocal = false,
                IsEnabled = true,
                ContextLength = 8192
            });

            models.Add(new AiModel
            {
                Name = _configuration["AI:Anthropic:Model"] ?? "claude-3-opus-20240229",
                Provider = "Anthropic",
                IsLocal = false,
                IsEnabled = true,
                ContextLength = 200000
            });

            var azureDeployment = _configuration["AI:Azure:DeploymentName"];
            if (!string.IsNullOrEmpty(azureDeployment))
            {
                models.Add(new AiModel
                {
                    Name = azureDeployment,
                    Provider = "Azure",
                    IsLocal = false,
                    IsEnabled = true,
                    ContextLength = 8192
                });
            }

            models.Add(new AiModel { Name = "gemini-pro", Provider = "Gemini", IsLocal = false, IsEnabled = true, ContextLength = 30720 });
            models.Add(new AiModel { Name = "deepseek-chat", Provider = "DeepSeek", IsLocal = false, IsEnabled = true, ContextLength = 32768 });
            models.Add(new AiModel { Name = "mixtral-8x7b-32768", Provider = "Groq", IsLocal = false, IsEnabled = true, ContextLength = 32768 });
            models.Add(new AiModel { Name = "openrouter/auto", Provider = "OpenRouter", IsLocal = false, IsEnabled = true, ContextLength = 131072 });

            return await Task.FromResult(models);
        }

        private async Task SendOpenAiAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = _configuration["AI:OpenAI:Endpoint"] ?? "https://api.openai.com/v1";
            var apiKey = _configuration["AI:OpenAI:ApiKey"] ?? "";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096"),
                temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7")
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/chat/completions")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendAzureOpenAiAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = _configuration["AI:Azure:Endpoint"] ?? "";
            var apiKey = _configuration["AI:Azure:ApiKey"] ?? "";
            var deployment = _configuration["AI:Azure:DeploymentName"] ?? model;

            var requestBody = new
            {
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096"),
                temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7")
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/openai/deployments/{deployment}/chat/completions?api-version=2024-02-15-preview")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Add("api-key", apiKey);

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendClaudeAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = _configuration["AI:Anthropic:Endpoint"] ?? "https://api.anthropic.com/v1";
            var apiKey = _configuration["AI:Anthropic:ApiKey"] ?? "";

            var systemMessages = history.Where(m => m.Role == "system").ToList();
            var chatMessages = history.Where(m => m.Role != "system").ToList();

            var requestBody = new
            {
                model,
                system = systemMessages.LastOrDefault()?.Content ?? "",
                messages = chatMessages.Select(m => new { role = m.Role == "assistant" ? "assistant" : "user", content = m.Content }),
                max_tokens = 4096,
                stream = true
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/messages")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Add("x-api-key", apiKey);
            request.Headers.Add("anthropic-version", "2023-06-01");

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..].Trim();
                if (string.IsNullOrEmpty(data)) continue;

                using var doc = JsonDocument.Parse(data);
                if (doc.RootElement.TryGetProperty("type", out var typeEl) && typeEl.GetString() == "content_block_delta")
                {
                    var delta = doc.RootElement.GetProperty("delta");
                    if (delta.TryGetProperty("text", out var textEl))
                    {
                        var content = textEl.GetString();
                        if (!string.IsNullOrEmpty(content))
                            onStream(content);
                    }
                }
            }
        }

        private async Task SendGeminiAsync(string model, List<Message> history, Action<string> onStream)
        {
            var apiKey = _configuration["AI:Gemini:ApiKey"] ?? "";

            var contents = new List<object>();
            foreach (var msg in history)
            {
                if (msg.Role == "system") continue;
                contents.Add(new
                {
                    role = msg.Role == "assistant" ? "model" : "user",
                    parts = new[] { new { text = msg.Content } }
                });
            }

            var requestBody = new
            {
                contents,
                generationConfig = new
                {
                    temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7"),
                    maxOutputTokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096")
                }
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"https://generativelanguage.googleapis.com/v1beta/models/{model}:streamGenerateContent?key={apiKey}&alt=sse")
            {
                Content = JsonContent.Create(requestBody)
            };

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;

                using var doc = JsonDocument.Parse(line);
                if (doc.RootElement.TryGetProperty("candidates", out var candidates) && candidates.GetArrayLength() > 0)
                {
                    var candidate = candidates[0];
                    if (candidate.TryGetProperty("content", out var contentEl) &&
                        contentEl.TryGetProperty("parts", out var parts) &&
                        parts.GetArrayLength() > 0 &&
                        parts[0].TryGetProperty("text", out var textEl))
                    {
                        var content = textEl.GetString();
                        if (!string.IsNullOrEmpty(content))
                            onStream(content);
                    }
                }
            }
        }

        private async Task SendDeepSeekAsync(string model, List<Message> history, Action<string> onStream)
        {
            var apiKey = _configuration["AI:DeepSeek:ApiKey"] ?? "";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096"),
                temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7")
            };

            var request = new HttpRequestMessage(HttpMethod.Post, "https://api.deepseek.com/v1/chat/completions")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendGroqAsync(string model, List<Message> history, Action<string> onStream)
        {
            var apiKey = _configuration["AI:Groq:ApiKey"] ?? "";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096"),
                temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7")
            };

            var request = new HttpRequestMessage(HttpMethod.Post, "https://api.groq.com/openai/v1/chat/completions")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendOpenRouterAsync(string model, List<Message> history, Action<string> onStream)
        {
            var apiKey = _configuration["AI:OpenRouter:ApiKey"] ?? "";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = int.Parse(_configuration["AI:OpenAI:MaxTokens"] ?? "4096"),
                temperature = double.Parse(_configuration["AI:OpenAI:Temperature"] ?? "0.7")
            };

            var request = new HttpRequestMessage(HttpMethod.Post, "https://openrouter.ai/api/v1/chat/completions")
            {
                Content = JsonContent.Create(requestBody)
            };
            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);
            request.Headers.Add("HTTP-Referer", "https://atomassistant.app");
            request.Headers.Add("X-Title", "AtomAssistant");

            using var response = await _httpClient.SendAsync(request, HttpCompletionOption.ResponseHeadersRead);
            response.EnsureSuccessStatusCode();

            using var stream = await response.Content.ReadAsStreamAsync();
            using var reader = new StreamReader(stream);

            while (!reader.EndOfStream)
            {
                var line = await reader.ReadLineAsync();
                if (string.IsNullOrEmpty(line)) continue;
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }
    }
}
