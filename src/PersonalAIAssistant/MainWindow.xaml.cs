using System.Windows;
using PersonalAIAssistant.ViewModels;

namespace PersonalAIAssistant;

public partial class MainWindow : Window
{
    public MainWindow(MainViewModel viewModel)
    {
        InitializeComponent();
        DataContext = viewModel;
    }
}
