import 'package:shared_preferences/shared_preferences.dart';

import '../api_client.dart';
import '../models/ai_assistant.dart';

/// مستودع المساعد الذكي — كل الاتصال يمر عبر Laravel API.
class AiAssistantRepository {
  AiAssistantRepository(this._api);

  static const _sessionTokenKey = 'wajhatak_ai_session_token';

  final LuxApiClient _api;
  AiBootstrap? _bootstrap;

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

  Future<({AiChatMessage message, int? conversationId, String? sessionToken})>
  sendMessage(
    String message, {
    int? conversationId,
    double? latitude,
    double? longitude,
    double radiusKm = 10,
  }) async {
    final payload = <String, dynamic>{
      'message': message,
      if (conversationId != null) 'conversation_id': conversationId,
      if (conversationId == null) 'session_token': await _sessionToken(),
      'locale': 'ar',
      if (latitude != null && longitude != null) ...{
        'latitude': latitude,
        'longitude': longitude,
        'radius_km': radiusKm,
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
      createdAt: DateTime.now(),
    );

    final token = data['session_token'] as String?;
    if (token != null && token.isNotEmpty) {
      await _saveSessionToken(token);
    }

    return (
      message: reply,
      conversationId: data['conversation_id'] as int?,
      sessionToken: token,
    );
  }

  Future<void> clearConversation(int conversationId) =>
      _api.delete('/ai/conversations/$conversationId');

  Future<List<AiConversationItem>> listConversations() async {
    final json = await _api.get('/ai/conversations');
    final data = json['data'] as List<dynamic>? ?? const [];

    return data
        .whereType<Map<String, dynamic>>()
        .map(AiConversationItem.fromJson)
        .toList(growable: false);
  }

  Future<List<AiChatMessage>> getConversationMessages(
    int conversationId,
  ) async {
    final json = await _api.get('/ai/conversations/$conversationId/messages');
    final root = json['data'] as Map<String, dynamic>? ?? const {};
    final data = root['messages'] as List<dynamic>? ?? const [];

    return data
        .whereType<Map<String, dynamic>>()
        .map(AiChatMessage.fromJson)
        .toList(growable: false);
  }

  Future<int> createConversation() async {
    final json = await _api.post(
      '/ai/conversations',
      data: {'locale': 'ar'},
    );
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return data['id'] as int;
  }

  Future<void> pinConversation(int conversationId, bool pinned) {
    return _api.patch(
      '/ai/conversations/$conversationId/pin',
      data: {'pinned': pinned},
    );
  }

  Future<String> _sessionToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(_sessionTokenKey);
      if (token != null && token.isNotEmpty) return token;

      final fresh =
          DateTime.now().microsecondsSinceEpoch.toRadixString(36) +
          DateTime.now().hashCode.toRadixString(36);

      await prefs.setString(_sessionTokenKey, fresh);
      return fresh;
    } on Object catch (_) {
      return 'anon-fallback';
    }
  }

  Future<void> _saveSessionToken(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_sessionTokenKey, token);
    } on Object catch (_) {
      // فشل التخزين المحلي لا يجب أن يمنع الرد الحالي.
    }
  }
}
