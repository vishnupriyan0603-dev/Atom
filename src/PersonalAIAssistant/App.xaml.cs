using System.Windows;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using PersonalAIAssistant.Services;
using PersonalAIAssistant.ViewModels;
using PersonalAIAssistant.Views;
using Serilog;

namespace PersonalAIAssistant;

public partial class App : Application
{
    private IHost? _host;

    protected override async void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        Log.Logger = new LoggerConfiguration()
            .MinimumLevel.Information()
            .WriteTo.File("logs/assistant-.log", rollingInterval: RollingInterval.Day)
            .CreateLogger();

        _host = Host.CreateDefaultBuilder()
            .ConfigureAppConfiguration(config => config.AddJsonFile("appsettings.json", optional: true, reloadOnChange: true))
            .UseSerilog()
            .ConfigureServices(RegisterServices)
            .Build();

        await _host.StartAsync();
        await _host.Services.GetRequiredService<IDatabaseInitializer>().InitializeAsync();

        var themeService = _host.Services.GetRequiredService<IThemeService>();
        await themeService.ApplySavedThemeAsync();

        MainWindow = _host.Services.GetRequiredService<MainWindow>();
        MainWindow.Show();
    }

    protected override async void OnExit(ExitEventArgs e)
    {
        if (_host is not null)
        {
            await _host.StopAsync(TimeSpan.FromSeconds(3));
            _host.Dispose();
        }

        Log.CloseAndFlush();
        base.OnExit(e);
    }

    private static void RegisterServices(IServiceCollection services)
    {
        services.AddSingleton<IDatabaseConnectionFactory, SqliteConnectionFactory>();
        services.AddSingleton<IDatabaseInitializer, DatabaseInitializer>();
        services.AddSingleton<ISettingsService, SettingsService>();
        services.AddSingleton<IThemeService, ThemeService>();
        services.AddSingleton<IAiProviderRegistry, AiProviderRegistry>();
        services.AddSingleton<IAiService, AiService>();
        services.AddSingleton<IChatRepository, ChatRepository>();
        services.AddSingleton<IFileAnalysisService, FileAnalysisService>();
        services.AddSingleton<IPluginRegistry, PluginRegistry>();
        services.AddSingleton<INavigationService, NavigationService>();

        services.AddSingleton<MainViewModel>();
        services.AddSingleton<DashboardViewModel>();
        services.AddSingleton<ChatViewModel>();
        services.AddSingleton<ModelsViewModel>();
        services.AddSingleton<PromptLibraryViewModel>();
        services.AddSingleton<FilesViewModel>();
        services.AddSingleton<PluginsViewModel>();
        services.AddSingleton<SettingsViewModel>();

        services.AddSingleton<MainWindow>();
    }
}
