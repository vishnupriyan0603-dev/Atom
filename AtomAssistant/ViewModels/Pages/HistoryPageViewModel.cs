using System;
using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Linq;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class HistoryPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<ChatItem> allChats = new();

        [ObservableProperty]
        private ObservableCollection<ChatItem> groupedChats = new();

        [ObservableProperty]
        private ChatItem? selectedChat;

        [ObservableProperty]
        private string searchText = string.Empty;

        public HistoryPageViewModel()
        {
            LoadSampleData();
            FilterChats();
        }

        partial void OnSearchTextChanged(string value) => FilterChats();

        private void LoadSampleData()
        {
            AllChats = new ObservableCollection<ChatItem>
            {
                new() { Title = "How to implement MVVM in WPF",   Preview = "Discussion about MVVM pattern and data binding...", LastModified = DateTime.Now.AddHours(-2) },
                new() { Title = "Debugging LINQ queries",         Preview = "Troubleshooting LINQ query performance issues...", LastModified = DateTime.Now.AddDays(-1) },
                new() { Title = "ASP.NET Core best practices",    Preview = "Best practices for ASP.NET Core web API...",        LastModified = DateTime.Now.AddDays(-2) },
                new() { Title = "SQL query optimization",         Preview = "Optimizing complex JOIN queries...",              LastModified = DateTime.Now.AddDays(-5) },
                new() { Title = "React component design patterns",Preview = "Designing reusable React components...",            LastModified = DateTime.Now.AddDays(-7) },
                new() { Title = "Docker compose setup",           Preview = "Setting up multi-container Docker environment...", LastModified = DateTime.Now.AddDays(-14) },
                new() { Title = "Code review for PR #142",        Preview = "Reviewing pull request changes...",                LastModified = DateTime.Now.AddDays(-30) },
                new() { Title = "Archived chat example",          Preview = "Old conversation from last month...",              LastModified = DateTime.Now.AddDays(-60), IsArchived = true }
            };

            GroupedChats = new ObservableCollection<ChatItem>(AllChats);
        }

        private void FilterChats()
        {
            var filtered = AllChats.AsEnumerable();

            if (!string.IsNullOrWhiteSpace(SearchText))
            {
                var search = SearchText.ToLower();
                filtered = filtered.Where(c =>
                    c.Title.ToLower().Contains(search) ||
                    c.Preview.ToLower().Contains(search));
            }

            GroupedChats = new ObservableCollection<ChatItem>(filtered.OrderByDescending(c => c.LastModified));
        }

        [RelayCommand]
        private void ArchiveChat(ChatItem chat)
        {
            if (chat != null)
            {
                chat.IsArchived = true;
                FilterChats();
            }
        }

        [RelayCommand]
        private void DeleteChat(ChatItem chat)
        {
            if (chat != null)
            {
                AllChats.Remove(chat);
                FilterChats();
            }
        }

        [RelayCommand]
        private void RestoreChat(ChatItem chat)
        {
            if (chat != null)
            {
                chat.IsArchived = false;
                FilterChats();
            }
        }
    }
}
