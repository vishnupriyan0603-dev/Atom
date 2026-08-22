using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed class FilesViewModel(IFileAnalysisService fileAnalysisService)
{
    public IReadOnlyList<string> SupportedFileTypes { get; } = fileAnalysisService.SupportedFileTypes;
}
