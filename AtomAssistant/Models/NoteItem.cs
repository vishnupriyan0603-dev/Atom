using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class NoteItem : ObservableObject
    {
        [ObservableProperty]
        private string title = string.Empty;

        [ObservableProperty]
        private string content = string.Empty;

        [ObservableProperty]
        private string folder = string.Empty;

        [ObservableProperty]
        private DateTime lastModified = DateTime.Now;

        [ObservableProperty]
        private bool isSelected;
    }
}
