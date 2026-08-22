using Dapper;
using PersonalAIAssistant.Models;

namespace PersonalAIAssistant.Services;

public sealed class ChatRepository(IDatabaseConnectionFactory connectionFactory) : IChatRepository
{
    public async Task<IReadOnlyList<ChatSession>> GetRecentChatsAsync(int take)
    {
        using var connection = connectionFactory.CreateConnection();
        var rows = await connection.QueryAsync<ChatSession>(
            "select * from Chats order by UpdatedAt desc limit @take",
            new { take });
        return rows.ToList();
    }

    public async Task<ChatSession> CreateChatAsync(string title, string provider, string model)
    {
        using var connection = connectionFactory.CreateConnection();
        var now = DateTime.UtcNow;
        var id = await connection.ExecuteScalarAsync<long>(
            """
            insert into Chats(Title, Provider, Model, CreatedAt, UpdatedAt)
            values(@title, @provider, @model, @now, @now);
            select last_insert_rowid();
            """,
            new { title, provider, model, now });

        return new ChatSession { Id = id, Title = title, Provider = provider, Model = model, CreatedAt = now, UpdatedAt = now };
    }

    public async Task AddMessageAsync(long chatId, string role, string content)
    {
        using var connection = connectionFactory.CreateConnection();
        await connection.ExecuteAsync(
            """
            insert into ChatMessages(ChatId, Role, Content, CreatedAt)
            values(@chatId, @role, @content, @now);
            update Chats set UpdatedAt = @now where Id = @chatId;
            """,
            new { chatId, role, content, now = DateTime.UtcNow });
    }

    public async Task<IReadOnlyList<ChatMessage>> GetMessagesAsync(long chatId)
    {
        using var connection = connectionFactory.CreateConnection();
        var rows = await connection.QueryAsync<ChatMessage>(
            "select * from ChatMessages where ChatId = @chatId order by CreatedAt",
            new { chatId });
        return rows.ToList();
    }
}
