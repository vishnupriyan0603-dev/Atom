using System;
using System.Collections.Generic;
using System.Collections.ObjectModel;
using System.Linq;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using AtomAssistant.Models;

namespace AtomAssistant.ViewModels.Pages
{
    public partial class PromptsPageViewModel : ObservableObject
    {
        [ObservableProperty]
        private ObservableCollection<PromptItem> prompts = new();

        [ObservableProperty]
        private ObservableCollection<PromptItem> filteredPrompts = new();

        [ObservableProperty]
        private ObservableCollection<string> categories = new()
        {
            "All", "Coding", "PHP", "Laravel", "CodeIgniter", "React",
            "HTML", "CSS", "SQL", "Linux", "Business", "Marketing", "Personal"
        };

        [ObservableProperty]
        private string selectedCategory = "All";

        [ObservableProperty]
        private string searchText = string.Empty;

        [ObservableProperty]
        private PromptItem? selectedPrompt;

        public PromptsPageViewModel()
        {
            LoadSamplePrompts();
            FilterPrompts();

            SelectedCategory = "All";
        }

        partial void OnSelectedCategoryChanged(string value) => FilterPrompts();
        partial void OnSearchTextChanged(string value) => FilterPrompts();

        private void LoadSamplePrompts()
        {
            Prompts = new ObservableCollection<PromptItem>
            {
                new() { Title = "Code Review Checklist", Content = "Review code for: 1) Correctness 2) Performance 3) Security 4) Readability", Category = "Coding", IsFavorite = true },
                new() { Title = "Laravel Artisan Commands", Content = "List common Artisan commands for daily development workflow", Category = "Laravel", IsFavorite = false },
                new() { Title = "SQL Query Optimizer", Content = "Optimize a slow-running SQL query by analyzing the execution plan", Category = "SQL", IsFavorite = true },
                new() { Title = "React Component Template", Content = "Generate a functional React component with TypeScript and tests", Category = "React", IsFavorite = false },
                new() { Title = "PHP Unit Test Template", Content = "Create a PHPUnit test class with setUp, tearDown and test methods", Category = "PHP", IsFavorite = true },
                new() { Title = "HTML Email Template", Content = "Responsive HTML email with inline CSS for email clients", Category = "HTML", IsFavorite = false }
            };

            FilteredPrompts = new ObservableCollection<PromptItem>(Prompts);
        }

        private void FilterPrompts()
        {
            var filtered = Prompts.AsEnumerable();

            if (!string.IsNullOrWhiteSpace(SelectedCategory) && SelectedCategory != "All")
            {
                filtered = filtered.Where(p => p.Category == SelectedCategory);
            }

            if (!string.IsNullOrWhiteSpace(SearchText))
            {
                var search = SearchText.ToLower();
                filtered = filtered.Where(p =>
                    p.Title.ToLower().Contains(search) ||
                    p.Content.ToLower().Contains(search));
            }

            FilteredPrompts = new ObservableCollection<PromptItem>(filtered);
        }

        [RelayCommand]
        private void AddPrompt()
        {
            var newPrompt = new PromptItem
            {
                Title = "New Prompt",
                Content = "Enter your prompt content here...",
                Category = SelectedCategory != "All" ? SelectedCategory : "Coding",
                DateCreated = DateTime.Now
            };
            Prompts.Add(newPrompt);
            FilterPrompts();
        }

        [RelayCommand]
        private void EditPrompt(PromptItem prompt)
        {
            if (prompt != null)
            {
                // Open edit dialog logic
            }
        }

        [RelayCommand]
        private void DeletePrompt(PromptItem prompt)
        {
            if (prompt != null)
            {
                Prompts.Remove(prompt);
                FilterPrompts();
            }
        }

        [RelayCommand]
        private void CopyPrompt(PromptItem prompt)
        {
            if (prompt != null)
            {
                System.Windows.Clipboard.SetText(prompt.Content);
            }
        }

        [RelayCommand]
        private async Task Import()
        {
            // File picker and JSON import logic
            await Task.CompletedTask;
        }

        [RelayCommand]
        private async Task Export()
        {
            // Save file dialog and JSON export logic
            await Task.CompletedTask;
        }
    }
}
