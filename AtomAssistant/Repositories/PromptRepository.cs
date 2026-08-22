using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class PromptRepository
    {
        private readonly DatabaseService _db;

        public PromptRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<Prompt>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Prompt>("SELECT * FROM Prompts ORDER BY UpdatedAt DESC");
        }

        public async Task<IEnumerable<Prompt>> GetByCategoryAsync(string category)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Prompt>(
                "SELECT * FROM Prompts WHERE Category = @Category ORDER BY Title",
                new { Category = category });
        }

        public async Task<IEnumerable<Prompt>> GetFavoritesAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Prompt>(
                "SELECT * FROM Prompts WHERE IsFavorite = 1 ORDER BY Title");
        }

        public async Task<Prompt> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<Prompt>(
                "SELECT * FROM Prompts WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(Prompt prompt)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO Prompts (Title, Content, Category, IsFavorite, CreatedAt, UpdatedAt)
                        VALUES (@Title, @Content, @Category, @IsFavorite, @CreatedAt, @UpdatedAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, prompt);
        }

        public async Task<int> UpdateAsync(Prompt prompt)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE Prompts SET Title = @Title, Content = @Content,
                        Category = @Category, IsFavorite = @IsFavorite, UpdatedAt = @UpdatedAt
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, prompt);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM Prompts WHERE Id = @Id", new { Id = id });
        }
    }
}
