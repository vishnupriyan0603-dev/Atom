using Dapper;

namespace PersonalAIAssistant.Services;

public sealed class DatabaseInitializer(IDatabaseConnectionFactory connectionFactory) : IDatabaseInitializer
{
    public async Task InitializeAsync()
    {
        using var connection = connectionFactory.CreateConnection();
        connection.Open();

        const string sql = """
        create table if not exists Settings (
            Key text primary key,
            Value text not null
        );

        create table if not exists Chats (
            Id integer primary key autoincrement,
            Title text not null,
            Provider text not null,
            Model text not null,
            CreatedAt text not null,
            UpdatedAt text not null
        );

        create table if not exists ChatMessages (
            Id integer primary key autoincrement,
            ChatId integer not null,
            Role text not null,
            Content text not null,
            IsPinned integer not null default 0,
            CreatedAt text not null,
            foreign key(ChatId) references Chats(Id)
        );

        create table if not exists Prompts (
            Id integer primary key autoincrement,
            Category text not null,
            Title text not null,
            Body text not null,
            IsFavorite integer not null default 0
        );

        create index if not exists IX_ChatMessages_ChatId_CreatedAt on ChatMessages(ChatId, CreatedAt);
        """;

        await connection.ExecuteAsync(sql);
    }
}
