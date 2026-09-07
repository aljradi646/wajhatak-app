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
}
