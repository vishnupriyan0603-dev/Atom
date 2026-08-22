using System.IO;
using System.Windows;
using Microsoft.Extensions.Configuration;
using Newtonsoft.Json;

namespace AtomAssistant.Helpers;

public static class WindowHelper
{
    private static readonly string SettingsFilePath = Path.Combine(
        AppContext.BaseDirectory, "Config", "window-state.json");

    private static readonly JsonSerializerSettings JsonSettings = new()
    {
        Formatting = Formatting.Indented
    };

    public static void SaveWindowState(Window window)
    {
        try
        {
            var configDir = Path.GetDirectoryName(SettingsFilePath);
            if (!string.IsNullOrEmpty(configDir) && !Directory.Exists(configDir))
            {
                Directory.CreateDirectory(configDir);
            }

            var state = new WindowStateData
            {
                Left = window.Left,
                Top = window.Top,
                Width = window.Width,
                Height = window.Height,
                WindowState = window.WindowState
            };

            var json = JsonConvert.SerializeObject(state, JsonSettings);
            File.WriteAllText(SettingsFilePath, json);
        }
        catch
        {
            // Silently fail - window state save is non-critical
        }
    }

    public static void RestoreWindowState(Window window)
    {
        try
        {
            if (!File.Exists(SettingsFilePath))
                return;

            var json = File.ReadAllText(SettingsFilePath);
            var state = JsonConvert.DeserializeObject<WindowStateData>(json, JsonSettings);

            if (state == null)
                return;

            if (state.WindowState == WindowState.Maximized)
            {
                window.WindowState = WindowState.Normal;
                window.Left = state.Left;
                window.Top = state.Top;
                window.Width = state.Width;
                window.Height = state.Height;
                window.WindowState = WindowState.Maximized;
            }
            else
            {
                window.Left = state.Left;
                window.Top = state.Top;
                window.Width = state.Width;
                window.Height = state.Height;
                window.WindowState = state.WindowState;
            }
        }
        catch
        {
            // Silently fail - window state restore is non-critical
        }
    }

    public static void CenterWindowOnScreen(Window window)
    {
        window.WindowStartupLocation = WindowStartupLocation.CenterScreen;
    }

    public static void CenterWindowOnOwner(Window window)
    {
        window.WindowStartupLocation = WindowStartupLocation.CenterOwner;
    }

    private class WindowStateData
    {
        public double Left { get; set; }
        public double Top { get; set; }
        public double Width { get; set; }
        public double Height { get; set; }
        public WindowState WindowState { get; set; }
    }
}