namespace PersonalAIAssistant.Models;

public sealed class ChatMessage
{
    public long Id { get; set; }
    public long ChatId { get; set; }
    public string Role { get; set; } = "user";
    public string Content { get; set; } = string.Empty;
    public bool IsPinned { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}
