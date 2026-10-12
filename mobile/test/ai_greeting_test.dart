import 'package:flutter_test/flutter_test.dart';
import '../lib/state/ai_assistant_controller.dart';

void main() {
  group('buildAiWelcomeMessage', () {
    test('uses only the first name and morning greeting', () {
      final greeting = buildAiWelcomeMessage('عبدالرحمن سعد الجرادي', now: DateTime(2026, 10, 10, 8));
      expect(greeting, startsWith('صباح الخير، عبدالرحمن'));
      expect(greeting, contains('مساعد وجهتك الذكي'));
      expect(greeting, isNot(contains('سعد الجرادي')));
    });

    test('changes greeting in the afternoon', () {
      expect(buildAiWelcomeMessage('سامي صالح', now: DateTime(2026, 10, 10, 14)), startsWith('أهلًا، سامي'));
    });

    test('uses evening greeting and works without a name', () {
      expect(buildAiWelcomeMessage('محمد', now: DateTime(2026, 10, 10, 20)), startsWith('مساء الخير، محمد'));
      expect(buildAiWelcomeMessage(null, now: DateTime(2026, 10, 10, 20)), startsWith('مساء الخير 🌙'));
    });
  });
}
