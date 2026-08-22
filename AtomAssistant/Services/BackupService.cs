using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.Services
{
    public class BackupService
    {
        private readonly IConfiguration _configuration;
        private readonly ILogger<BackupService> _logger;
        private readonly string _databasePath;
        private readonly string _backupDirectory;

        public BackupService(IConfiguration configuration, ILogger<BackupService> logger)
        {
            _configuration = configuration;
            _logger = logger;

            var connectionString = _configuration.GetConnectionString("DefaultConnection")
                ?? _configuration["Database:ConnectionString"]
                ?? "Data Source=Data/atomassistant.db";

            var dataSource = ParseDataSource(connectionString);
            _databasePath = Path.IsPathRooted(dataSource)
                ? dataSource
                : Path.Combine(AppDomain.CurrentDomain.BaseDirectory, dataSource);

            _backupDirectory = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Backups");
            if (!Directory.Exists(_backupDirectory))
                Directory.CreateDirectory(_backupDirectory);
        }

        public async Task<string> BackupDatabase()
        {
            if (!File.Exists(_databasePath))
                throw new FileNotFoundException("Database file not found", _databasePath);

            var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
            var backupFileName = $"atomassistant_backup_{timestamp}.db";
            var backupPath = Path.Combine(_backupDirectory, backupFileName);

            await Task.Run(() => File.Copy(_databasePath, backupPath, true));

            _logger.LogInformation("Database backed up to {BackupPath}", backupPath);

            var infoPath = backupPath + ".json";
            var info = new BackupInfo
            {
                FileName = backupFileName,
                OriginalDatabase = _databasePath,
                BackupDate = DateTime.Now,
                FileSize = new FileInfo(backupPath).Length
            };

            var json = System.Text.Json.JsonSerializer.Serialize(info, new System.Text.Json.JsonSerializerOptions { WriteIndented = true });
            await File.WriteAllTextAsync(infoPath, json);

            return backupPath;
        }

        public async Task RestoreDatabase(string backupPath)
        {
            if (!File.Exists(backupPath))
                throw new FileNotFoundException("Backup file not found", backupPath);

            _logger.LogWarning("Restoring database from {BackupPath}", backupPath);

            var tempBackup = _databasePath + ".pre_restore";
            if (File.Exists(_databasePath))
            {
                File.Copy(_databasePath, tempBackup, true);
            }

            try
            {
                await Task.Run(() => File.Copy(backupPath, _databasePath, true));
                _logger.LogInformation("Database restored successfully from {BackupPath}", backupPath);

                if (File.Exists(tempBackup))
                    File.Delete(tempBackup);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to restore database from {BackupPath}", backupPath);
                if (File.Exists(tempBackup))
                {
                    File.Copy(tempBackup, _databasePath, true);
                    File.Delete(tempBackup);
                }
                throw;
            }
        }

        public async Task<List<BackupInfo>> ListBackups()
        {
            if (!Directory.Exists(_backupDirectory))
                return new List<BackupInfo>();

            var backups = new List<BackupInfo>();
            var backupFiles = Directory.GetFiles(_backupDirectory, "*.db")
                .OrderByDescending(f => f)
                .ToList();

            foreach (var file in backupFiles)
            {
                var infoPath = file + ".json";
                BackupInfo info;

                if (File.Exists(infoPath))
                {
                    try
                    {
                        var json = await File.ReadAllTextAsync(infoPath);
                        info = System.Text.Json.JsonSerializer.Deserialize<BackupInfo>(json) ?? new BackupInfo();
                        info.FileName = Path.GetFileName(file);
                    }
                    catch
                    {
                        info = CreateBackupInfoFromFile(file);
                    }
                }
                else
                {
                    info = CreateBackupInfoFromFile(file);
                }

                info.FileSize = new FileInfo(file).Length;
                backups.Add(info);
            }

            return backups;
        }

        public async Task DeleteBackup(string backupFileName)
        {
            var backupPath = Path.Combine(_backupDirectory, backupFileName);

            if (File.Exists(backupPath))
            {
                await Task.Run(() => File.Delete(backupPath));
                _logger.LogInformation("Deleted backup {BackupFileName}", backupFileName);
            }

            var infoPath = backupPath + ".json";
            if (File.Exists(infoPath))
            {
                await Task.Run(() => File.Delete(infoPath));
            }
        }

        public string GetBackupDirectory()
        {
            return _backupDirectory;
        }

        private static BackupInfo CreateBackupInfoFromFile(string filePath)
        {
            var fileName = Path.GetFileName(filePath);
            var fileInfo = new FileInfo(filePath);

            DateTime backupDate;
            if (fileName.StartsWith("atomassistant_backup_") && fileName.EndsWith(".db"))
            {
                var datePart = fileName.Replace("atomassistant_backup_", "").Replace(".db", "");
                DateTime.TryParseExact(datePart, "yyyyMMdd_HHmmss", null,
                    System.Globalization.DateTimeStyles.None, out backupDate);
            }
            else
            {
                backupDate = fileInfo.CreationTime;
            }

            return new BackupInfo
            {
                FileName = fileName,
                BackupDate = backupDate,
                FileSize = fileInfo.Length
            };
        }

        private static string ParseDataSource(string connectionString)
        {
            var parts = connectionString.Split(';');
            foreach (var part in parts)
            {
                var trimmed = part.Trim();
                if (trimmed.StartsWith("Data Source=", StringComparison.OrdinalIgnoreCase) ||
                    trimmed.StartsWith("DataSource=", StringComparison.OrdinalIgnoreCase))
                {
                    var value = trimmed.Split('=', 2)[1].Trim();
                    return value;
                }
            }
            return "Data/atomassistant.db";
        }

        public class BackupInfo
        {
            public string FileName { get; set; } = "";
            public string? OriginalDatabase { get; set; }
            public DateTime BackupDate { get; set; }
            public long FileSize { get; set; }
        }
    }
}
