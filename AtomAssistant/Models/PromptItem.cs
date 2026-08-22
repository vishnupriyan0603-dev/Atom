using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class PromptItem : ObservableObject
    {
        [ObservableProperty]
        private string title = string.Empty;

        [ObservableProperty]
        private string content = string.Empty;

        [ObservableProperty]
        private string category = string.Empty;

        [ObservableProperty]
        private bool isFavorite;

        [ObservableProperty]
        private DateTime dateCreated = DateTime.Now;
    }
}
