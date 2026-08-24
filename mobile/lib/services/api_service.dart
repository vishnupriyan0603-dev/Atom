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

  Future<List<dynamic>> fetchGovernancePolicies() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/governance/policies'));
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

  // ── Phase 23 — Personal AI Brain API Methods ──────────────────────────────

  /// GET /api/v1/brain/status — Brain state, environment awareness, personality.
  Future<Map<String, dynamic>> fetchBrainStatus() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/brain/status'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/brain/context — Active context window summary.
  Future<Map<String, dynamic>> fetchBrainContext() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/brain/context'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/brain/reset-context — Reset the active conversation context.
  Future<bool> resetBrainContext() async {
    try {
      final response = await http.post(Uri.parse('$baseUrl/brain/reset-context'));
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  /// GET /api/v1/brain/intent?q=<text> — Dry-run intent classification.
  Future<Map<String, dynamic>> classifyIntent(String text) async {
    try {
      final uri = Uri.parse('$baseUrl/brain/intent').replace(queryParameters: {'q': text});
      final response = await http.get(uri);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 24 — Multi-Modal Voice & Vision API Methods ─────────────────────

  /// POST /api/v1/voice/synthesize — Synthesize text into speech instructions/audio.
  Future<Map<String, dynamic>> synthesizeVoice(String text, {String voice = 'en-IN-Standard-A'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/voice/synthesize'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'text': text, 'voice': voice}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/vision/analyze — Analyze image or screenshot base64.
  Future<Map<String, dynamic>> analyzeVision(String base64Image, {String taskType = 'general_analysis', String prompt = ''}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vision/analyze'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'image_base64': base64Image,
          'task_type': taskType,
          'prompt': prompt,
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 25 — Proactive Daemon API Methods ───────────────────────────────

  /// GET /api/v1/daemon/status — Live daemon state and pulse metrics.
  Future<Map<String, dynamic>> fetchDaemonStatus() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/daemon/status'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/daemon/briefing — Retrieve latest morning/evening briefing.
  Future<Map<String, dynamic>> fetchLatestBriefing({String type = 'morning'}) async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/daemon/briefing?type=$type'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/daemon/pulse — Trigger immediate life-cycle pulse.
  Future<bool> triggerDaemonPulse() async {
    try {
      final response = await http.post(Uri.parse('$baseUrl/daemon/pulse'));
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }
}

