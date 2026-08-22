using System;

namespace AtomAssistant.Models
{
    public class AiModel
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string Provider { get; set; }
        public string ApiEndpoint { get; set; }
        public string ApiKey { get; set; }
        public bool IsLocal { get; set; }
        public bool IsEnabled { get; set; }
        public int ContextLength { get; set; }
        public DateTime CreatedAt { get; set; }
    }
}
