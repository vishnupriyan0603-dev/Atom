using System;
using System.Collections.ObjectModel;
using System.Threading.Tasks;
using System.Windows.Media;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;

namespace AtomAssistant.ViewModels.Pages;

public partial class UpdatesPageViewModel : ObservableObject
{
    [ObservableProperty]
    private string _currentVersion = "1.0.0";

    [ObservableProperty]
    private string _updateStatus = "You are up to date.";

    [ObservableProperty]
    private Brush _updateStatusColor = new SolidColorBrush(Color.FromRgb(34, 197, 94));

    [ObservableProperty]
    private bool _isChecking;

    [ObservableProperty]
    private bool _isCheckingEnabled = true;

    [ObservableProperty]
    private bool _hasUpdates;

    [ObservableProperty]
    private ObservableCollection<UpdateItem> _availableUpdates = new();

    [ObservableProperty]
    private ObservableCollection<ReleaseNoteItem> _releaseNotes = new()
    {
        new()
        {
            Version = "1.0.0",
            Date = "2026-07-27",
            Notes = "- Initial public release\n- Multi-model chat support (OpenAI, Anthropic, Ollama)\n" +
                    "- Prompt library with templates\n- File management and attachment support\n" +
                    "- Plugin system for extensibility\n- Knowledge base integration\n" +
                    "- Notes and history management\n- Dark/Light/System theme support"
        },
        new()
        {
            Version = "0.9.0",
            Date = "2026-06-15",
            Notes = "- Beta release\n- Knowledge base integration\n- Performance improvements\n" +
                    "- Bug fixes and stability enhancements"
        },
        new()
        {
            Version = "0.8.0",
            Date = "2026-05-01",
            Notes = "- Alpha release\n- Basic chat functionality\n- Model switching support\n" +
                    "- Initial prompt library"
        }
    };

    [RelayCommand]
    private async Task CheckForUpdates()
    {
        IsChecking = true;
        IsCheckingEnabled = false;

        try
        {
            await Task.Delay(2000);

            var latestVersion = "1.0.0";

            if (string.Compare(latestVersion, CurrentVersion, StringComparison.OrdinalIgnoreCase) > 0)
            {
                HasUpdates = true;
                AvailableUpdates.Clear();
                AvailableUpdates.Add(new UpdateItem
                {
                    Version = latestVersion,
                    Date = "2026-07-27",
                    DownloadUrl = "https://github.com/atom/atomassistant/releases"
                });

                UpdateStatus = $"Version {latestVersion} is available.";
                UpdateStatusColor = new SolidColorBrush(Color.FromRgb(239, 68, 68));
            }
            else
            {
                HasUpdates = false;
                UpdateStatus = "You are up to date.";
                UpdateStatusColor = new SolidColorBrush(Color.FromRgb(34, 197, 94));
            }
        }
        catch
        {
            UpdateStatus = "Failed to check for updates. Please try again.";
            UpdateStatusColor = new SolidColorBrush(Color.FromRgb(239, 68, 68));
        }
        finally
        {
            IsChecking = false;
            IsCheckingEnabled = true;
        }
    }

    [RelayCommand]
    private void Update(UpdateItem item)
    {
        if (item == null || string.IsNullOrEmpty(item.DownloadUrl))
            return;

        var psi = new System.Diagnostics.ProcessStartInfo
        {
            FileName = item.DownloadUrl,
            UseShellExecute = true
        };
        System.Diagnostics.Process.Start(psi);
    }
}

public class UpdateItem
{
    public string Version { get; set; } = string.Empty;
    public string Date { get; set; } = string.Empty;
    public string DownloadUrl { get; set; } = string.Empty;
}

public class ReleaseNoteItem
{
    public string Version { get; set; } = string.Empty;
    public string Date { get; set; } = string.Empty;
    public string Notes { get; set; } = string.Empty;
}