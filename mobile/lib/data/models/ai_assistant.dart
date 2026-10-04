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
    this.referenceCode,
    this.address,
    this.agentPhone,
    this.isAlternative = false,
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
  final String? referenceCode;
  final String? address;
  final String? agentPhone;
  final bool isAlternative;
  final double matchScore;

  // The API may supply distance later; keep the model/UI contract nullable.
  double? get distanceKm => null;

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
      referenceCode: json['reference_code'] as String?,
      address: json['address'] as String?,
      agentPhone: json['agent_phone'] as String?,
      isAlternative: json['is_alternative'] as bool? ?? false,
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
    welcomeMessage: json['welcome_message'] as String? ?? 'كيف أخدمك اليوم؟',
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
    this.responseType = 'text',
    this.actions = const [],
    this.createdAt,
  });

  final int id;
  final String role; // user | assistant
  final String content;
  final List<AiPropertyResult> properties;
  final String status; // ok | blocked | error | streaming
  final String responseType;
  final List<Map<String, dynamic>> actions;
  final DateTime? createdAt;

  bool get isUser => role == 'user';

  factory AiChatMessage.local({
    required bool isUser,
    required String content,
    List<AiPropertyResult> properties = const [],
    String status = 'ok',
    String responseType = 'text',
    List<Map<String, dynamic>> actions = const [],
  }) {
    final stamp = DateTime.now().microsecondsSinceEpoch;
    return AiChatMessage(
      id: isUser ? -stamp : stamp,
      role: isUser ? 'user' : 'assistant',
      content: content,
      properties: properties,
      status: status,
      responseType: responseType,
      actions: actions,
      createdAt: DateTime.now(),
    );
  }

  AiChatMessage copyWith({
    int? id,
    String? role,
    String? content,
    List<AiPropertyResult>? properties,
    String? status,
    String? responseType,
    List<Map<String, dynamic>>? actions,
    DateTime? createdAt,
  }) => AiChatMessage(
    id: id ?? this.id,
    role: role ?? this.role,
    content: content ?? this.content,
    properties: properties ?? this.properties,
    status: status ?? this.status,
    responseType: responseType ?? this.responseType,
    actions: actions ?? this.actions,
    createdAt: createdAt ?? this.createdAt,
  );

  factory AiChatMessage.fromJson(Map<String, dynamic> json) {
    return AiChatMessage(
      id: json['id'] as int,
      role: json['role'] as String,
      content: json['content'] as String,
      properties: (json['properties'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(AiPropertyResult.fromJson)
          .toList(growable: false),
      status: json['status'] as String? ?? 'ok',
      responseType: json['response_type'] as String? ?? 'text',
      actions: (json['actions'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(growable: false),
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'] as String)
          : null,
    );
  }
}

/// عنصر في قائمة المحادثات.
@immutable
class AiConversationItem {
  const AiConversationItem({
    required this.id,
    required this.lastMessageAt,
    this.title = 'محادثة جديدة',
    this.isPinned = false,
    this.messageCount = 0,
  });

  final int id;
  final String title;
  final bool isPinned;
  final DateTime lastMessageAt;
  final int messageCount;

  factory AiConversationItem.fromJson(Map<String, dynamic> json) {
    return AiConversationItem(
      id: json['id'] as int,
      title: json['title'] as String? ?? 'محادثة جديدة',
      isPinned: json['is_pinned'] as bool? ?? false,
      lastMessageAt: DateTime.parse(json['last_message_at'] as String? ?? DateTime.now().toIso8601String()),
      messageCount: json['messages_count'] as int? ?? json['message_count'] as int? ?? 0,
    );
  }
}


@immutable
class AiStreamEvent {
  const AiStreamEvent({
    required this.event,
    this.id,
    this.delta,
    this.data,
    this.message,
    this.statusCode,
  });

  final String event;
  final int? id;
  final String? delta;
  final Map<String, dynamic>? data;
  final String? message;
  final int? statusCode;

  factory AiStreamEvent.fromJson(Map<String, dynamic> json) => AiStreamEvent(
    event: json['event']?.toString() ?? 'message',
    id: (json['id'] as num?)?.toInt(),
    delta: json['delta']?.toString(),
    data: json['data'] is Map<String, dynamic>
        ? json['data'] as Map<String, dynamic>
        : json['result'] is Map<String, dynamic>
            ? json['result'] as Map<String, dynamic>
            : null,
    message: json['message']?.toString(),
    statusCode: (json['status_code'] as num?)?.toInt(),
  );
}
