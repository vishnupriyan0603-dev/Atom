import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'dart:convert';
import '../services/local_server_service.dart';
import '../services/pairing_service.dart';
import '../services/database_service.dart';

class MyLifeServerScreen extends StatefulWidget {
  const MyLifeServerScreen({Key? key}) : super(key: key);

  @override
  State<MyLifeServerScreen> createState() => _MyLifeServerScreenState();
}

class _MyLifeServerScreenState extends State<MyLifeServerScreen> {
  static const _serviceChannel = MethodChannel('com.atom.ai/mylife_service');

  bool _isServerActive = false;
  bool _isForegroundServiceActive = false;
  bool _isTrackingPaused = false;
  String _localIp = "127.0.0.1";
  int _port = 8765;
  int _liveSteps = 0;
  Map<String, dynamic>? _summary;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await PairingService().initialize();
    await _startServer();
    await _refreshStats();
  }

  Future<void> _startServer() async {
    final success = await LocalServerService().startServer();
    if (mounted) {
      setState(() {
        _isServerActive = success;
        _localIp = LocalServerService().localIp;
        _port = LocalServerService().port;
      });
    }

    // Start native Android foreground service
    try {
      await _serviceChannel.invokeMethod('startForegroundService');
      _isForegroundServiceActive = true;
    } catch (_) {}
  }

  Future<void> _refreshStats() async {
    try {
      final summary = await DatabaseService().getAggregatedSummary();
      final steps = await _serviceChannel.invokeMethod<int>('getLiveSteps') ?? 0;
      final isPaused = await _serviceChannel.invokeMethod<bool>('isTrackingPaused') ?? false;

      if (mounted) {
        setState(() {
          _summary = summary;
          _liveSteps = steps;
          _isTrackingPaused = isPaused;
        });
      }
    } catch (_) {}
  }

  Future<void> _toggleTracking() async {
    try {
      if (_isTrackingPaused) {
        await _serviceChannel.invokeMethod('startForegroundService');
      } else {
        await _serviceChannel.invokeMethod('stopForegroundService');
      }
      await _refreshStats();
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final pairing = PairingService();
    final qrPayload = jsonEncode(pairing.getQrPairingPayload(_localIp, port: _port));

    return Scaffold(
      backgroundColor: const Color(0xFF080A0D),
      appBar: AppBar(
        title: const Text('MyLife OS Server', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
        backgroundColor: const Color(0xFF0C0F14),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: Color(0xFF00F2FE)),
            onPressed: _refreshStats,
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // 1. Server Status Card
            _buildServerStatusCard(),
            const SizedBox(height: 16),

            // 2. Hardware & Live Sensor Bar
            _buildLiveSensorRow(),
            const SizedBox(height: 16),

            // 3. Pairing & Security Hub
            _buildPairingCard(pairing, qrPayload),
            const SizedBox(height: 16),

            // 4. Quick Module Overview
            if (_summary != null) _buildSummaryCard(),
          ],
        ),
      ),
    );
  }

  Widget _buildServerStatusCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _isServerActive ? const Color(0xFF00F2FE).withOpacity(0.3) : Colors.red.withOpacity(0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _isServerActive ? const Color(0xFF10B981) : Colors.red,
                      boxShadow: [
                        BoxShadow(
                          color: _isServerActive ? const Color(0xFF10B981).withOpacity(0.5) : Colors.red.withOpacity(0.5),
                          blurRadius: 8,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    _isServerActive ? 'LOCAL SERVER ONLINE' : 'SERVER OFFLINE',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 1.2,
                      color: _isServerActive ? const Color(0xFF10B981) : Colors.red,
                    ),
                  ),
                ],
              ),
              Text(
                'Samsung Galaxy M31',
                style: TextStyle(fontSize: 11, color: Colors.grey.shade400, fontWeight: FontWeight.w500),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            'http://$_localIp:$_port',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'monospace'),
          ),
          const SizedBox(height: 4),
          const Text(
            'mDNS: mylife-os.local:8765 (Port 8765)',
            style: TextStyle(fontSize: 12, color: Color(0xFF00F2FE)),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  icon: Icon(_isTrackingPaused ? Icons.play_arrow_rounded : Icons.pause_rounded, size: 18),
                  label: Text(_isTrackingPaused ? 'Resume Tracking' : 'Pause Tracking', style: const TextStyle(fontSize: 12)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _isTrackingPaused ? const Color(0xFF10B981) : const Color(0xFF1E2838),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: _toggleTracking,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLiveSensorRow() {
    return Row(
      children: [
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF11151C),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF1E2838)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.directions_walk_rounded, color: Color(0xFF00F2FE), size: 16),
                    SizedBox(width: 6),
                    Text('Hardware Steps', style: TextStyle(color: Colors.grey, fontSize: 11)),
                  ],
                ),
                const SizedBox(height: 8),
                Text('$_liveSteps', style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF11151C),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF1E2838)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.lock_outline_rounded, color: Color(0xFFA855F7), size: 16),
                    SizedBox(width: 6),
                    Text('Database Store', style: TextStyle(color: Colors.grey, fontSize: 11)),
                  ],
                ),
                const SizedBox(height: 8),
                const Text('SQLCipher 256', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildPairingCard(PairingService pairing, String qrPayload) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Device Pairing Hub', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white)),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: pairing.isPaired ? const Color(0xFF10B981).withOpacity(0.2) : Colors.amber.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  pairing.isPaired ? 'PAIRED: ${pairing.pairedClientName ?? "ATOM"}' : 'NOT PAIRED',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                    color: pairing.isPaired ? const Color(0xFF10B981) : Colors.amber,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            pairing.isPaired
                ? 'Your Samsung Galaxy M31 is paired with ATOM on Windows. All requests are authenticated with HMAC-SHA256.'
                : 'Scan this QR code in ATOM Web Admin or CLI to pair your Windows computer with this phone.',
            style: TextStyle(fontSize: 12, color: Colors.grey.shade400),
          ),
          const SizedBox(height: 16),
          Center(
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
              child: QrImageView(
                data: qrPayload,
                version: QrVersions.auto,
                size: 160.0,
              ),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.copy_rounded, size: 14),
                  label: const Text('Copy Secret Key', style: TextStyle(fontSize: 11)),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF00F2FE),
                    side: const BorderSide(color: Color(0xFF00F2FE)),
                  ),
                  onPressed: () {
                    Clipboard.setData(ClipboardData(text: pairing.sharedSecret ?? ''));
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Pairing secret copied to clipboard!')),
                    );
                  },
                ),
              ),
              const SizedBox(width: 10),
              if (pairing.isPaired)
                OutlinedButton(
                  child: const Text('Unpair', style: TextStyle(fontSize: 11, color: Colors.red)),
                  style: OutlinedButton.styleFrom(side: const BorderSide(color: Colors.red)),
                  onPressed: () async {
                    await pairing.unpair();
                    setState(() {});
                  },
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard() {
    final fin = _summary?['finance'] ?? {};
    final act = _summary?['activities'] ?? {};
    final lrn = _summary?['learning'] ?? {};

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF11151C),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF1E2838)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('14-Module Snapshot', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildMetricCol('Cash Balance', '₹${fin['total_balance'] ?? 0}', Colors.emerald),
              _buildMetricCol('Pending Tasks', '${act['pending_tasks_count'] ?? 0}', Colors.amber),
              _buildMetricCol('GATE Study', '${lrn['study_hours_today'] ?? 0} hrs', Colors.cyan),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildMetricCol(String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: color)),
      ],
    );
  }
}
