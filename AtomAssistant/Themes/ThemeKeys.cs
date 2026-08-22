using System.Windows;

namespace AtomAssistant.Themes;

public enum ThemeType
{
    Light,
    Dark,
    System
}

public static class ThemeKeys
{
    // Background
    public static ResourceKey WindowBackgroundBrush { get; } = CreateKey();
    public static ResourceKey PageBackgroundBrush { get; } = CreateKey();
    public static ResourceKey CardBackgroundBrush { get; } = CreateKey();
    public static ResourceKey SidebarBackgroundBrush { get; } = CreateKey();
    public static ResourceKey TopBarBackgroundBrush { get; } = CreateKey();
    public static ResourceKey InputBackgroundBrush { get; } = CreateKey();

    // Text
    public static ResourceKey PrimaryTextBrush { get; } = CreateKey();
    public static ResourceKey SecondaryTextBrush { get; } = CreateKey();
    public static ResourceKey DisabledTextBrush { get; } = CreateKey();
    public static ResourceKey InverseTextBrush { get; } = CreateKey();

    // Borders
    public static ResourceKey SubtleBorderBrush { get; } = CreateKey();
    public static ResourceKey CardBorderBrush { get; } = CreateKey();
    public static ResourceKey InputBorderBrush { get; } = CreateKey();
    public static ResourceKey FocusBorderBrush { get; } = CreateKey();

    // Accents
    public static ResourceKey PrimaryAccentBrush { get; } = CreateKey();
    public static ResourceKey PrimaryAccentHoverBrush { get; } = CreateKey();
    public static ResourceKey PrimaryAccentPressedBrush { get; } = CreateKey();
    public static ResourceKey SuccessAccentBrush { get; } = CreateKey();
    public static ResourceKey WarningAccentBrush { get; } = CreateKey();
    public static ResourceKey ErrorAccentBrush { get; } = CreateKey();
    public static ResourceKey InfoAccentBrush { get; } = CreateKey();

    // Interactive States
    public static ResourceKey HoverBackgroundBrush { get; } = CreateKey();
    public static ResourceKey PressedBackgroundBrush { get; } = CreateKey();
    public static ResourceKey SelectedBackgroundBrush { get; } = CreateKey();
    public static ResourceKey SelectedTextBrush { get; } = CreateKey();

    // Effects
    public static ResourceKey CardShadowEffect { get; } = CreateKey();
    public static ResourceKey PopupShadowEffect { get; } = CreateKey();

    // Corner Radii
    public static ResourceKey CardCornerRadius { get; } = CreateKey();
    public static ResourceKey ButtonCornerRadius { get; } = CreateKey();
    public static ResourceKey InputCornerRadius { get; } = CreateKey();
    public static ResourceKey BadgeCornerRadius { get; } = CreateKey();

    // Styles
    public static ResourceKey CardStyle { get; } = CreateKey();
    public static ResourceKey SectionHeaderStyle { get; } = CreateKey();
    public static ResourceKey BodyTextStyle { get; } = CreateKey();
    public static ResourceKey DividerStyle { get; } = CreateKey();

    private static ResourceKey CreateKey([System.Runtime.CompilerServices.CallerMemberName] string name = "")
    {
        return new ComponentResourceKey(typeof(ThemeKeys), name);
    }
}