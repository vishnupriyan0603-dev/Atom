using System;

namespace AtomAssistant.Models
{
    public class Message
    {
        public int Id { get; set; }
        public int ChatId { get; set; }
        public string Role { get; set; }
        public string Content { get; set; }
        public DateTime CreatedAt { get; set; }
        public int? TokensIn { get; set; }
        public int? TokensOut { get; set; }
        public string Model { get; set; }
    }
}
