using AtomAssistant.Models;
using AtomAssistant.Repositories;
using Microsoft.Extensions.Logging;

namespace AtomAssistant.Services
{
    public class KnowledgeService
    {
        private readonly IKnowledgeRepository _knowledgeRepository;
        private readonly ILogger<KnowledgeService> _logger;

        public KnowledgeService(IKnowledgeRepository knowledgeRepository, ILogger<KnowledgeService> logger)
        {
            _knowledgeRepository = knowledgeRepository;
            _logger = logger;
        }

        public async Task<KnowledgeItem> AddDocument(string title, string content, string collection, string? filePath = null, string? fileType = null)
        {
            var item = new KnowledgeItem
            {
                Title = title,
                Content = content,
                FilePath = filePath ?? "",
                FileType = fileType ?? "",
                Collection = collection,
                Embedding = ComputeEmbedding(content),
                CreatedAt = DateTime.UtcNow
            };

            await _knowledgeRepository.AddAsync(item);
            _logger.LogInformation("Added document '{Title}' to collection '{Collection}'", title, collection);
            return item;
        }

        public async Task<List<KnowledgeItem>> SearchSimilar(string query, string? collection = null, int topK = 10)
        {
            var queryEmbedding = ComputeEmbedding(query);
            var allItems = collection != null
                ? await _knowledgeRepository.GetByCollectionAsync(collection)
                : await _knowledgeRepository.GetAllAsync();

            var scored = new List<(KnowledgeItem Item, double Score)>();

            foreach (var item in allItems)
            {
                if (item.Embedding == null || item.Embedding.Length == 0) continue;

                var itemEmbedding = ByteArrayToFloatArray(item.Embedding);
                var similarity = CosineSimilarity(queryEmbedding, itemEmbedding);
                scored.Add((item, similarity));
            }

            return scored
                .OrderByDescending(s => s.Score)
                .Take(topK)
                .Select(s => s.Item)
                .ToList();
        }

        public async Task DeleteDocument(int documentId)
        {
            await _knowledgeRepository.DeleteAsync(documentId);
            _logger.LogInformation("Deleted knowledge document {DocumentId}", documentId);
        }

        public async Task<List<string>> GetCollections()
        {
            var items = await _knowledgeRepository.GetAllAsync();
            return items
                .Select(i => i.Collection)
                .Where(c => !string.IsNullOrEmpty(c))
                .Distinct()
                .OrderBy(c => c)
                .ToList();
        }

        public async Task<List<KnowledgeItem>> GetDocumentsByCollection(string collection)
        {
            return await _knowledgeRepository.GetByCollectionAsync(collection);
        }

        public async Task<KnowledgeItem?> GetDocument(int documentId)
        {
            return await _knowledgeRepository.GetByIdAsync(documentId);
        }

        public async Task<List<KnowledgeItem>> GetAllDocuments()
        {
            return await _knowledgeRepository.GetAllAsync();
        }

        private static float[] ComputeEmbedding(string text)
        {
            var tokens = text.ToLowerInvariant()
                .Split(new[] { ' ', '\n', '\r', '\t', '.', ',', '!', '?', ';', ':', '(', ')', '[', ']', '{', '}', '"', '\'', '-', '_', '/', '\\', '@', '#', '$', '%', '^', '&', '*', '+', '=', '<', '>', '~', '`', '|' }, StringSplitOptions.RemoveEmptyEntries);

            var tokenSet = tokens.Distinct().ToList();
            var embedding = new float[256];

            for (int i = 0; i < 256 && i < tokenSet.Count; i++)
            {
                var hash = (uint)tokenSet[i].GetHashCode();
                embedding[i] = (hash % 10000) / 10000.0f;
            }

            var tf = new Dictionary<string, int>();
            foreach (var token in tokens)
            {
                if (tf.ContainsKey(token))
                    tf[token]++;
                else
                    tf[token] = 1;
            }

            var docLength = tokens.Length;
            for (int i = 0; i < 256 && i < tokenSet.Count; i++)
            {
                var token = tokenSet[i];
                embedding[i] *= (float)tf[token] / docLength;
            }

            var norm = (float)Math.Sqrt(embedding.Sum(v => v * v));
            if (norm > 0)
            {
                for (int i = 0; i < embedding.Length; i++)
                    embedding[i] /= norm;
            }

            return embedding;
        }

        private static float[] ByteArrayToFloatArray(byte[] bytes)
        {
            var floats = new float[bytes.Length / 4];
            for (int i = 0; i < floats.Length; i++)
            {
                floats[i] = BitConverter.ToSingle(bytes, i * 4);
            }
            return floats;
        }

        private static byte[] FloatArrayToByteArray(float[] floats)
        {
            var bytes = new byte[floats.Length * 4];
            for (int i = 0; i < floats.Length; i++)
            {
                var floatBytes = BitConverter.GetBytes(floats[i]);
                Array.Copy(floatBytes, 0, bytes, i * 4, 4);
            }
            return bytes;
        }

        private static double CosineSimilarity(float[] a, float[] b)
        {
            if (a.Length != b.Length) return 0;

            double dotProduct = 0;
            double normA = 0;
            double normB = 0;

            for (int i = 0; i < a.Length; i++)
            {
                dotProduct += a[i] * b[i];
                normA += a[i] * a[i];
                normB += b[i] * b[i];
            }

            var denominator = Math.Sqrt(normA) * Math.Sqrt(normB);
            return denominator == 0 ? 0 : dotProduct / denominator;
        }
    }
}
