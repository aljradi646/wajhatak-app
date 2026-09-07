import '../api_client.dart';
import '../models/models.dart';

/// الأنواع والمزايا والمواقع والعملات — كلها من الخادم (data-driven).
class TaxonomyRepository {
  TaxonomyRepository(this._api);

  final LuxApiClient _api;

  Future<List<TaxonomyItem>> propertyTypes() async {
    final json = await _api.get('/property-types');
    return _taxonomy(json);
  }

  Future<List<TaxonomyItem>> features() async {
    final json = await _api.get('/features');
    return _taxonomy(json);
  }

  Future<List<Currency>> currencies() async {
    final json = await _api.get('/currencies');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(Currency.fromJson)
        .toList();
  }

  Future<List<LocationItem>> countries() => _locations('/countries');

  Future<List<LocationItem>> regions(int countryId) =>
      _locations('/regions', {'country_id': countryId});

  Future<List<LocationItem>> cities(int regionId) =>
      _locations('/cities', {'region_id': regionId});

  Future<List<LocationItem>> areas(int cityId) =>
      _locations('/areas', {'city_id': cityId});

  List<TaxonomyItem> _taxonomy(Map<String, dynamic> json) =>
      (json['data'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(TaxonomyItem.fromJson)
          .toList(growable: false);

  Future<List<LocationItem>> _locations(
    String path, [
    Map<String, dynamic>? query,
  ]) async {
    final json = await _api.get(path, query: query);
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(LocationItem.fromJson)
        .toList(growable: false);
  }
}
