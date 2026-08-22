using System.Windows;
using System.Windows.Controls;
using AtomAssistant.Helpers;
using AtomAssistant.ViewModels;
using Microsoft.Extensions.DependencyInjection;
using Wpf.Ui;
using Wpf.Ui.Abstractions.Controls;
using Wpf.Ui.Controls;

namespace AtomAssistant.Views;

public partial class MainWindow : UiWindow, INavigationWindow
{
    private readonly NavigationHelper _navigationHelper;
    private readonly MainWindowViewModel _viewModel;

    public MainWindow(
        NavigationHelper navigationHelper,
        MainWindowViewModel viewModel)
    {
        _navigationHelper = navigationHelper;
        _viewModel = viewModel;

        InitializeComponent();

        DataContext = _viewModel;

        _navigationHelper.SetNavigationControl(RootNavigation);

        Loaded += OnLoaded;
        Closing += OnClosing;
    }

    private void OnLoaded(object sender, RoutedEventArgs e)
    {
        WindowHelper.RestoreWindowState(this);
    }

    private void OnClosing(object? sender, System.ComponentModel.CancelEventArgs e)
    {
        WindowHelper.SaveWindowState(this);
    }

    public INavigationView GetNavigation()
    {
        return RootNavigation;
    }

    public bool Navigate(Type pageType)
    {
        return RootNavigation.Navigate(pageType);
    }

    public void SetServiceProvider(IServiceProvider serviceProvider)
    {
        RootNavigation.SetServiceProvider(serviceProvider);
    }

    public void SetPageType(Type pageType)
    {
        RootNavigation.PageType = pageType;
    }

    public void ShowWindow()
    {
        Show();
    }

    public void CloseWindow()
    {
        Close();
    }
}