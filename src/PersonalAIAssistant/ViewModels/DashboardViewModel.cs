using CommunityToolkit.Mvvm.ComponentModel;
using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed partial class DashboardViewModel : ObservableObject
{
    public DashboardViewModel(IAiProviderRegistry aiProviderRegistry, IFileAnalysisService fileAnalysisService)
    {
        InstalledModels = aiProviderRegistry.GetModels().Where(model => model.IsInstalled).Select(model => model.Name).ToArray();
        RecentFiles = fileAnalysisService.SupportedFileTypes.Take(5).Select(type => $"{type} ready").ToArray();
    }

    public IReadOnlyList<string> RecentChats { get; } = ["Architecture planning", "Laravel controller review", "Marketing copy"];
    public IReadOnlyList<string> FavoritePrompts { get; } = ["Explain this code", "Find security issues", "Summarize document"];
    public IReadOnlyList<string> InstalledModels { get; }
    public IReadOnlyList<string> RecentFiles { get; }
    public string StorageUsage => "218 MB";
    public string SystemStatus => "Ready";
    public string GpuStatus => "Available";
    public string CpuUsage => "12%";
    public string RamUsage => "6.8 GB";
}
