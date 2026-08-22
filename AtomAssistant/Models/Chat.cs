using System;

namespace AtomAssistant.Models
{
    public class Chat
    {
        public int Id { get; set; }
        public string Title { get; set; }
        public string Model { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime UpdatedAt { get; set; }
        public bool IsPinned { get; set; }
        public int? FolderId { get; set; }
        public string Tags { get; set; }
    }
}
