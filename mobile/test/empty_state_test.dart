import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/ui/widgets/feedback/empty_state.dart';

void main() {
  testWidgets('empty state invokes its recovery action', (tester) async {
    var pressed = false;

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: EmptyState(
            title: 'لا توجد نتائج مطابقة',
            body: 'امسح الفلاتر للعودة إلى جميع العقارات.',
            actionLabel: 'عرض جميع العقارات',
            onAction: () => pressed = true,
            icon: Icons.search_off_rounded,
          ),
        ),
      ),
    );

    final action = find.text('عرض جميع العقارات');
    expect(action, findsOneWidget);

    await tester.tap(action);
    await tester.pump();

    expect(pressed, isTrue);
  });
}
