using System.Windows;
using System.Windows.Controls;
using AtomAssistant.Helpers;

namespace AtomAssistant.Controls;

public partial class SidebarControl : UserControl
{
    private readonly NavigationHelper _navigationHelper;

    public SidebarControl(NavigationHelper navigationHelper)
    {
        InitializeComponent();
        _navigationHelper = navigationHelper;

        NavigationView.Loaded += OnNavigationViewLoaded;
        NavigationView.ItemInvoked += OnNavigationItemInvoked;
    }

    private void OnNavigationViewLoaded(object sender, RoutedEventArgs e)
    {
        _navigationHelper.RegisterPages(NavigationView);
    }

    private void OnNavigationItemInvoked(object sender, Wpf.Ui.Controls.NavigationViewItemInvokedEventArgs e)
    {
        if (e.InvokedItemContainer?.Tag is string tag)
        {
            _navigationHelper.Navigate(tag);
        }
    }
}
