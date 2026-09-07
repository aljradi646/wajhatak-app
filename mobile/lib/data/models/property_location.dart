import 'package:flutter/foundation.dart';

/// موقع العقار — قد يرتبط بهيكل الدولة/المحافظة/المدينة/المنطقة أو يكون حرًا.
@immutable
class PropertyLocation {
  const PropertyLocation({
    this.countryId,
    this.regionId,
    this.cityId,
    this.areaId,
    this.country,
    this.region,
    this.city,
    this.district,
    this.neighborhood,
    this.address,
    this.latitude,
    this.longitude,
  });

  final int? countryId;
  final int? regionId;
  final int? cityId;
  final int? areaId;
  final String? country;
  final String? region;
  final String? city;
  final String? district;
  final String? neighborhood;
  final String? address;
  final double? latitude;
  final double? longitude;

  String get shortLabel => [
    district,
    city,
  ].whereType<String>().where((v) => v.isNotEmpty).join('، ');

  String get fullLabel => [
    neighborhood,
    district,
    city,
    country,
  ].whereType<String>().where((v) => v.isNotEmpty).join('، ');

  bool get hasCoordinates => latitude != null && longitude != null;

  factory PropertyLocation.fromJson(Map<String, dynamic> json) =>
      PropertyLocation(
        countryId: json['country_id'] as int?,
        regionId: json['region_id'] as int?,
        cityId: json['city_id'] as int?,
        areaId: json['area_id'] as int?,
        country: json['country'] as String?,
        region: json['region'] as String?,
        city: json['city'] as String?,
        district: json['district'] as String?,
        neighborhood: json['neighborhood'] as String?,
        address: json['address'] as String?,
        latitude: (json['latitude'] as num?)?.toDouble(),
        longitude: (json['longitude'] as num?)?.toDouble(),
      );

  Map<String, dynamic> toJson() => {
    if (countryId != null) 'country_id': countryId,
    if (regionId != null) 'region_id': regionId,
    if (cityId != null) 'city_id': cityId,
    if (areaId != null) 'area_id': areaId,
    if (country != null) 'country': country,
    if (region != null) 'region': region,
    'city': city,
    if (district != null) 'district': district,
    if (neighborhood != null) 'neighborhood': neighborhood,
    if (address != null) 'address': address,
    if (latitude != null) 'latitude': latitude,
    if (longitude != null) 'longitude': longitude,
  };
}
