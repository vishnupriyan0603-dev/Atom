using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed partial class SelfImprovementViewModel : ObservableObject
{
    private readonly ISelfImprovementService _selfImprovementService;

    [ObservableProperty]
    private IReadOnlyList<AtomFlawItem> _flaws = [];

    [ObservableProperty]
    private IReadOnlyList<AtomExperimentItem> _experiments = [];

    [ObservableProperty]
    private IReadOnlyList<AtomApprovalItem> _pendingApprovals = [];

    [ObservableProperty]
    private bool _isLoading;

    [ObservableProperty]
    private string _statusMessage = "Ready";

    public SelfImprovementViewModel(ISelfImprovementService selfImprovementService)
    {
        _selfImprovementService = selfImprovementService;
        _ = LoadDataAsync();
    }

    [RelayCommand]
    public async Task LoadDataAsync()
    {
        IsLoading = true;
        StatusMessage = "Fetching self-improvement metrics & safety approvals...";

        Flaws = await _selfImprovementService.GetFlawsAsync();
        Experiments = await _selfImprovementService.GetExperimentsAsync();
        PendingApprovals = await _selfImprovementService.GetPendingApprovalsAsync();

        IsLoading = false;
        StatusMessage = $"Loaded {Flaws.Count} flaw record(s), {Experiments.Count} experiment(s), and {PendingApprovals.Count} pending approval(s).";
    }

    [RelayCommand]
    public async Task ApproveAsync(AtomApprovalItem item)
    {
        if (item is null) return;
        StatusMessage = $"Approving experiment #{item.ExperimentId}...";
        var success = await _selfImprovementService.ApproveExperimentAsync(item.Id, "AdminUser");
        if (success)
        {
            StatusMessage = $"Successfully approved experiment #{item.ExperimentId}!";
            await LoadDataAsync();
        }
        else
        {
            StatusMessage = $"Failed to approve experiment #{item.ExperimentId}. Check backend log.";
        }
    }

    [RelayCommand]
    public async Task RejectAsync(AtomApprovalItem item)
    {
        if (item is null) return;
        StatusMessage = $"Rejecting experiment #{item.ExperimentId}...";
        var success = await _selfImprovementService.RejectExperimentAsync(item.Id, "AdminUser", "Rejected from desktop UI");
        if (success)
        {
            StatusMessage = $"Rejected experiment #{item.ExperimentId}.";
            await LoadDataAsync();
        }
        else
        {
            StatusMessage = $"Failed to reject experiment #{item.ExperimentId}.";
        }
    }
}
