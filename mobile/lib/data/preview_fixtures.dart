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
    return properties
        .where((property) {
          final searchable =
              '${property.title} ${property.location?.shortLabel ?? ''} ${property.typeName ?? ''}'
                  .toLowerCase();
          return (normalizedSearch.isEmpty ||
                  searchable.contains(normalizedSearch)) &&
              (query.transactionType == null ||
                  property.transactionType == query.transactionType);
        })
        .toList(growable: false);
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
