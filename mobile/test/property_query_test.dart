import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/models/property_query.dart';

void main() {
  group('PropertyQuery', () {
    test('serializes ranges, neighborhood and false-valued filters', () {
      const query = PropertyQuery(
        search: '  شقة  ',
        city: ' صنعاء ',
        district: ' حدة ',
        neighborhood: ' السنينة ',
        transactionType: 'rent',
        sort: 'price_asc',
        propertyType: 'apartment',
        minPrice: 10000,
        maxPrice: 50000,
        minArea: 70,
        maxArea: 140,
        bedroomsMin: 2,
        bedroomsMax: 4,
        bathroomsMin: 1,
        bathroomsMax: 2,
        parkingSpacesMin: 1,
        parkingSpacesMax: 2,
        isFurnished: false,
        isNew: false,
        isFeatured: true,
      );

      expect(query.toParameters(), {
        'q': 'شقة',
        'city': 'صنعاء',
        'district': 'حدة',
        'neighborhood': 'السنينة',
        'transaction_type': 'rent',
        'sort': 'price_asc',
        'property_type': 'apartment',
        'min_price': 10000.0,
        'max_price': 50000.0,
        'min_area': 70.0,
        'max_area': 140.0,
        'bedrooms_min': 2,
        'bedrooms_max': 4,
        'bathrooms_min': 1,
        'bathrooms_max': 2,
        'parking_spaces_min': 1,
        'parking_spaces_max': 2,
        'is_furnished': 0,
        'is_new': false,
        'is_featured': true,
      });
    });

    test('keeps legacy minimum aliases and recognizes false as an active filter', () {
      const query = PropertyQuery(bedrooms: 2, bathrooms: 1, parkingSpaces: 1);
      expect(query.toParameters(), {
        'bedrooms': 2,
        'bathrooms': 1,
        'parking_spaces': 1,
      });
      expect(const PropertyQuery().isEmpty, isTrue);
      expect(const PropertyQuery(isFurnished: false).isEmpty, isFalse);
    });

    test('copyWith can explicitly clear nullable filters', () {
      const original = PropertyQuery(city: 'صنعاء', maxArea: 120, isFurnished: false);
      final cleared = original.copyWith(city: null, maxArea: null, isFurnished: null);
      expect(cleared.city, isNull);
      expect(cleared.maxArea, isNull);
      expect(cleared.isFurnished, isNull);
      expect(cleared.toParameters(), isEmpty);
    });
  });
}
