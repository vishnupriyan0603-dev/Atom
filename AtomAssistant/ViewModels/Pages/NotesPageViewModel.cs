using System;
using System.Collections.ObjectModel;
using System.Linq;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class NotesPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<NoteFolderItem> folders = new();

        [ObservableProperty]
        private ObservableCollection<NoteItem> notes = new();

        [ObservableProperty]
        private NoteItem? selectedNote;

        [ObservableProperty]
        private NoteFolderItem? selectedFolder;

        [ObservableProperty]
        private string searchText = string.Empty;

        public NotesPageViewModel()
        {
            LoadSampleData();
        }

        partial void OnSearchTextChanged(string value) => FilterNotes();

        private void LoadSampleData()
        {
            var generalNotes = new ObservableCollection<NoteItem>
            {
                new() { Title = "Project ideas",      Content = "Brainstorming new features...",    Folder = "General" },
                new() { Title = "Meeting notes",      Content = "Sprint planning discussion...",    Folder = "General" }
            };

            var devNotes = new ObservableCollection<NoteItem>
            {
                new() { Title = "API design patterns", Content = "RESTful API guidelines...",        Folder = "Development" },
                new() { Title = "Code snippets",      Content = "Useful C# code examples...",       Folder = "Development" }
            };

            Folders = new ObservableCollection<NoteFolderItem>
            {
                new() { Name = "General",     Notes = generalNotes },
                new() { Name = "Development", Notes = devNotes },
                new() { Name = "Personal",    Notes = new ObservableCollection<NoteItem>() },
                new() { Name = "Archive",     Notes = new ObservableCollection<NoteItem>() }
            };

            Notes = new ObservableCollection<NoteItem>(generalNotes.Concat(devNotes));
            SelectedFolder = Folders.FirstOrDefault();
        }

        private void FilterNotes()
        {
            if (string.IsNullOrWhiteSpace(SearchText))
            {
                Notes = new ObservableCollection<NoteItem>(
                    Folders.SelectMany(f => f.Notes));
            }
            else
            {
                var search = SearchText.ToLower();
                Notes = new ObservableCollection<NoteItem>(
                    Folders.SelectMany(f => f.Notes)
                        .Where(n => n.Title.ToLower().Contains(search) ||
                                    n.Content.ToLower().Contains(search)));
            }
        }

        [RelayCommand]
        private void CreateFolder()
        {
            var newFolder = new NoteFolderItem
            {
                Name = $"New Folder {Folders.Count + 1}",
                Notes = new ObservableCollection<NoteItem>()
            };
            Folders.Add(newFolder);
        }

        [RelayCommand]
        private void CreateNote()
        {
            var targetFolder = SelectedFolder ?? Folders.FirstOrDefault();
            if (targetFolder == null) return;

            var newNote = new NoteItem
            {
                Title = "Untitled Note",
                Content = string.Empty,
                Folder = targetFolder.Name,
                LastModified = DateTime.Now
            };

            targetFolder.Notes.Add(newNote);
            Notes.Add(newNote);
            SelectedNote = newNote;
        }

        [RelayCommand]
        private void DeleteNote()
        {
            if (SelectedNote == null) return;

            var folder = Folders.FirstOrDefault(f => f.Name == SelectedNote.Folder);
            folder?.Notes.Remove(SelectedNote);
            Notes.Remove(SelectedNote);
            SelectedNote = null;
        }

        [RelayCommand]
        private void RenameNote()
        {
            // Rename dialog logic
        }
    }
}
