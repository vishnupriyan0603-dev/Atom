using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class KnowledgeItem : ObservableObject
    {
        [ObservableProperty]
        private string title = string.Empty;

        [ObservableProperty]
        private string fileType = string.Empty;

        [ObservableProperty]
        private string collection = string.Empty;

        [ObservableProperty]
        private long fileSize;

        [ObservableProperty]
        private DateTime dateAdded = DateTime.Now;

        [ObservableProperty]
        private bool isSelected;
    }
}
