namespace PersonalAIAssistant.ViewModels;

public sealed class SettingsViewModel
{
    public IReadOnlyList<string> ProviderSettings { get; } =
    [
        "OpenAI", "Claude", "Gemini", "DeepSeek", "Groq", "Mistral", "OpenRouter", "Azure OpenAI", "Ollama", "LM Studio"
    ];
}
