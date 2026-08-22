namespace PersonalAIAssistant.Services;

public sealed class PluginRegistry : IPluginRegistry
{
    public IReadOnlyList<string> InstalledPlugins { get; } =
    [
        "File Analyzer", "Code Assistant", "Prompt Tools", "Local Model Controller"
    ];
}
