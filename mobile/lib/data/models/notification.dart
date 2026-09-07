import 'package:flutter/foundation.dart';

/// إشعار مستخدم — يحمل معرّفات المصدر للتنقل عند النقر.
@immutable
class LuxNotification {
  const LuxNotification({
    required this.id,
    required this.message,
    required this.createdAt,
    this.kind,
    this.title,
    this.isRead = false,
    this.conversationId,
    this.viewingRequestId,
    this.propertyId,
    this.actorName,
    this.actorAvatarUrl,
  });

  final String id;
  final String message;
  final DateTime createdAt;
  final String? kind;
  final String? title;
  final bool isRead;
  final int? conversationId;
  final int? viewingRequestId;
  final int? propertyId;
  final String? actorName;
  final String? actorAvatarUrl;

  bool get isMessage =>
      kind == 'message_received' || kind == 'message' || kind == 'message_sent';

  bool get isViewingRequest =>
      kind == 'viewing_request_created' ||
      kind == 'viewing_request_updated' ||
      kind == 'viewing_request';

  factory LuxNotification.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return LuxNotification(
      id: json['id'] as String? ?? '',
      message: data['message'] as String? ?? 'لديك إشعار جديد.',
      kind: data['kind'] as String?,
      title: data['title'] as String?,
      isRead: json['read_at'] != null,
      createdAt:
          DateTime.tryParse(json['created_at'] as String? ?? '') ??
          DateTime.now(),
      conversationId: (data['conversation_id'] as num?)?.toInt(),
      viewingRequestId: (data['viewing_request_id'] as num?)?.toInt(),
      propertyId: (data['property_id'] as num?)?.toInt(),
      actorName: data['actor_name'] as String?,
      actorAvatarUrl: data['actor_avatar_url'] as String?,
    );
  }
}