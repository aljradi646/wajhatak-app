import 'package:flutter/foundation.dart';

/// معايير بحث/تصفية العقارات المرسلة إلى API.
@immutable
class PropertyQuery {
  const PropertyQuery({
    this.search = '',
    this.transactionType,
    this.sort,
    this.city,
    this.district,
    this.propertyType,
    this.minPrice,
    this.maxPrice,
    this.minArea,
    this.bedrooms,
    this.bathrooms,
    this.isFurnished,
  });

  final String search;
  final String? transactionType;
  final String? sort;
  final String? city;
  final String? district;
  final String? propertyType;
  final double? minPrice;
  final double? maxPrice;
  final double? minArea;
  final int? bedrooms;
  final int? bathrooms;
  final bool? isFurnished;

  bool get isEmpty =>
      search.trim().isEmpty &&
      transactionType == null &&
      sort == null &&
      city == null &&
      district == null &&
      propertyType == null &&
      minPrice == null &&
      maxPrice == null &&
      minArea == null &&
      bedrooms == null &&
      bathrooms == null &&
      isFurnished == null;

  Map<String, dynamic> toParameters() => {
    if (search.trim().isNotEmpty) 'q': search.trim(),
    if (transactionType != null) 'transaction_type': transactionType,
    if (sort != null) 'sort': sort,
    if (city != null && city!.isNotEmpty) 'city': city,
    if (district != null && district!.isNotEmpty) 'district': district,
    if (propertyType != null && propertyType!.isNotEmpty)
      'property_type': propertyType,
    if (minPrice != null) 'min_price': minPrice,
    if (maxPrice != null) 'max_price': maxPrice,
    if (minArea != null) 'min_area': minArea,
    if (bedrooms != null) 'bedrooms': bedrooms,
    if (bathrooms != null) 'bathrooms': bathrooms,
    if (isFurnished != null && isFurnished!) 'is_furnished': 1,
  };

  PropertyQuery copyWith({
    String? search,
    String? transactionType,
    String? sort,
    String? city,
    String? district,
    String? propertyType,
    double? minPrice,
    double? maxPrice,
    double? minArea,
    int? bedrooms,
    int? bathrooms,
    bool? isFurnished,
  }) => PropertyQuery(
    search: search ?? this.search,
    transactionType: transactionType ?? this.transactionType,
    sort: sort ?? this.sort,
    city: city ?? this.city,
    district: district ?? this.district,
    propertyType: propertyType ?? this.propertyType,
    minPrice: minPrice ?? this.minPrice,
    maxPrice: maxPrice ?? this.maxPrice,
    minArea: minArea ?? this.minArea,
    bedrooms: bedrooms ?? this.bedrooms,
    bathrooms: bathrooms ?? this.bathrooms,
    isFurnished: isFurnished ?? this.isFurnished,
  );

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PropertyQuery &&
          runtimeType == other.runtimeType &&
          search == other.search &&
          transactionType == other.transactionType &&
          sort == other.sort &&
          city == other.city &&
          district == other.district &&
          propertyType == other.propertyType &&
          minPrice == other.minPrice &&
          maxPrice == other.maxPrice &&
          minArea == other.minArea &&
          bedrooms == other.bedrooms &&
          bathrooms == other.bathrooms &&
          isFurnished == other.isFurnished;

  @override
  int get hashCode => Object.hash(
    search,
    transactionType,
    sort,
    city,
    district,
    propertyType,
    minPrice,
    maxPrice,
    minArea,
    bedrooms,
    bathrooms,
    isFurnished,
  );
}
