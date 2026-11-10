import 'dart:convert';
import 'dart:io';
import 'package:shelf/shelf.dart';
import 'package:shelf/shelf_io.dart' as io;
import 'package:shelf_router/shelf_router.dart';
import 'database_service.dart';
import 'pairing_service.dart';

/**
 * LocalServerService — Embedded local REST API server hosted on Samsung Galaxy M31 (Port 8765).
 * Connects ATOM on Windows to the phone's authoritative SQLite database over local Wi-Fi.
 */
class LocalServerService {
  static final LocalServerService _instance = LocalServerService._internal();
  factory LocalServerService() => _instance;
  LocalServerService._internal();

  HttpServer? _server;
  bool _isRunning = false;
  int _port = 8765;
  String _localIp = "127.0.0.1";

  bool get isRunning => _isRunning;
  int get port => _port;
  String get localIp => _localIp;

  Future<bool> startServer({int port = 8765}) async {
    if (_isRunning) return true;
    _port = port;

    try {
      _localIp = await _resolveLocalIp();

      final router = Router();
      _registerRoutes(router);

      final handler = Pipeline()
          .addMiddleware(_corsMiddleware())
          .addMiddleware(_authMiddleware())
          .addHandler(router);

      _server = await io.serve(handler, InternetAddress(_localIp), _port);
      _isRunning = true;
      return true;
    } catch (e) {
      _isRunning = false;
      return false;
    }
  }

  Future<void> stopServer() async {
    if (_server != null) {
      await _server!.close(force: true);
      _server = null;
    }
    _isRunning = false;
  }

  Future<String> _resolveLocalIp() async {
    try {
      final interfaces = await NetworkInterface.list(
        type: InternetAddressType.IPv4,
        includeLoopback: false,
      );
      for (final interface in interfaces) {
        for (final address in interface.addresses) {
          if (_isPrivateIpv4(address.address)) return address.address;
        }
      }
    } catch (_) {}
    return "127.0.0.1";
  }

  bool _isPrivateIpv4(String address) {
    final octets = address.split('.').map(int.tryParse).toList();
    if (octets.length != 4 ||
        octets.any((octet) => octet == null || octet < 0 || octet > 255)) {
      return false;
    }
    final first = octets[0]!;
    final second = octets[1]!;
    return first == 10 ||
        (first == 172 && second >= 16 && second <= 31) ||
        (first == 192 && second == 168);
  }

  void _registerRoutes(Router router) {
    // 1. Health Ping (Public)
    router.get('/api/v1/ping', (Request request) {
      return _jsonResponse({
        'status': 'ok',
        'service': 'MyLife OS Local Server',
        'device': 'Samsung Galaxy M31',
        'port': _port,
        'timestamp': DateTime.now().toIso8601String(),
      });
    });

    // 2. Initial Device Pairing Handshake (public route with required HMAC proof)
    router.post('/api/v1/pair', (Request request) async {
      try {
        final decoded = jsonDecode(await request.readAsString());
        if (decoded is! Map<String, dynamic>) {
          return _jsonResponse({'success': false, 'error': 'Invalid pairing request'}, status: 400);
        }
        final clientId = decoded['client_id'];
        final deviceName = decoded['device_name'];
        final fingerprint = decoded['client_fingerprint'];
        final timestamp = decoded['timestamp'];
        final signature = decoded['signature'];
        if (clientId is! String ||
            deviceName is! String ||
            fingerprint is! String ||
            timestamp is! int ||
            signature is! String ||
            !PairingService().verifyPairingProof(
              clientName: clientId,
              deviceName: deviceName,
              fingerprint: fingerprint,
              timestampStr: timestamp.toString(),
              signature: signature,
            )) {
          return _jsonResponse({'success': false, 'error': 'Pairing proof is invalid or expired'}, status: 401);
        }

        await PairingService().registerPairedClient(clientId, fingerprint);
        await DatabaseService().logAudit('SYSTEM', 'PAIR', clientId, 'Successfully paired client $clientId');

        return _jsonResponse({
          'success': true,
          'message': 'Successfully paired with MyLife OS on Samsung Galaxy M31',
          'device': 'Samsung Galaxy M31',
          'paired': true,
        });
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 3. System & Tracking Status (Protected)
    router.get('/api/v1/status', (Request request) {
      return _jsonResponse({
        'server': {
          'running': _isRunning,
          'port': _port,
          'local_ip': _localIp,
          'mdns': 'mylife-os.local:$_port',
        },
        'battery': {
          'level': null,
          'charging': null,
          'available': false,
        },
        'tracking': {
          'is_paused': null,
          'step_counter': null,
          'gps_geofence': null,
          'available': false,
        },
        'paired': PairingService().isPaired,
        'paired_client': PairingService().pairedClientName,
      });
    });

    // 4. Executive 14-Module Summary (Protected - Core ATOM Brain Gateway)
    router.get('/api/v1/modules/summary', (Request request) async {
      final summary = await DatabaseService().getAggregatedSummary();
      return _jsonResponse({
        'success': true,
        'device': 'Samsung Galaxy M31',
        'data': summary,
      });
    });

    // 5. Habits Endpoints (Protected)
    router.get('/api/v1/habits', (Request request) async {
      final habits = await DatabaseService().getHabits();
      return _jsonResponse({'success': true, 'data': habits});
    });

    router.post('/api/v1/habits/<id>/checkin', (Request request, String id) async {
      final habitId = int.tryParse(id);
      if (habitId == null) {
        return _jsonResponse({'success': false, 'error': 'Invalid habit ID'}, status: 400);
      }
      await DatabaseService().checkInHabit(habitId);
      await DatabaseService().logAudit('07_habits', 'CHECKIN', 'ATOM_API', 'Habit ID $habitId checked in');
      return _jsonResponse({'success': true, 'message': 'Habit checked in successfully'});
    });

    // 6. Activities & Tasks Endpoints (Protected)
    router.get('/api/v1/activities/today', (Request request) async {
      final tasks = await DatabaseService().getTasks();
      return _jsonResponse({'success': true, 'data': {'tasks': tasks}});
    });

    router.post('/api/v1/activities/tasks', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final title = body['title'] ?? 'New Task';
        final priority = body['priority'] ?? 'MEDIUM';
        final dueDate = body['due_date'];

        final taskId = await DatabaseService().insertTask(title, priority: priority, dueDate: dueDate);
        await DatabaseService().logAudit('06_activities', 'CREATE_TASK', 'ATOM_API', 'Created task: $title');
        return _jsonResponse({'success': true, 'task_id': taskId}, status: 201);
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 7. Health & Hydration Endpoints (Protected)
    router.post('/api/v1/health/water', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final amountMl = (body['amount_ml'] as num?)?.toInt() ?? 250;
        await DatabaseService().logWaterIntake(amountMl);
        await DatabaseService().logAudit('03_health', 'LOG_WATER', 'ATOM_API', 'Logged $amountMl ml water');
        return _jsonResponse({'success': true, 'logged_ml': amountMl});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 8. Finance Transactions & Summary (Protected)
    router.get('/api/v1/finance/summary', (Request request) async {
      final period = request.url.queryParameters['period'] ?? 'month';
      final summary = await DatabaseService().getFinanceSummary(period);
      return _jsonResponse({'success': true, 'data': summary});
    });

    router.get('/api/v1/finance/transactions', (Request request) async {
      final limit = int.tryParse(request.url.queryParameters['limit'] ?? '50') ?? 50;
      final category = request.url.queryParameters['category'];
      final trans = await DatabaseService().getTransactions(limit: limit, category: category);
      return _jsonResponse({'success': true, 'data': trans});
    });

    router.get('/api/v1/finance/accounts', (Request request) async {
      final accounts = await DatabaseService().getAccounts();
      return _jsonResponse({'success': true, 'data': accounts});
    });

    router.post('/api/v1/finance/transactions', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final row = {
          'account_id': body['account_id'] ?? 1,
          'type': body['type'] ?? 'EXPENSE',
          'category': body['category'] ?? 'General',
          'amount': (body['amount'] as num).toDouble(),
          'transaction_date': body['transaction_date'] ?? DateTime.now().toIso8601String(),
          'note': body['note'],
        };
        final transId = await DatabaseService().insertTransaction(row);
        await DatabaseService().logAudit('02_finance', 'CREATE_TRANSACTION', 'ATOM_API', 'Recorded ${row['type']}: ${row['amount']}');
        return _jsonResponse({'success': true, 'transaction_id': transId}, status: 201);
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 9. Identity Endpoints (Module 01)
    router.get('/api/v1/identity', (Request request) async {
      final profile = await DatabaseService().getProfile();
      return _jsonResponse({'success': true, 'data': profile});
    });

    router.put('/api/v1/identity', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        await DatabaseService().updateProfile(body);
        return _jsonResponse({'success': true, 'message': 'Identity profile updated'});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 10. Health & Vitals (Module 03)
    router.get('/api/v1/health/today', (Request request) async {
      final summary = await DatabaseService().getAggregatedSummary();
      return _jsonResponse({'success': true, 'data': summary['health']});
    });

    router.post('/api/v1/health/metrics', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final type = body['metric_type'] ?? 'WEIGHT_KG';
        final val = (body['metric_value'] as num).toDouble();
        await DatabaseService().logHealthMetric(type, val, notes: body['notes']);
        return _jsonResponse({'success': true, 'metric': type, 'value': val});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 11. Mental Wellbeing (Module 04)
    router.get('/api/v1/wellbeing/mood', (Request request) async {
      final logs = await DatabaseService().getMoodLogs();
      return _jsonResponse({'success': true, 'data': logs});
    });

    router.post('/api/v1/wellbeing/mood', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final rating = (body['mood_rating'] as num?)?.toInt() ?? 4;
        final emotion = body['primary_emotion'] ?? 'Calm';
        await DatabaseService().logMood(rating, emotion, entry: body['journal_entry']);
        return _jsonResponse({'success': true, 'message': 'Mood logged'});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 12. Location (Module 05)
    router.get('/api/v1/location/places', (Request request) async {
      final places = await DatabaseService().getPlaces();
      return _jsonResponse({'success': true, 'data': places});
    });

    router.post('/api/v1/location/breadcrumbs', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final lat = (body['latitude'] as num).toDouble();
        final lon = (body['longitude'] as num).toDouble();
        await DatabaseService().logBreadcrumb(lat, lon, accuracy: (body['accuracy'] as num?)?.toDouble());
        return _jsonResponse({'success': true});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 13. Education & GATE (Module 08)
    router.get('/api/v1/learning/progress', (Request request) async {
      final subjects = await DatabaseService().getLearningSubjects();
      return _jsonResponse({'success': true, 'data': subjects});
    });

    router.post('/api/v1/learning/study-session', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        final subjectId = (body['subject_id'] as num).toInt();
        final topic = body['topic'] ?? 'GATE Study';
        final duration = (body['duration_minutes'] as num).toInt();
        await DatabaseService().logStudySession(subjectId, topic, duration, problemsSolved: (body['problems_solved'] as num?)?.toInt() ?? 0);
        return _jsonResponse({'success': true, 'message': 'Study session logged'});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 14. Career & Social (Modules 09 & 10)
    router.get('/api/v1/career/summary', (Request request) async {
      final career = await DatabaseService().getCareerSummary();
      return _jsonResponse({'success': true, 'data': career});
    });

    router.get('/api/v1/social/contacts', (Request request) async {
      final contacts = await DatabaseService().getContacts();
      return _jsonResponse({'success': true, 'data': contacts});
    });

    // 15. Nutrition (Module 11)
    router.get('/api/v1/nutrition/today', (Request request) async {
      final meals = await DatabaseService().getTodayMeals();
      return _jsonResponse({'success': true, 'data': meals});
    });

    router.post('/api/v1/nutrition/meals', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        await DatabaseService().logMeal(
          body['meal_type'] ?? 'LUNCH',
          body['food_items'] ?? 'Meal',
          calories: (body['calories'] as num?)?.toDouble(),
          cost: (body['cost'] as num?)?.toDouble(),
        );
        return _jsonResponse({'success': true, 'message': 'Meal logged'});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });

    // 16. Documents & Insights (Modules 13 & 14)
    router.get('/api/v1/documents', (Request request) async {
      final docs = await DatabaseService().getDocuments();
      return _jsonResponse({'success': true, 'data': docs});
    });

    router.get('/api/v1/insights', (Request request) async {
      final insights = await DatabaseService().getInsights();
      return _jsonResponse({'success': true, 'data': insights});
    });

    router.post('/api/v1/insights', (Request request) async {
      try {
        final body = jsonDecode(await request.readAsString());
        await DatabaseService().addInsight(body['insight_type'] ?? 'DAILY_SUMMARY', body['title'] ?? 'Insight', body['content'] ?? '');
        return _jsonResponse({'success': true, 'message': 'Insight recorded'});
      } catch (e) {
        return _jsonResponse({'success': false, 'error': e.toString()}, status: 400);
      }
    });
  }

  Middleware _corsMiddleware() {
    return (Handler innerHandler) {
      return (Request request) async {
        if (request.method == 'OPTIONS') {
          return Response.ok('', headers: _corsHeaders());
        }
        final response = await innerHandler(request);
        return response.change(headers: _corsHeaders());
      };
    };
  }

  Middleware _authMiddleware() {
    return (Handler innerHandler) {
      return (Request request) async {
        // Public endpoints bypass HMAC check
        final path = request.url.path;
        if (path == 'api/v1/ping' || path == 'api/v1/pair') {
          return innerHandler(request);
        }

        if (!PairingService().isPaired) {
          return _jsonResponse({'success': false, 'error': 'Device pairing is required'}, status: 401);
        }

        final signature = request.headers['x-mylife-signature'];
        final timestampStr = request.headers['x-mylife-timestamp'];
        final clientName = request.headers['x-mylife-client'];

        if (signature == null ||
            timestampStr == null ||
            clientName == null ||
            clientName != PairingService().pairedClientName) {
          return _jsonResponse({
            'success': false,
            'error': 'Unauthorized: Missing X-MyLife-Signature or X-MyLife-Timestamp header',
          }, status: 401);
        }

        // Cache body for signature validation
        final bodyBytes = await request.read().toList();
        final rawBody = utf8.decode(bodyBytes.expand((x) => x).toList());

        // Recreate request with readable body stream
        final newRequest = request.change(body: rawBody);

        final isValid = PairingService().verifySignature(
          method: request.method,
          pathWithQuery: '/${request.url.toString()}',
          timestampStr: timestampStr,
          signature: signature,
          rawBody: rawBody,
        );

        if (!isValid) {
          return _jsonResponse({
            'success': false,
            'error': 'Unauthorized: Invalid HMAC signature or replay window expired (±60s)',
          }, status: 401);
        }

        return innerHandler(newRequest);
      };
    };
  }

  Map<String, String> _corsHeaders() {
    return {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
      'Access-Control-Allow-Headers': 'Origin, Content-Type, Accept, X-MyLife-Signature, X-MyLife-Client, X-MyLife-Timestamp',
    };
  }

  Response _jsonResponse(Map<String, dynamic> body, {int status = 200}) {
    return Response(
      status,
      body: jsonEncode(body),
      headers: {
        'Content-Type': 'application/json',
        ..._corsHeaders(),
      },
    );
  }
}

