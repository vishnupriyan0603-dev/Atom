using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class ModelsPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<AiModelItem> cloudModels = new();

        [ObservableProperty]
        private ObservableCollection<AiModelItem> localModels = new();

        public ModelsPageViewModel()
        {
            InitializeModels();
        }

        private void InitializeModels()
        {
            CloudModels = new ObservableCollection<AiModelItem>
            {
                new() { Name = "OpenAI",       ModelId = "gpt-4o",           IsEnabled = false },
                new() { Name = "Claude",       ModelId = "claude-3-opus",    IsEnabled = false },
                new() { Name = "Gemini",       ModelId = "gemini-1.5-pro",   IsEnabled = false },
                new() { Name = "DeepSeek",     ModelId = "deepseek-chat",    IsEnabled = false },
                new() { Name = "Groq",         ModelId = "llama3-70b",       IsEnabled = false },
                new() { Name = "Mistral",      ModelId = "mistral-large",    IsEnabled = false },
                new() { Name = "OpenRouter",   ModelId = "gpt-4o-mini",      IsEnabled = false },
                new() { Name = "Anthropic",    ModelId = "claude-3-haiku",   IsEnabled = false },
                new() { Name = "Azure OpenAI", ModelId = "gpt-4o",           IsEnabled = false }
            };

            LocalModels = new ObservableCollection<AiModelItem>
            {
                new() { Name = "Ollama",   IsLocal = true, IsRunning = false, RamUsage = "8 GB", VramUsage = "4 GB", ContextLength = 4096, IsInstalled = true },
                new() { Name = "LM Studio", IsLocal = true, IsRunning = false, RamUsage = "12 GB", VramUsage = "6 GB", ContextLength = 8192, IsInstalled = true },
                new() { Name = "GPT4All",  IsLocal = true, IsRunning = false, RamUsage = "4 GB", VramUsage = "2 GB", ContextLength = 2048, IsInstalled = true },
                new() { Name = "llama.cpp", IsLocal = true, IsRunning = false, RamUsage = "6 GB", VramUsage = "3 GB", ContextLength = 4096, IsInstalled = false }
            };
        }

        [RelayCommand]
        private async Task AutoDetectLocalModels()
        {
            // Simulate detection
            await Task.Delay(1500);

            foreach (var model in LocalModels)
            {
                model.IsInstalled = true;
            }
        }

        [RelayCommand]
        private void ToggleLocalModel(AiModelItem model)
        {
            if (model != null)
            {
                model.IsRunning = !model.IsRunning;
            }
        }
    }
}
