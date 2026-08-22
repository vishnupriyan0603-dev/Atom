namespace PersonalAIAssistant.Models;

public sealed class ChatSession
{
    public long Id { get; set; }
    public string Title { get; set; } = "New chat";
    public string Provider { get; set; } = "Local";
    public string Model { get; set; } = "Ollama:llama3.1";
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime UpdatedAt { get; set; } = DateTime.UtcNow;
}
