using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.Extensions.Configuration;

namespace AtomAssistant.Services
{
    public class BackendService
    {
        private readonly HttpClient _httpClient;
        private string _token;
        private string _baseUrl;

        public bool IsConnected => !string.IsNullOrEmpty(_token);
        public string BaseUrl => _baseUrl;
        public string Email { get; private set; }

        public BackendService(IConfiguration configuration)
        {
            _httpClient = new HttpClient();
            _baseUrl = configuration["Backend:Url"] ?? "http://localhost:8080";
            _token = configuration["Backend:Token"] ?? string.Empty;
            Email = configuration["Backend:Email"] ?? string.Empty;

            _httpClient.BaseAddress = new Uri(_baseUrl);
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));

            if (!string.IsNullOrEmpty(_token))
            {
                _httpClient.DefaultRequestHeaders.Authorization =
                    new AuthenticationHeaderValue("Bearer", _token);
            }
        }

        public async Task<(bool success, string error)> RegisterAsync(string email, string password, string name = "")
        {
            var body = new { email, password, name };
            var response = await PostAsync("/api/auth/register", body);
            if (response.success)
            {
                var data = response.data.GetProperty("token").GetString();
                _token = data;
                Email = email;
                _httpClient.DefaultRequestHeaders.Authorization =
                    new AuthenticationHeaderValue("Bearer", _token);
            }
            return (response.success, response.error);
        }

        public async Task<(bool success, string error)> LoginAsync(string email, string password)
        {
            var body = new { email, password };
            var response = await PostAsync("/api/auth/login", body);
            if (response.success)
            {
                var data = response.data.GetProperty("token").GetString();
                _token = data;
                Email = email;
                _httpClient.DefaultRequestHeaders.Authorization =
                    new AuthenticationHeaderValue("Bearer", _token);
            }
            return (response.success, response.error);
        }

        public async Task<(bool success, string error, JsonElement data)> GetAsync(string endpoint)
        {
            try
            {
                var response = await _httpClient.GetAsync(endpoint);
                var json = await response.Content.ReadAsStringAsync();
                var doc = JsonDocument.Parse(json);
                var root = doc.RootElement;

                if (response.IsSuccessStatusCode && root.GetProperty("success").GetBoolean())
                {
                    return (true, null, root.GetProperty("data"));
                }

                return (false, root.GetProperty("message").GetString(), default);
            }
            catch (Exception ex)
            {
                return (false, ex.Message, default);
            }
        }

        public async Task<(bool success, string error, JsonElement data)> PostAsync(string endpoint, object body)
        {
            try
            {
                var content = new StringContent(
                    JsonSerializer.Serialize(body),
                    Encoding.UTF8,
                    "application/json");

                var response = await _httpClient.PostAsync(endpoint, content);
                var json = await response.Content.ReadAsStringAsync();
                var doc = JsonDocument.Parse(json);
                var root = doc.RootElement;

                if (response.IsSuccessStatusCode && root.GetProperty("success").GetBoolean())
                {
                    return (true, null, root.GetProperty("data"));
                }

                return (false, root.GetProperty("message").GetString(), default);
            }
            catch (Exception ex)
            {
                return (false, ex.Message, default);
            }
        }

        public async Task<(bool success, string error, JsonElement data)> PutAsync(string endpoint, object body)
        {
            try
            {
                var content = new StringContent(
                    JsonSerializer.Serialize(body),
                    Encoding.UTF8,
                    "application/json");

                var response = await _httpClient.PutAsync(endpoint, content);
                var json = await response.Content.ReadAsStringAsync();
                var doc = JsonDocument.Parse(json);
                var root = doc.RootElement;

                if (response.IsSuccessStatusCode && root.GetProperty("success").GetBoolean())
                {
                    return (true, null, root.GetProperty("data"));
                }

                return (false, root.GetProperty("message").GetString(), default);
            }
            catch (Exception ex)
            {
                return (false, ex.Message, default);
            }
        }

        public async Task<(bool success, string error)> DeleteAsync(string endpoint)
        {
            try
            {
                var response = await _httpClient.DeleteAsync(endpoint);
                return (response.IsSuccessStatusCode, null);
            }
            catch (Exception ex)
            {
                return (false, ex.Message);
            }
        }

        public async Task<(bool success, string error)> TestConnectionAsync()
        {
            try
            {
                var response = await _httpClient.GetAsync("/api/auth/me");
                return (response.IsSuccessStatusCode, null);
            }
            catch (Exception ex)
            {
                return (false, ex.Message);
            }
        }

        public void Disconnect()
        {
            _token = null;
            Email = null;
            _httpClient.DefaultRequestHeaders.Authorization = null;
        }
    }
}
