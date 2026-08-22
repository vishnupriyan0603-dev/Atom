using AtomAssistant.ViewModels.Pages;
using Wpf.Ui.Controls;

namespace AtomAssistant.Views.Pages
{
    public partial class ChatPage : UiPage
    {
        public ChatPage(ChatPageViewModel viewModel)
        {
            InitializeComponent();
            DataContext = viewModel;
        }
    }
}
