namespace PersonalAIAssistant.Services;

public record AtomFlawItem(string PromptVersion, string ModelName, double AccuracyScore, double ErrorRate, int EvaluationCount);
public record AtomExperimentItem(int Id, string Title, string TargetComponent, string Status, double BaselineScore, double CandidateScore, double ImprovementPct, bool HumanApproved);
public record AtomApprovalItem(int Id, int ExperimentId, string Action, string RequestedBy, string Status, string Reason, string CreatedAt);

public interface ISelfImprovementService
{
    Task<IReadOnlyList<AtomFlawItem>> GetFlawsAsync();
    Task<IReadOnlyList<AtomExperimentItem>> GetExperimentsAsync();
    Task<IReadOnlyList<AtomApprovalItem>> GetPendingApprovalsAsync();
    Task<bool> ApproveExperimentAsync(int approvalId, string approverName);
    Task<bool> RejectExperimentAsync(int approvalId, string approverName, string reason);
}
