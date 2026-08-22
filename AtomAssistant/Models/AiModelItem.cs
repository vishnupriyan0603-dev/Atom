using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class AiModelItem : ObservableObject
    {
        [ObservableProperty]
        private string name = string.Empty;

        [ObservableProperty]
        private string provider = string.Empty;

        [ObservableProperty]
        private string modelId = string.Empty;

        [ObservableProperty]
        private bool isEnabled;

        [ObservableProperty]
        private bool isLocal;

        [ObservableProperty]
        private bool isRunning;

        [ObservableProperty]
        private string apiKey = string.Empty;

        [ObservableProperty]
        private string ramUsage = string.Empty;

        [ObservableProperty]
        private string vramUsage = string.Empty;

        [ObservableProperty]
        private int contextLength;

        [ObservableProperty]
        private bool isInstalled;
    }
}
