using System.Data;
using PersonalAIAssistant.Models;

namespace PersonalAIAssistant.Services;

public interface IDatabaseConnectionFactory
{
    IDbConnection CreateConnection();
}

public interface IDatabaseInitializer
{
    Task InitializeAsync();
}

public interface ISettingsService
{
    Task<string> GetAsync(string key, string fallback);
    Task SaveAsync(string key, string value);
}

public interface IThemeService
{
    Task ApplySavedThemeAsync();
    Task SetThemeAsync(string themeName);
}

public interface IAiProviderRegistry
{
    IReadOnlyList<AiModelInfo> GetModels();
}

public interface IChatRepository
{
    Task<IReadOnlyList<ChatSession>> GetRecentChatsAsync(int take);
    Task<ChatSession> CreateChatAsync(string title, string provider, string model);
    Task AddMessageAsync(long chatId, string role, string content);
    Task<IReadOnlyList<ChatMessage>> GetMessagesAsync(long chatId);
}

public interface IFileAnalysisService
{
    IReadOnlyList<string> SupportedFileTypes { get; }
}

public interface IPluginRegistry
{
    IReadOnlyList<string> InstalledPlugins { get; }
}

public interface INavigationService
{
    object ResolvePage(string pageKey);
}

public interface IAiService
{
    Task<string> SendMessageAsync(long chatId, string message, string model, string provider, IReadOnlyList<ChatMessage> history);
}
