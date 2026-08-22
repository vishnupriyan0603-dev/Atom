using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using Wpf.Ui;
using Wpf.Ui.Abstractions;
using Wpf.Ui.Controls;

namespace AtomAssistant.Helpers;

public class NavigationHelper : INavigationService
{
    private readonly IServiceProvider _serviceProvider;
    private INavigationView _navigationView;

    private readonly Dictionary<string, Type> _pageRegistry = new()
    {
        { "newchat", typeof(Views.Pages.ChatPage) },
        { "chats", typeof(Views.Pages.ChatsPage) },
        { "models", typeof(Views.Pages.ModelsPage) },
        { "prompts", typeof(Views.Pages.PromptLibraryPage) },
        { "files", typeof(Views.Pages.FilesPage) },
        { "plugins", typeof(Views.Pages.PluginsPage) },
        { "history", typeof(Views.Pages.HistoryPage) },
        { "notes", typeof(Views.Pages.NotesPage) },
        { "knowledge", typeof(Views.Pages.KnowledgeBasePage) },
        { "settings", typeof(Views.Pages.SettingsPage) }
    };

    public NavigationHelper(IServiceProvider serviceProvider)
    {
        _serviceProvider = serviceProvider;
    }

    public void SetNavigationControl(INavigationView navigationView)
    {
        _navigationView = navigationView;

        if (_navigationView is System.Windows.Controls.Control control)
        {
            control.Loaded += (_, _) =>
            {
                Navigate(typeof(Views.Pages.ChatPage));
            };
        }
    }

    public bool Navigate(Type pageType)
    {
        if (_navigationView == null)
            return false;

        var pageUri = pageType.FullName ?? pageType.Name;
        _navigationView.Navigate(pageUri);
        return true;
    }

    public bool Navigate(string pageId)
    {
        if (_pageRegistry.TryGetValue(pageId.ToLowerInvariant(), out var pageType))
        {
            return Navigate(pageType);
        }

        return false;
    }

    public void RegisterPages(INavigationView navigationView)
    {
        if (_navigationView == null)
        {
            SetNavigationControl(navigationView);
        }
    }

    public INavigationView GetNavigationControl()
    {
        return _navigationView;
    }

    public void GoBack()
    {
        _navigationView?.GoBack();
    }
}
