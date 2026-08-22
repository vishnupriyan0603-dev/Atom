using PersonalAIAssistant.Models;

namespace PersonalAIAssistant.Services;

public sealed class AiProviderRegistry : IAiProviderRegistry
{
    private static readonly AiModelInfo[] Models =
    [
        new("OpenAI", "GPT-4.1", true, false, false, "1M", "Cloud"),
        new("Anthropic", "Claude Sonnet", true, false, false, "200K", "Cloud"),
        new("Google", "Gemini", true, false, false, "1M", "Cloud"),
        new("DeepSeek", "DeepSeek Chat", true, false, false, "64K", "Cloud"),
        new("Groq", "Llama 3.3", true, false, false, "128K", "Cloud"),
        new("Mistral", "Mistral Large", true, false, false, "128K", "Cloud"),
        new("OpenRouter", "Router", true, false, false, "Varies", "Cloud"),
        new("Azure OpenAI", "Deployment", true, false, false, "Varies", "Cloud"),
        new("Ollama", "llama3.1", false, true, false, "128K", "Local RAM"),
        new("LM Studio", "Local server", false, false, false, "Varies", "Local RAM"),
        new("GPT4All", "Local model", false, false, false, "8K", "Local RAM"),
        new("llama.cpp", "GGUF runtime", false, false, false, "Varies", "RAM/VRAM")
    ];

    public IReadOnlyList<AiModelInfo> GetModels() => Models;
}
