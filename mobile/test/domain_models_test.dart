import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/core/utils/format_money.dart';
import 'package:wajhatak/data/models/models.dart';

void main() {
  group('Currency model (data-driven)', () {
    test('parses API currency payload with all fields', () {
      final currency = Currency.fromJson({
        'code': 'SAR',
        'name_ar': 'ريال سعودي',
        'name_en': 'Saudi Riyal',
        'symbol_ar': 'ر.س',
        'symbol_en': 'SR',
        'flag': '🇸🇦',
        'decimals': 0,
        'is_default': false,
      });

      expect(currency.code, 'SAR');
      expect(currency.nameAr, 'ريال سعودي');
      expect(currency.symbolAr, 'ر.س');
      expect(currency.flag, '🇸🇦');
      expect(currency.isDefault, isFalse);
      expect(currency.decimals, 0);
    });

    test('fallback catalogue lists YER as default with SAR and USD', () {
      expect(Currency.fallback.length, 3);
      expect(Currency.fallback.first.code, 'YER');
      expect(Currency.fallback.first.isDefault, isTrue);
      expect(
        Currency.fallback.map((c) => c.code).toSet(),
        {'YER', 'SAR', 'USD'},
      );
    });

    test('equality is by code', () {
      expect(Currency.fromJson({'code': 'YER'}), Currency.fromJson({'code': 'YER'}));
    });
  });

  group('formatMoney', () {
    test('formats amounts with Arabic currency symbols', () {
      final yer = formatMoney(45000000, 'YER');
      expect(yer, contains('ر.ي'));

      final sar = formatMoney(850000, 'SAR');
      expect(sar, contains('ر.س'));

      final usd = formatMoney(250000, 'USD');
      expect(usd, contains(r'$'));
    });

    test('currencySymbolAr falls back to raw code for unknown currencies', () {
      expect(currencySymbolAr('EUR'), 'EUR');
      expect(currencyNameAr('YER'), 'ريال يمني');
      expect(currencyFlag('SAR'), '🇸🇦');
    });

    test('formatArea appends square meters', () {
      expect(formatArea(180), contains('م²'));
    });
  });

  group('Conversation model (client + agent uniqueness)', () {
    test('title is the agent name, not the property title', () {
      final conversation = ConversationItem.fromJson({
        'id': 7,
        'property': {'id': 3, 'title': 'فيلا فاخرة في صنعاء'},
        'client': {'id': 2, 'name': 'أحمد العميل'},
        'agent': {'id': 5, 'name': 'عبدالله العقاري'},
        'last_message_at': '2026-08-28T10:30:00.000000Z',
        'last_message': {'body': 'مرحبًا', 'message_type': 'text'},
      });

      expect(conversation.title, 'عبدالله العقاري');
      expect(conversation.agentName, 'عبدالله العقاري');
      expect(conversation.agentId, 5);
      expect(conversation.clientName, 'أحمد العميل');
    });

    test('falls back to property title when agent is missing', () {
      final conversation = ConversationItem.fromJson({
        'id': 9,
        'property': {'id': 3, 'title': 'شقة في حدأ'},
      });

      expect(conversation.title, 'شقة في حدأ');
    });

    test('detects property-card preview messages', () {
      final conversation = ConversationItem.fromJson({
        'id': 1,
        'last_message': {'body': '', 'message_type': 'property'},
      });
      expect(conversation.previewIsPropertyCard, isTrue);
    });
  });

  group('ChatMessage property cards', () {
    test('parses property message with embedded property payload', () {
      final message = ChatMessage.fromJson({
        'id': 12,
        'body': '',
        'sender_id': 2,
        'message_type': 'property',
        'property_id': 15,
        'property': {
          'id': 15,
          'title': 'برج تجاري — العليا',
          'price': 1200000,
          'currency': 'USD',
          'transaction_type': 'sale',
          'area': 320,
          'bedrooms': null,
          'bathrooms': 4,
          'location': {'city': 'صنعاء', 'district': 'حدأ'},
          'cover_url': 'https://example.com/cover.jpg',
        },
        'created_at': '2026-08-28T09:00:00.000000Z',
        'read_at': null,
      });

      expect(message.isPropertyCard, isTrue);
      expect(message.property!.title, 'برج تجاري — العليا');
      expect(message.property!.currency, 'USD');
      expect(message.property!.city, 'صنعاء');
      expect(message.property!.district, 'حدأ');
      expect(message.isRead, isFalse);
    });

    test('text messages are not property cards', () {
      final message = ChatMessage.fromJson({
        'id': 13,
        'body': 'السلام عليكم، هل العقار ما زال متاحًا؟',
        'sender_id': 2,
        'message_type': 'text',
        'created_at': '2026-08-28T09:01:00.000000Z',
        'read_at': '2026-08-28T10:00:00.000000Z',
      });

      expect(message.isPropertyCard, isFalse);
      expect(message.isRead, isTrue);
    });
  });

  group('Property model', () {
    test('defaults currency to YER and provides copyWith for favorites', () {
      final property = LuxProperty.fromJson({
        'id': 1,
        'title': 'شقة اختبارية',
        'price': 5000000,
        'transaction_type': 'sale',
      });

      expect(property.currency, 'YER');
      expect(property.isRent, isFalse);
      expect(property.transactionLabel, 'للبيع');

      final favorited = property.copyWith(isFavorited: true);
      expect(favorited.isFavorited, isTrue);
      expect(property.isFavorited, isFalse);
    });

    test('rent label and cover image prefer is_cover image', () {
      final property = LuxProperty.fromJson({
        'id': 2,
        'title': 'استوديو للإيجار',
        'price': 150000,
        'transaction_type': 'rent',
        'images': [
          {'id': 1, 'url': 'https://example.com/1.jpg', 'is_cover': false},
          {'id': 2, 'url': 'https://example.com/2.jpg', 'is_cover': true},
        ],
      });

      expect(property.isRent, isTrue);
      expect(property.transactionLabel, 'للإيجار');
      expect(property.coverUrl, 'https://example.com/2.jpg');
    });
  });

  group('PropertyQuery', () {
    test('expanded filters serialize to API parameters', () {
      final query = PropertyQuery(
        search: '  صنعاء  ',
        transactionType: 'rent',
        sort: 'price_asc',
        city: 'صنعاء',
        minPrice: 100000,
        isFurnished: true,
      );

      final params = query.toParameters();
      expect(params['q'], 'صنعاء');
      expect(params['transaction_type'], 'rent');
      expect(params['sort'], 'price_asc');
      expect(params['city'], 'صنعاء');
      expect(params['min_price'], 100000);
      expect(params['is_furnished'], 1);
      expect(query.isEmpty, isFalse);
    });

    test('copyWith preserves unmodified filters', () {
      final base = PropertyQuery(search: 'صنعاء', bedrooms: 3);
      final updated = base.copyWith(sort: 'price_desc');

      expect(updated.search, 'صنعاء');
      expect(updated.bedrooms, 3);
      expect(updated.sort, 'price_desc');
      expect(updated == base, isFalse);
    });
  });

  group('LuxUser', () {
    test('parses roles and computes agent access', () {
      final agent = LuxUser.fromJson({
        'id': 5,
        'name': 'وكيل',
        'email': 'a@b.c',
        'roles': ['agent'],
      });
      final client = LuxUser.fromJson({
        'id': 6,
        'name': 'عميل',
        'email': 'x@y.z',
        'roles': ['user'],
      });

      expect(agent.isAgent, isTrue);
      expect(client.isAgent, isFalse);
      expect(client.copyWith(phone: '0500000000').phone, '0500000000');
    });
  });

  group('LocationItem', () {
    test('resolves parent id from country/region/city keys', () {
      final country = LocationItem.fromJson({
        'id': 1,
        'name_ar': 'اليمن',
        'code': 'YE',
        'currency_code': 'YER',
      });
      final region = LocationItem.fromJson({
        'id': 2,
        'name_ar': 'تعز',
        'country_id': 1,
      });

      expect(country.currencyCode, 'YER');
      expect(region.parentId, 1);
      expect(country == LocationItem.fromJson({'id': 1, 'name_ar': 'اليمن'}), isTrue);
    });
  });
}
