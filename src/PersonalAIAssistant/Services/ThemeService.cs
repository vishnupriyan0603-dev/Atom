using System.Windows;

namespace PersonalAIAssistant.Services;

public sealed class ThemeService(ISettingsService settingsService) : IThemeService
{
    public async Task ApplySavedThemeAsync()
    {
        await SetThemeAsync("Dark");
    }

    public async Task SetThemeAsync(string themeName)
    {
        // Enforce pure Dark theme throughout desktop app
        var uri = new Uri("Resources/Themes/Dark.xaml", UriKind.Relative);
        var dictionaries = Application.Current.Resources.MergedDictionaries;
        var existing = dictionaries.FirstOrDefault(d => d.Source?.OriginalString.Contains("Resources/Themes/") == true);

        if (existing is not null)
        {
            dictionaries.Remove(existing);
        }

        dictionaries.Insert(0, new ResourceDictionary { Source = uri });
        await settingsService.SaveAsync("Theme", "Dark");
    }
}
