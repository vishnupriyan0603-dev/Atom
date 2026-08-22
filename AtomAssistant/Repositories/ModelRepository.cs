using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class ModelRepository
    {
        private readonly DatabaseService _db;

        public ModelRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<AiModel>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<AiModel>("SELECT * FROM AiModels ORDER BY Name");
        }

        public async Task<IEnumerable<AiModel>> GetEnabledAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<AiModel>(
                "SELECT * FROM AiModels WHERE IsEnabled = 1 ORDER BY Name");
        }

        public async Task<AiModel> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<AiModel>(
                "SELECT * FROM AiModels WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(AiModel model)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO AiModels (Name, Provider, ApiEndpoint, ApiKey,
                        IsLocal, IsEnabled, ContextLength, CreatedAt)
                        VALUES (@Name, @Provider, @ApiEndpoint, @ApiKey,
                        @IsLocal, @IsEnabled, @ContextLength, @CreatedAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, model);
        }

        public async Task<int> UpdateAsync(AiModel model)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE AiModels SET Name = @Name, Provider = @Provider,
                        ApiEndpoint = @ApiEndpoint, ApiKey = @ApiKey,
                        IsLocal = @IsLocal, IsEnabled = @IsEnabled,
                        ContextLength = @ContextLength WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, model);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM AiModels WHERE Id = @Id", new { Id = id });
        }
    }
}
