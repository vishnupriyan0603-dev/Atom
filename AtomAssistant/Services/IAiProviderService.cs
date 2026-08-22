using AtomAssistant.Models;

namespace AtomAssistant.Services
{
    public interface IAiProviderService
    {
        Task SendMessageAsync(string model, List<Message> history, Action<string> onStream);
        Task<List<AiModel>> GetModelsAsync();
    }
}
