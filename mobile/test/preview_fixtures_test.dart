import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/core/config/app_config.dart';
import 'package:wajhatak/data/preview_fixtures.dart';
import 'package:wajhatak/data/models/models.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('normal builds do not enable local preview fixtures', () {
    expect(AppConfig.isUiPreview, isFalse);
  });

  test(
    'preview bundle exposes rich typed data for both visual workspaces',
    () async {
      final fixtures = await PreviewFixtureRepository.load();
      final properties = await fixtures.list(
        const PropertyQuery(transactionType: 'sale'),
      );
      final agent = fixtures.sessionForRole('agent');

      expect(properties.length, greaterThanOrEqualTo(3));
      expect(properties.every((item) => item.images.isNotEmpty), isTrue);
      expect(agent.user.isAgent, isTrue);
      expect((await fixtures.mine()).length, greaterThanOrEqualTo(3));
      expect(
        (await fixtures.viewingRequests()).length,
        greaterThanOrEqualTo(3),
      );
    },
  );

  test('preview search applies price and area bounds and real sort options', () async {
    final fixtures = await PreviewFixtureRepository.load();
    final all = await fixtures.list();
    expect(all, isNotEmpty);

    final ascending = await fixtures.list(const PropertyQuery(sort: 'price_asc'));
    final prices = ascending.map((item) => item.price).toList();
    expect(prices, orderedEquals([...prices]..sort()));

    final availableWithArea = all.where((item) => item.area != null).toList();
    if (availableWithArea.isNotEmpty) {
      final minArea = availableWithArea.map((item) => item.area!).reduce((a, b) => a < b ? a : b);
      final maxArea = availableWithArea.map((item) => item.area!).reduce((a, b) => a > b ? a : b);
      final bounded = await fixtures.list(PropertyQuery(minArea: minArea, maxArea: maxArea));
      expect(bounded.every((item) => item.area != null && item.area! >= minArea && item.area! <= maxArea), isTrue);
    }
  );
}
