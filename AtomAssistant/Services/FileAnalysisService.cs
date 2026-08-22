using Markdig;

namespace AtomAssistant.Services
{
    public class FileAnalysisService
    {
        public async Task<string> AnalyzePdf(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("PDF file not found", filePath);

            using var doc = UglyToad.PdfPig.PdfDocument.Open(filePath);
            var text = new System.Text.StringBuilder();

            foreach (var page in doc.GetPages())
            {
                text.AppendLine(page.Text);
            }

            return await Task.FromResult(text.ToString());
        }

        public async Task<string> AnalyzeWord(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("Word file not found", filePath);

            using var stream = File.OpenRead(filePath);
            using var reader = new DocumentFormat.OpenXml.Wordprocessing.WordprocessingDocument(stream, false);

            var text = new System.Text.StringBuilder();
            var body = reader.MainDocumentPart?.Document.Body;

            if (body != null)
            {
                foreach (var para in body.Elements<DocumentFormat.OpenXml.Wordprocessing.Paragraph>())
                {
                    text.AppendLine(para.InnerText);
                }
            }

            return await Task.FromResult(text.ToString());
        }

        public async Task<string> AnalyzeExcel(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("Excel file not found", filePath);

            using var stream = File.OpenRead(filePath);
            using var reader = new DocumentFormat.OpenXml.Spreadsheet.SpreadsheetDocument(stream, false);

            var text = new System.Text.StringBuilder();
            var workbookPart = reader.WorkbookPart;

            if (workbookPart == null) return "";

            foreach (var sheet in workbookPart.Workbook.Descendants<DocumentFormat.OpenXml.Spreadsheet.Sheet>())
            {
                text.AppendLine($"=== Sheet: {sheet.Name} ===");
                var worksheetPart = workbookPart.GetPartById(sheet.Id) as DocumentFormat.OpenXml.Spreadsheet.WorksheetPart;
                if (worksheetPart == null) continue;

                var rows = worksheetPart.Worksheet.Descendants<DocumentFormat.OpenXml.Spreadsheet.Row>();
                foreach (var row in rows)
                {
                    var cells = row.Descendants<DocumentFormat.OpenXml.Spreadsheet.Cell>()
                        .Select(c => GetCellValue(c, workbookPart.SharedStringTablePart));
                    text.AppendLine(string.Join("\t", cells));
                }
            }

            return await Task.FromResult(text.ToString());
        }

        public async Task<string> AnalyzeCsv(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("CSV file not found", filePath);

            var lines = await File.ReadAllLinesAsync(filePath);
            var text = new System.Text.StringBuilder();

            foreach (var line in lines)
            {
                text.AppendLine(line);
            }

            return text.ToString();
        }

        public async Task<string> AnalyzeImage(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("Image file not found", filePath);

            var info = new FileInfo(filePath);
            var text = new System.Text.StringBuilder();

            text.AppendLine($"File: {info.Name}");
            text.AppendLine($"Size: {info.Length} bytes");
            text.AppendLine($"Extension: {info.Extension}");

            using var fs = new FileStream(filePath, FileMode.Open, FileAccess.Read);
            using var img = System.Drawing.Image.FromStream(fs);

            text.AppendLine($"Width: {img.Width}px");
            text.AppendLine($"Height: {img.Height}px");
            text.AppendLine($"Horizontal Resolution: {img.HorizontalResolution}dpi");
            text.AppendLine($"Vertical Resolution: {img.VerticalResolution}dpi");
            text.AppendLine($"Pixel Format: {img.PixelFormat}");

            return await Task.FromResult(text.ToString());
        }

        public async Task<string> AnalyzeCode(string filePath)
        {
            if (!File.Exists(filePath))
                throw new FileNotFoundException("Code file not found", filePath);

            var extension = Path.GetExtension(filePath)?.ToLowerInvariant();
            var content = await File.ReadAllTextAsync(filePath);

            var language = extension switch
            {
                ".cs" => "csharp",
                ".vb" => "vbnet",
                ".fs" => "fsharp",
                ".py" => "python",
                ".js" => "javascript",
                ".ts" => "typescript",
                ".tsx" => "tsx",
                ".jsx" => "jsx",
                ".html" => "html",
                ".css" => "css",
                ".scss" => "scss",
                ".sql" => "sql",
                ".json" => "json",
                ".xml" => "xml",
                ".yaml" or ".yml" => "yaml",
                ".md" => "markdown",
                ".java" => "java",
                ".cpp" or ".cc" or ".cxx" => "cpp",
                ".c" => "c",
                ".h" => "c",
                ".hpp" => "cpp",
                ".go" => "go",
                ".rs" => "rust",
                ".rb" => "ruby",
                ".php" => "php",
                ".swift" => "swift",
                ".kt" or ".kts" => "kotlin",
                ".dart" => "dart",
                ".sh" => "bash",
                ".ps1" => "powershell",
                _ => "text"
            };

            var pipeline = new MarkdownPipelineBuilder().Build();
            var codeBlock = $"```{language}\n{content}\n```";
            var markdown = $"# File: {Path.GetFileName(filePath)}\n\n**Language:** {language}\n\n**Path:** {filePath}\n\n**Lines:** {content.Split('\n').Length}\n\n## Contents\n\n{codeBlock}";
            var html = Markdown.ToHtml(markdown, pipeline);

            return markdown;
        }

        private static string GetCellValue(DocumentFormat.OpenXml.Spreadsheet.Cell cell, DocumentFormat.OpenXml.Spreadsheet.SharedStringTablePart? sharedStringTable)
        {
            if (cell.CellValue == null) return "";

            var value = cell.CellValue.Text;

            if (cell.DataType != null && cell.DataType.Value == DocumentFormat.OpenXml.Spreadsheet.CellValues.SharedString && sharedStringTable != null)
            {
                if (int.TryParse(value, out var index) && index < sharedStringTable.SharedStringTable.Elements<DocumentFormat.OpenXml.Spreadsheet.SharedStringItem>().Count())
                {
                    value = sharedStringTable.SharedStringTable.Elements<DocumentFormat.OpenXml.Spreadsheet.SharedStringItem>().ElementAt(index).Text.Text;
                }
            }

            return value ?? "";
        }
    }
}
