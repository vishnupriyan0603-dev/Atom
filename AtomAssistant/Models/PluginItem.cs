using CommunityToolkit.Mvvm.ComponentModel;

namespace AtomAssistant.Models
{
    public partial class PluginItem : ObservableObject
    {
        [ObservableProperty]
        private string name = string.Empty;

        [ObservableProperty]
        private string description = string.Empty;

        [ObservableProperty]
        private string version = string.Empty;

        [ObservableProperty]
        private string author = string.Empty;

        [ObservableProperty]
        private bool isEnabled;

        [ObservableProperty]
        private bool hasUpdate;

        [ObservableProperty]
        private bool isInstalled;
    }
}
