using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class KnowledgeRepository
    {
        private readonly DatabaseService _db;

        public KnowledgeRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<KnowledgeItem>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<KnowledgeItem>(
                "SELECT * FROM KnowledgeItems ORDER BY CreatedAt DESC");
        }

        public async Task<IEnumerable<KnowledgeItem>> GetByCollectionAsync(string collection)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<KnowledgeItem>(
                "SELECT * FROM KnowledgeItems WHERE Collection = @Collection ORDER BY Title",
                new { Collection = collection });
        }

        public async Task<KnowledgeItem> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<KnowledgeItem>(
                "SELECT * FROM KnowledgeItems WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(KnowledgeItem item)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO KnowledgeItems (Title, Content, FilePath, FileType,
                        Collection, Embedding, CreatedAt)
                        VALUES (@Title, @Content, @FilePath, @FileType,
                        @Collection, @Embedding, @CreatedAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, item);
        }

        public async Task<int> UpdateAsync(KnowledgeItem item)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE KnowledgeItems SET Title = @Title, Content = @Content,
                        FilePath = @FilePath, FileType = @FileType,
                        Collection = @Collection, Embedding = @Embedding
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, item);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM KnowledgeItems WHERE Id = @Id", new { Id = id });
        }

        public async Task<IEnumerable<string>> GetCollectionsAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<string>(
                "SELECT DISTINCT Collection FROM KnowledgeItems WHERE Collection IS NOT NULL ORDER BY Collection");
        }
    }
}
