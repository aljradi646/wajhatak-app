import 'dart:convert';

import 'package:flutter/services.dart';

import 'models/models.dart';

/// Development-only fixture reader for visual regression and UX review.
/// It is instantiated exclusively through the AppConfig debug gate in providers.
class PreviewFixtureRepository {
  PreviewFixtureRepository._(this._data);

  static const assetPath = 'assets/fixtures/ui_preview.json';
  static Future<PreviewFixtureRepository>? _cached;

  final Map<String, dynamic> _data;

  static Future<PreviewFixtureRepository> load() {
    return _cached ??= rootBundle.loadString(assetPath).then((source) {
      final value = jsonDecode(source);
      if (value is! Map<String, dynamic>) {
        throw const FormatException('صيغة بيانات المعاينة غير صالحة.');
      }
      return PreviewFixtureRepository._(value);
    });
  }

  SessionData sessionForRole(String requestedRole) {
    final sessions = _map('sessions');
    final key = requestedRole == 'agent' ? 'agent' : 'client';
    final value = sessions[key] as Map<String, dynamic>? ?? const {};
    return SessionData(
      token: value['token'] as String? ?? 'ui-preview-token',
      user: LuxUser.fromJson(_asMap(value['user'])),
    );
  }

  Future<List<LuxProperty>> list([
    PropertyQuery query = const PropertyQuery(),
  ]) async {
    final normalizedSearch = query.search.trim().toLowerCase();
    final matchingTypeIds = _list('property_types')
        .where((item) => item['slug'] == query.propertyType)
        .map((item) => int.tryParse('${item['id']}'))
        .whereType<int>()
        .toSet();

    final result = properties.where((property) {
      final location = property.location;
      final searchable =
          '${property.title} ${property.description ?? ''} ${location?.fullLabel ?? ''} ${property.typeName ?? ''}'
              .toLowerCase();
      final matchesSearch = normalizedSearch.isEmpty ||
          searchable.contains(normalizedSearch);
      final matchesType = query.propertyType == null ||
          (property.typeId != null && matchingTypeIds.contains(property.typeId));
      final matchesLocation =
          (query.city == null || location?.city == query.city) &&
          (query.district == null || location?.district == query.district) &&
          (query.neighborhood == null ||
              location?.neighborhood == query.neighborhood);
      final matchesNumbers =
          (query.minPrice == null || property.price >= query.minPrice!) &&
          (query.maxPrice == null || property.price <= query.maxPrice!) &&
          (query.minArea == null ||
              (property.area != null && property.area! >= query.minArea!)) &&
          (query.maxArea == null ||
              (property.area != null && property.area! <= query.maxArea!)) &&
          ((query.bedroomsMin ?? query.bedrooms) == null ||
              (property.bedrooms != null &&
                  property.bedrooms! >= (query.bedroomsMin ?? query.bedrooms)!)) &&
          (query.bedroomsMax == null ||
              (property.bedrooms != null &&
                  property.bedrooms! <= query.bedroomsMax!)) &&
          ((query.bathroomsMin ?? query.bathrooms) == null ||
              (property.bathrooms != null &&
                  property.bathrooms! >= (query.bathroomsMin ?? query.bathrooms)!)) &&
          (query.bathroomsMax == null ||
              (property.bathrooms != null &&
                  property.bathrooms! <= query.bathroomsMax!)) &&
          ((query.parkingSpacesMin ?? query.parkingSpaces) == null ||
              (property.parkingSpaces != null &&
                  property.parkingSpaces! >=
                      (query.parkingSpacesMin ?? query.parkingSpaces)!)) &&
          (query.parkingSpacesMax == null ||
              (property.parkingSpaces != null &&
                  property.parkingSpaces! <= query.parkingSpacesMax!));
      return matchesSearch &&
          (query.transactionType == null ||
              property.transactionType == query.transactionType) &&
          matchesType &&
          matchesLocation &&
          matchesNumbers &&
          (query.isFurnished == null ||
              property.isFurnished == query.isFurnished) &&
          (query.isNew == null || property.isNew == query.isNew) &&
          (query.isFeatured == null || property.isFeatured == query.isFeatured);
    }).toList(growable: true);

    result.sort((a, b) {
      switch (query.sort) {
        case 'price_asc':
          return a.price.compareTo(b.price);
        case 'price_desc':
          return b.price.compareTo(a.price);
        case 'area_asc':
          return _compareNullable<double>(a.area, b.area, descending: false);
        case 'area_desc':
          return _compareNullable<double>(a.area, b.area, descending: true);
        case 'newest':
          return _compareNullable<DateTime>(
            a.publishedAt,
            b.publishedAt,
            descending: true,
          );
        case 'oldest':
          return _compareNullable<DateTime>(
            a.publishedAt,
            b.publishedAt,
            descending: false,
          );
        default:
          final featured = (b.isFeatured ? 1 : 0).compareTo(a.isFeatured ? 1 : 0);
          if (featured != 0) return featured;
          final date = _compareNullable<DateTime>(
            a.publishedAt,
            b.publishedAt,
            descending: true,
          );
          return date != 0 ? date : b.id.compareTo(a.id);
      }
    });

    return result;
  }

  int _compareNullable<T extends Comparable<T>>(
    T? a,
    T? b, {
    required bool descending,
  }) {
    if (a == null) return b == null ? 0 : 1;
    if (b == null) return -1;
    return descending ? b.compareTo(a) : a.compareTo(b);
  }

  List<LuxProperty> get properties =>
      _list('properties').map(LuxProperty.fromJson).toList(growable: false);

  Future<LuxProperty> detail(int id) async {
    return properties.firstWhere(
      (property) => property.id == id,
      orElse: () =>
          throw StateError('العقار المطلوب غير موجود في بيانات المعاينة.'),
    );
  }

  Future<List<LuxProperty>> favorites() async => properties
      .where((property) => property.isFavorited)
      .toList(growable: false);

  Future<List<LuxProperty>> mine() async => properties
      .where((property) => property.agent?.id == 200)
      .toList(growable: false);

  Future<List<TaxonomyItem>> propertyTypes() async => _list(
    'property_types',
  ).map(TaxonomyItem.fromJson).toList(growable: false);

  Future<List<TaxonomyItem>> features() async =>
      _list('features').map(TaxonomyItem.fromJson).toList(growable: false);

  Future<List<ConversationItem>> conversations() async => _list(
    'conversations',
  ).map(ConversationItem.fromJson).toList(growable: false);

  Future<List<ChatMessage>> messages(int conversationId) async {
    final messages = _map('messages');
    final values = messages['$conversationId'] as List<dynamic>? ?? const [];
    return values
        .whereType<Map<String, dynamic>>()
        .map(ChatMessage.fromJson)
        .toList(growable: false);
  }

  Future<List<ViewingRequestItem>> viewingRequests() async => _list(
    'viewing_requests',
  ).map(ViewingRequestItem.fromJson).toList(growable: false);

  Future<List<LuxNotification>> notifications() async => _list(
    'notifications',
  ).map(LuxNotification.fromJson).toList(growable: false);

  List<Map<String, dynamic>> _list(String key) =>
      (_data[key] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(growable: false);

  Map<String, dynamic> _map(String key) => _asMap(_data[key]);

  static Map<String, dynamic> _asMap(Object? value) =>
      value is Map<String, dynamic> ? value : const {};
}
