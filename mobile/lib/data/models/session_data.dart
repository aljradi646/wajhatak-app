import 'package:flutter/foundation.dart';

import 'user.dart';

/// جلسة المصادقة — المستخدم + رمز Sanctum.
@immutable
class SessionData {
  const SessionData({required this.user, required this.token});

  final LuxUser user;
  final String token;

  SessionData copyWith({LuxUser? user, String? token}) =>
      SessionData(user: user ?? this.user, token: token ?? this.token);
}
