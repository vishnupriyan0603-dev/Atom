using Dapper;

namespace PersonalAIAssistant.Services;

public sealed class SettingsService(IDatabaseConnectionFactory connectionFactory) : ISettingsService
{
    public async Task<string> GetAsync(string key, string fallback)
    {
        using var connection = connectionFactory.CreateConnection();
        return await connection.QuerySingleOrDefaultAsync<string>(
            "select Value from Settings where Key = @key",
            new { key }) ?? fallback;
    }

    public async Task SaveAsync(string key, string value)
    {
        using var connection = connectionFactory.CreateConnection();
        await connection.ExecuteAsync(
            """
            insert into Settings(Key, Value) values(@key, @value)
            on conflict(Key) do update set Value = excluded.Value
            """,
            new { key, value });
    }
}
