using System.IO.Compression;
using System.Text.Json;
using AtomAssistant.Models;
using AtomAssistant.Repositories;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.Services
{
    public class PluginService
    {
        private readonly IPluginRepository _pluginRepository;
        private readonly ILogger<PluginService> _logger;
        private readonly string _pluginsDirectory;

        public PluginService(IPluginRepository pluginRepository, ILogger<PluginService> logger)
        {
            _pluginRepository = pluginRepository;
            _logger = logger;
            _pluginsDirectory = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Plugins");

            if (!Directory.Exists(_pluginsDirectory))
                Directory.CreateDirectory(_pluginsDirectory);
        }

        public async Task<List<PluginInfo>> LoadPlugins()
        {
            var plugins = new List<PluginInfo>();
            var directories = Directory.GetDirectories(_pluginsDirectory);

            foreach (var dir in directories)
            {
                var manifestPath = Path.Combine(dir, "manifest.json");
                if (!File.Exists(manifestPath)) continue;

                try
                {
                    var manifestJson = await File.ReadAllTextAsync(manifestPath);
                    var manifest = JsonSerializer.Deserialize<PluginManifest>(manifestJson);
                    if (manifest == null) continue;

                    var plugin = new PluginInfo
                    {
                        Name = manifest.Name,
                        Version = manifest.Version,
                        Author = manifest.Author,
                        Description = manifest.Description,
                        IconPath = Path.Combine(dir, manifest.Icon ?? "icon.png"),
                        IsEnabled = true,
                        InstalledAt = DateTime.UtcNow
                    };

                    var existing = await _pluginRepository.GetByNameAsync(plugin.Name);
                    if (existing == null)
                    {
                        await _pluginRepository.AddAsync(plugin);
                    }
                    else
                    {
                        plugin.Id = existing.Id;
                        plugin.IsEnabled = existing.IsEnabled;
                        plugin.InstalledAt = existing.InstalledAt;
                        await _pluginRepository.UpdateAsync(plugin);
                    }

                    plugins.Add(plugin);
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Failed to load plugin from {Directory}", dir);
                }
            }

            return plugins;
        }

        public async Task EnablePlugin(int pluginId)
        {
            var plugin = await _pluginRepository.GetByIdAsync(pluginId);
            if (plugin != null)
            {
                plugin.IsEnabled = true;
                await _pluginRepository.UpdateAsync(plugin);
                _logger.LogInformation("Enabled plugin {PluginName}", plugin.Name);
            }
        }

        public async Task DisablePlugin(int pluginId)
        {
            var plugin = await _pluginRepository.GetByIdAsync(pluginId);
            if (plugin != null)
            {
                plugin.IsEnabled = false;
                await _pluginRepository.UpdateAsync(plugin);
                _logger.LogInformation("Disabled plugin {PluginName}", plugin.Name);
            }
        }

        public async Task<List<PluginInfo>> GetInstalledPlugins()
        {
            return await _pluginRepository.GetAllAsync();
        }

        public async Task<List<PluginInfo>> ScanForPlugins()
        {
            var scanned = await LoadPlugins();
            var dbPlugins = await _pluginRepository.GetAllAsync();

            foreach (var dbPlugin in dbPlugins)
            {
                if (!scanned.Any(p => p.Name == dbPlugin.Name))
                {
                    scanned.Add(dbPlugin);
                }
            }

            return scanned;
        }

        public async Task<PluginInfo> InstallPlugin(string pluginPath)
        {
            if (!File.Exists(pluginPath))
                throw new FileNotFoundException("Plugin package not found", pluginPath);

            var tempDir = Path.Combine(Path.GetTempPath(), "AtomAssistant_Plugins", Guid.NewGuid().ToString());
            Directory.CreateDirectory(tempDir);

            try
            {
                if (pluginPath.EndsWith(".zip", StringComparison.OrdinalIgnoreCase))
                {
                    ZipFile.ExtractToDirectory(pluginPath, tempDir);
                }
                else if (Directory.Exists(pluginPath))
                {
                    CopyDirectory(pluginPath, tempDir);
                }
                else
                {
                    throw new ArgumentException("Plugin path must be a .zip file or a directory");
                }

                var manifestPath = Path.Combine(tempDir, "manifest.json");
                if (!File.Exists(manifestPath))
                    throw new InvalidOperationException("Plugin package must contain a manifest.json file");

                var manifestJson = await File.ReadAllTextAsync(manifestPath);
                var manifest = JsonSerializer.Deserialize<PluginManifest>(manifestJson);

                if (manifest == null || string.IsNullOrEmpty(manifest.Name))
                    throw new InvalidOperationException("Invalid manifest.json");

                var pluginDir = Path.Combine(_pluginsDirectory, manifest.Name);
                if (Directory.Exists(pluginDir))
                    Directory.Delete(pluginDir, true);

                CopyDirectory(tempDir, pluginDir);

                var plugin = new PluginInfo
                {
                    Name = manifest.Name,
                    Version = manifest.Version,
                    Author = manifest.Author,
                    Description = manifest.Description,
                    IconPath = Path.Combine(pluginDir, manifest.Icon ?? "icon.png"),
                    IsEnabled = true,
                    InstalledAt = DateTime.UtcNow
                };

                await _pluginRepository.AddAsync(plugin);
                _logger.LogInformation("Installed plugin {PluginName} v{Version}", plugin.Name, plugin.Version);

                return plugin;
            }
            finally
            {
                if (Directory.Exists(tempDir))
                    Directory.Delete(tempDir, true);
            }
        }

        private static void CopyDirectory(string sourceDir, string destDir)
        {
            Directory.CreateDirectory(destDir);

            foreach (var file in Directory.GetFiles(sourceDir))
            {
                var dest = Path.Combine(destDir, Path.GetFileName(file));
                File.Copy(file, dest, true);
            }

            foreach (var dir in Directory.GetDirectories(sourceDir))
            {
                var dest = Path.Combine(destDir, Path.GetFileName(dir));
                CopyDirectory(dir, dest);
            }
        }

        private class PluginManifest
        {
            public string Name { get; set; } = "";
            public string Version { get; set; } = "1.0.0";
            public string Author { get; set; } = "";
            public string Description { get; set; } = "";
            public string? Icon { get; set; }
            public string EntryPoint { get; set; } = "";
        }
    }
}
