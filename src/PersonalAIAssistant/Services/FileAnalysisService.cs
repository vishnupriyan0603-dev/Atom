namespace PersonalAIAssistant.Services;

public sealed class FileAnalysisService : IFileAnalysisService
{
    public IReadOnlyList<string> SupportedFileTypes { get; } =
    [
        "PDF", "Word", "Excel", "CSV", "Image", "ZIP", "Source Code", "Markdown", "Text"
    ];
}
