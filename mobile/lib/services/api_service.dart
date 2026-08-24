import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = "http://localhost:8080/api/v1";

  Future<Map<String, dynamic>> fetchTelemetryMetrics() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/telemetry/metrics'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  Future<List<dynamic>> fetchMemories() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/memory'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<List<dynamic>> fetchPendingApprovals() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/approvals?status=pending'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<List<dynamic>> fetchAgentTasks() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/agents/tasks'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<bool> createAgentTask(String objective) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/agents/tasks'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'objective': objective}),
      );
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<List<dynamic>> fetchWorkflows() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/workflows'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<bool> executeWorkflow(int id) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/workflows/$id/execute'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'input': {'objective': 'Mobile workflow dispatch'}}),
      );
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<List<dynamic>> fetchSwarms() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/swarms'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<bool> createSwarm(String objective) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/swarms'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'objective': objective}),
      );
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<List<dynamic>> fetchEvaluationRuns() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/evaluations/runs'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<List<dynamic>> fetchRoutingCandidates() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/routing/candidates'));
      if (response.statusCode == 200) {
        final body = jsonDecode(response.body);
        return body['data'] ?? [];
      }
    } catch (_) {}
    return [];
  }

  Future<bool> approveRequest(int id) async {





    try {
      final response = await http.post(Uri.parse('$baseUrl/approvals/$id/approve'));
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  Future<bool> rejectRequest(int id) async {
    try {
      final response = await http.post(Uri.parse('$baseUrl/approvals/$id/reject'));
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }
}
