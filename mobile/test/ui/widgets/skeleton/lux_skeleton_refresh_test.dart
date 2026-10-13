import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/ui/widgets/skeleton/lux_skeleton.dart';

void main() {
  testWidgets('LuxAsyncView keeps the last successful content during refresh',
      (tester) async {
    AsyncValue<int> value = const AsyncData<int>(7);

    await tester.pumpWidget(
      ProviderScope(
        child: MaterialApp(
          home: StatefulBuilder(
            builder: (context, setState) => Scaffold(
              body: Column(
                children: [
                  TextButton(
                    onPressed: () => setState(() {
                      value = const AsyncLoading<int>();
                    }),
                    child: const Text('Refresh'),
                  ),
                  Expanded(
                    child: LuxAsyncView<int>(
                      value: value,
                      loading: const Text('skeleton'),
                      data: (number) => Text('value:$number'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    expect(find.text('value:7'), findsOneWidget);
    await tester.tap(find.text('Refresh'));
    await tester.pump();

    expect(find.text('value:7'), findsOneWidget);
    expect(find.text('skeleton'), findsNothing);
  });

  testWidgets('SliverAsyncView keeps the last successful slivers during refresh',
      (tester) async {
    AsyncValue<int> value = const AsyncData<int>(9);

    await tester.pumpWidget(
      ProviderScope(
        child: MaterialApp(
          home: StatefulBuilder(
            builder: (context, setState) => Scaffold(
              body: CustomScrollView(
                slivers: [
                  SliverToBoxAdapter(
                    child: TextButton(
                      onPressed: () => setState(() {
                        value = const AsyncLoading<int>();
                      }),
                      child: const Text('Refresh'),
                    ),
                  ),
                  SliverAsyncView<int>(
                    value: value,
                    loading: const SliverToBoxAdapter(
                      child: Text('skeleton'),
                    ),
                    data: (number) => [
                      SliverToBoxAdapter(child: Text('value:$number')),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );

    expect(find.text('value:9'), findsOneWidget);
    await tester.tap(find.text('Refresh'));
    await tester.pump();

    expect(find.text('value:9'), findsOneWidget);
    expect(find.text('skeleton'), findsNothing);
  });
}
