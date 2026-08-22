using AtomAssistant.Database;
using AtomAssistant.Models;
using AtomAssistant.Repositories;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.Services
{
    public class ChatService
    {
        private readonly IChatRepository _chatRepository;
        private readonly IMessageRepository _messageRepository;
        private readonly IAiProviderService _aiProvider;
        private readonly ILogger<ChatService> _logger;

        public ChatService(
            IChatRepository chatRepository,
            IMessageRepository messageRepository,
            IAiProviderService aiProvider,
            ILogger<ChatService> logger)
        {
            _chatRepository = chatRepository;
            _messageRepository = messageRepository;
            _aiProvider = aiProvider;
            _logger = logger;
        }

        public async Task<Chat> CreateChat(string title, string model)
        {
            var chat = new Chat
            {
                Title = title,
                Model = model,
                CreatedAt = DateTime.UtcNow,
                UpdatedAt = DateTime.UtcNow
            };

            await _chatRepository.AddAsync(chat);
            _logger.LogInformation("Created chat {ChatId}: {Title}", chat.Id, title);
            return chat;
        }

        public async Task DeleteChat(int chatId)
        {
            await _messageRepository.DeleteByChatIdAsync(chatId);
            await _chatRepository.DeleteAsync(chatId);
            _logger.LogInformation("Deleted chat {ChatId}", chatId);
        }

        public async Task<List<Message>> SendMessage(int chatId, string content, string model)
        {
            var userMessage = new Message
            {
                ChatId = chatId,
                Role = "user",
                Content = content,
                CreatedAt = DateTime.UtcNow,
                Model = model
            };

            await _messageRepository.AddAsync(userMessage);

            var history = await _messageRepository.GetByChatIdAsync(chatId);
            var historyList = history.ToList();

            var fullResponse = new System.Text.StringBuilder();

            var assistantMessage = new Message
            {
                ChatId = chatId,
                Role = "assistant",
                Content = "",
                CreatedAt = DateTime.UtcNow,
                Model = model
            };

            await _messageRepository.AddAsync(assistantMessage);

            await _aiProvider.SendMessageAsync(model, historyList, chunk =>
            {
                fullResponse.Append(chunk);
            });

            assistantMessage.Content = fullResponse.ToString();
            await _messageRepository.UpdateAsync(assistantMessage);

            var chat = await _chatRepository.GetByIdAsync(chatId);
            if (chat != null)
            {
                chat.UpdatedAt = DateTime.UtcNow;
                await _chatRepository.UpdateAsync(chat);
            }

            return new List<Message> { userMessage, assistantMessage };
        }

        public async Task<List<Message>> GetChatHistory(int chatId)
        {
            var messages = await _messageRepository.GetByChatIdAsync(chatId);
            return messages.OrderBy(m => m.CreatedAt).ToList();
        }

        public async Task<List<Chat>> SearchChats(string query)
        {
            return await _chatRepository.SearchAsync(query);
        }

        public async Task<List<Message>> ExportChat(int chatId)
        {
            return await GetChatHistory(chatId);
        }

        public async Task<List<Chat>> GetAllChats()
        {
            return await _chatRepository.GetAllAsync();
        }

        public async Task<Chat?> GetChat(int chatId)
        {
            return await _chatRepository.GetByIdAsync(chatId);
        }

        public async Task UpdateChatTitle(int chatId, string title)
        {
            var chat = await _chatRepository.GetByIdAsync(chatId);
            if (chat != null)
            {
                chat.Title = title;
                chat.UpdatedAt = DateTime.UtcNow;
                await _chatRepository.UpdateAsync(chat);
            }
        }
    }
}
