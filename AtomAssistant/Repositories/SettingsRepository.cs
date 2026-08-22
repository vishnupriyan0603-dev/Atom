using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class SettingsRepository
    {
        private readonly DatabaseService _db;

        public SettingsRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<UserSettings>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<UserSettings>("SELECT * FROM UserSettings");
        }

        public async Task<UserSettings> GetByKeyAsync(string key)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<UserSettings>(
                "SELECT * FROM UserSettings WHERE Key = @Key", new { Key = key });
        }

        public async Task<UserSettings> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<UserSettings>(
                "SELECT * FROM UserSettings WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> UpsertAsync(UserSettings setting)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO UserSettings (Key, Value, Type)
                        VALUES (@Key, @Value, @Type)
                        ON CONFLICT(Key) DO UPDATE SET Value = @Value, Type = @Type;
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, setting);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM UserSettings WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> DeleteByKeyAsync(string key)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM UserSettings WHERE Key = @Key", new { Key = key });
        }
    }
}
