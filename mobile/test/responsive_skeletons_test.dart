import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/ui/widgets.dart';

Future<void> _expectNoLayoutException(
  WidgetTester tester,
  Widget child,
  double width,
) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);

  await tester.pumpWidget(
    MaterialApp(
      theme: ThemeData(useMaterial3: true),
      home: Scaffold(body: child),
    ),
  );

  // Shimmer controllers intentionally repeat; avoid pumpAndSettle.
  await tester.pump(const Duration(milliseconds: 100));
  expect(tester.takeException(), isNull);
}

void main() {
  group('Responsive skeletons on compact screens', () {
    for (final width in <double>[280, 320, 360]) {
      final widthLabel = width.toInt().toString() + 'px';

      testWidgets('agent profile skeleton fits ' + widthLabel, (
        tester,
      ) async {
        await _expectNoLayoutException(
          tester,
          const AgentProfileSkeleton(),
          width,
        );
      });

      testWidgets('report skeleton fits ' + widthLabel, (tester) async {
        await _expectNoLayoutException(
          tester,
          const AgentReportSkeleton(),
          width,
        );
      });

      testWidgets('property details skeleton fits ' + widthLabel, (
        tester,
      ) async {
        await _expectNoLayoutException(
          tester,
          const PropertyDetailsSkeleton(),
          width,
        );
      });

      testWidgets('saved screen skeleton fits ' + widthLabel, (
        tester,
      ) async {
        await _expectNoLayoutException(
          tester,
          const SavedScreenSkeleton(),
          width,
        );
      });
    }
  });
}
