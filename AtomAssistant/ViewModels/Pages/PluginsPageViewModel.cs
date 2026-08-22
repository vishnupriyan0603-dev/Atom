using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class PluginsPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<PluginItem> installedPlugins = new();

        [ObservableProperty]
        private ObservableCollection<PluginItem> marketplacePlugins = new();

        [ObservableProperty]
        private PluginItem? selectedPlugin;

        [ObservableProperty]
        private PluginItem? selectedMarketplacePlugin;

        public PluginsPageViewModel()
        {
            LoadSampleData();
        }

        private void LoadSampleData()
        {
            InstalledPlugins = new ObservableCollection<PluginItem>
            {
                new() { Name = "Code Analyzer",       Description = "Real-time code analysis and suggestions",   Version = "2.1.0", IsEnabled = true,  HasUpdate = true,  Author = "AtomTeam" },
                new() { Name = "Git Integration",      Description = "Git commands and status directly in chat", Version = "1.5.3", IsEnabled = true,  HasUpdate = false, Author = "AtomTeam" },
                new() { Name = "Terminal Emulator",    Description = "Embedded terminal with command execution",  Version = "1.0.0", IsEnabled = false, HasUpdate = false, Author = "Community" },
                new() { Name = "Markdown Preview",     Description = "Live markdown rendering panel",             Version = "0.9.2", IsEnabled = true,  HasUpdate = true,  Author = "Community" }
            };

            MarketplacePlugins = new ObservableCollection<PluginItem>
            {
                new() { Name = "Database Browser",     Description = "Browse and query databases",                Version = "1.0.0", Author = "AtomTeam" },
                new() { Name = "AI Image Generator",   Description = "Generate images with DALL-E / Stable Diffusion", Version = "0.5.0", Author = "Community" },
                new() { Name = "Web Scraper",          Description = "Extract and analyze web content",           Version = "2.0.0", Author = "Community" },
                new() { Name = "Unit Test Generator",  Description = "Auto-generate unit tests for your code",   Version = "1.2.0", Author = "AtomTeam" }
            };
        }

        [RelayCommand]
        private async Task PluginSettings(PluginItem plugin)
        {
            // Open plugin-specific settings dialog
            await Task.Delay(300);
        }

        [RelayCommand]
        private async Task UpdatePlugin(PluginItem plugin)
        {
            if (plugin != null)
            {
                // Download and install update logic
                await Task.Delay(2000);
                plugin.HasUpdate = false;
            }
        }

        [RelayCommand]
        private async Task InstallPlugin(PluginItem plugin)
        {
            if (plugin != null)
            {
                // Download and install logic
                await Task.Delay(1500);
                InstalledPlugins.Add(new PluginItem
                {
                    Name = plugin.Name,
                    Description = plugin.Description,
                    Version = plugin.Version,
                    IsEnabled = true,
                    Author = plugin.Author
                });
                MarketplacePlugins.Remove(plugin);
            }
        }
    }
}
