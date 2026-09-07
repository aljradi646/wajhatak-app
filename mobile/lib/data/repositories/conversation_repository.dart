import '../api_client.dart';
import '../models/models.dart';

/// المحادثات والرسائل — التفرد (عميل + وكيل) مفروض من الخادم.
class ConversationRepository {
  ConversationRepository(this._api);

  final LuxApiClient _api;

  Future<List<ConversationItem>> conversations() async {
    final json = await _api.get('/conversations');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(ConversationItem.fromJson)
        .toList();
  }

  Future<ConversationItem> startConversation(int propertyId) async {
    final json = await _api.post(
      '/conversations',
      data: {'property_id': propertyId},
    );
    return ConversationItem.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<List<ChatMessage>> messages(int conversationId) async {
    final json = await _api.get('/conversations/$conversationId/messages');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(ChatMessage.fromJson)
        .toList();
  }

  Future<ChatMessage> sendMessage(int conversationId, String body) async {
    final json = await _api.post(
      '/conversations/$conversationId/messages',
      data: {'body': body},
    );
    return ChatMessage.fromJson(json['data'] as Map<String, dynamic>);
  }
}
