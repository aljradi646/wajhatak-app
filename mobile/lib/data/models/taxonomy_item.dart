import 'package:flutter/foundation.dart';

/// نوع عقار أو ميزة (taxonomy) قادمة من API.
@immutable
class TaxonomyItem {
  const TaxonomyItem({
    required this.id,
    required this.name,
    this.slug,
    this.icon,
  });

  final int id;
  final String name;
  final String? slug;
  final String? icon;

  factory TaxonomyItem.fromJson(Map<String, dynamic> json) => TaxonomyItem(
    id: json['id'] as int,
    name: json['name_ar'] as String? ?? json['name_en'] as String? ?? '',
    slug: json['slug'] as String?,
    icon: json['icon'] as String?,
  );

  Map<String, dynamic> toJson() =>
      {'id': id, 'name': name, if (slug != null) 'slug': slug};
}
