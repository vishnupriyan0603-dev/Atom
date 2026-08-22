using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class NoteFolderItem : ObservableObject
    {
        [ObservableProperty]
        private string name = string.Empty;

        [ObservableProperty]
        private ObservableCollection<NoteItem> notes = new();

        [ObservableProperty]
        private bool isExpanded = true;

        [ObservableProperty]
        private bool isSelected;

        public int NoteCount => Notes.Count;
    }
}
