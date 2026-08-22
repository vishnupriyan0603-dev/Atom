using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class KnowledgePageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<NoteFolderItem> collections = new();

        [ObservableProperty]
        private ObservableCollection<KnowledgeItem> documents = new();

        [ObservableProperty]
        private NoteFolderItem? selectedCollection;

        [ObservableProperty]
        private KnowledgeItem? selectedDocument;

        [ObservableProperty]
        private string searchText = string.Empty;

        public KnowledgePageViewModel()
        {
            LoadSampleData();
        }

        partial void OnSearchTextChanged(string value) => FilterDocuments();

        private void LoadSampleData()
        {
            Collections = new ObservableCollection<NoteFolderItem>
            {
                new() { Name = "Project Documentation" },
                new() { Name = "Code References" },
                new() { Name = "Meeting Notes" },
                new() { Name = "Research Papers" }
            };

            Documents = new ObservableCollection<KnowledgeItem>
            {
                new() { Title = "Architecture Overview.pdf",    FileType = "PDF",   Collection = "Project Documentation", DateAdded = new DateTime(2026, 7, 20) },
                new() { Title = "API Reference.docx",          FileType = "Word",  Collection = "Code References",       DateAdded = new DateTime(2026, 7, 18) },
                new() { Title = "Sprint Planning Notes.md",     FileType = "Markdown", Collection = "Meeting Notes",      DateAdded = new DateTime(2026, 7, 15) },
                new() { Title = "ML Paper Analysis.pdf",        FileType = "PDF",   Collection = "Research Papers",      DateAdded = new DateTime(2026, 7, 10) }
            };

            SelectedCollection = Collections.Count > 0 ? Collections[0] : null;
        }

        private void FilterDocuments()
        {
            // Filter logic based on search text and selected collection
        }

        [RelayCommand]
        private async Task ImportFolder()
        {
            // Folder browser dialog logic
            await Task.Delay(500);
        }

        [RelayCommand]
        private void DeleteDocument(KnowledgeItem document)
        {
            if (document != null)
            {
                Documents.Remove(document);
            }
        }
    }
}
