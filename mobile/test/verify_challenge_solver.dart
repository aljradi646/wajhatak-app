import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:wajhatak/data/api_client.dart';

Future<void> main() async {
  final storage = const FlutterSecureStorage(
    wOptions: WindowsOptions(useBackwardCompatibility: false),
  );
  final tokenStore = TokenStore(storage);
  final client = LuxApiClient(tokenStore);
  // محاولة تسجيل دخول خاطئة للتأكد أن الخطأ حقيقي من السيرفر لا من التحدي
  try {
    await client.post('/auth/login', data: {
      'phone': '777000111',
      'password': 'wrong-password-xyz',
    });
    print('RESULT: UNEXPECTED_SUCCESS');
  } catch (e) {
    print('RESULT: login rejected as expected (${e.toString().substring(0, 90)})');
  }
}