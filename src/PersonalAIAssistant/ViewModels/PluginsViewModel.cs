using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed class PluginsViewModel(IPluginRegistry pluginRegistry)
{
    public IReadOnlyList<string> InstalledPlugins { get; } = pluginRegistry.InstalledPlugins;
    public IReadOnlyList<string> AvailableHooks { get; } = ["Chat command", "File processor", "Model provider", "Voice provider"];
}
