import 'package:flutter/foundation.dart';

import 'property_agent.dart';
import 'property_image.dart';
import 'property_location.dart';

/// العقار — مصدر الحقيقة القادم من Laravel API.
@immutable
class LuxProperty {
  const LuxProperty({
    required this.id,
    required this.title,
    required this.price,
    required this.currency,
    required this.transactionType,
    this.slug,
    this.referenceCode,
    this.description,
    this.area,
    this.bedrooms,
    this.bathrooms,
    this.parkingSpaces,
    this.isFurnished = false,
    this.isNew = false,
    this.isFeatured = false,
    this.isFavorited = false,
    this.status,
    this.publishedAt,
    this.typeId,
    this.typeName,
    this.location,
    this.agent,
    this.images = const [],
    this.featureNames = const [],
    this.featureIds = const [],
  });

  final int id;
  final String title;
  final double price;
  final String currency;
  final String transactionType;
  final String? slug;
  final String? referenceCode;
  final String? description;
  final double? area;
  final int? bedrooms;
  final int? bathrooms;
  final int? parkingSpaces;
  final bool isFurnished;
  final bool isNew;
  final bool isFeatured;
  final bool isFavorited;
  final String? status;
  final DateTime? publishedAt;
  final int? typeId;
  final String? typeName;
  final PropertyLocation? location;
  final PropertyAgent? agent;
  final List<PropertyImage> images;
  final List<String> featureNames;
  final List<int> featureIds;

  bool get isRent => transactionType == 'rent';

  String get transactionLabel => isRent ? 'للإيجار' : 'للبيع';

  String get coverUrl =>
      images
          .where((image) => image.isCover)
          .map((image) => image.url)
          .firstOrNull ??
      (images.isEmpty ? '' : images.first.url);

  LuxProperty copyWith({bool? isFavorited}) => LuxProperty(
    id: id,
    title: title,
    price: price,
    currency: currency,
    transactionType: transactionType,
    slug: slug,
    referenceCode: referenceCode,
    description: description,
    area: area,
    bedrooms: bedrooms,
    bathrooms: bathrooms,
    parkingSpaces: parkingSpaces,
    isFurnished: isFurnished,
    isNew: isNew,
    isFeatured: isFeatured,
    isFavorited: isFavorited ?? this.isFavorited,
    status: status,
    publishedAt: publishedAt,
    typeId: typeId,
    typeName: typeName,
    location: location,
    agent: agent,
    images: images,
    featureNames: featureNames,
    featureIds: featureIds,
  );

  factory LuxProperty.fromJson(Map<String, dynamic> json) {
    final type = json['type'] as Map<String, dynamic>?;
    final location = json['location'] as Map<String, dynamic>?;
    final agent = json['agent'] as Map<String, dynamic>?;
    final rawFeatures = (json['features'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .toList();
    final featureNames = rawFeatures
        .map(
          (feature) =>
              feature['name_ar'] as String? ??
              feature['name_en'] as String? ??
              '',
        )
        .where((name) => name.isNotEmpty)
        .toList();
    final featureIds = rawFeatures
        .map((feature) => feature['id'] as int?)
        .whereType<int>()
        .toList();
    return LuxProperty(
      id: json['id'] as int,
      title: json['title'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      currency: json['currency'] as String? ?? 'YER',
      transactionType: json['transaction_type'] as String? ?? 'sale',
      slug: json['slug'] as String?,
      referenceCode: json['reference_code'] as String?,
      description: json['description'] as String?,
      area: (json['area'] as num?)?.toDouble(),
      bedrooms: json['bedrooms'] as int?,
      bathrooms: json['bathrooms'] as int?,
      parkingSpaces: json['parking_spaces'] as int?,
      isFurnished: json['is_furnished'] as bool? ?? false,
      isNew: json['is_new'] as bool? ?? false,
      isFeatured: json['is_featured'] as bool? ?? false,
      isFavorited: json['is_favorited'] as bool? ?? false,
      status: json['status'] as String?,
      publishedAt: DateTime.tryParse(json['published_at'] as String? ?? ''),
      typeId: type?['id'] as int?,
      typeName: type?['name_ar'] as String? ?? type?['name_en'] as String?,
      location: location == null ? null : PropertyLocation.fromJson(location),
      agent: agent == null ? null : PropertyAgent.fromJson(agent),
      images: (json['images'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(PropertyImage.fromJson)
          .toList(),
      featureNames: featureNames,
      featureIds: featureIds,
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'title': title,
    'price': price,
    'currency': currency,
    'transaction_type': transactionType,
    'description': description,
    'area': area,
    'bedrooms': bedrooms,
    'bathrooms': bathrooms,
    'parking_spaces': parkingSpaces,
    'is_furnished': isFurnished,
    'is_new': isNew,
    'is_favorited': isFavorited,
    'status': status,
    'type_id': typeId,
    'type_name': typeName,
    'location': location?.toJson(),
    'agent': agent?.toJson(),
    'images': images.map((image) => image.toJson()).toList(),
    'features': featureNames,
  };
}
