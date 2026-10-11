import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/models/user.dart';

void main() {
  const base = <String, dynamic>{
    'id': 7,
    'name': 'Test User',
    'email': 'test@example.test',
    'roles': <String>['user'],
    'email_verified': false,
  };

  test('legacy cached user defaults email verification policy to enabled', () {
    final user = LuxUser.fromJson(base);
    expect(user.emailVerificationEnabled, isTrue);
  });

  test('server can disable verification and the flag survives local caching', () {
    final user = LuxUser.fromJson({
      ...base,
      'email_verification_enabled': false,
    });
    expect(user.emailVerificationEnabled, isFalse);
    expect(LuxUser.fromJson(user.toJson()).emailVerificationEnabled, isFalse);
  });
}
