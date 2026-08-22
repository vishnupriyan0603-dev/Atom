using System;

namespace AtomAssistant.Models
{
    public class PluginInfo
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string Version { get; set; }
        public string Author { get; set; }
        public string Description { get; set; }
        public string IconPath { get; set; }
        public bool IsEnabled { get; set; }
        public DateTime InstalledAt { get; set; }
    }
}
