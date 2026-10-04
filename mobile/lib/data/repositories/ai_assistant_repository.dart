import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api_client.dart';
import '../models/ai_assistant.dart';

/// مستودع المساعد الذكي — كل الاتصالات عبر LuxApiClient (Laravel فقط).
/// لا يتصل التطبيق بخادم النموذج مطلقًا؛ مفتاح جلسة الزائر يُخزن محليًا
/// للحفاظ على سياق المحادثة بين الجلسات.
class AiAssistantRepository {
  AiAssistantRepository(this._api);

  static const _sessionTokenKey = 'wajhatak_ai_session_token';
  final LuxApiClient _api;

  AiBootstrap? _bootstrap;

  /// إعدادات واجهة المساعد (تُجلب مرة لكل جلسة تطبيق).
  Future<AiBootstrap> bootstrap() async {
    final cached = _bootstrap;
    if (cached != null) return cached;
    final json = await _api.get('/ai/bootstrap');
    final value = AiBootstrap.fromJson(
      json['data'] as Map<String, dynamic>? ?? const {},
    );
    _bootstrap = value;
    return value;
  }

  /// إرسال رسالة — الخادم يفهم، يبحث في القاعدة، يولّد الرد ويطهّره.
  Future<({AiChatMessage message, int? conversationId, String? sessionToken})> sendMessage(
    String message, {
    int? conversationId,
    List<Map<String, String>> history = const [],
    double? latitude,
    double? longitude,
  }) async {
    final payload = <String, dynamic>{
      'message': message,
      if (conversationId != null) 'conversation_id': conversationId,
      if (conversationId == null) 'session_token': await _sessionToken(),
      'locale': 'ar',
      if (latitude != null && longitude != null) ...{
        'latitude': latitude,
        'longitude': longitude,
        'radius_km': 10,
      },
    };

    final json = await _api.post('/ai/chat', data: payload);
    final data = json['data'] as Map<String, dynamic>? ?? const {};

    final reply = AiChatMessage(
      id: (data['message_id'] as int?) ?? DateTime.now().microsecondsSinceEpoch,
      role: 'assistant',
      content: data['reply'] as String? ?? '',
      properties: (data['properties'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(AiPropertyResult.fromJson)
          .toList(growable: false),
      status: data['status'] as String? ?? 'ok',
      responseType: data['response_type'] as String? ?? 'text',
      actions: (data['actions'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(growable: false),
      createdAt: DateTime.now(),
    );

    // حفظ مفتاح الجلسة (للزوار) لمتابعة السياق لاحقًا.
    final token = data['session_token'] as String?;
    final conversation = data['conversation_id'] as int?;
    if (token != null && token.isNotEmpty) await _saveSessionToken(token);
    return (
      message: reply,
      conversationId: conversation,
      sessionToken: token,
    );
  }

  Future<void> _persistStreamSessionToken(AiStreamEvent event) async {
    if (event.event != 'done') return;
    final token = event.data?['session_token']?.toString();
    if (token != null && token.isNotEmpty) {
      await _saveSessionToken(token);
    }
  }

  Stream<AiStreamEvent> streamMessage(
    String message, {
    int? conversationId,
    double? latitude,
    double? longitude,
    CancelToken? cancelToken,
  }) async* {
    final payload = <String, dynamic>{
      'message': message,
      if (conversationId != null) 'conversation_id': conversationId,
      if (conversationId == null) 'session_token': await _sessionToken(),
      'locale': 'ar',
      if (latitude != null && longitude != null) ...{
        'latitude': latitude,
        'longitude': longitude,
        'radius_km': 10,
      },
    };

    final response = await _api.postStream(
      '/ai/chat/stream',
      data: payload,
      cancelToken: cancelToken,
    );
    final body = response.data;
    if (body == null) {
      throw ApiFailure('تعذر بدء بث المساعد.', statusCode: response.statusCode);
    }

    String eventName = 'message';
    final dataLines = <String>[];

    AiStreamEvent? flushEvent() {
      if (dataLines.isEmpty) return null;
      try {
        final decoded = jsonDecode(dataLines.join('\\n'));
        if (decoded is! Map<String, dynamic>) return null;
        return AiStreamEvent.fromJson({...decoded, 'event': decoded['event'] ?? eventName});
      } catch (_) {
        return null;
      }
    }

    await for (final line in body.stream
        .transform(utf8.decoder)
        .transform(const LineSplitter())) {
      if (line.isEmpty) {
        final event = flushEvent();
        if (event != null) yield event;
        eventName = 'message';
        dataLines.clear();
        continue;
      }
      if (line.startsWith('event:')) {
        eventName = line.substring(6).trim();
      } else if (line.startsWith('data:')) {
        dataLines.add(line.substring(5).trimLeft());
      }
    }

    final event = flushEvent();
    if (event != null) yield event;
  }

  /// مسح محادثة المستخدم المسجل (متطلب اختياري عند وجود حساب).
  Future<void> clearConversation(int conversationId) =>
      _api.delete('/ai/conversations/$conversationId');

  Future<void> submitFeedback(int messageId, bool helpful) async {
    await _api.post(
      '/ai/messages/$messageId/feedback',
      data: {'feedback': helpful ? 'helpful' : 'not_helpful'},
    );
  }


  /// قائمة محادثات المستخدم (للمستخدمين المسجلين فقط).
  Future<List<AiConversationItem>> listConversations() async {
    final json = await _api.get('/ai/conversations');
    final data = json['data'] as List<dynamic>? ?? const [];
    return data
        .map((e) => AiConversationItem.fromJson(e as Map<String, dynamic>))
        .toList(growable: false);
  }

  /// رسائل محادثة محددة.
  Future<List<AiChatMessage>> getConversationMessages(
    int conversationId,
  ) async {
    final json = await _api.get('/ai/conversations/$conversationId/messages');
    final root = json['data'] as Map<String, dynamic>? ?? const {};
    final data = root['messages'] as List<dynamic>? ?? const [];
    return data.map((e) => AiChatMessage.fromJson(e as Map<String, dynamic>)).toList(growable: false);
  }

  // ------------------------------------------------------------------

  Future<String> _sessionToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(_sessionTokenKey);
      if (token != null && token.isNotEmpty) return token;
      final fresh =
          DateTime.now().microsecondsSinceEpoch.toRadixString(36) +
          (DateTime.now().hashCode.toRadixString(36));
      await prefs.setString(_sessionTokenKey, fresh);
      return fresh;
    } on Object {
      return 'anon-fallback';
    }
  }

  Future<void> _saveSessionToken(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_sessionTokenKey, token);
    } on Object {
      // تجاهل — الجلسة ستجدد مفتاحًا جديدًا لاحقًا.
    }
  }

  Future<int> createConversation() async {
    final json = await _api.post('/ai/conversations', data: {'locale': 'ar'});
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return data['id'] as int;
  }

  Future<void> pinConversation(int conversationId, bool pinned) =>
      _api.patch('/ai/conversations/$conversationId/pin', data: {'pinned': pinned});
}
