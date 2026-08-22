using System.Data;
using System.IO;
using Microsoft.Data.Sqlite;
using Microsoft.Extensions.Configuration;

namespace PersonalAIAssistant.Services;

public sealed class SqliteConnectionFactory(IConfiguration configuration) : IDatabaseConnectionFactory
{
    public IDbConnection CreateConnection()
    {
        var fileName = configuration["App:DatabaseFileName"] ?? "assistant.db";
        var folder = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "AtomAssistant");
        Directory.CreateDirectory(folder);
        return new SqliteConnection($"Data Source={Path.Combine(folder, fileName)}");
    }
}
