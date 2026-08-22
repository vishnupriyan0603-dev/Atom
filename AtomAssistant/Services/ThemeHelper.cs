using System;
using Wpf.Ui.Appearance;

namespace AtomAssistant.Services
{
    public static class ThemeHelper
    {
        public static void ApplyTheme(string theme)
        {
            switch (theme.ToLower())
            {
                case "light":
                    ApplicationThemeManager.Apply(ThemeType.Light);
                    break;
                case "dark":
                    ApplicationThemeManager.Apply(ThemeType.Dark);
                    break;
                case "system":
                case "auto":
                    ApplicationThemeManager.Apply(ThemeType.Auto);
                    break;
                default:
                    ApplicationThemeManager.Apply(ThemeType.Auto);
                    break;
            }
        }

        public static string GetCurrentTheme()
        {
            var current = ApplicationThemeManager.GetAppTheme();
            return current switch
            {
                ThemeType.Dark => "Dark",
                ThemeType.Light => "Light",
                ThemeType.Auto => "System",
                _ => "System"
            };
        }
    }
}
