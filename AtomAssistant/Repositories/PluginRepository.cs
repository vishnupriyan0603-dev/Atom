using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class PluginRepository
    {
        private readonly DatabaseService _db;

        public PluginRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<PluginInfo>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<PluginInfo>("SELECT * FROM PluginInfo ORDER BY Name");
        }

        public async Task<IEnumerable<PluginInfo>> GetEnabledAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<PluginInfo>(
                "SELECT * FROM PluginInfo WHERE IsEnabled = 1 ORDER BY Name");
        }

        public async Task<PluginInfo> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<PluginInfo>(
                "SELECT * FROM PluginInfo WHERE Id = @Id", new { Id = id });
        }

        public async Task<PluginInfo> GetByNameAsync(string name)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<PluginInfo>(
                "SELECT * FROM PluginInfo WHERE Name = @Name", new { Name = name });
        }

        public async Task<int> InsertAsync(PluginInfo plugin)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO PluginInfo (Name, Version, Author, Description,
                        IconPath, IsEnabled, InstalledAt)
                        VALUES (@Name, @Version, @Author, @Description,
                        @IconPath, @IsEnabled, @InstalledAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, plugin);
        }

        public async Task<int> UpdateAsync(PluginInfo plugin)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE PluginInfo SET Name = @Name, Version = @Version,
                        Author = @Author, Description = @Description,
                        IconPath = @IconPath, IsEnabled = @IsEnabled
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, plugin);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM PluginInfo WHERE Id = @Id", new { Id = id });
        }
    }
}
