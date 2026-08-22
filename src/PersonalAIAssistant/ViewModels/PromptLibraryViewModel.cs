using PersonalAIAssistant.Models;

namespace PersonalAIAssistant.ViewModels;

public sealed class PromptLibraryViewModel
{
    public IReadOnlyList<string> Categories { get; } =
    [
        "Coding", "PHP", "Laravel", "CodeIgniter", "React", "HTML", "CSS", "SQL", "Linux", "Business", "Marketing", "Personal"
    ];

    public IReadOnlyList<PromptTemplate> Prompts { get; } =
    [
        new() { Category = "Coding", Title = "Code review", Body = "Review this code for bugs, security, and maintainability.", IsFavorite = true },
        new() { Category = "SQL", Title = "Optimize query", Body = "Find performance risks and safer indexes for this query." },
        new() { Category = "Business", Title = "Executive summary", Body = "Summarize this material for a business stakeholder.", IsFavorite = true }
    ];
}
