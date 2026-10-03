import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:wajhatak/data/api_client.dart';

Future<void> main() async {
  final storage = const FlutterSecureStorage(
    wOptions: WindowsOptions(useBackwardCompatibility: false),
  );
  final tokenStore = TokenStore(storage);
  final client = LuxApiClient(tokenStore);
  try {
    await client.post('/auth/login', data: {
      'phone': '777000111',
      'password': 'wrong-password-xyz',
    });
    debugPrint('RESULT: UNEXPECTED_SUCCESS');
  } catch (e) {
    debugPrint('RESULT: login rejected as expected (${e.toString().substring(0, 90)})');
  }
}
