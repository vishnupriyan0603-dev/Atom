using System.Net.Http;
using System.Net.Http.Json;
using System.Text.Json;
using PersonalAIAssistant.Models;

namespace PersonalAIAssistant.Services;

public sealed class AiService(IChatRepository chatRepository, ISettingsService settingsService) : IAiService
{
    private readonly HttpClient _http = new() { Timeout = TimeSpan.FromSeconds(60) };

    public async Task<string> SendMessageAsync(long chatId, string message, string model, string provider, IReadOnlyList<ChatMessage> history)
    {
        await chatRepository.AddMessageAsync(chatId, "user", message);

        var response = provider?.ToLowerInvariant() switch
        {
            "ollama" => await CallOllamaAsync(model, history, message),
            "openai" => await CallOpenAiAsync(model, history, message),
            "anthropic" => await CallAnthropicAsync(model, history, message),
            _ => await CallGenericEndpointAsync(provider, model, history, message),
        };

        var content = response ?? FallbackResponse(provider, model);

        await chatRepository.AddMessageAsync(chatId, "assistant", content);

        return content;
    }

    private async Task<string?> CallOllamaAsync(string model, IReadOnlyList<ChatMessage> history, string message)
    {
        try
        {
            var messages = BuildHistory(history, message);
            var payload = new { model = model.Contains(':') ? model : $"{model}:latest", messages, stream = false };
            var resp = await _http.PostAsJsonAsync("http://localhost:11434/api/chat", payload);
            if (!resp.IsSuccessStatusCode) return null;

            var json = await resp.Content.ReadFromJsonAsync<JsonElement>();
            return json.TryGetProperty("message", out var m) && m.TryGetProperty("content", out var c) ? c.GetString() : null;
        }
        catch
        {
            return null;
        }
    }

    private async Task<string?> CallOpenAiAsync(string model, IReadOnlyList<ChatMessage> history, string message)
    {
        try
        {
            var apiKey = await settingsService.GetAsync("openai_api_key", "");
            if (string.IsNullOrEmpty(apiKey)) return null;

            _http.DefaultRequestHeaders.Remove("Authorization");
            _http.DefaultRequestHeaders.Add("Authorization", $"Bearer {apiKey}");

            var messages = BuildHistory(history, message);
            var payload = new { model = model ?? "gpt-4", messages, stream = false };
            var resp = await _http.PostAsJsonAsync("https://api.openai.com/v1/chat/completions", payload);
            if (!resp.IsSuccessStatusCode) return null;

            var json = await resp.Content.ReadFromJsonAsync<JsonElement>();
            return json.TryGetProperty("choices", out var choices) && choices.GetArrayLength() > 0
                ? choices[0].TryGetProperty("message", out var msg) && msg.TryGetProperty("content", out var c) ? c.GetString() : null
                : null;
        }
        catch
        {
            return null;
        }
    }

    private async Task<string?> CallAnthropicAsync(string model, IReadOnlyList<ChatMessage> history, string message)
    {
        try
        {
            var apiKey = await settingsService.GetAsync("anthropic_api_key", "");
            if (string.IsNullOrEmpty(apiKey)) return null;

            _http.DefaultRequestHeaders.Remove("x-api-key");
            _http.DefaultRequestHeaders.Add("x-api-key", apiKey);
            _http.DefaultRequestHeaders.Add("anthropic-version", "2023-06-01");

            var messages = BuildHistory(history, message).Select(m => new { role = m.role, content = m.content }).ToList();
            var payload = new { model = model ?? "claude-3-sonnet-20240229", max_tokens = 4096, messages };
            var resp = await _http.PostAsJsonAsync("https://api.anthropic.com/v1/messages", payload);
            if (!resp.IsSuccessStatusCode) return null;

            var json = await resp.Content.ReadFromJsonAsync<JsonElement>();
            return json.TryGetProperty("content", out var content) && content.GetArrayLength() > 0
                ? content[0].TryGetProperty("text", out var t) ? t.GetString() : null
                : null;
        }
        catch
        {
            return null;
        }
    }

    private async Task<string?> CallGenericEndpointAsync(string? provider, string? model, IReadOnlyList<ChatMessage> history, string message)
    {
        if (string.IsNullOrEmpty(provider)) return null;

        try
        {
            var endpoint = await settingsService.GetAsync($"{provider.ToLowerInvariant()}_endpoint", "");
            var apiKey = await settingsService.GetAsync($"{provider.ToLowerInvariant()}_api_key", "");
            if (string.IsNullOrEmpty(endpoint)) return null;

            if (!string.IsNullOrEmpty(apiKey))
            {
                _http.DefaultRequestHeaders.Remove("Authorization");
                _http.DefaultRequestHeaders.Add("Authorization", $"Bearer {apiKey}");
            }

            var messages = BuildHistory(history, message);
            var payload = new { model = model ?? "default", messages, stream = false };
            var resp = await _http.PostAsJsonAsync(endpoint, payload);
            if (!resp.IsSuccessStatusCode) return null;

            return await resp.Content.ReadAsStringAsync();
        }
        catch
        {
            return null;
        }
    }

    private record ChatMsgPayload(string role, string content);

    private static List<ChatMsgPayload> BuildHistory(IReadOnlyList<ChatMessage> history, string newMessage)
    {
        var messages = history
            .Where(m => !string.IsNullOrEmpty(m.Content))
            .Select(m => new ChatMsgPayload(m.Role == "assistant" ? "assistant" : "user", m.Content))
            .ToList();

        messages.Add(new ChatMsgPayload("user", newMessage));
        return messages;
    }

    private static string FallbackResponse(string? provider, string? model)
    {
        var modelName = !string.IsNullOrEmpty(model) ? model : "default";
        var providerName = !string.IsNullOrEmpty(provider) ? provider : "local";

        return $"""
            I'm running in preview mode with {providerName} ({modelName}).

            To connect a real AI provider:
              - **Ollama**: Run `ollama serve` on localhost:11434
              - **OpenAI**: Set your API key in Settings
              - **Anthropic**: Set your API key in Settings
              - **LM Studio**: Start the local server

            Your message has been saved to the local database. Once a provider is connected, I'll respond intelligently.
            """;
    }
}
