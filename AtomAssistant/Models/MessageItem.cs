using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class MessageItem : ObservableObject
    {
        [ObservableProperty]
        private string content = string.Empty;

        [ObservableProperty]
        private bool isUser;

        [ObservableProperty]
        private DateTime timestamp = DateTime.Now;

        [ObservableProperty]
        private bool isStreaming;

        [ObservableProperty]
        private bool isSelected;
    }
}
