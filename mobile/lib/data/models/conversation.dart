import 'package:flutter/foundation.dart';


/// محادثة بين عميل ووكيل — فريدة لكل (عميل + وكيل) على الخادم.
@immutable
class ConversationItem {
  const ConversationItem({
    required this.id,
    required this.title,
    this.agentId,
    this.propertyId,
    this.preview,
    this.previewIsPropertyCard = false,
    this.lastMessageAt,
    this.clientName,
    this.agentName,
    this.agentAvatarUrl,
    this.clientAvatarUrl,
    this.unreadCount = 0,
  });

  final int id;
  final String title;
  final int? agentId;
  final int? propertyId;
  final String? preview;
  final bool previewIsPropertyCard;
  final DateTime? lastMessageAt;
  final String? clientName;
  final String? agentName;
  final String? agentAvatarUrl;
  final String? clientAvatarUrl;
  final int unreadCount;

  factory ConversationItem.fromJson(Map<String, dynamic> json) {
    final property = json['property'] as Map<String, dynamic>?;
    final last = json['last_message'] as Map<String, dynamic>?;
    final client = json['client'] as Map<String, dynamic>?;
    final agent = json['agent'] as Map<String, dynamic>?;
    final agentName = agent?['name'] as String?;
    final lastType = last?['message_type'] as String?;
    return ConversationItem(
      id: json['id'] as int,
      // عنوان المحادثة هو اسم الوكيل وليس اسم العقار.
      title: agentName?.isNotEmpty == true
          ? agentName!
          : property?['title'] as String? ?? 'محادثة',
      agentId: agent?['id'] as int?,
      propertyId: property?['id'] as int?,
      preview: last?['body'] as String?,
      previewIsPropertyCard: lastType == 'property',
      lastMessageAt: DateTime.tryParse(
        json['last_message_at'] as String? ?? '',
      ),
      clientName: client?['name'] as String?,
      agentName: agentName,
      agentAvatarUrl: agent?['avatar_url'] as String?,
      clientAvatarUrl: client?['avatar_url'] as String?,
      unreadCount: (json['unread_count'] as num?)?.toInt() ?? 0,
    );
  }
}
