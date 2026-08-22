using System;
using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;
using AtomAssistant.Services;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class ChatPageViewModel : ObservableObject
    {
        private readonly ChatService _chatService;
        private readonly ILogger<ChatPageViewModel> _logger;
        private long _activeChatId;
        private string _activeModel = "gpt-4o";

        [ObservableProperty]
        private ObservableCollection<MessageItem> messages = new();

        [ObservableProperty]
        private string inputText = string.Empty;

        [ObservableProperty]
        private bool isStreaming;

        [ObservableProperty]
        private MessageItem? selectedMessage;

        public ChatPageViewModel(ChatService chatService, ILogger<ChatPageViewModel> logger)
        {
            _chatService = chatService;
            _logger = logger;
            AddWelcomeMessage();
        }

        private void AddWelcomeMessage()
        {
            Messages.Add(new MessageItem
            {
                Content = "Hello! I'm AtomAssistant. How can I help you today?",
                IsUser = false,
                Timestamp = DateTime.Now
            });
        }

        public void SetActiveModel(string model)
        {
            _activeModel = model;
        }

        public async Task LoadChat(long chatId)
        {
            _activeChatId = chatId;
            Messages.Clear();
            try
            {
                var history = await _chatService.GetChatHistory((int)chatId);
                foreach (var msg in history)
                {
                    Messages.Add(new MessageItem
                    {
                        Content = msg.Content,
                        IsUser = msg.Role == "user",
                        Timestamp = msg.CreatedAt
                    });
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to load chat {ChatId}", chatId);
                AddWelcomeMessage();
            }
        }

        [RelayCommand]
        private async Task Send()
        {
            if (string.IsNullOrWhiteSpace(InputText))
                return;

            var userText = InputText.Trim();

            var userMessage = new MessageItem
            {
                Content = userText,
                IsUser = true,
                Timestamp = DateTime.Now
            };

            Messages.Add(userMessage);
            InputText = string.Empty;

            if (_activeChatId == 0)
            {
                Chat chat;
                try
                {
                    chat = await _chatService.CreateChat("New Chat", _activeModel);
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Failed to create chat");
                    return;
                }
                _activeChatId = chat.Id;
            }

            IsStreaming = true;
            var assistantMessage = new MessageItem
            {
                Content = string.Empty,
                IsUser = false,
                Timestamp = DateTime.Now,
                IsStreaming = true
            };

            Messages.Add(assistantMessage);

            try
            {
                var messages = await _chatService.SendMessage((int)_activeChatId, userText, _activeModel);

                var response = string.Empty;
                foreach (var msg in messages)
                {
                    if (msg.Role == "assistant")
                    {
                        response = msg.Content;
                        break;
                    }
                }

                for (int i = 0; i < response.Length; i++)
                {
                    assistantMessage.Content += response[i];
                    if (i % 5 == 0) await Task.Delay(1);
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to send message");
                assistantMessage.Content = "Error: " + ex.Message;
            }

            assistantMessage.IsStreaming = false;
            IsStreaming = false;
        }

        [RelayCommand]
        private void Stop()
        {
            IsStreaming = false;
            if (Messages.Count > 0)
            {
                var lastMessage = Messages[^1];
                lastMessage.IsStreaming = false;
            }
        }

        [RelayCommand]
        private void Attach() { }

        [RelayCommand]
        private void VoiceInput() { }

        [RelayCommand]
        private void AddImage() { }

        [RelayCommand]
        private void CopyCode(MessageItem message)
        {
            if (message != null)
            {
                System.Windows.Clipboard.SetText(message.Content);
            }
        }

        [RelayCommand]
        private void EditMessage(MessageItem message)
        {
            if (message != null)
            {
                InputText = message.Content;
                Messages.Remove(message);
            }
        }

        [RelayCommand]
        private void Regenerate(MessageItem message)
        {
            if (message != null && !message.IsUser)
            {
                var index = Messages.IndexOf(message);
                if (index > 0)
                {
                    Messages.Remove(message);
                    var userMsg = Messages[index - 1];
                    _ = Send();
                }
            }
        }

        [RelayCommand]
        private void DeleteMessage(MessageItem message)
        {
            if (message != null)
            {
                Messages.Remove(message);
            }
        }

        [RelayCommand]
        private void ExportMessage(MessageItem message) { }
    }
}
