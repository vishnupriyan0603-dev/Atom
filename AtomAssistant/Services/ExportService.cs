using System.Text;
using System.Text.Json;
using AtomAssistant.Models;

namespace AtomAssistant.Services
{
    public class ExportService
    {
        public async Task<string> ExportAsJson(Chat chat, List<Message> messages)
        {
            var export = new
            {
                chat = new
                {
                    chat.Id,
                    chat.Title,
                    chat.Model,
                    chat.CreatedAt,
                    chat.UpdatedAt
                },
                messages = messages.Select(m => new
                {
                    m.Id,
                    m.Role,
                    m.Content,
                    m.CreatedAt,
                    m.TokensIn,
                    m.TokensOut,
                    m.Model
                })
            };

            var json = JsonSerializer.Serialize(export, new JsonSerializerOptions
            {
                WriteIndented = true
            });

            return await Task.FromResult(json);
        }

        public async Task<string> ExportAsText(Chat chat, List<Message> messages)
        {
            var sb = new StringBuilder();

            sb.AppendLine($"Title: {chat.Title}");
            sb.AppendLine($"Model: {chat.Model}");
            sb.AppendLine($"Created: {chat.CreatedAt:yyyy-MM-dd HH:mm:ss}");
            sb.AppendLine($"Exported: {DateTime.Now:yyyy-MM-dd HH:mm:ss}");
            sb.AppendLine(new string('-', 60));
            sb.AppendLine();

            foreach (var msg in messages)
            {
                var role = msg.Role switch
                {
                    "user" => "You",
                    "assistant" => "Assistant",
                    "system" => "System",
                    _ => msg.Role
                };

                sb.AppendLine($"[{msg.CreatedAt:yyyy-MM-dd HH:mm:ss}] {role}:");
                sb.AppendLine(msg.Content);
                sb.AppendLine();
            }

            return await Task.FromResult(sb.ToString());
        }

        public async Task<string> ExportAsMarkdown(Chat chat, List<Message> messages)
        {
            var sb = new StringBuilder();

            sb.AppendLine($"# {chat.Title}");
            sb.AppendLine();
            sb.AppendLine($"- **Model:** {chat.Model}");
            sb.AppendLine($"- **Created:** {chat.CreatedAt:yyyy-MM-dd HH:mm:ss}");
            sb.AppendLine($"- **Exported:** {DateTime.Now:yyyy-MM-dd HH:mm:ss}");
            sb.AppendLine();
            sb.AppendLine("---");
            sb.AppendLine();

            foreach (var msg in messages)
            {
                var role = msg.Role switch
                {
                    "user" => "👤 User",
                    "assistant" => "🤖 Assistant",
                    "system" => "⚙️ System",
                    _ => msg.Role
                };

                sb.AppendLine($"### {role} — {msg.CreatedAt:yyyy-MM-dd HH:mm:ss}");
                sb.AppendLine();

                var content = msg.Content;
                if (IsProbablyMarkdown(content))
                {
                    sb.AppendLine(content);
                }
                else
                {
                    var escaped = content.Replace("_", "\\_");
                    sb.AppendLine(escaped);
                }

                sb.AppendLine();
            }

            return await Task.FromResult(sb.ToString());
        }

        public async Task<byte[]> ExportAsPdf(Chat chat, List<Message> messages)
        {
            var markdown = await ExportAsMarkdown(chat, messages);

            var html = $@"<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<style>
  body {{ font-family: 'Segoe UI', Arial, sans-serif; margin: 40px; }}
  h1 {{ color: #333; }}
  .message {{ margin-bottom: 20px; padding: 10px; border-left: 3px solid #0078D4; }}
  .message.user {{ background: #f0f0f0; }}
  .message.assistant {{ background: #f8f8ff; }}
  .role {{ font-weight: bold; color: #0078D4; }}
  .time {{ color: #888; font-size: 0.8em; }}
</style>
</head>
<body>
<h1>{System.Net.WebUtility.HtmlEncode(chat.Title)}</h1>
<p>Model: {System.Net.WebUtility.HtmlEncode(chat.Model)} | Created: {chat.CreatedAt:yyyy-MM-dd HH:mm:ss}</p>
<hr>";

            foreach (var msg in messages)
            {
                var roleClass = msg.Role == "user" ? "user" : "assistant";
                var roleLabel = msg.Role switch
                {
                    "user" => "User",
                    "assistant" => "Assistant",
                    "system" => "System",
                    _ => msg.Role
                };

                html += $@"
<div class='message {roleClass}'>
  <div class='role'>{System.Net.WebUtility.HtmlEncode(roleLabel)}</div>
  <div class='time'>{msg.CreatedAt:yyyy-MM-dd HH:mm:ss}</div>
  <p>{System.Net.WebUtility.HtmlEncode(msg.Content).Replace("\n", "<br>")}</p>
</div>";
            }

            html += "\n</body>\n</html>";

            using var ms = new MemoryStream();
            var renderer = new ChromePdf.Renderer();
            var pdf = renderer.RenderHtmlAsPdf(html);
            return pdf.BinaryData;
        }

        public async Task SaveToFile(string filePath, string content)
        {
            var directory = Path.GetDirectoryName(filePath);
            if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
                Directory.CreateDirectory(directory);

            await File.WriteAllTextAsync(filePath, content);
        }

        public async Task SaveToFile(string filePath, byte[] content)
        {
            var directory = Path.GetDirectoryName(filePath);
            if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
                Directory.CreateDirectory(directory);

            await File.WriteAllBytesAsync(filePath, content);
        }

        private static bool IsProbablyMarkdown(string content)
        {
            if (content.Contains("```")) return true;
            if (content.Contains("**")) return true;
            if (content.Contains("##")) return true;
            if (content.Contains("[") && content.Contains("](")) return true;
            if (content.StartsWith("#")) return true;
            return false;
        }
    }
}
