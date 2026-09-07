import 'package:flutter/foundation.dart';

/// طلب معاينة عقار.
@immutable
class ViewingRequestItem {
  const ViewingRequestItem({
    required this.id,
    required this.title,
    required this.status,
    required this.date,
    this.time,
    this.clientName,
    this.clientAvatarUrl,
    this.notes,
    this.propertyId,
  });

  final int id;
  final String title;
  final String status;
  final DateTime date;
  final String? time;
  final String? clientName;
  final String? clientAvatarUrl;
  final String? notes;
  final int? propertyId;

  bool get isPending => status == 'pending';
  bool get isConfirmed => status == 'confirmed';
  bool get isRejected => status == 'rejected';
  bool get isCancelled => status == 'cancelled';
  bool get isCompleted => status == 'completed';

  String get statusLabel => switch (status) {
    'pending' => 'بانتظار الرد',
    'confirmed' => 'مؤكد',
    'rejected' => 'مرفوض',
    'cancelled' => 'ملغي',
    'completed' => 'مكتمل',
    _ => status,
  };

  factory ViewingRequestItem.fromJson(Map<String, dynamic> json) {
    final property = json['property'] as Map<String, dynamic>?;
    final client = json['client'] as Map<String, dynamic>?;
    return ViewingRequestItem(
      id: json['id'] as int,
      title: property?['title'] as String? ?? 'عقار',
      status: json['status'] as String? ?? 'pending',
      date:
          DateTime.tryParse(json['scheduled_date'] as String? ?? '') ??
          DateTime.now(),
      time: json['scheduled_time'] as String?,
      clientName: client?['name'] as String?,
      clientAvatarUrl: client?['avatar_url'] as String?,
      notes: json['notes'] as String?,
      propertyId: property?['id'] as int?,
    );
  }
}
