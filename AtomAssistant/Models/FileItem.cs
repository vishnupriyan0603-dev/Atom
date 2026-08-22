using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class FileItem : ObservableObject
    {
        [ObservableProperty]
        private string fileName = string.Empty;

        [ObservableProperty]
        private string filePath = string.Empty;

        [ObservableProperty]
        private string fileType = string.Empty;

        [ObservableProperty]
        private long fileSize;

        [ObservableProperty]
        private DateTime dateAdded = DateTime.Now;
    }
}
