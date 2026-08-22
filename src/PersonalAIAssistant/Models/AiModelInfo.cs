namespace PersonalAIAssistant.Models;

public sealed record AiModelInfo(
    string Provider,
    string Name,
    bool IsCloud,
    bool IsInstalled,
    bool IsRunning,
    string ContextLength,
    string MemoryUsage);
