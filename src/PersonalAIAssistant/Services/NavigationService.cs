using PersonalAIAssistant.ViewModels;
using PersonalAIAssistant.Views;
using Microsoft.Extensions.DependencyInjection;

namespace PersonalAIAssistant.Services;

public sealed class NavigationService(IServiceProvider serviceProvider) : INavigationService
{
    public object ResolvePage(string pageKey) => pageKey switch
    {
        "Dashboard" => new DashboardView { DataContext = serviceProvider.GetRequiredService<DashboardViewModel>() },
        "Chat" => new ChatView { DataContext = serviceProvider.GetRequiredService<ChatViewModel>() },
        "Models" => new ModelsView { DataContext = serviceProvider.GetRequiredService<ModelsViewModel>() },
        "Prompts" => new PromptLibraryView { DataContext = serviceProvider.GetRequiredService<PromptLibraryViewModel>() },
        "Files" => new FilesView { DataContext = serviceProvider.GetRequiredService<FilesViewModel>() },
        "Plugins" => new PluginsView { DataContext = serviceProvider.GetRequiredService<PluginsViewModel>() },
        "Settings" => new SettingsView { DataContext = serviceProvider.GetRequiredService<SettingsViewModel>() },
        _ => new DashboardView { DataContext = serviceProvider.GetRequiredService<DashboardViewModel>() }
    };
}
