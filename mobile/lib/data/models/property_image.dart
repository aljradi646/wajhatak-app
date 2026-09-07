import 'package:flutter/foundation.dart';

/// صورة عقار قادمة من التخزين العام للخادم.
@immutable
class PropertyImage {
  const PropertyImage({
    required this.id,
    required this.url,
    this.isCover = false,
    this.altText,
    this.sortOrder = 0,
  });

  final int id;
  final String url;
  final bool isCover;
  final String? altText;
  final int sortOrder;

  factory PropertyImage.fromJson(Map<String, dynamic> json) => PropertyImage(
    id: json['id'] as int,
    url: json['url'] as String? ?? '',
    isCover: json['is_cover'] as bool? ?? false,
    altText: json['alt_text'] as String?,
    sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'url': url,
    'is_cover': isCover,
    if (altText != null) 'alt_text': altText,
    'sort_order': sortOrder,
  };
}
