import 'package:flutter/foundation.dart';

/// عنصر في التسلسل الهرمي للمواقع: دولة ← محافظة ← مدينة ← منطقة.
@immutable
class LocationItem {
  const LocationItem({
    required this.id,
    required this.name,
    this.parentId,
    this.code,
    this.currencyCode,
    this.isActive = true,
  });

  final int id;
  final String name;
  final int? parentId;
  final String? code;
  final String? currencyCode;
  final bool isActive;

  factory LocationItem.fromJson(Map<String, dynamic> json) => LocationItem(
    id: json['id'] as int,
    name: json['name_ar'] as String? ?? json['name_en'] as String? ?? '',
    parentId:
        json['country_id'] as int? ??
        json['region_id'] as int? ??
        json['city_id'] as int?,
    code: json['code'] as String?,
    currencyCode: json['currency_code'] as String?,
    isActive: json['is_active'] as bool? ?? true,
  );

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is LocationItem && id == other.id;

  @override
  int get hashCode => id;
}
