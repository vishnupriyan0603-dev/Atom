using System;
using System.ComponentModel;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Input;

namespace AtomAssistant.Controls;

public partial class ChatControl : UserControl
{
    public static readonly DependencyProperty MessageTextProperty =
        DependencyProperty.Register(nameof(MessageText), typeof(string), typeof(ChatControl),
            new FrameworkPropertyMetadata(string.Empty, FrameworkPropertyMetadataOptions.BindsTwoWayByDefault));

    public static readonly DependencyProperty IsStreamingProperty =
        DependencyProperty.Register(nameof(IsStreaming), typeof(bool), typeof(ChatControl),
            new PropertyMetadata(false, OnIsStreamingChanged));

    public static readonly DependencyProperty IsTypingProperty =
        DependencyProperty.Register(nameof(IsTyping), typeof(bool), typeof(ChatControl),
            new PropertyMetadata(false, OnIsTypingChanged));

    public static readonly DependencyProperty SendCommandProperty =
        DependencyProperty.Register(nameof(SendCommand), typeof(ICommand), typeof(ChatControl));

    public static readonly DependencyProperty AttachFileCommandProperty =
        DependencyProperty.Register(nameof(AttachFileCommand), typeof(ICommand), typeof(ChatControl));

    public static readonly DependencyProperty ImageCommandProperty =
        DependencyProperty.Register(nameof(ImageCommand), typeof(ICommand), typeof(ChatControl));

    public static readonly DependencyProperty MicrophoneCommandProperty =
        DependencyProperty.Register(nameof(MicrophoneCommand), typeof(ICommand), typeof(ChatControl));

    public static readonly DependencyProperty StopCommandProperty =
        DependencyProperty.Register(nameof(StopCommand), typeof(ICommand), typeof(ChatControl));

    public ChatControl()
    {
        InitializeComponent();

        SendButton.Click += OnSendClick;
        AttachFileButton.Click += (_, _) => AttachFileCommand?.Execute(null);
        ImageButton.Click += (_, _) => ImageCommand?.Execute(null);
        MicrophoneButton.Click += (_, _) => MicrophoneCommand?.Execute(null);
        StopButton.Click += (_, _) => StopCommand?.Execute(null);

        MessageTextBox.KeyDown += OnMessageTextBoxKeyDown;
    }

    public string MessageText
    {
        get => (string)GetValue(MessageTextProperty);
        set => SetValue(MessageTextProperty, value);
    }

    public bool IsStreaming
    {
        get => (bool)GetValue(IsStreamingProperty);
        set => SetValue(IsStreamingProperty, value);
    }

    public bool IsTyping
    {
        get => (bool)GetValue(IsTypingProperty);
        set => SetValue(IsTypingProperty, value);
    }

    public ICommand SendCommand
    {
        get => (ICommand)GetValue(SendCommandProperty);
        set => SetValue(SendCommandProperty, value);
    }

    public ICommand AttachFileCommand
    {
        get => (ICommand)GetValue(AttachFileCommandProperty);
        set => SetValue(AttachFileCommandProperty, value);
    }

    public ICommand ImageCommand
    {
        get => (ICommand)GetValue(ImageCommandProperty);
        set => SetValue(ImageCommandProperty, value);
    }

    public ICommand MicrophoneCommand
    {
        get => (ICommand)GetValue(MicrophoneCommandProperty);
        set => SetValue(MicrophoneCommandProperty, value);
    }

    public ICommand StopCommand
    {
        get => (ICommand)GetValue(StopCommandProperty);
        set => SetValue(StopCommandProperty, value);
    }

    public void AppendMessage(string sender, string message, bool isUser = false)
    {
        var border = new Border
        {
            Margin = new Thickness(0, 4),
            Padding = new Thickness(12, 8),
            CornerRadius = new CornerRadius(8),
            Background = isUser
                ? (System.Windows.Media.Brush)TryFindResource("AccentTextFillColorPrimaryBrush")
                : (System.Windows.Media.Brush)TryFindResource("ControlFillColorDefaultBrush"),
            HorizontalAlignment = isUser ? HorizontalAlignment.Right : HorizontalAlignment.Left,
            MaxWidth = 400
        };

        var stack = new StackPanel();
        var header = new TextBlock
        {
            Text = sender,
            FontWeight = FontWeights.SemiBold,
            FontSize = 12,
            Margin = new Thickness(0, 0, 0, 4),
            Foreground = isUser
                ? (System.Windows.Media.Brush)TryFindResource("TextOnAccentFillColorPrimaryBrush")
                : (System.Windows.Media.Brush)TryFindResource("TextFillColorPrimaryBrush")
        };
        stack.Children.Add(header);

        var body = new TextBlock
        {
            Text = message,
            TextWrapping = TextWrapping.Wrap,
            FontSize = 14,
            Foreground = isUser
                ? (System.Windows.Media.Brush)TryFindResource("TextOnAccentFillColorPrimaryBrush")
                : (System.Windows.Media.Brush)TryFindResource("TextFillColorPrimaryBrush")
        };
        stack.Children.Add(body);
        border.Child = stack;

        MessagesPanel.Children.Insert(Math.Max(0, MessagesPanel.Children.Count - 1), border);

        Dispatcher.BeginInvoke(new Action(() =>
        {
            ChatScrollViewer.ScrollToBottom();
        }), System.Windows.Threading.DispatcherPriority.Background);
    }

    public void AppendStreamingChunk(string chunk)
    {
        if (MessagesPanel.Children.Count > 1 &&
            MessagesPanel.Children[^2] is Border border &&
            border.Child is StackPanel stack &&
            stack.Children.Count > 1 &&
            stack.Children[1] is TextBlock streamingBlock)
        {
            streamingBlock.Text += chunk;
            Dispatcher.BeginInvoke(new Action(() =>
            {
                ChatScrollViewer.ScrollToBottom();
            }), System.Windows.Threading.DispatcherPriority.Background);
        }
    }

    public void ClearMessages()
    {
        MessagesPanel.Children.Clear();
        TypingIndicator.Visibility = Visibility.Collapsed;
        MessagesPanel.Children.Add(TypingIndicator);
    }

    private static void OnIsStreamingChanged(DependencyObject d, DependencyPropertyChangedEventArgs e)
    {
        if (d is ChatControl control)
        {
            var isStreaming = (bool)e.NewValue;
            control.SendButton.Visibility = isStreaming ? Visibility.Collapsed : Visibility.Visible;
            control.StopButton.Visibility = isStreaming ? Visibility.Visible : Visibility.Collapsed;
        }
    }

    private static void OnIsTypingChanged(DependencyObject d, DependencyPropertyChangedEventArgs e)
    {
        if (d is ChatControl control)
        {
            control.TypingIndicator.Visibility = (bool)e.NewValue ? Visibility.Visible : Visibility.Collapsed;
        }
    }

    private void OnSendClick(object sender, RoutedEventArgs e)
    {
        if (SendCommand?.CanExecute(MessageText) == true)
        {
            SendCommand.Execute(MessageText);
        }
    }

    private void OnMessageTextBoxKeyDown(object sender, KeyEventArgs e)
    {
        if (e.Key == Key.Enter && !Keyboard.Modifiers.HasFlag(ModifierKeys.Shift))
        {
            e.Handled = true;

            if (!string.IsNullOrWhiteSpace(MessageText) && SendCommand?.CanExecute(MessageText) == true)
            {
                SendCommand.Execute(MessageText);
            }
        }
    }
}
