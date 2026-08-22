using System.Collections.Generic;
using System.Threading.Tasks;

namespace AtomAssistant.Services
{
    public class SyncService
    {
        private readonly BackendService _backendService;
        private readonly ChatService _chatService;

        public SyncService(BackendService backendService, ChatService chatService)
        {
            _backendService = backendService;
            _chatService = chatService;
        }

        public async Task SyncAllAsync()
        {
            if (!_backendService.IsConnected) return;
            await SyncChatsAsync();
        }

        public async Task SyncChatsAsync()
        {
            if (!_backendService.IsConnected) return;

            var chats = await _chatService.GetAllChats();
            foreach (var chat in chats)
            {
                await _backendService.PostAsync("/api/chats", new
                {
                    title = chat.Title,
                    model = chat.Model,
                    is_pinned = chat.IsPinned
                });
            }
        }
    }
}
