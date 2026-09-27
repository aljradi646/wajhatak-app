import 'package:flutter/foundation.dart';

/// عقار مُعاد من المساعد الذكي — مصدره فهرس البحث المتزامن مع قاعدة البيانات.
/// كل الحقوق تأتي من الخادم؛ لا شيء يُصنع محليًا.
@immutable
class AiPropertyResult {
  const AiPropertyResult({
    required this.propertyId,
    required this.title,
    required this.type,
    required this.transactionType,
    required this.isFurnished,
    required this.available,
    this.typeSlug,
    this.city,
    this.district,
    this.neighborhood,
    this.price,
    this.currency,
    this.area,
    this.bedrooms,
    this.bathrooms,
    this.isNew = false,
    this.isFeatured = false,
    this.imageUrl,
    this.matchScore = 0,
  });

  final int propertyId;
  final String title;
  final String? type;
  final String? typeSlug;
  final String transactionType;
  final String? city;
  final String? district;
  final String? neighborhood;
  final double? price;
  final String? currency;
  final double? area;
  final int? bedrooms;
  final int? bathrooms;
  final bool isFurnished;
  final bool isNew;
  final bool isFeatured;
  final bool available;
  final String? imageUrl;
  final double matchScore;

  bool get isRent => transactionType == 'rent';

  String get transactionLabel => isRent ? 'للإيجار' : 'للبيع';

  String get locationLabel {
    final parts = [
      if (district != null && district!.isNotEmpty) district!,
      if (neighborhood != null && neighborhood!.isNotEmpty) neighborhood!,
      if (city != null && city!.isNotEmpty) city!,
    ];
    return parts.isEmpty ? 'الموقع غير محدد' : parts.join(' - ');
  }

  factory AiPropertyResult.fromJson(Map<String, dynamic> json) {
    return AiPropertyResult(
      propertyId: json['property_id'] as int,
      title: json['title'] as String? ?? '',
      type: json['type'] as String?,
      typeSlug: json['type_slug'] as String?,
      transactionType: json['transaction_type'] as String? ?? 'sale',
      city: json['city'] as String?,
      district: json['district'] as String?,
      neighborhood: json['neighborhood'] as String?,
      price: (json['price'] as num?)?.toDouble(),
      currency: json['currency'] as String?,
      area: (json['area'] as num?)?.toDouble(),
      bedrooms: json['bedrooms'] as int?,
      bathrooms: json['bathrooms'] as int?,
      isFurnished: json['is_furnished'] as bool? ?? false,
      isNew: json['is_new'] as bool? ?? false,
      isFeatured: json['is_featured'] as bool? ?? false,
      available: json['available'] as bool? ?? true,
      imageUrl: json['image_url'] as String?,
      matchScore: (json['match_score'] as num?)?.toDouble() ?? 0,
    );
  }
}

/// إعدادات واجهة المساعد من الخادم (بلا أي أسرار).
@immutable
class AiBootstrap {
  const AiBootstrap({
    required this.enabled,
    required this.assistantName,
    required this.welcomeMessage,
    required this.suggestions,
  });

  final bool enabled;
  final String assistantName;
  final String welcomeMessage;
  final List<String> suggestions;

  factory AiBootstrap.fromJson(Map<String, dynamic> json) => AiBootstrap(
    enabled: json['enabled'] as bool? ?? true,
    assistantName: json['assistant_name'] as String? ?? 'مساعد وجهتك',
    welcomeMessage:
        json['welcome_message'] as String? ?? 'كيف أخدمك اليوم؟',
    suggestions: (json['suggestions'] as List<dynamic>? ?? const [])
        .map((e) => e.toString())
        .toList(growable: false),
  );
}

/// رسالة داخل محادثة المساعد.
@immutable
class AiChatMessage {
  const AiChatMessage({
    required this.id,
    required this.role,
    required this.content,
    this.properties = const [],
    this.status = 'ok',
    this.createdAt,
  });

  final int id;
  final String role; // user | assistant
  final String content;
  final List<AiPropertyResult> properties;
  final String status; // ok | blocked | error
  final DateTime? createdAt;

  bool get isUser => role == 'user';

  factory AiChatMessage.local({
    required bool isUser,
    required String content,
    List<AiPropertyResult> properties = const [],
    String status = 'ok',
  }) {
    final stamp = DateTime.now().microsecondsSinceEpoch;
    return AiChatMessage(
      id: isUser ? -stamp : stamp,
      role: isUser ? 'user' : 'assistant',
      content: content,
      properties: properties,
      status: status,
      createdAt: DateTime.now(),
    );
  }
}
