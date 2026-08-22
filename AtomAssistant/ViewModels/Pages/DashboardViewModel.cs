using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class DashboardViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<ChatItem> recentChats = new();

        [ObservableProperty]
        private ObservableCollection<FileItem> recentFiles = new();

        [ObservableProperty]
        private ObservableCollection<PromptItem> favoritePrompts = new();

        [ObservableProperty]
        private int gpuUsage;

        [ObservableProperty]
        private int cpuUsage;

        [ObservableProperty]
        private int ramUsage;

        [ObservableProperty]
        private string gpuUsageText = "0%";

        [ObservableProperty]
        private string cpuUsageText = "0%";

        [ObservableProperty]
        private string ramUsageText = "0%";

        [ObservableProperty]
        private string systemUptime = "0:00:00";

        [ObservableProperty]
        private string storageUsed = "0 GB";

        [ObservableProperty]
        private string storageFree = "0 GB";

        [ObservableProperty]
        private double storageUsagePercent;

        [ObservableProperty]
        private int installedModelsCount;

        public bool HasRecentChats => RecentChats.Count > 0;
        public bool HasNoRecentChats => RecentChats.Count == 0;
        public int RecentChatsCount => RecentChats.Count;

        public bool HasRecentFiles => RecentFiles.Count > 0;
        public bool HasNoRecentFiles => RecentFiles.Count == 0;
        public int RecentFilesCount => RecentFiles.Count;

        public bool HasFavoritePrompts => FavoritePrompts.Count > 0;
        public bool HasNoFavoritePrompts => FavoritePrompts.Count == 0;
        public int FavoritePromptsCount => FavoritePrompts.Count;

        public DashboardViewModel()
        {
            LoadDashboard();
        }

        [RelayCommand]
        private void LoadDashboard()
        {
            RecentChats = new ObservableCollection<ChatItem>
            {
                new() { Title = "How to implement MVVM in WPF" },
                new() { Title = "Debugging LINQ queries" },
                new() { Title = "ASP.NET Core best practices" }
            };

            RecentFiles = new ObservableCollection<FileItem>
            {
                new() { FileName = "Program.cs" },
                new() { FileName = "appsettings.json" },
                new() { FileName = "MainWindow.xaml" }
            };

            FavoritePrompts = new ObservableCollection<PromptItem>
            {
                new() { Title = "Code Review Checklist" },
                new() { Title = "SQL Query Optimizer" },
                new() { Title = "API Endpoint Design" }
            };

            GpuUsage = 45;
            CpuUsage = 62;
            RamUsage = 71;
            GpuUsageText = $"{GpuUsage}%";
            CpuUsageText = $"{CpuUsage}%";
            RamUsageText = $"{RamUsage}%";
            SystemUptime = TimeSpan.FromHours(48).ToString(@"d\.hh\:mm\:ss");
            StorageUsed = "124.5 GB";
            StorageFree = "375.5 GB";
            StorageUsagePercent = 24.9;
            InstalledModelsCount = 3;

            OnPropertyChanged(nameof(HasRecentChats));
            OnPropertyChanged(nameof(HasNoRecentChats));
            OnPropertyChanged(nameof(RecentChatsCount));
            OnPropertyChanged(nameof(HasRecentFiles));
            OnPropertyChanged(nameof(HasNoRecentFiles));
            OnPropertyChanged(nameof(RecentFilesCount));
            OnPropertyChanged(nameof(HasFavoritePrompts));
            OnPropertyChanged(nameof(HasNoFavoritePrompts));
            OnPropertyChanged(nameof(FavoritePromptsCount));
        }
    }
}
