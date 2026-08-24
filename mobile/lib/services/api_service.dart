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

  // ── Phase 26 — Developer IDE Protocol (LSP) API Methods ───────────────────

  /// GET /api/v1/lsp/capabilities — Retrieve language server capabilities.
  Future<Map<String, dynamic>> fetchLspCapabilities() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/lsp/capabilities'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/lsp/complete — Request code completions.
  Future<Map<String, dynamic>> requestCodeCompletion(String prefix, {String fileName = 'code.php'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/lsp/complete'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'prefix': prefix, 'file_name': fileName}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 27 — Desktop Automation & OS Sidecar API Methods ────────────────

  /// GET /api/v1/desktop/status — Retrieve live OS sidecar and active window state.
  Future<Map<String, dynamic>> fetchDesktopStatus() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/desktop/status'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/desktop/clipboard/analyze — Analyze clipboard buffer text.
  Future<Map<String, dynamic>> analyzeClipboardContent(String content) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/desktop/clipboard/analyze'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'content': content}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 28 — Real-Time WebSocket & Sync API Methods ─────────────────────

  /// GET /api/v1/sync/peers — Retrieve active connected peer devices.
  Future<Map<String, dynamic>> fetchSyncPeers() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/sync/peers'));
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/sync/register — Register mobile client peer.
  Future<Map<String, dynamic>> registerSyncPeer(String deviceId, String deviceName) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/sync/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'device_id': deviceId,
          'client_type': 'mobile_flutter',
          'device_name': deviceName,
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/sync/push — Push state delta to sync hub.
  Future<Map<String, dynamic>> pushSyncDelta(String entityType, String entityId, Map<String, dynamic> payload) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/sync/push'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'entity_type': entityType,
          'entity_id': entityId,
          'payload': payload,
          'device_id': 'mobile_flutter',
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 29 — Autonomous Testing & CI/CD Pipeline API Methods ────────────

  /// POST /api/v1/cicd/test/generate — Synthesize automated PHPUnit test suite.
  Future<Map<String, dynamic>> generateUnitTests(String code, {String className = 'Component'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/cicd/test/generate'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'code': code, 'class_name': className}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/cicd/pipeline/trigger — Trigger multi-stage CI/CD pipeline pass.
  Future<Map<String, dynamic>> triggerCiPipeline() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/cicd/pipeline/trigger'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'stages': ['lint', 'unit_tests', 'security_scan', 'coverage_check', 'build_check']}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 30 — Long-Horizon Planning & Graph-of-Thought API Methods ───────

  /// POST /api/v1/planning/decompose — Decompose high-level goal into hierarchical DAG.
  Future<Map<String, dynamic>> decomposeGoal(String goal, {int maxDepth = 3}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/planning/decompose'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'goal': goal, 'max_depth': maxDepth}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/planning/search — Multi-branch Graph-of-Thought search.
  Future<Map<String, dynamic>> searchPlanTree(String goal, {int branchingFactor = 3, int maxDepth = 3}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/planning/search'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'goal': goal, 'branching_factor': branchingFactor, 'max_depth': maxDepth}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/planning/execute-step — Execute individual plan step with verification.
  Future<Map<String, dynamic>> executePlanStep(String treeId, String nodeId, {dynamic output}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/planning/execute-step'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'tree_id': treeId, 'node_id': nodeId, 'output': output}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/planning/rollback — Backtrack failed node and select alternate branch.
  Future<Map<String, dynamic>> rollbackPlan(String treeId, String nodeId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/planning/rollback'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'tree_id': treeId, 'node_id': nodeId}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 31 — Mathematical & Algorithmic Computation API Methods ─────────

  /// POST /api/v1/compute/solve — Solve algebraic equation with step-by-step derivation.
  Future<Map<String, dynamic>> solveEquation(String equation) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/compute/solve'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'equation': equation}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/compute/matrix — Compute matrix operations (invert, determinant, multiply).
  Future<Map<String, dynamic>> computeMatrix(String operation, dynamic matrixA, {dynamic matrixB}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/compute/matrix'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'operation': operation, 'matrix_a': matrixA, 'matrix_b': matrixB}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/compute/statistics — Compute descriptive statistics and linear regression.
  Future<Map<String, dynamic>> computeStatistics(List<double> data, {String mode = 'describe', List<double>? dataY}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/compute/statistics'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'mode': mode, 'data': data, 'data_y': dataY}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/compute/complexity — Analyze source code Big-O time and space complexity.
  Future<Map<String, dynamic>> analyzeComplexity(String code) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/compute/complexity'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'code': code}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }
}



