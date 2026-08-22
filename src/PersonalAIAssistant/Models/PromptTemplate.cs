namespace PersonalAIAssistant.Models;

public sealed class PromptTemplate
{
    public long Id { get; set; }
    public string Category { get; set; } = "General";
    public string Title { get; set; } = string.Empty;
    public string Body { get; set; } = string.Empty;
    public bool IsFavorite { get; set; }
}
