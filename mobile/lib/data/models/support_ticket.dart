import 'package:flutter/foundation.dart';
@immutable
class SupportTicketMessage {
  const SupportTicketMessage({required this.body,required this.senderRole,required this.createdAt,this.senderName,this.emailStatus});
  final String body; final String senderRole; final DateTime createdAt; final String? senderName; final String? emailStatus;
  factory SupportTicketMessage.fromJson(Map<String,dynamic> json)=>SupportTicketMessage(
    body:json['body'] as String? ?? '', senderRole:json['sender_role'] as String? ?? 'user',
    senderName:(json['user'] as Map<String,dynamic>?)?['name'] as String?,
    createdAt:DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
    emailStatus:json['email_status'] as String?,
  );
}
@immutable
class SupportTicket {
  const SupportTicket({required this.id,required this.reference,required this.subject,required this.category,required this.status,required this.createdAt,required this.updatedAt,this.priority='normal',this.messages=const []});
  final int id; final String reference; final String subject; final String category; final String status; final String priority; final DateTime createdAt; final DateTime updatedAt; final List<SupportTicketMessage> messages;
  bool get isClosed=>status=='closed';
  String get statusLabel=>switch(status){'open'=>'مفتوح','in_progress'=>'قيد المعالجة','waiting_user'=>'بانتظارك','resolved'=>'تم الحل','closed'=>'مغلق',_=>status};
  factory SupportTicket.fromJson(Map<String,dynamic> json)=>SupportTicket(
    id:(json['id'] as num).toInt(),reference:json['reference'] as String? ?? '',subject:json['subject'] as String? ?? '',
    category:json['category'] as String? ?? 'other',status:json['status'] as String? ?? 'open',priority:json['priority'] as String? ?? 'normal',
    createdAt:DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
    updatedAt:DateTime.tryParse(json['updated_at'] as String? ?? '') ?? DateTime.now(),
    messages:(json['messages'] as List<dynamic>? ?? const []).whereType<Map<String,dynamic>>().map(SupportTicketMessage.fromJson).toList(),
  );
}
