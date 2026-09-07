import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/models/models.dart';

void main() {
  group('LUX domain models', () {
    test('maps Arabic property data and a stable cover image', () {
      final property = LuxProperty.fromJson({
        'id': 42,
        'title': 'شقة بإطلالة مفتوحة',
        'price': 850000,
        'currency': 'SAR',
        'transaction_type': 'sale',
        'is_favorited': true,
        'type': {'name_ar': 'شقة'},
        'location': {'city': 'الرياض', 'district': 'العليا'},
        'images': [
          {'id': 1, 'url': 'https://example.com/one.jpg', 'is_cover': false},
          {'id': 2, 'url': 'https://example.com/two.jpg', 'is_cover': true},
        ],
      });

      expect(property.title, 'شقة بإطلالة مفتوحة');
      expect(property.location?.shortLabel, 'العليا، الرياض');
      expect(property.coverUrl, 'https://example.com/two.jpg');
      expect(property.isFavorited, isTrue);
    });

    test('does not send empty search parameters to the real API', () {
      expect(const PropertyQuery().toParameters(), isEmpty);
      expect(const PropertyQuery(search: '  شقة  ', transactionType: 'sale', sort: 'price_asc').toParameters(), {
        'q': 'شقة',
        'transaction_type': 'sale',
        'sort': 'price_asc',
      });
    });
  });
}
