import 'dart:convert';

import 'package:atom_mobile/services/pairing_service.dart';
import 'package:crypto/crypto.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late PairingService pairing;
  late String secret;

  setUp(() async {
    pairing = PairingService();
    await pairing.initialize();
    secret = pairing.getQrPairingPayload('192.168.1.2')['shared_secret'] as String;
  });

  String pairingProof({
    required String client,
    required String device,
    required String fingerprint,
    required String timestamp,
  }) {
    final message = '$client\n$device\n$fingerprint\n$timestamp';
    return Hmac(sha256, utf8.encode(secret)).convert(utf8.encode(message)).toString();
  }

  test('accepts a correctly signed, current pairing proof', () {
    final timestamp = (DateTime.now().millisecondsSinceEpoch ~/ 1000).toString();
    final signature = pairingProof(
      client: 'ATOM-WINDOWS',
      device: 'Vishnu-Desktop-PC',
      fingerprint: 'atom-win-a89c01bf',
      timestamp: timestamp,
    );

    expect(
      pairing.verifyPairingProof(
        clientName: 'ATOM-WINDOWS',
        deviceName: 'Vishnu-Desktop-PC',
        fingerprint: 'atom-win-a89c01bf',
        timestampStr: timestamp,
        signature: signature,
      ),
      isTrue,
    );
  });

  test('rejects a tampered client identity and stale pairing proof', () {
    final timestamp = (DateTime.now().millisecondsSinceEpoch ~/ 1000).toString();
    final signature = pairingProof(
      client: 'ATOM-WINDOWS',
      device: 'Vishnu-Desktop-PC',
      fingerprint: 'atom-win-a89c01bf',
      timestamp: timestamp,
    );

    expect(
      pairing.verifyPairingProof(
        clientName: 'UNTRUSTED-CLIENT',
        deviceName: 'Vishnu-Desktop-PC',
        fingerprint: 'atom-win-a89c01bf',
        timestampStr: timestamp,
        signature: signature,
      ),
      isFalse,
    );
    expect(
      pairing.verifyPairingProof(
        clientName: 'ATOM-WINDOWS',
        deviceName: 'Vishnu-Desktop-PC',
        fingerprint: 'atom-win-a89c01bf',
        timestampStr: '1',
        signature: signature,
      ),
      isFalse,
    );
  });

  test('rejects malformed signatures and empty pairing identity fields', () {
    final timestamp = (DateTime.now().millisecondsSinceEpoch ~/ 1000).toString();
    expect(
      pairing.verifyPairingProof(
        clientName: 'ATOM-WINDOWS',
        deviceName: 'Vishnu-Desktop-PC',
        fingerprint: 'atom-win-a89c01bf',
        timestampStr: timestamp,
        signature: 'not-a-signature',
      ),
      isFalse,
    );
    expect(
      pairing.verifyPairingProof(
        clientName: '',
        deviceName: 'Vishnu-Desktop-PC',
        fingerprint: 'atom-win-a89c01bf',
        timestampStr: timestamp,
        signature: pairingProof(
          client: '',
          device: 'Vishnu-Desktop-PC',
          fingerprint: 'atom-win-a89c01bf',
          timestamp: timestamp,
        ),
      ),
      isFalse,
    );
  });

  test('validates signed API requests and rejects altered signatures', () {
    const path = '/api/v1/status?detail=1';
    const body = '';
    final timestamp = (DateTime.now().millisecondsSinceEpoch ~/ 1000).toString();
    final bodyHash = sha256.convert(utf8.encode(body)).toString();
    final message = 'GET\n$path\n$timestamp\n$bodyHash';
    final signature = Hmac(sha256, utf8.encode(secret))
        .convert(utf8.encode(message))
        .toString();

    expect(
      pairing.verifySignature(
        method: 'GET',
        pathWithQuery: path,
        timestampStr: timestamp,
        signature: signature,
        rawBody: body,
      ),
      isTrue,
    );
    expect(
      pairing.verifySignature(
        method: 'GET',
        pathWithQuery: path,
        timestampStr: timestamp,
        signature: '${signature}00',
        rawBody: body,
      ),
      isFalse,
    );
  });
}
