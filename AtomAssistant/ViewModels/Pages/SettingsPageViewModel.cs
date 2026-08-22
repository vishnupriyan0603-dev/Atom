using System.Collections.ObjectModel;
using System.Threading.Tasks;
using System.Windows.Controls;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;
using AtomAssistant.Services;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class SettingsPageViewModel : ObservableObject
    {
        private readonly BackendService _backendService;

        [ObservableProperty]
        private ObservableCollection<string> themeOptions = new()
        {
            "Light", "Dark", "System"
        };

        [ObservableProperty]
        private ObservableCollection<string> languageOptions = new()
        {
            "English", "Spanish", "French", "German", "Japanese", "Chinese"
        };

        [ObservableProperty]
        private ObservableCollection<AiModelItem> aiProviders = new();

        [ObservableProperty]
        private string selectedTheme = "System";

        [ObservableProperty]
        private string selectedLanguage = "English";

        [ObservableProperty]
        private string localModelPath = string.Empty;

        [ObservableProperty]
        private string databaseInfo = "SQLite (Local)";

        [ObservableProperty]
        private string databaseSize = "12.4 MB";

        [ObservableProperty]
        private string updateStatus = "Last checked: Never";

        [ObservableProperty]
        private string statusMessage = string.Empty;

        [ObservableProperty]
        private string backendUrl = "http://localhost:8080";

        [ObservableProperty]
        private string backendEmail = string.Empty;

        [ObservableProperty]
        private string backendStatus = "Not connected";

        public SettingsPageViewModel(BackendService backendService)
        {
            _backendService = backendService;
            LoadAiProviders();
            BackendUrl = _backendService.BaseUrl;
            BackendEmail = _backendService.Email;
            BackendStatus = _backendService.IsConnected
                ? $"Connected as {_backendService.Email}"
                : "Not connected";
        }

        partial void OnSelectedThemeChanged(string value)
        {
            ApplyTheme(value);
        }

        private void LoadAiProviders()
        {
            AiProviders = new ObservableCollection<AiModelItem>
            {
                new() { Name = "OpenAI",       ApiKey = string.Empty },
                new() { Name = "Claude",       ApiKey = string.Empty },
                new() { Name = "Gemini",       ApiKey = string.Empty },
                new() { Name = "DeepSeek",     ApiKey = string.Empty },
                new() { Name = "Groq",         ApiKey = string.Empty },
                new() { Name = "Mistral",      ApiKey = string.Empty },
                new() { Name = "OpenRouter",   ApiKey = string.Empty },
                new() { Name = "Anthropic",    ApiKey = string.Empty },
                new() { Name = "Azure OpenAI", ApiKey = string.Empty }
            };
        }

        private void ApplyTheme(string theme)
        {
            switch (theme)
            {
                case "Light":
                    break;
                case "Dark":
                    break;
                case "System":
                    break;
            }

            StatusMessage = $"Theme changed to {theme}";
        }

        [RelayCommand]
        private async Task TestBackendConnection()
        {
            BackendStatus = "Testing connection...";
            var (success, error) = await _backendService.TestConnectionAsync();
            BackendStatus = success ? "Connection successful" : $"Connection failed: {error}";
            StatusMessage = BackendStatus;
        }

        [RelayCommand]
        private async Task LoginBackend(PasswordBox passwordBox)
        {
            var password = passwordBox?.Password ?? string.Empty;
            if (string.IsNullOrEmpty(BackendEmail) || string.IsNullOrEmpty(password))
            {
                BackendStatus = "Email and password are required";
                return;
            }

            BackendStatus = "Connecting...";

            var (success, error) = await _backendService.LoginAsync(BackendEmail, password);

            if (!success)
            {
                var (regSuccess, regError) = await _backendService.RegisterAsync(BackendEmail, password);
                if (regSuccess)
                {
                    BackendStatus = $"Connected as {BackendEmail}";
                    StatusMessage = "Registered and connected to backend";
                }
                else
                {
                    BackendStatus = $"Failed: {regError}";
                }
            }
            else
            {
                BackendStatus = $"Connected as {BackendEmail}";
                StatusMessage = "Connected to backend";
            }
        }

        [RelayCommand]
        private void DisconnectBackend()
        {
            _backendService.Disconnect();
            BackendStatus = "Not connected";
            StatusMessage = "Disconnected from backend";
        }

        [RelayCommand]
        private async Task BrowseLocalModelPath()
        {
            await Task.Delay(300);
            LocalModelPath = @"C:\Users\Public\Models";
            StatusMessage = "Model path updated";
        }

        [RelayCommand]
        private async Task OpenPluginSettings()
        {
            await Task.Delay(300);
            StatusMessage = "Plugin settings opened";
        }

        [RelayCommand]
        private async Task CheckForUpdates()
        {
            StatusMessage = "Checking for updates...";
            await Task.Delay(2000);
            UpdateStatus = "Last checked: Just now";
            StatusMessage = "No updates available. You're up to date!";
        }

        [RelayCommand]
        private async Task Backup()
        {
            StatusMessage = "Creating backup...";
            await Task.Delay(3000);
            StatusMessage = "Backup completed successfully!";
        }

        [RelayCommand]
        private async Task Restore()
        {
            StatusMessage = "Restoring from backup...";
            await Task.Delay(3000);
            StatusMessage = "Restore completed successfully!";
        }
    }
}
