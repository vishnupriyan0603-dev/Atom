using System.Windows;

namespace PersonalAIAssistant.Services;

public sealed class ThemeService(ISettingsService settingsService) : IThemeService
{
    public async Task ApplySavedThemeAsync()
    {
        var theme = await settingsService.GetAsync("Theme", "Auto");
        await SetThemeAsync(theme);
    }

    public async Task SetThemeAsync(string themeName)
    {
        var resolvedTheme = themeName == "Auto" ? ResolveWindowsTheme() : themeName;
        var uri = new Uri($"Resources/Themes/{resolvedTheme}.xaml", UriKind.Relative);
        var dictionaries = Application.Current.Resources.MergedDictionaries;
        var existing = dictionaries.FirstOrDefault(d => d.Source?.OriginalString.Contains("Resources/Themes/") == true);

        if (existing is not null)
        {
            dictionaries.Remove(existing);
        }

        dictionaries.Insert(0, new ResourceDictionary { Source = uri });
        await settingsService.SaveAsync("Theme", themeName);
    }

    private static string ResolveWindowsTheme()
    {
        const string key = @"Software\Microsoft\Windows\CurrentVersion\Themes\Personalize";
        using var personalize = Microsoft.Win32.Registry.CurrentUser.OpenSubKey(key);
        var value = personalize?.GetValue("AppsUseLightTheme");
        return value is int intValue && intValue == 0 ? "Dark" : "Light";
    }
}
