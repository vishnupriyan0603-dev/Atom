using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using PersonalAIAssistant.Models;
using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public partial class MainViewModel : ObservableObject
{
    private readonly INavigationService _navigationService;
    private readonly IThemeService _themeService;

    [ObservableProperty] private string searchText = string.Empty;
    [ObservableProperty] private string currentModel = "Ollama:llama3.1";
    [ObservableProperty] private object? currentPage;
    [ObservableProperty] private NavigationItem? selectedNavigationItem;
    [ObservableProperty] private string selectedTheme = "Auto";

    public ObservableCollection<NavigationItem> NavigationItems { get; } =
    [
        new("Dashboard", "Dashboard"),
        new("Chats", "Chat"),
        new("AI Models", "Models"),
        new("Prompt Library", "Prompts"),
        new("Files", "Files"),
        new("Plugins", "Plugins"),
        new("History", "Chat"),
        new("Notes", "Dashboard"),
        new("Knowledge Base", "Files"),
        new("Settings", "Settings")
    ];

    public IReadOnlyList<string> ThemeOptions { get; } = ["Light", "Dark", "Auto"];

    public MainViewModel(INavigationService navigationService, IThemeService themeService)
    {
        _navigationService = navigationService;
        _themeService = themeService;
        SelectedNavigationItem = NavigationItems[0];
    }

    partial void OnSelectedNavigationItemChanged(NavigationItem? value)
    {
        if (value is not null)
        {
            CurrentPage = _navigationService.ResolvePage(value.PageKey);
        }
    }

    async partial void OnSelectedThemeChanged(string value)
    {
        await _themeService.SetThemeAsync(value);
    }

    [RelayCommand]
    private void NewChat()
    {
        SelectedNavigationItem = NavigationItems.First(item => item.PageKey == "Chat");
    }
}
