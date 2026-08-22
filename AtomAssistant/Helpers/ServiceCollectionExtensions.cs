using System;
using System.Data;
using System.IO;
using Microsoft.Data.Sqlite;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using AtomAssistant.Controls;
using AtomAssistant.Converters;
using AtomAssistant.Helpers;
using AtomAssistant.Repositories;
using AtomAssistant.Services;
using AtomAssistant.ViewModels;
using AtomAssistant.ViewModels.Pages;
using AtomAssistant.Views;
using AtomAssistant.Views.Pages;

namespace AtomAssistant.Helpers;

public static class ServiceCollectionExtensions
{
    public static IServiceCollection AddApplicationServices(this IServiceCollection services, IConfiguration configuration)
    {
        var baseDir = AppContext.BaseDirectory;
        var dataDir = Path.Combine(baseDir, "Data");
        var logsDir = Path.Combine(baseDir, "Logs");

        Directory.CreateDirectory(dataDir);
        Directory.CreateDirectory(logsDir);

        services.AddSingleton<HttpClient>(sp => new HttpClient { Timeout = TimeSpan.FromSeconds(120) });

        services.AddSingleton<IDbConnection>(sp =>
        {
            var connString = configuration.GetConnectionString("DefaultConnection")
                ?? configuration["Database:ConnectionString"]
                ?? "Data Source=" + Path.Combine(dataDir, "atomassistant.db");
            return new SqliteConnection(connString);
        });

        services.AddRepositories();
        services.AddServices();
        services.AddViewModels();
        services.AddViews();
        services.AddConverters();
        services.AddControls();

        return services;
    }

    private static IServiceCollection AddRepositories(this IServiceCollection services)
    {
        services.AddSingleton<ChatRepository>();
        services.AddSingleton<MessageRepository>();
        services.AddSingleton<ConversationRepository>();
        services.AddSingleton<ModelRepository>();
        services.AddSingleton<PromptRepository>();
        services.AddSingleton<FileRepository>();
        services.AddSingleton<PluginRepository>();
        services.AddSingleton<NoteRepository>();
        services.AddSingleton<KnowledgeBaseRepository>();
        services.AddSingleton<SettingsRepository>();

        return services;
    }

    private static IServiceCollection AddServices(this IServiceCollection services)
    {
        services.AddSingleton<IAiProviderService, CloudAiService>();
        services.AddSingleton<ChatService>();
        services.AddSingleton<FileService>();
        services.AddSingleton<PluginService>();
        services.AddSingleton<ExportService>();
        services.AddSingleton<MarkdownService>();
        services.AddSingleton<BackendService>();
        services.AddSingleton<SyncService>();

        return services;
    }

    private static IServiceCollection AddViewModels(this IServiceCollection services)
    {
        services.AddTransient<MainWindowViewModel>();
        services.AddTransient<ChatPageViewModel>();
        services.AddTransient<ChatsViewModel>();
        services.AddTransient<ModelsViewModel>();
        services.AddTransient<PromptLibraryViewModel>();
        services.AddTransient<FilesViewModel>();
        services.AddTransient<PluginsViewModel>();
        services.AddTransient<HistoryViewModel>();
        services.AddTransient<NotesViewModel>();
        services.AddTransient<KnowledgeBaseViewModel>();
        services.AddTransient<SettingsViewModel>();

        return services;
    }

    private static IServiceCollection AddViews(this IServiceCollection services)
    {
        services.AddTransient<MainWindow>();
        services.AddTransient<ChatPage>();
        services.AddTransient<ChatsPage>();
        services.AddTransient<ModelsPage>();
        services.AddTransient<PromptLibraryPage>();
        services.AddTransient<FilesPage>();
        services.AddTransient<PluginsPage>();
        services.AddTransient<HistoryPage>();
        services.AddTransient<NotesPage>();
        services.AddTransient<KnowledgeBasePage>();
        services.AddTransient<SettingsPage>();

        return services;
    }

    private static IServiceCollection AddConverters(this IServiceCollection services)
    {
        services.AddSingleton<BoolToVisibilityConverter>();
        services.AddSingleton<ThemeIndexConverter>();

        return services;
    }

    private static IServiceCollection AddControls(this IServiceCollection services)
    {
        services.AddTransient<SidebarControl>();
        services.AddTransient<ChatControl>();

        return services;
    }
}
