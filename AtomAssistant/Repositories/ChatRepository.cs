using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class ChatRepository
    {
        private readonly DatabaseService _db;

        public ChatRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<Chat>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Chat>("SELECT * FROM Chats ORDER BY UpdatedAt DESC");
        }

        public async Task<Chat> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<Chat>(
                "SELECT * FROM Chats WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(Chat chat)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO Chats (Title, Model, CreatedAt, UpdatedAt, IsPinned, FolderId, Tags)
                        VALUES (@Title, @Model, @CreatedAt, @UpdatedAt, @IsPinned, @FolderId, @Tags);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, chat);
        }

        public async Task<int> UpdateAsync(Chat chat)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE Chats SET Title = @Title, Model = @Model,
                        UpdatedAt = @UpdatedAt, IsPinned = @IsPinned,
                        FolderId = @FolderId, Tags = @Tags WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, chat);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM Chats WHERE Id = @Id", new { Id = id });
        }

        public async Task<IEnumerable<Message>> GetMessagesAsync(int chatId)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Message>(
                "SELECT * FROM Messages WHERE ChatId = @ChatId ORDER BY CreatedAt",
                new { ChatId = chatId });
        }

        public async Task<Message> GetMessageByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<Message>(
                "SELECT * FROM Messages WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertMessageAsync(Message message)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO Messages (ChatId, Role, Content, CreatedAt, TokensIn, TokensOut, Model)
                        VALUES (@ChatId, @Role, @Content, @CreatedAt, @TokensIn, @TokensOut, @Model);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, message);
        }

        public async Task<int> UpdateMessageAsync(Message message)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE Messages SET Role = @Role, Content = @Content,
                        TokensIn = @TokensIn, TokensOut = @TokensOut, Model = @Model
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, message);
        }

        public async Task<int> DeleteMessageAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM Messages WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> DeleteMessagesByChatIdAsync(int chatId)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM Messages WHERE ChatId = @ChatId", new { ChatId = chatId });
        }
    }
}
