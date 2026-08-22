using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.Linq;
using System.Windows.Input;
using AtomAssistant.Helpers;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using Microsoft.Extensions.Configuration;
using Wpf.Ui;
using Wpf.Ui.Controls;

namespace AtomAssistant.ViewModels;

public partial class MainWindowViewModel : ObservableObject
{
    private readonly ThemeHelper _themeHelper;
    private readonly INavigationService _navigationService;
    private readonly IConfiguration _configuration;
    private readonly IServiceProvider _serviceProvider;

    [ObservableProperty]
    private object? _currentPage;

    [ObservableProperty]
    private string _currentModel;

    [ObservableProperty]
    private string _searchQuery = string.Empty;

    [ObservableProperty]
    private bool _isNavigationPaneOpen = true;

    [ObservableProperty]
    private bool _isChatInputVisible;

    [ObservableProperty]
    private string _themeIcon = "WeatherSunny16";

    [ObservableProperty]
    private ObservableCollection<string> _availableModels = new()
    {
        "gpt-4o",
        "gpt-4o-mini",
        "claude-3-opus",
        "claude-3-sonnet",
        "llama3",
        "mistral"
    };

    private readonly List<string> _pageTypes = new()
    {
        "newchat", "chats", "models", "prompts",
        "files", "plugins", "history", "notes",
        "knowledge", "settings"
    };

    public MainWindowViewModel(
        ThemeHelper themeHelper,
        INavigationService navigationService,
        IConfiguration configuration,
        IServiceProvider serviceProvider)
    {
        _themeHelper = themeHelper;
        _navigationService = navigationService;
        _configuration = configuration;
        _serviceProvider = serviceProvider;

        CurrentModel = configuration["AI:OpenAI:Model"] ?? "gpt-4o";

        UpdateThemeIcon();

        _themeHelper.ThemeChanged += mode =>
        {
            UpdateThemeIcon();
        };
    }

    private void UpdateThemeIcon()
    {
        ThemeIcon = _themeHelper.CurrentMode switch
        {
            ThemeMode.Light => "WeatherSunny16",
            ThemeMode.Dark => "WeatherMoon16",
            ThemeMode.System => "SettingsBrightness16",
            _ => "WeatherSunny16"
        };
    }

    [RelayCommand]
    private void NavigateToPage(string pageTag)
    {
        if (string.IsNullOrEmpty(pageTag))
            return;

        _navigationService.Navigate(pageTag);

        IsChatInputVisible = pageTag.Equals("newchat", StringComparison.OrdinalIgnoreCase);
    }

    [RelayCommand]
    private void ToggleTheme()
    {
        var newMode = _themeHelper.CurrentMode switch
        {
            ThemeMode.Light => ThemeMode.Dark,
            ThemeMode.Dark => ThemeMode.System,
            ThemeMode.System => ThemeMode.Light,
            _ => ThemeMode.Light
        };

        _themeHelper.ApplyTheme(newMode);
    }

    [RelayCommand]
    private void Search()
    {
        if (string.IsNullOrWhiteSpace(SearchQuery))
            return;

        var trimmedQuery = SearchQuery.Trim();
        _navigationService.Navigate(typeof(Views.Pages.ChatsPage));
    }
}