using System.IO;
using Microsoft.Data.Sqlite;

namespace AtomAssistant.Database
{
    public class DatabaseService
    {
        private readonly string _connectionString;

        public DatabaseService(string dbPath = null)
        {
            if (string.IsNullOrEmpty(dbPath))
            {
                dbPath = Path.Combine(
                    System.AppDomain.CurrentDomain.BaseDirectory,
                    "atomassistant.db");
            }

            _connectionString = $"Data Source={dbPath}";
        }

        public SqliteConnection GetConnection()
        {
            var connection = new SqliteConnection(_connectionString);
            connection.Open();
            return connection;
        }

        public void Initialize()
        {
            using var connection = GetConnection();
            var sql = ResourceReader.GetSchemaSql();
            using var command = new SqliteCommand(sql, connection);
            command.ExecuteNonQuery();
        }

        private static class ResourceReader
        {
            public static string GetSchemaSql()
            {
                return @"
CREATE TABLE IF NOT EXISTS Chats (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Title TEXT NOT NULL,
    Model TEXT,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    UpdatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    IsPinned INTEGER NOT NULL DEFAULT 0,
    FolderId INTEGER,
    Tags TEXT
);

CREATE TABLE IF NOT EXISTS Messages (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    ChatId INTEGER NOT NULL,
    Role TEXT NOT NULL,
    Content TEXT NOT NULL,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    TokensIn INTEGER,
    TokensOut INTEGER,
    Model TEXT,
    FOREIGN KEY (ChatId) REFERENCES Chats(Id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS UserSettings (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Key TEXT NOT NULL UNIQUE,
    Value TEXT NOT NULL,
    Type TEXT NOT NULL DEFAULT 'string'
);

CREATE TABLE IF NOT EXISTS AiModels (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Provider TEXT NOT NULL,
    ApiEndpoint TEXT,
    ApiKey TEXT,
    IsLocal INTEGER NOT NULL DEFAULT 0,
    IsEnabled INTEGER NOT NULL DEFAULT 1,
    ContextLength INTEGER NOT NULL DEFAULT 4096,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS Prompts (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Title TEXT NOT NULL,
    Content TEXT NOT NULL,
    Category TEXT,
    IsFavorite INTEGER NOT NULL DEFAULT 0,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    UpdatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS PluginInfo (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Version TEXT NOT NULL,
    Author TEXT,
    Description TEXT,
    IconPath TEXT,
    IsEnabled INTEGER NOT NULL DEFAULT 1,
    InstalledAt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS KnowledgeItems (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Title TEXT NOT NULL,
    Content TEXT,
    FilePath TEXT,
    FileType TEXT,
    Collection TEXT,
    Embedding BLOB,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS FileRecords (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    OriginalName TEXT NOT NULL,
    Path TEXT NOT NULL,
    Size INTEGER NOT NULL DEFAULT 0,
    Type TEXT,
    ChatId INTEGER,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (ChatId) REFERENCES Chats(Id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Notes (
    Id INTEGER PRIMARY KEY AUTOINCREMENT,
    Title TEXT NOT NULL,
    Content TEXT,
    Folder TEXT,
    IsFavorite INTEGER NOT NULL DEFAULT 0,
    CreatedAt TEXT NOT NULL DEFAULT (datetime('now')),
    UpdatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_messages_chat_id ON Messages(ChatId);
CREATE INDEX IF NOT EXISTS idx_chats_folder_id ON Chats(FolderId);
CREATE INDEX IF NOT EXISTS idx_chats_is_pinned ON Chats(IsPinned);
CREATE INDEX IF NOT EXISTS idx_knowledge_items_collection ON KnowledgeItems(Collection);
CREATE INDEX IF NOT EXISTS idx_file_records_chat_id ON FileRecords(ChatId);
CREATE INDEX IF NOT EXISTS idx_notes_folder ON Notes(Folder);
CREATE INDEX IF NOT EXISTS idx_prompts_category ON Prompts(Category);
";
            }
        }
    }
}
