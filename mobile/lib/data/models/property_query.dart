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
    this.neighborhood,
    this.propertyType,
    this.minPrice,
    this.maxPrice,
    this.minArea,
    this.maxArea,
    this.bedrooms,
    this.bathrooms,
    this.parkingSpaces,
    this.bedroomsMin,
    this.bedroomsMax,
    this.bathroomsMin,
    this.bathroomsMax,
    this.parkingSpacesMin,
    this.parkingSpacesMax,
    this.isFurnished,
    this.isNew,
    this.isFeatured,
  });

  static const Object _unset = Object();

  final String search;
  final String? transactionType;
  final String? sort;
  final String? city;
  final String? district;
  final String? neighborhood;
  final String? propertyType;
  final double? minPrice;
  final double? maxPrice;
  final double? minArea;
  final double? maxArea;

  /// الأسماء القديمة تبقى متاحة كحد أدنى عند عدم إرسال الحد الجديد.
  final int? bedrooms;
  final int? bathrooms;
  final int? parkingSpaces;
  final int? bedroomsMin;
  final int? bedroomsMax;
  final int? bathroomsMin;
  final int? bathroomsMax;
  final int? parkingSpacesMin;
  final int? parkingSpacesMax;

  /// null = بلا فلتر؛ false = فلتر صريح على العقارات غير المفروشة/غير الجديدة.
  final bool? isFurnished;
  final bool? isNew;
  final bool? isFeatured;

  bool get isEmpty =>
      search.trim().isEmpty &&
      (transactionType?.trim().isNotEmpty != true) &&
      (sort?.trim().isNotEmpty != true) &&
      (city?.trim().isNotEmpty != true) &&
      (district?.trim().isNotEmpty != true) &&
      (neighborhood?.trim().isNotEmpty != true) &&
      (propertyType?.trim().isNotEmpty != true) &&
      minPrice == null &&
      maxPrice == null &&
      minArea == null &&
      maxArea == null &&
      bedrooms == null &&
      bathrooms == null &&
      parkingSpaces == null &&
      bedroomsMin == null &&
      bedroomsMax == null &&
      bathroomsMin == null &&
      bathroomsMax == null &&
      parkingSpacesMin == null &&
      parkingSpacesMax == null &&
      isFurnished == null &&
      isNew == null &&
      isFeatured == null;

  Map<String, dynamic> toParameters() => {
    if (search.trim().isNotEmpty) 'q': search.trim(),
    if (transactionType?.trim().isNotEmpty == true)
      'transaction_type': transactionType!.trim(),
    if (sort?.trim().isNotEmpty == true) 'sort': sort!.trim(),
    if (city?.trim().isNotEmpty == true) 'city': city!.trim(),
    if (district?.trim().isNotEmpty == true) 'district': district!.trim(),
    if (neighborhood?.trim().isNotEmpty == true)
      'neighborhood': neighborhood!.trim(),
    if (propertyType?.trim().isNotEmpty == true)
      'property_type': propertyType!.trim(),
    if (minPrice != null) 'min_price': minPrice,
    if (maxPrice != null) 'max_price': maxPrice,
    if (minArea != null) 'min_area': minArea,
    if (maxArea != null) 'max_area': maxArea,
    if (bedroomsMin != null)
      'bedrooms_min': bedroomsMin
    else if (bedrooms != null)
      'bedrooms': bedrooms,
    if (bedroomsMax != null) 'bedrooms_max': bedroomsMax,
    if (bathroomsMin != null)
      'bathrooms_min': bathroomsMin
    else if (bathrooms != null)
      'bathrooms': bathrooms,
    if (bathroomsMax != null) 'bathrooms_max': bathroomsMax,
    if (parkingSpacesMin != null)
      'parking_spaces_min': parkingSpacesMin
    else if (parkingSpaces != null)
      'parking_spaces': parkingSpaces,
    if (parkingSpacesMax != null) 'parking_spaces_max': parkingSpacesMax,
    if (isFurnished != null) 'is_furnished': isFurnished! ? 1 : 0,
    if (isNew != null) 'is_new': isNew,
    if (isFeatured != null) 'is_featured': isFeatured,
  };

  /// تمرير null يمسح الفلتر، وترك الوسيط دون قيمة يحافظ عليه.
  PropertyQuery copyWith({
    String? search,
    Object? transactionType = _unset,
    Object? sort = _unset,
    Object? city = _unset,
    Object? district = _unset,
    Object? neighborhood = _unset,
    Object? propertyType = _unset,
    Object? minPrice = _unset,
    Object? maxPrice = _unset,
    Object? minArea = _unset,
    Object? maxArea = _unset,
    Object? bedrooms = _unset,
    Object? bathrooms = _unset,
    Object? parkingSpaces = _unset,
    Object? bedroomsMin = _unset,
    Object? bedroomsMax = _unset,
    Object? bathroomsMin = _unset,
    Object? bathroomsMax = _unset,
    Object? parkingSpacesMin = _unset,
    Object? parkingSpacesMax = _unset,
    Object? isFurnished = _unset,
    Object? isNew = _unset,
    Object? isFeatured = _unset,
  }) => PropertyQuery(
    search: search ?? this.search,
    transactionType: identical(transactionType, _unset) ? this.transactionType : transactionType as String?,
    sort: identical(sort, _unset) ? this.sort : sort as String?,
    city: identical(city, _unset) ? this.city : city as String?,
    district: identical(district, _unset) ? this.district : district as String?,
    neighborhood: identical(neighborhood, _unset) ? this.neighborhood : neighborhood as String?,
    propertyType: identical(propertyType, _unset) ? this.propertyType : propertyType as String?,
    minPrice: identical(minPrice, _unset) ? this.minPrice : minPrice as double?,
    maxPrice: identical(maxPrice, _unset) ? this.maxPrice : maxPrice as double?,
    minArea: identical(minArea, _unset) ? this.minArea : minArea as double?,
    maxArea: identical(maxArea, _unset) ? this.maxArea : maxArea as double?,
    bedrooms: identical(bedrooms, _unset) ? this.bedrooms : bedrooms as int?,
    bathrooms: identical(bathrooms, _unset) ? this.bathrooms : bathrooms as int?,
    parkingSpaces: identical(parkingSpaces, _unset) ? this.parkingSpaces : parkingSpaces as int?,
    bedroomsMin: identical(bedroomsMin, _unset) ? this.bedroomsMin : bedroomsMin as int?,
    bedroomsMax: identical(bedroomsMax, _unset) ? this.bedroomsMax : bedroomsMax as int?,
    bathroomsMin: identical(bathroomsMin, _unset) ? this.bathroomsMin : bathroomsMin as int?,
    bathroomsMax: identical(bathroomsMax, _unset) ? this.bathroomsMax : bathroomsMax as int?,
    parkingSpacesMin: identical(parkingSpacesMin, _unset) ? this.parkingSpacesMin : parkingSpacesMin as int?,
    parkingSpacesMax: identical(parkingSpacesMax, _unset) ? this.parkingSpacesMax : parkingSpacesMax as int?,
    isFurnished: identical(isFurnished, _unset) ? this.isFurnished : isFurnished as bool?,
    isNew: identical(isNew, _unset) ? this.isNew : isNew as bool?,
    isFeatured: identical(isFeatured, _unset) ? this.isFeatured : isFeatured as bool?,
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
          neighborhood == other.neighborhood &&
          propertyType == other.propertyType &&
          minPrice == other.minPrice &&
          maxPrice == other.maxPrice &&
          minArea == other.minArea &&
          maxArea == other.maxArea &&
          bedrooms == other.bedrooms &&
          bathrooms == other.bathrooms &&
          parkingSpaces == other.parkingSpaces &&
          bedroomsMin == other.bedroomsMin &&
          bedroomsMax == other.bedroomsMax &&
          bathroomsMin == other.bathroomsMin &&
          bathroomsMax == other.bathroomsMax &&
          parkingSpacesMin == other.parkingSpacesMin &&
          parkingSpacesMax == other.parkingSpacesMax &&
          isFurnished == other.isFurnished &&
          isNew == other.isNew &&
          isFeatured == other.isFeatured;

  @override
  int get hashCode => Object.hashAll([
    search, transactionType, sort, city, district, neighborhood, propertyType,
    minPrice, maxPrice, minArea, maxArea, bedrooms, bathrooms, parkingSpaces,
    bedroomsMin, bedroomsMax, bathroomsMin, bathroomsMax, parkingSpacesMin,
    parkingSpacesMax, isFurnished, isNew, isFeatured,
  ]);
}
