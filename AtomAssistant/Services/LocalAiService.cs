using System.Net.Http.Json;
using System.Text.Json;
using AtomAssistant.Models;
using Microsoft.Extensions.Configuration;

namespace AtomAssistant.Services
{
    public class LocalAiService : IAiProviderService
    {
        private readonly HttpClient _httpClient;
        private readonly IConfiguration _configuration;

        public LocalAiService(HttpClient httpClient, IConfiguration configuration)
        {
            _httpClient = httpClient;
            _configuration = configuration;
        }

        public async Task SendMessageAsync(string model, List<Message> history, Action<string> onStream)
        {
            var provider = DetectProvider(model);

            switch (provider)
            {
                case "ollama":
                    await SendOllamaAsync(model, history, onStream);
                    break;
                case "lmstudio":
                    await SendLmStudioAsync(model, history, onStream);
                    break;
                case "gpt4all":
                    await SendGpt4AllAsync(model, history, onStream);
                    break;
                case "llamacpp":
                    await SendLlamacppAsync(model, history, onStream);
                    break;
                default:
                    await SendOllamaAsync(model, history, onStream);
                    break;
            }
        }

        public async Task<List<AiModel>> GetModelsAsync()
        {
            var models = new List<AiModel>();

            try
            {
                var ollamaEndpoint = _configuration["AI:Ollama:Endpoint"] ?? "http://localhost:11434";
                var ollamaResponse = await _httpClient.GetAsync($"{ollamaEndpoint}/api/tags");
                if (ollamaResponse.IsSuccessStatusCode)
                {
                    var json = await ollamaResponse.Content.ReadAsStringAsync();
                    using var doc = JsonDocument.Parse(json);
                    foreach (var modelEl in doc.RootElement.GetProperty("models").EnumerateArray())
                    {
                        models.Add(new AiModel
                        {
                            Name = modelEl.GetProperty("name").GetString(),
                            Provider = "ollama",
                            ApiEndpoint = ollamaEndpoint,
                            IsLocal = true,
                            IsEnabled = true,
                            ContextLength = 4096
                        });
                    }
                }
            }
            catch { }

            try
            {
                var lmStudioResponse = await _httpClient.GetAsync("http://localhost:1234/v1/models");
                if (lmStudioResponse.IsSuccessStatusCode)
                {
                    var json = await lmStudioResponse.Content.ReadAsStringAsync();
                    using var doc = JsonDocument.Parse(json);
                    foreach (var modelEl in doc.RootElement.GetProperty("data").EnumerateArray())
                    {
                        models.Add(new AiModel
                        {
                            Name = modelEl.GetProperty("id").GetString(),
                            Provider = "lmstudio",
                            ApiEndpoint = "http://localhost:1234",
                            IsLocal = true,
                            IsEnabled = true,
                            ContextLength = 4096
                        });
                    }
                }
            }
            catch { }

            try
            {
                var gpt4AllResponse = await _httpClient.GetAsync("http://localhost:4891/v1/models");
                if (gpt4AllResponse.IsSuccessStatusCode)
                {
                    models.Add(new AiModel
                    {
                        Name = "gpt4all",
                        Provider = "gpt4all",
                        ApiEndpoint = "http://localhost:4891",
                        IsLocal = true,
                        IsEnabled = true,
                        ContextLength = 2048
                    });
                }
            }
            catch { }

            try
            {
                var llamacppResponse = await _httpClient.GetAsync("http://localhost:8080/v1/models");
                if (llamacppResponse.IsSuccessStatusCode)
                {
                    var json = await llamacppResponse.Content.ReadAsStringAsync();
                    using var doc = JsonDocument.Parse(json);
                    if (doc.RootElement.TryGetProperty("data", out var data))
                    {
                        foreach (var modelEl in data.EnumerateArray())
                        {
                            models.Add(new AiModel
                            {
                                Name = modelEl.GetProperty("id").GetString(),
                                Provider = "llamacpp",
                                ApiEndpoint = "http://localhost:8080",
                                IsLocal = true,
                                IsEnabled = true,
                                ContextLength = 4096
                            });
                        }
                    }
                    else if (doc.RootElement.TryGetProperty("models", out var modelsArr))
                    {
                        foreach (var modelEl in modelsArr.EnumerateArray())
                        {
                            models.Add(new AiModel
                            {
                                Name = modelEl.GetProperty("name").GetString(),
                                Provider = "llamacpp",
                                ApiEndpoint = "http://localhost:8080",
                                IsLocal = true,
                                IsEnabled = true,
                                ContextLength = 4096
                            });
                        }
                    }
                }
            }
            catch { }

            return models;
        }

        private string DetectProvider(string model)
        {
            if (model.Contains("ollama", StringComparison.OrdinalIgnoreCase) ||
                model.Contains("llama", StringComparison.OrdinalIgnoreCase) ||
                model.Contains("mistral", StringComparison.OrdinalIgnoreCase) ||
                model.Contains("phi", StringComparison.OrdinalIgnoreCase))
                return "ollama";

            if (model.Contains("lmstudio", StringComparison.OrdinalIgnoreCase))
                return "lmstudio";

            if (model.Contains("gpt4all", StringComparison.OrdinalIgnoreCase))
                return "gpt4all";

            if (model.Contains("llamacpp", StringComparison.OrdinalIgnoreCase))
                return "llamacpp";

            return "ollama";
        }

        private async Task SendOllamaAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = _configuration["AI:Ollama:Endpoint"] ?? "http://localhost:11434";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/api/chat")
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
                if (doc.RootElement.TryGetProperty("done", out var done) && done.GetBoolean())
                    break;

                if (doc.RootElement.TryGetProperty("message", out var msgEl) &&
                    msgEl.TryGetProperty("content", out var contentEl))
                {
                    var content = contentEl.GetString();
                    if (!string.IsNullOrEmpty(content))
                        onStream(content);
                }
            }
        }

        private async Task SendLmStudioAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = "http://localhost:1234";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = 4096,
                temperature = 0.7
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/v1/chat/completions")
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
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendGpt4AllAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = "http://localhost:4891";

            var requestBody = new
            {
                model,
                messages = history.Select(m => new { role = m.Role, content = m.Content }),
                stream = true,
                max_tokens = 2048,
                temperature = 0.7
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/v1/chat/completions")
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
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                var content = doc.RootElement.GetProperty("choices")[0].GetProperty("delta").GetProperty("content").GetString();
                if (!string.IsNullOrEmpty(content))
                    onStream(content);
            }
        }

        private async Task SendLlamacppAsync(string model, List<Message> history, Action<string> onStream)
        {
            var endpoint = "http://localhost:8080";

            var prompt = string.Join("\n", history.Select(m => $"<|{m.Role}|>\n{m.Content}"));
            prompt += "\n<|assistant|>\n";

            var requestBody = new
            {
                prompt,
                stream = true,
                n_predict = 4096,
                temperature = 0.7
            };

            var request = new HttpRequestMessage(HttpMethod.Post, $"{endpoint}/completions")
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
                if (!line.StartsWith("data: ")) continue;

                var data = line[6..];
                if (data == "[DONE]") break;

                using var doc = JsonDocument.Parse(data);
                if (doc.RootElement.TryGetProperty("content", out var contentEl))
                {
                    var content = contentEl.GetString();
                    if (!string.IsNullOrEmpty(content))
                        onStream(content);
                }
            }
        }
    }
}
