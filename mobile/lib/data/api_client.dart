import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/config/app_config.dart';
import '../core/services/infinityfree_challenge_solver.dart';
import '../data/models/models.dart';

class ApiFailure implements Exception {
  ApiFailure(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class TokenStore {
  TokenStore(this._storage);

  static const _tokenKey = 'lux_access_token';
  static const _loginTimeKey = 'lux_login_time';
  static const _userKey = 'lux_cached_user';
  static const _sessionDuration = Duration(days: 30);
  final FlutterSecureStorage _storage;

  // --- التوكن ----------------------------------------------------------

  Future<String?> read() async {
    try {
      final token = await _storage.read(key: _tokenKey);
      if (token == null || token.isEmpty) return null;
      final loginTimeStr = await _storage.read(key: _loginTimeKey);
      if (loginTimeStr != null) {
        final loginTime = DateTime.tryParse(loginTimeStr);
        if (loginTime != null &&
            DateTime.now().difference(loginTime) > _sessionDuration) {
          await clear();
          return null;
        }
      }
      return token;
    } on Object {
      return null;
    }
  }

  Future<void> write(String token) async {
    try {
      await _storage.write(key: _tokenKey, value: token);
      await _storage.write(
        key: _loginTimeKey,
        value: DateTime.now().toIso8601String(),
      );
    } on Object {}
  }

  Future<void> clear() async {
    try {
      await _storage.delete(key: _tokenKey);
      await _storage.delete(key: _loginTimeKey);
      await _storage.delete(key: _userKey);
    } on Object {}
  }

  Future<void> refreshSession() async {
    try {
      await _storage.write(
        key: _loginTimeKey,
        value: DateTime.now().toIso8601String(),
      );
    } on Object {}
  }

  // --- بيانات المستخدم المخزّنة محليًا -----------------------------------

  Future<void> saveUser(LuxUser user) async {
    try {
      await _storage.write(key: _userKey, value: jsonEncode(user.toJson()));
    } on Object {}
  }

  Future<LuxUser?> readUser() async {
    try {
      final raw = await _storage.read(key: _userKey);
      if (raw == null || raw.isEmpty) return null;
      return LuxUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } on Object {
      return null;
    }
  }
}

class LuxApiClient {
  LuxApiClient(
    this._tokenStore, {
    InfinityFreeChallengeSolver? challengeSolver,
    String? baseUrl,
  }) : _challengeSolver =
           challengeSolver ??
           InfinityFreeChallengeSolver(
             probeBaseUrl: baseUrl ?? AppConfig.apiBaseUrl,
           ),
       _dio = Dio(
         BaseOptions(
           baseUrl: baseUrl ?? AppConfig.apiBaseUrl,
           connectTimeout: AppConfig.connectTimeout,
           receiveTimeout: AppConfig.receiveTimeout,
           // يجب أن يطابق User-Agent الخاص بطلبات الـ API نظير طلب حلّ
           // التحدّي؛ يُصدِر السيرفر تحدّيًا مختلفًا لكل UA ويرفض الكوكي
           // المحسوب تحت UA آخر.
           headers: const {
             'Accept': 'application/json',
             'User-Agent': InfinityFreeChallengeSolver.userAgent,
           },
         ),
       ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          // إيقاع هادئ + قاطع دارة يمنع الضغط على الخادم (وهو سبب منع 429).
          await _throttle();
          // تجاوز تحدي InfinityFree: نضمن وجود كوكي __test صالح قبل كل طلب
          // (ensureCookie ترجع الكوكي فورًا لو كان طازجًا دون أي طلب إضافي).
          if (_challengeSolver.cookie == null) {
            await _challengeSolver.ensureCookie();
          }
          final challengeCookie = _challengeSolver.cookie;
          if (challengeCookie != null) {
            options.headers['Cookie'] = challengeCookie;
          }

          final token = await _tokenStore.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
            // بعض الاستضافات (InfinityFree/LiteSpeed) تحذف رأس Authorization قبل
            // وصوله لـ PHP؛ نرسل الرمز في رأس مخصص كمسارٍ بديل يعرّفه الخادم.
            options.headers['X-Auth-Token'] = token;
          }
          handler.next(options);
        },
        onResponse: (response, handler) async {
          _recordSuccess();
          // استجابة صفحة تحدي جديدة → أنشئ الكوكي وأعد المحاولة مرة بعد مرة.
          if (_isChallengeHtml(response.data) &&
              !response.requestOptions.extra.containsKey(_retryTag)) {
            final solved = await _challengeSolver.forceSolve();
            if (solved != null) {
              final retried = await _retryWithCookie(
                response.requestOptions,
                solved,
                markRetried: true,
              );
              if (retried != null) {
                handler.resolve(retried);
                return;
              }
            }
          }
          unawaited(_tokenStore.refreshSession());
          handler.next(response);
        },
        onError: (error, handler) async {
          final statusCode = error.response?.statusCode;

          // مهما كانت الحالة، سجّل الفشل لتغذية قاطع الدارة قبل المحاولة.
          _recordFailure();

          // تخفيف الاستضافة (429) — لا نعيد المحاولة إطلاقًا ونورد خطأً واضحًا.
          if (statusCode == 429) {
            handler.reject(
              DioException(
                requestOptions: error.requestOptions,
                response: error.response,
                type: error.type,
                error: ApiFailure(
                  'طلبات كثيرة جدًا. انتظر قليلًا ثم أعد المحاولة.',
                  statusCode: 429,
                ),
              ),
            );
            return;
          }

          // تعرّف على تحدي InfinityFree الحقيقي فقط وأعد المحاولة (مرة واحدة
          // للطلب، وبإيقاع محدود). أي صفحة حماية أخرى لن تُعد.
          if (_isChallengeHtml(error.response?.data) &&
              !error.requestOptions.extra.containsKey(_retryTag)) {
            final solved = await _challengeSolver.forceSolve();
            if (solved != null) {
              final retried = await _retryWithCookie(
                error.requestOptions,
                solved,
                markRetried: true,
              );
              if (retried != null) {
                handler.resolve(retried);
                return;
              }
            }
          }

          if (statusCode == 401 || statusCode == 403) {
            unawaited(_tokenStore.clear());
          }
          final payload = error.response?.data;
          final message =
              payload is Map<String, dynamic>
                  ? (payload['message'] as String? ??
                     _extractValidationErrors(payload))
                  : null;
          handler.reject(
            DioException(
              requestOptions: error.requestOptions,
              response: error.response,
              type: error.type,
              error: ApiFailure(
                message ?? _fallbackMessage(error),
                statusCode: statusCode,
              ),
            ),
          );
        },
      ),
    );
  }

  /// علامة تُلصق على الطلبات المُعاد إرسالها بعد تجاوز التحدي لتفادي
  /// حلقات لا نهائية لو استمر التحدي بالصدفة.
  static const _retryTag = '_wj_challenge_retried';
  static const _rateCooldown = Duration(milliseconds: 350);
  static const _breakerThreshold = 5;
  static const _breakerCooldown = Duration(seconds: 20);

  final TokenStore _tokenStore;
  final Dio _dio;
  final InfinityFreeChallengeSolver _challengeSolver;

  DateTime _lastSent = DateTime.now();
  int _rapidFailureStreak = 0;
  DateTime? _breakerLiftedAt;

  /// هل الناقل (قاطع الدارة) مفتوح؟ أي هل نحن في فترة تهدئة بسبب فشل متكرر
  /// أو مضايقات 429؟ يخضع لها كل طلب لمنع الضغط على الخادم.
  bool get _isBreakerOpen {
    final at = _breakerLiftedAt;
    return at != null && DateTime.now().difference(at) < _breakerCooldown;
  }

  Future<void> _waitOutBreaker() async {
    final at = _breakerLiftedAt;
    if (at == null) return;
    final remaining = _breakerCooldown - DateTime.now().difference(at);
    if (remaining > Duration.zero && _isBreakerOpen) {
      await Future.delayed(remaining);
    }
  }

  /// يفرض فجوة زمنية دنيا بين الطلبات للحفاظ على إيقاع هادئ غير مريب.
  Future<void> _throttle() async {
    await _waitOutBreaker();
    final sinceLast = DateTime.now().difference(_lastSent);
    if (sinceLast < _rateCooldown) {
      await Future.delayed(_rateCooldown - sinceLast);
    }
    _lastSent = DateTime.now();
  }

  /// يسجّل فشلًا سريعًا؛ يفتح الناقل عند تجاوز العتبة ليهدأ الخادم.
  void _recordFailure() {
    _rapidFailureStreak++;
    if (_rapidFailureStreak >= _breakerThreshold) {
      _breakerLiftedAt = DateTime.now();
      _rapidFailureStreak = 0;
    }
  }

  void _recordSuccess() {
    _rapidFailureStreak = 0;
  }

  /// أداة كشف صارمة لتحدّي InfinityFree الفعلي (نصّ AES مع 3 قيم `toNumbers`).
  /// يرفض أي صفحة حماية/429 أو نصوص أخرى كي لا نعيد المحاولة عبثًا.
  bool _isChallengeHtml(Object? data) {
    if (data is! String) return false;
    final lower = data.toLowerCase();
    // حماية الاستضافة من الإساءة (429 Scanner) — لا نتعامل معها كتحدٍّ قابل للحل.
    if (lower.contains('too many requests') ||
        lower.contains('429') ||
        lower.contains('scanner activity') ||
        lower.contains('please try again later')) {
      return false;
    }
    // لا نعتبرها تحدّيًا حقيقيًا إلا إذا وُجدت محاولايتا AES كاملتان.
    final n = RegExp(r'toNumbers\("([0-9a-f]{32,})"').allMatches(lower);
    var count = 0;
    for (final _ in n.take(3)) {
      count++;
    }
    return count >= 3;
  }

  Future<Response<dynamic>?> _retryWithCookie(
    RequestOptions requestOptions,
    String cookie, {
    bool markRetried = false,
  }) async {
    try {
      final copy = requestOptions.copyWith();
      copy.headers['Cookie'] = cookie;
      if (markRetried) {
        copy.extra[_retryTag] = true;
      }
      return await _dio.fetch(copy);
    } on Object {
      return null;
    }
  }

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    try {
      final response = await _dio.get<Map<String, dynamic>>(
        path,
        queryParameters: query,
      );
      return response.data ?? const {};
    } on DioException catch (error) {
      throw _toFailure(error);
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Object? data,
    Options? options,
  }) async {
    try {
      final isUpload = data is FormData;
      final effectiveOptions = (options ?? Options()).copyWith(
        // رفع الملفات (صور) يحتاج مهلة أطول على استضافة بطيئة.
        sendTimeout: isUpload ? const Duration(seconds: 120) : null,
        receiveTimeout: isUpload ? const Duration(seconds: 120) : null,
      );
      final response = await _dio.post<dynamic>(
        path,
        data: data,
        options: effectiveOptions,
      );
      final body = response.data;
      if (body is Map<String, dynamic>) return body;
      return const {};
    } on DioException catch (error) {
      throw _toFailure(error);
    }
  }

  Future<Map<String, dynamic>> patch(
    String path, {
    Object? data,
  }) async {
    try {
      final response = await _dio.patch<dynamic>(path, data: data);
      final body = response.data;
      if (body is Map<String, dynamic>) return body;
      return const {};
    } on DioException catch (error) {
      throw _toFailure(error);
    }
  }

  Future<void> delete(String path) async {
    try {
      await _dio.delete<void>(path);
    } on DioException catch (error) {
      throw _toFailure(error);
    }
  }

  ApiFailure _toFailure(DioException error) =>
      error.error is ApiFailure
          ? error.error! as ApiFailure
          : ApiFailure(
              _fallbackMessage(error),
              statusCode: error.response?.statusCode,
            );

  String _fallbackMessage(DioException error) {
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.connectionError) {
      return 'تعذر الاتصال بالخادم. تحقّق من عنوان الشبكة وإعدادات الاتصال.';
    }
    if (error.type == DioExceptionType.receiveTimeout) {
      return 'استغرق الخادم وقتًا أطول من المتوقع.';
    }
    return 'تعذر إتمام الطلب. حاول مرة أخرى.';
  }

  String _extractValidationErrors(Map<String, dynamic> payload) {
    final errors = payload['errors'];
    if (errors is Map<String, dynamic> && errors.isNotEmpty) {
      final messages = <String>[];
      for (final entry in errors.entries) {
        final fieldErrors = entry.value;
        if (fieldErrors is List) {
          messages.addAll(fieldErrors.map((e) => e.toString()));
        }
      }
      if (messages.isNotEmpty) return messages.join('\n');
    }
    return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
  }
}
