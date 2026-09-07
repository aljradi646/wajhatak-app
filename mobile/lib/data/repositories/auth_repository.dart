import 'dart:io';

import 'package:dio/dio.dart';

import '../api_client.dart';
import '../models/models.dart';
import '../../core/utils/image_compressor.dart';

/// المصادقة والحساب الشخصي.
class AuthRepository {
  AuthRepository(this._api, this._tokenStore);

  final LuxApiClient _api;
  final TokenStore _tokenStore;

  Future<SessionData?> restore() async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) return null;

    // جرّب تحميل بيانات المستخدم من التخزين المحلي أولاً (للحالات بدون إنترنت).
    final cachedUser = await _tokenStore.readUser();

    try {
      final json = await _api.get('/me');
      await _tokenStore.refreshSession();
      final user = LuxUser.fromJson(json['data'] as Map<String, dynamic>);
      await _tokenStore.saveUser(user);
      return SessionData(user: user, token: token);
    } on ApiFailure catch (e) {
      if (e.statusCode == 401 || e.statusCode == 403) {
        // الخادم رفض التوكن — امسح كل شيء.
        await _tokenStore.clear();
        return null;
      }
      // خطأ شبكة أو مؤقت — استخدم الكاش المحلي إن وُجد.
      if (cachedUser != null) {
        return SessionData(user: cachedUser, token: token);
      }
      return null;
    }
  }

  Future<SessionData> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final json = await _api.post(
      '/auth/login',
      data: {'email': email, 'password': password, 'device_name': deviceName},
    );
    return _saveSession(json);
  }

  Future<SessionData> register({
    required String name,
    required String email,
    required String password,
    required String accountType,
    String? phone,
  }) async {
    final json = await _api.post(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        'account_type': accountType,
        if (phone != null && phone.trim().isNotEmpty) 'phone': phone.trim(),
        'locale': 'ar',
      },
    );
    return _saveSession(json);
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } finally {
      await _tokenStore.clear();
    }
  }

  Future<SessionData> updateProfile({
    required SessionData session,
    required String name,
    String? phone,
    String? locale,
  }) async {
    final json = await _api.patch(
      '/me',
      data: {
        'name': name.trim(),
        'phone': phone?.trim(),
        'locale': ?locale,
      },
    );
    final user = LuxUser.fromJson(json['data'] as Map<String, dynamic>);
    await _tokenStore.saveUser(user);
    return SessionData(user: user, token: session.token);
  }

  Future<SessionData> uploadAvatar({
    required SessionData session,
    required String imagePath,
  }) async {
    final compressed = await ImageCompressor(
      maxDimension: 512,
      quality: 84,
      maxBytes: 256 * 1024,
    ).compress(File(imagePath));
    final formData = FormData.fromMap({
      'avatar': await MultipartFile.fromFile(compressed),
    });
    final json = await _api.post('/me/avatar', data: formData);
    final user = LuxUser.fromJson(json['data'] as Map<String, dynamic>);
    await _tokenStore.saveUser(user);
    return SessionData(user: user, token: session.token);
  }

  Future<Map<String, bool>> updateNotificationPreferences(
    Map<String, bool> data,
  ) async {
    final json = await _api.patch('/me/notification-preferences', data: data);
    final response = json['data'] as Map<String, dynamic>;
    return response.map((key, value) => MapEntry(key, value as bool? ?? true));
  }

  Future<SessionData> _saveSession(Map<String, dynamic> json) async {
    final data = json['data'] as Map<String, dynamic>;
    final token = data['token'] as String;
    final user = LuxUser.fromJson(data['user'] as Map<String, dynamic>);
    await _tokenStore.write(token);
    await _tokenStore.saveUser(user);
    return SessionData(user: user, token: token);
  }
}
