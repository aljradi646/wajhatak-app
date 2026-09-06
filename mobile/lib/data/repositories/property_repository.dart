import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../api_client.dart';
import '../models/models.dart';
import '../../core/utils/image_compressor.dart';

/// صفحة واحدة من بيانات عقارات مضمّنة بالترقيم.
@immutable
class PropertyPageResult {
  const PropertyPageResult({
    required this.items,
    required this.hasMore,
    required this.nextPage,
  });

  final List<LuxProperty> items;
  final bool hasMore;
  final int nextPage;
}

/// ملف الوكيل (بياناته + صفحته الأولى من المنشورات).
@immutable
class AgentProfileResult {
  const AgentProfileResult({required this.agent, required this.page});

  final PropertyAgent agent;
  final PropertyPageResult page;
}

/// العقارات والمفضلة والصور.
class PropertyRepository {
  PropertyRepository(this._api);

  final LuxApiClient _api;
  final ImageCompressor _compressor = ImageCompressor();

  Future<List<LuxProperty>> list([
    PropertyQuery query = const PropertyQuery(),
  ]) async {
    final json = await _api.get('/properties', query: query.toParameters());
    return _propertyList(json);
  }

  Future<LuxProperty> detail(int id) async {
    final json = await _api.get('/properties/$id');
    return LuxProperty.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<AgentProfileResult> agentProfile(
    int agentId, {
    int perPage = 24,
  }) async {
    final json = await _api.get(
      '/agents/$agentId',
      query: {'per_page': perPage},
    );
    final data =
        json['data'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final agent = PropertyAgent.fromJson(data['agent'] as Map<String, dynamic>);
    return AgentProfileResult(
      agent: agent,
      page: _parsePage(data['properties']),
    );
  }

  Future<PropertyPageResult> agentProperties(
    int agentId, {
    int page = 1,
    int perPage = 24,
  }) => _api
      .get(
        '/agents/$agentId/properties',
        query: {'page': page, 'per_page': perPage},
      )
      .then(_parsePage);

  Future<List<LuxProperty>> favorites() async {
    final json = await _api.get('/favorites');
    return _propertyList(json);
  }

  Future<List<LuxProperty>> mine() async {
    final json = await _api.get('/agent/properties');
    return _propertyList(json);
  }

  Future<void> setFavorite(int propertyId, bool shouldFavorite) async {
    if (shouldFavorite) {
      await _api.post('/favorites', data: {'property_id': propertyId});
    } else {
      await _api.delete('/favorites/$propertyId');
    }
  }

  Future<void> create(Map<String, dynamic> data, List<File> images) async {
    final map = <String, dynamic>{};
    _flattenToForm(map, '', data);
    if (images.isNotEmpty) {
      for (var i = 0; i < images.length; i++) {
        // ضغط سريع قبل الرفع لتجنّب مهلة الخادم عند الصور الكبيرة.
        final compressed = await _compressor.compress(images[i]);
        map['images[$i]'] = await MultipartFile.fromFile(compressed);
      }
    }
    await _api.post('/properties', data: FormData.fromMap(map));
  }

  Future<LuxProperty> update(int propertyId, Map<String, dynamic> data) async {
    final safe = _normalizeForJson(data);
    final json = await _api.patch('/properties/$propertyId', data: safe);
    return LuxProperty.fromJson(json['data'] as Map<String, dynamic>);
  }

  Future<void> delete(int propertyId) => _api.delete('/properties/$propertyId');

  Future<void> uploadImage(int propertyId, File image) async {
    final compressed = await _compressor.compress(image);
    final formData = FormData.fromMap({
      'image': await MultipartFile.fromFile(compressed),
    });
    await _api.post('/properties/$propertyId/images', data: formData);
  }

  Future<void> deleteImage(int propertyId, int imageId) async {
    await _api.delete('/properties/$propertyId/images/$imageId');
  }

  Future<void> setCoverImage(int propertyId, int imageId) async {
    await _api.post('/properties/$propertyId/images/$imageId/cover');
  }

  static void _flattenToForm(
    Map<String, dynamic> out,
    String prefix,
    dynamic value,
  ) {
    if (value is Map<String, dynamic>) {
      for (final entry in value.entries) {
        final key = prefix.isEmpty ? entry.key : '$prefix[${entry.key}]';
        _flattenToForm(out, key, entry.value);
      }
    } else if (value is List) {
      for (var i = 0; i < value.length; i++) {
        _flattenToForm(out, '$prefix[$i]', value[i]);
      }
    } else if (value is bool) {
      out[prefix] = value ? 1 : 0;
    } else if (value != null) {
      out[prefix] = value;
    }
  }

  static Map<String, dynamic> _normalizeForJson(Map<String, dynamic> data) {
    return data.map((key, value) {
      if (value is bool) return MapEntry(key, value ? 1 : 0);
      if (value is Map<String, dynamic>) {
        return MapEntry(key, _normalizeForJson(value));
      }
      return MapEntry(key, value);
    });
  }

  List<LuxProperty> _propertyList(Map<String, dynamic> json) =>
      (json['data'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(LuxProperty.fromJson)
          .toList(growable: false);

  /// يفكّك استجابة "data + meta" الخاصة بـ Laravel Paginator.
  PropertyPageResult _parsePage(Object? container) {
    final map = container is Map<String, dynamic>
        ? container
        : const <String, dynamic>{};
    final items = (map['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(LuxProperty.fromJson)
        .toList(growable: false);
    final meta =
        map['meta'] as Map<String, dynamic>? ?? const <String, dynamic>{};
    final current = (meta['current_page'] as num?)?.toInt() ?? 1;
    final last = (meta['last_page'] as num?)?.toInt() ?? current;
    return PropertyPageResult(
      items: items,
      hasMore: current < last,
      nextPage: current + 1,
    );
  }
}
