using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class FilesPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<FileItem> files = new();

        [ObservableProperty]
        private FileItem? selectedFile;

        [ObservableProperty]
        private string statusText = "Ready";

        [RelayCommand]
        private void AddFile(FileItem file)
        {
            if (file != null)
            {
                Files.Add(file);
                StatusText = $"Added: {file.FileName}";
            }
        }

        [RelayCommand]
        private void RemoveFile(FileItem file)
        {
            if (file != null)
            {
                Files.Remove(file);
                StatusText = $"Removed: {file.FileName}";
            }
        }

        [RelayCommand]
        private async Task Analyze()
        {
            if (SelectedFile == null)
            {
                StatusText = "Please select a file to analyze.";
                return;
            }

            StatusText = $"Analyzing {SelectedFile.FileName}...";
            await Task.Delay(2000);
            StatusText = $"Analysis complete for {SelectedFile.FileName}";
        }

        [RelayCommand]
        private async Task Summarize()
        {
            if (SelectedFile == null)
            {
                StatusText = "Please select a file to summarize.";
                return;
            }

            StatusText = $"Summarizing {SelectedFile.FileName}...";
            await Task.Delay(1500);
            StatusText = $"Summary generated for {SelectedFile.FileName}";
        }

        [RelayCommand]
        private async Task ChatWithFile()
        {
            if (SelectedFile == null)
            {
                StatusText = "Please select a file to chat about.";
                return;
            }

            StatusText = $"Opening chat for {SelectedFile.FileName}...";
            await Task.Delay(500);
            StatusText = $"Chat session started for {SelectedFile.FileName}";
        }
    }
}
