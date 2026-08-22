using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class FileRepository
    {
        private readonly DatabaseService _db;

        public FileRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<FileRecord>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<FileRecord>(
                "SELECT * FROM FileRecords ORDER BY CreatedAt DESC");
        }

        public async Task<IEnumerable<FileRecord>> GetByChatIdAsync(int chatId)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<FileRecord>(
                "SELECT * FROM FileRecords WHERE ChatId = @ChatId ORDER BY CreatedAt",
                new { ChatId = chatId });
        }

        public async Task<IEnumerable<FileRecord>> GetByTypeAsync(string type)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<FileRecord>(
                "SELECT * FROM FileRecords WHERE Type = @Type ORDER BY CreatedAt DESC",
                new { Type = type });
        }

        public async Task<FileRecord> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<FileRecord>(
                "SELECT * FROM FileRecords WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(FileRecord file)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO FileRecords (Name, OriginalName, Path, Size, Type, ChatId, CreatedAt)
                        VALUES (@Name, @OriginalName, @Path, @Size, @Type, @ChatId, @CreatedAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, file);
        }

        public async Task<int> UpdateAsync(FileRecord file)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE FileRecords SET Name = @Name, OriginalName = @OriginalName,
                        Path = @Path, Size = @Size, Type = @Type, ChatId = @ChatId
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, file);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM FileRecords WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> DeleteByChatIdAsync(int chatId)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM FileRecords WHERE ChatId = @ChatId", new { ChatId = chatId });
        }
    }
}
