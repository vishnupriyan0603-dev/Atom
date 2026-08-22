using System.Windows.Controls;
using PersonalAIAssistant.ViewModels;

namespace PersonalAIAssistant.Views;

public partial class SelfImprovementView : UserControl
{
    public SelfImprovementView(SelfImprovementViewModel viewModel)
    {
        InitializeComponent();
        DataContext = viewModel;
    }
}
