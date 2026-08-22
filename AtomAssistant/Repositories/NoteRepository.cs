using System.Collections.Generic;
using System.Data;
using System.Threading.Tasks;
using Dapper;
using AtomAssistant.Database;
using AtomAssistant.Models;

namespace AtomAssistant.Repositories
{
    public class NoteRepository
    {
        private readonly DatabaseService _db;

        public NoteRepository(DatabaseService db)
        {
            _db = db;
        }

        public async Task<IEnumerable<Note>> GetAllAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Note>("SELECT * FROM Notes ORDER BY UpdatedAt DESC");
        }

        public async Task<IEnumerable<Note>> GetByFolderAsync(string folder)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Note>(
                "SELECT * FROM Notes WHERE Folder = @Folder ORDER BY UpdatedAt DESC",
                new { Folder = folder });
        }

        public async Task<IEnumerable<Note>> GetFavoritesAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<Note>(
                "SELECT * FROM Notes WHERE IsFavorite = 1 ORDER BY UpdatedAt DESC");
        }

        public async Task<Note> GetByIdAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryFirstOrDefaultAsync<Note>(
                "SELECT * FROM Notes WHERE Id = @Id", new { Id = id });
        }

        public async Task<int> InsertAsync(Note note)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"INSERT INTO Notes (Title, Content, Folder, IsFavorite, CreatedAt, UpdatedAt)
                        VALUES (@Title, @Content, @Folder, @IsFavorite, @CreatedAt, @UpdatedAt);
                        SELECT last_insert_rowid();";
            return await conn.ExecuteScalarAsync<int>(sql, note);
        }

        public async Task<int> UpdateAsync(Note note)
        {
            using IDbConnection conn = _db.GetConnection();
            var sql = @"UPDATE Notes SET Title = @Title, Content = @Content,
                        Folder = @Folder, IsFavorite = @IsFavorite, UpdatedAt = @UpdatedAt
                        WHERE Id = @Id";
            return await conn.ExecuteAsync(sql, note);
        }

        public async Task<int> DeleteAsync(int id)
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.ExecuteAsync(
                "DELETE FROM Notes WHERE Id = @Id", new { Id = id });
        }

        public async Task<IEnumerable<string>> GetFoldersAsync()
        {
            using IDbConnection conn = _db.GetConnection();
            return await conn.QueryAsync<string>(
                "SELECT DISTINCT Folder FROM Notes WHERE Folder IS NOT NULL ORDER BY Folder");
        }
    }
}
