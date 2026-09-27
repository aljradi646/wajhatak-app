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
  Future<AiChatMessage> sendMessage(
    String message, {
    int? conversationId,
    List<Map<String, String>> history = const [],
  }) async {
    final payload = <String, dynamic>{
      'message': message,
      if (conversationId != null) 'conversation_id': conversationId,
      if (conversationId == null) 'session_token': await _sessionToken(),
      'locale': 'ar',
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

    // حفظ مفتاح الجلسة (للزوار) لمتابعة السياق لاحقًا.
    final token = data['session_token'] as String?;
    final conversation = data['conversation_id'] as int?;
    if (conversation == null && token != null && token.isNotEmpty) {
      await _saveSessionToken(token);
    }

    return reply;
  }

  /// مسح محادثة المستخدم المسجل (متطلب اختياري عند وجود حساب).
  Future<void> clearConversation(int conversationId) =>
      _api.delete('/ai/conversations/$conversationId');

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
}
