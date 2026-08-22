using System;

namespace AtomAssistant.Models
{
    public class FileRecord
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string OriginalName { get; set; }
        public string Path { get; set; }
        public long Size { get; set; }
        public string Type { get; set; }
        public int? ChatId { get; set; }
        public DateTime CreatedAt { get; set; }
    }
}
