using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using PersonalAIAssistant.Models;
using PersonalAIAssistant.Services;

namespace PersonalAIAssistant.ViewModels;

public sealed partial class ChatViewModel(IChatRepository chatRepository, IAiService aiService, IAiProviderRegistry providerRegistry) : ObservableObject
{
    [ObservableProperty] private string inputText = string.Empty;
    [ObservableProperty] private bool isStreaming;
    private long _activeChatId;
    private string _activeProvider = "Local";
    private string _activeModel = "Ollama:llama3.1";

    public ObservableCollection<ChatMessage> Messages { get; } =
    [
        new() { Role = "assistant", Content = "Start a conversation, attach files, or ask for code help." }
    ];

    public IReadOnlyList<AiModelInfo> AvailableModels => providerRegistry.GetModels();

    [RelayCommand]
    private async Task SendAsync()
    {
        if (string.IsNullOrWhiteSpace(InputText))
        {
            return;
        }

        if (_activeChatId == 0)
        {
            var chat = await chatRepository.CreateChatAsync("New chat", _activeProvider, _activeModel);
            _activeChatId = chat.Id;
        }

        var userText = InputText.Trim();
        InputText = string.Empty;
        Messages.Add(new ChatMessage { ChatId = _activeChatId, Role = "user", Content = userText });

        IsStreaming = true;

        var history = Messages.TakeLast(20).ToList();
        var response = await aiService.SendMessageAsync(_activeChatId, userText, _activeModel, _activeProvider, history);

        Messages.Add(new ChatMessage { ChatId = _activeChatId, Role = "assistant", Content = response });

        IsStreaming = false;
    }

    public void SetActiveModel(string provider, string model)
    {
        _activeProvider = provider;
        _activeModel = model;
    }

    [RelayCommand]
    private async Task LoadChatAsync(long chatId)
    {
        _activeChatId = chatId;
        Messages.Clear();
        var messages = await chatRepository.GetMessagesAsync(chatId);
        foreach (var msg in messages)
        {
            Messages.Add(msg);
        }
    }

    [RelayCommand]
    private async Task NewChatAsync()
    {
        _activeChatId = 0;
        Messages.Clear();
        Messages.Add(new ChatMessage { Role = "assistant", Content = "Start a conversation, attach files, or ask for code help." });
        await Task.CompletedTask;
    }

    [RelayCommand]
    private void Stop() => IsStreaming = false;
}
