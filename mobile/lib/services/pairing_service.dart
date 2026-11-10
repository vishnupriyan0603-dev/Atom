import 'dart:convert';
import 'dart:io';
import 'dart:math';
import 'package:crypto/crypto.dart';
import 'package:path_provider/path_provider.dart';

/**
 * PairingService — Handles device pairing, shared secret storage,
 * and cryptographic HMAC-SHA256 request signature verification on Samsung Galaxy M31.
 */
class PairingService {
  static final PairingService _instance = PairingService._internal();
  factory PairingService() => _instance;
  PairingService._internal();

  String? _sharedSecret;
  String _deviceId = "M31-SM-M315F";
  bool _isPaired = false;
  String? _pairedClientName;

  bool get isPaired => _isPaired;
  String? get sharedSecret => _sharedSecret;
  String get deviceId => _deviceId;
  String? get pairedClientName => _pairedClientName;

  Future<void> initialize() async {
    try {
      final dir = await getApplicationDocumentsDirectory();
      final file = File('${dir.path}/mylife_pairing_vault.json');
      if (await file.exists()) {
        final content = await file.readAsString();
        final json = jsonDecode(content);
        _sharedSecret = json['shared_secret'];
        _deviceId = json['device_id'] ?? _deviceId;
        _isPaired = json['is_paired'] ?? false;
        _pairedClientName = json['paired_client'];
      } else {
        // Generate new persistent 256-bit cryptorandom secret
        _generateNewSecret();
        await _save();
      }
    } catch (_) {
      _generateNewSecret();
    }
  }

  void _generateNewSecret() {
    final random = Random.secure();
    final bytes = List<int>.generate(32, (_) => random.nextInt(256));
    _sharedSecret = bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    _isPaired = false;
  }

  Future<void> _save() async {
    try {
      final dir = await getApplicationDocumentsDirectory();
      final file = File('${dir.path}/mylife_pairing_vault.json');
      await file.writeAsString(jsonEncode({
        'shared_secret': _sharedSecret,
        'device_id': _deviceId,
        'is_paired': _isPaired,
        'paired_client': _pairedClientName,
      }));
    } catch (_) {}
  }

  Map<String, dynamic> getQrPairingPayload(String localIp, {int port = 8765}) {
    return {
      'version': 1,
      'service': 'mylife-os',
      'device_id': _deviceId,
      'device_name': 'Samsung Galaxy M31',
      'port': port,
      'host_hint': localIp,
      'mdns_name': 'mylife-os.local',
      'shared_secret': _sharedSecret,
    };
  }

  Future<bool> registerPairedClient(String clientName, String fingerprint) async {
    _isPaired = true;
    _pairedClientName = clientName;
    await _save();
    return true;
  }

  /// Verifies the short-lived pairing proof described in MYLIFE_PAIRING_SECURITY_SPEC.md.
  /// The proof binds the requested client identity and a short-lived timestamp.
  bool verifyPairingProof({
    required String clientName,
    required String deviceName,
    required String fingerprint,
    required String timestampStr,
    required String signature,
  }) {
    final secret = _sharedSecret;
    final timestamp = int.tryParse(timestampStr);
    if (secret == null ||
        timestamp == null ||
        !RegExp(r'^[0-9a-fA-F]{64}$').hasMatch(signature)) {
      return false;
    }

    final nowSeconds = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    if ((nowSeconds - timestamp).abs() > 60) return false;
    if (clientName.trim().isEmpty || deviceName.trim().isEmpty || fingerprint.trim().isEmpty) {
      return false;
    }

    final message = '$clientName\n$deviceName\n$fingerprint\n$timestampStr';
    final expected = Hmac(sha256, utf8.encode(secret)).convert(utf8.encode(message)).bytes;
    final supplied = _decodeHex(signature);
    if (supplied == null || supplied.length != expected.length) return false;

    var difference = 0;
    for (var index = 0; index < expected.length; index++) {
      difference |= expected[index] ^ supplied[index];
    }
    return difference == 0;
  }

  List<int>? _decodeHex(String value) {
    if (value.length.isOdd || !RegExp(r'^[0-9a-fA-F]+$').hasMatch(value)) return null;
    return List<int>.generate(
      value.length ~/ 2,
      (index) => int.parse(value.substring(index * 2, index * 2 + 2), radix: 16),
    );
  }

  Future<void> unpair() async {
    _generateNewSecret();
    _isPaired = false;
    _pairedClientName = null;
    await _save();
  }

  /**
   * Verifies an incoming HMAC-SHA256 signature against the shared secret.
   * Signature = HMAC_SHA256(secret, method + "\n" + path + "\n" + timestamp + "\n" + bodyHash)
   */
  bool verifySignature({
    required String method,
    required String pathWithQuery,
    required String timestampStr,
    required String signature,
    required String rawBody,
  }) {
    if (_sharedSecret == null) return false;

    // 1. Replay window check (±60 seconds)
    final timestamp = int.tryParse(timestampStr);
    if (timestamp == null) return false;

    final nowSeconds = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    if ((nowSeconds - timestamp).abs() > 60) {
      return false; // Request outside replay window
    }

    // 2. Hash body
    final bodyHash = rawBody.isEmpty
        ? "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855" // SHA-256 of empty string
        : sha256.convert(utf8.encode(rawBody)).toString();

    // 3. Reconstruct payload message
    final message = "${method.toUpperCase()}\n$pathWithQuery\n$timestampStr\n$bodyHash";

    // 4. Compute HMAC-SHA256
    final hmac = Hmac(sha256, utf8.encode(_sharedSecret!));
    final expectedDigest = hmac.convert(utf8.encode(message)).toString();

    final expected = expectedDigest.codeUnits;
    final supplied = signature.toLowerCase().codeUnits;
    if (expected.length != supplied.length) return false;
    var difference = 0;
    for (var index = 0; index < expected.length; index++) {
      difference |= expected[index] ^ supplied[index];
    }
    return difference == 0;
  }
}

