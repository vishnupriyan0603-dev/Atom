using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class ChatItem : ObservableObject
    {
        [ObservableProperty]
        private string title = string.Empty;

        [ObservableProperty]
        private string preview = string.Empty;

        [ObservableProperty]
        private DateTime lastModified = DateTime.Now;

        [ObservableProperty]
        private bool isArchived;
    }
}
