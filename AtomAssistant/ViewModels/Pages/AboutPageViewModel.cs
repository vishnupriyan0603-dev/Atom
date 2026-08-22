using System.Collections.Generic;
using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.ViewModels.Pages;

public partial class AboutPageViewModel : ObservableObject
{
    [ObservableProperty]
    private string _version = "1.0.0";

    [ObservableProperty]
    private string _description =
        "Atom AI Assistant is a modern desktop application that provides a unified interface for interacting with multiple AI models. " +
        "Built with .NET 9 and WPF, it offers a rich, native experience with support for chat conversations, prompt management, " +
        "file handling, plugins, and knowledge base integration.";

    [ObservableProperty]
    private string _license =
        "This application is provided under the MIT License. Copyright © 2026 Atom AI Assistant. All rights reserved.";

    [ObservableProperty]
    private ObservableCollection<LinkItem> _links = new()
    {
        new() { DisplayName = "GitHub Repository", Url = "https://github.com/atom/atomassistant" },
        new() { DisplayName = "Documentation", Url = "https://docs.atomassistant.dev" },
        new() { DisplayName = "Report an Issue", Url = "https://github.com/atom/atomassistant/issues" },
        new() { DisplayName = "Release Notes", Url = "https://github.com/atom/atomassistant/releases" }
    };

    [ObservableProperty]
    private ObservableCollection<ShortcutItem> _shortcuts = new()
    {
        new() { Key = "Ctrl+N", Description = "New Chat" },
        new() { Key = "Ctrl+Shift+[", Description = "Previous Chat" },
        new() { Key = "Ctrl+Shift+]", Description = "Next Chat" },
        new() { Key = "Ctrl+F", Description = "Search" },
        new() { Key = "Ctrl+B", Description = "Toggle Sidebar" },
        new() { Key = "Ctrl+L", Description = "Toggle Theme" },
        new() { Key = "Ctrl+K", Description = "Command Palette" },
        new() { Key = "Escape", Description = "Close Panel / Cancel" },
        new() { Key = "Ctrl+Enter", Description = "Send Message" },
        new() { Key = "Shift+Enter", Description = "New Line in Message" }
    };

    [ObservableProperty]
    private ObservableCollection<VersionHistoryItem> _versionHistory = new()
    {
        new()
        {
            Version = "1.0.0",
            Date = "2026-07-27",
            Notes = "Initial release with core chat functionality, multi-model support, prompt library, file management, and plugin system."
        },
        new()
        {
            Version = "0.9.0",
            Date = "2026-06-15",
            Notes = "Beta release with knowledge base integration and performance improvements."
        },
        new()
        {
            Version = "0.8.0",
            Date = "2026-05-01",
            Notes = "Alpha release with basic chat and model switching capabilities."
        }
    };
}

public class LinkItem
{
    public string DisplayName { get; set; } = string.Empty;
    public string Url { get; set; } = string.Empty;
}

public class ShortcutItem
{
    public string Key { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
}

public class VersionHistoryItem
{
    public string Version { get; set; } = string.Empty;
    public string Date { get; set; } = string.Empty;
    public string Notes { get; set; } = string.Empty;
}