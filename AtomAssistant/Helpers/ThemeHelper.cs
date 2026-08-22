using System;
using System.Runtime.InteropServices;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Documents;
using System.Windows.Media;
using Microsoft.Extensions.Configuration;
using Microsoft.Win32;
using Wpf.Ui;
using Wpf.Ui.Appearance;

namespace AtomAssistant.Helpers;

public enum ThemeMode
{
    Light = 0,
    Dark = 1,
    System = 2
}

public class ThemeHelper
{
    private readonly IConfiguration _configuration;
    private ThemeMode _currentMode;

    public ThemeHelper(IConfiguration configuration)
    {
        _configuration = configuration;
        _currentMode = ThemeMode.System;
    }

    public ThemeMode CurrentMode => _currentMode;

    public event Action<ThemeMode> ThemeChanged;

    public void InitializeTheme()
    {
        var modeSetting = _configuration["Theme:Mode"] ?? "System";

        _currentMode = modeSetting switch
        {
            "Light" => ThemeMode.Light,
            "Dark" => ThemeMode.Dark,
            _ => ThemeMode.System
        };

        ApplyTheme(_currentMode);
    }

    public void ApplyTheme(ThemeMode mode)
    {
        _currentMode = mode;

        var themeType = mode switch
        {
            ThemeMode.Light => ThemeType.Light,
            ThemeMode.Dark => ThemeType.Dark,
            ThemeMode.System => GetWindowsTheme(),
            _ => ThemeType.Light
        };

        ApplicationThemeManager.Apply(themeType);
        ApplyCustomThemeResources(themeType);

        ThemeChanged?.Invoke(mode);
    }

    private static void ApplyCustomThemeResources(ThemeType themeType)
    {
        if (themeType == ThemeType.Dark)
        {
            Application.Current.Resources["TextFillColorPrimaryBrush"] = new SolidColorBrush(Colors.White);
            Application.Current.Resources["TextFillColorSecondaryBrush"] = new SolidColorBrush(Color.FromRgb(0xE0, 0xE0, 0xF0));
            Application.Current.Resources["TextFillColorTertiaryBrush"] = new SolidColorBrush(Color.FromRgb(0xC0, 0xC0, 0xD8));
        }
        else
        {
            Application.Current.Resources["TextFillColorPrimaryBrush"] = new SolidColorBrush(Color.FromRgb(0x1A, 0x1A, 0x1A));
            Application.Current.Resources["TextFillColorSecondaryBrush"] = new SolidColorBrush(Color.FromRgb(0x4A, 0x4A, 0x4A));
            Application.Current.Resources["TextFillColorTertiaryBrush"] = new SolidColorBrush(Color.FromRgb(0x88, 0x88, 0x88));
        }

        SetDefaultTextStyle(themeType);
    }

    private static void SetDefaultTextStyle(ThemeType themeType)
    {
        var style = new Style(typeof(TextBlock));
        style.Setters.Add(new Setter(TextBlock.ForegroundProperty,
            new DynamicResourceExtension("TextFillColorPrimaryBrush")));
        style.Setters.Add(new Setter(TextElement.ForegroundProperty,
            new DynamicResourceExtension("TextFillColorPrimaryBrush")));
        Application.Current.Resources[typeof(TextBlock)] = style;
    }

    public static ThemeType GetWindowsTheme()
    {
        try
        {
            using var key = Registry.CurrentUser.OpenSubKey(
                @"Software\Microsoft\Windows\CurrentVersion\Themes\Personalize");
            if (key?.GetValue("AppsUseLightTheme") is int value)
            {
                return value == 0 ? ThemeType.Dark : ThemeType.Light;
            }
        }
        catch
        {
        }

        return ThemeType.Light;
    }

    public void ToggleTheme()
    {
        var newMode = _currentMode switch
        {
            ThemeMode.Light => ThemeMode.Dark,
            ThemeMode.Dark => ThemeMode.Light,
            ThemeMode.System => ThemeMode.Light,
            _ => ThemeMode.Light
        };

        ApplyTheme(newMode);
    }
}
