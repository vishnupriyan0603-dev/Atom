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

  // ── Phase 32 — Sandboxed Plugin Marketplace API Methods ───────────────────

  /// GET /api/v1/marketplace/plugins — Fetch plugin catalog and installed state.
  Future<Map<String, dynamic>> fetchMarketplacePlugins({String category = 'all'}) async {
    try {
      final uri = Uri.parse('$baseUrl/marketplace/plugins?category=$category');
      final response = await http.get(uri);
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/marketplace/install — Install a verified plugin.
  Future<Map<String, dynamic>> installPlugin(String pluginId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/marketplace/install'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'id': pluginId}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/marketplace/uninstall — Uninstall plugin.
  Future<Map<String, dynamic>> uninstallPlugin(String pluginId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/marketplace/uninstall'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'id': pluginId}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/marketplace/execute — Execute sandboxed plugin capability.
  Future<Map<String, dynamic>> executeSandboxedPlugin(String method, {Map<String, dynamic>? params}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/marketplace/execute'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'method': method, 'params': params ?? {}}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 33 — Federated Zero-Knowledge Vault API Methods ──────────────────

  /// POST /api/v1/vault/unlock — Authenticate passphrase and get session token.
  Future<Map<String, dynamic>> unlockVault(String passphrase) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vault/unlock'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'passphrase': passphrase}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/vault/store — Encrypt and store record with Merkle tree logging.
  Future<Map<String, dynamic>> storeVaultRecord(String key, String value, {String? passphrase, String? token}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vault/store'),
        headers: {
          'Content-Type': 'application/json',
          if (token != null) 'X-Vault-Token': token,
        },
        body: jsonEncode({'key': key, 'value': value, 'passphrase': passphrase}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/vault/retrieve — Decrypt and retrieve vault record.
  Future<Map<String, dynamic>> retrieveVaultRecord(String key, {String? passphrase, String? token}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vault/retrieve'),
        headers: {
          'Content-Type': 'application/json',
          if (token != null) 'X-Vault-Token': token,
        },
        body: jsonEncode({'key': key, 'passphrase': passphrase}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/vault/sync-deltas — Push/pull encrypted differential sync deltas.
  Future<Map<String, dynamic>> syncVaultDeltas({int sinceClock = 0, List<Map<String, dynamic>>? deltas}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/vault/sync-deltas'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'since_clock': sinceClock, 'deltas': deltas ?? []}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 34 — Real-Time Voice Duplex API Methods ─────────────────────────

  /// POST /api/v1/voice/duplex/start — Initialize streaming session.
  Future<Map<String, dynamic>> startVoiceDuplexSession() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/voice/duplex/start'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/voice/duplex/chunk — Stream audio chunk with VAD.
  Future<Map<String, dynamic>> sendVoiceChunk(int sequence, String base64Payload, {String? text, bool vadActive = true}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/voice/duplex/chunk'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'type': 'CHUNK',
          'sequence': sequence,
          'payload': base64Payload,
          'text': text ?? '',
          'vad_active': vadActive,
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/voice/duplex/interrupt — Trigger barge-in speech cut-off.
  Future<Map<String, dynamic>> interruptVoiceSpeech() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/voice/duplex/interrupt'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/voice/duplex/emotion — Classify speaker emotional tone.
  Future<Map<String, dynamic>> analyzeVoiceEmotion(Map<String, dynamic> acousticFeatures) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/voice/duplex/emotion'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(acousticFeatures),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 35 — Code Refactoring & Micro-Architecture API Methods ──────────

  /// POST /api/v1/refactor/smells — Scan source code and detect code smells.
  Future<Map<String, dynamic>> scanCodeSmells(String code) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/refactor/smells'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'code': code}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/refactor/transform — Apply automated AST transformation.
  Future<Map<String, dynamic>> applyRefactorTransformation(String type, String code, {Map<String, dynamic>? options}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/refactor/transform'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'type': type, 'code': code, 'options': options ?? {}}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/refactor/dependencies — Compute dependency graph coupling and cycles.
  Future<Map<String, dynamic>> analyzeDependencies(Map<String, dynamic> graph) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/refactor/dependencies'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'graph': graph}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/refactor/verify — Verify syntactic and semantic refactoring safety.
  Future<Map<String, dynamic>> verifyRefactorSafety(String original, String refactored) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/refactor/verify'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'original': original, 'refactored': refactored}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 36 — Enterprise Multi-Tenant RBAC & ABAC API Methods ────────────

  /// POST /api/v1/rbac/tenant/create — Provision tenant workspace.
  Future<Map<String, dynamic>> createTenantWorkspace(String tenantId, String name, {Map<String, dynamic>? quotas}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/rbac/tenant/create'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'tenant_id': tenantId, 'name': name, 'quotas': quotas ?? {}}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/rbac/check — Check RBAC/ABAC authorization.
  Future<Map<String, dynamic>> checkRbacPermission(String role, String permission, {Map<String, dynamic>? subject, Map<String, dynamic>? resource}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/rbac/check'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'role': role,
          'permission': permission,
          'subject': subject ?? {'role': role, 'mfa_enabled': true},
          'resource': resource ?? {'classification': 'INTERNAL'},
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/rbac/token/generate — Issue scoped HMAC token.
  Future<Map<String, dynamic>> generateScopedApiToken(String userId, String tenantId, List<String> scopes, {int ttl = 3600}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/rbac/token/generate'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'tenant_id': tenantId,
          'scopes': scopes,
          'ttl': ttl,
        }),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/rbac/token/revoke — Revoke active token.
  Future<Map<String, dynamic>> revokeScopedApiToken(String tokenId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/rbac/token/revoke'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'token_id': tokenId}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/rbac/matrix — Get full RBAC capability matrix.
  Future<Map<String, dynamic>> getRbacMatrix() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/rbac/matrix'),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 37 — WebRTC P2P Direct Mesh API Methods ─────────────────────────

  /// POST /api/v1/webrtc/peer/register — Register P2P peer.
  Future<Map<String, dynamic>> registerWebRtcPeer(String peerId, {String deviceType = 'mobile'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/webrtc/peer/register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'peer_id': peerId, 'device_type': deviceType}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/webrtc/sdp/offer — Post SDP Offer.
  Future<Map<String, dynamic>> sendSdpOffer(String fromPeer, String toPeer, String sdp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/webrtc/sdp/offer'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'from_peer': fromPeer, 'to_peer': toPeer, 'sdp': sdp}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/webrtc/sdp/answer — Complete SDP Answer handshake.
  Future<Map<String, dynamic>> sendSdpAnswer(String sessionId, String sdp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/webrtc/sdp/answer'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'session_id': sessionId, 'sdp': sdp}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/webrtc/gossip/sync — P2P Gossip state convergence.
  Future<Map<String, dynamic>> syncGossipState(Map<String, dynamic> digest, {Map<String, dynamic>? deltas}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/webrtc/gossip/sync'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'digest': digest, 'deltas': deltas ?? {}}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/webrtc/topology — Get active mesh topology.
  Future<Map<String, dynamic>> getWebRtcTopology() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/webrtc/topology'),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 38 — Time-Series Predictive Forecasting Brain API Methods ────────

  /// POST /api/v1/predictive/forecast — Run Holt-Winters time-series forecast.
  Future<Map<String, dynamic>> getPredictiveForecast(List<dynamic> series, {int horizon = 5}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/predictive/forecast'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'series': series, 'horizon': horizon}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/predictive/anomalies — Detect statistical Z-score anomalies.
  Future<Map<String, dynamic>> detectPredictiveAnomalies(List<dynamic> series) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/predictive/anomalies'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'series': series}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/predictive/saturation — Estimate resource saturation & TTE.
  Future<Map<String, dynamic>> predictResourceSaturation(List<dynamic> history, {double limit = 95.0}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/predictive/saturation'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'history': history, 'limit': limit}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 39 — Semantic Code Search & Vector Embedding API Methods ─────────

  /// POST /api/v1/search/query — Semantic natural language / code search.
  Future<Map<String, dynamic>> querySemanticCode(String query, {int topK = 5}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/search/query'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'query': query, 'top_k': topK}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/search/index — Index a source code snippet into vector store.
  Future<Map<String, dynamic>> indexSemanticCodeChunk(String code, {String file = 'src/Custom.php'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/search/index'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'code': code, 'file': file}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/search/stats — Get vector index statistics.
  Future<Map<String, dynamic>> getSemanticSearchStats() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/search/stats'),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  // ── Phase 40 — Self-Healing Infrastructure & Incident Response Methods ─────

  /// POST /api/v1/incident/classify — Classify runtime error / outage.
  Future<Map<String, dynamic>> classifyIncidentEvent(String message, {double errorRate = 0.0, double latencyMs = 0.0, String subsystem = 'core'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/incident/classify'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'message': message, 'error_rate': errorRate, 'latency_ms': latencyMs, 'subsystem': subsystem}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// POST /api/v1/incident/remediate — Execute self-healing runbook.
  Future<Map<String, dynamic>> executeRemediationRunbook(String runbook, {String subsystem = 'core'}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/incident/remediate'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'runbook': runbook, 'subsystem': subsystem}),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }

  /// GET /api/v1/incident/status — Get incident response status overview.
  Future<Map<String, dynamic>> getIncidentResponseStatus() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/incident/status'),
        headers: {'Content-Type': 'application/json'},
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
    } catch (_) {}
    return {'success': false};
  }
}












