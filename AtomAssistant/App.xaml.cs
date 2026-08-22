using System.Windows;
using AtomAssistant.Services;
using Wpf.Ui.Appearance;

namespace AtomAssistant
{
    public partial class App : Application
    {
        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);

            ThemeHelper.ApplyTheme("System");
        }
    }
}
