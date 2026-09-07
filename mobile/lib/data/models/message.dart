import 'package:flutter/foundation.dart';


/// بطاقة العقار السياقية داخل رسالة محادثة .
@immutable
class ChatMessageProperty {
  const ChatMessageProperty({
    required this.id,
    required this.title,
    required this.price,
    required this.currency,
    this.transactionType,
    this.area,
    this.bedrooms,
    this.bathrooms,
    this.city,
    this.district,
    this.coverUrl,
  });

  final int id;
  final String title;
  final double price;
  final String currency;
  final String? transactionType;
  final double? area;
  final int? bedrooms;
  final int? bathrooms;
  final String? city;
  final String? district;
  final String? coverUrl;

  factory ChatMessageProperty.fromJson(Map<String, dynamic> json) {
    final location = json['location'] as Map<String, dynamic>?;
    return ChatMessageProperty(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      currency: json['currency'] as String? ?? 'YER',
      transactionType: json['transaction_type'] as String?,
      area: (json['area'] as num?)?.toDouble(),
      bedrooms: json['bedrooms'] as int?,
      bathrooms: json['bathrooms'] as int?,
      city: location?['city'] as String?,
      district: location?['district'] as String?,
      coverUrl: json['cover_url'] as String?,
    );
  }
}

/// رسالة داخل محادثة — نصية أو بطاقة عقار.
@immutable
class ChatMessage {
  const ChatMessage({
    required this.id,
    required this.body,
    required this.senderId,
    required this.createdAt,
    this.readAt,
    this.messageType = 'text',
    this.propertyId,
    this.property,
  });

  final int id;
  final String body;
  final int senderId;
  final DateTime createdAt;
  final DateTime? readAt;
  final String messageType;
  final int? propertyId;
  final ChatMessageProperty? property;

  bool get isPropertyCard => messageType == 'property' && property != null;

  bool get isRead => readAt != null;

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    final propertyData = json['property'] as Map<String, dynamic>?;
    return ChatMessage(
      id: json['id'] as int,
      body: json['body'] as String? ?? '',
      senderId: json['sender_id'] as int,
      createdAt:
          DateTime.tryParse(json['created_at'] as String? ?? '') ??
          DateTime.now(),
      readAt: DateTime.tryParse(json['read_at'] as String? ?? ''),
      messageType: json['message_type'] as String? ?? 'text',
      propertyId: json['property_id'] as int?,
      property: propertyData != null
          ? ChatMessageProperty.fromJson(propertyData)
          : null,
    );
  }
}
