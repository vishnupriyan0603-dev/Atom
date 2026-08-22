using System;
using System.Globalization;
using System.Windows.Data;
using AtomAssistant.Helpers;

namespace AtomAssistant.Converters;

public class ThemeIndexConverter : IValueConverter
{
    public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
    {
        if (value is ThemeMode mode)
        {
            return (int)mode;
        }

        return 2;
    }

    public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
    {
        if (value is int index)
        {
            return index switch
            {
                0 => ThemeMode.Light,
                1 => ThemeMode.Dark,
                2 => ThemeMode.System,
                _ => ThemeMode.System
            };
        }

        return ThemeMode.System;
    }
}
