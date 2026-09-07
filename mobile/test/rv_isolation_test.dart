import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _Sess extends AsyncNotifier<int?> {
  @override
  Future<int?> build() async => 5;

  Future<void> clear() async {
    state = const AsyncData(null);
  }
}

final _sessionProvider = AsyncNotifierProvider<_Sess, int?>(_Sess.new);

final _dataProvider = FutureProvider<int>((ref) async {
  ref.watch(_sessionProvider);
  await Future<void>.delayed(Duration.zero);
  return 42;
});

void main() {
  test('deferred clear during build does not hang', () async {
    final container = ProviderContainer();
    addTearDown(container.dispose);

    // container.read(provider.future) يعطل إن كان الـ provider يراقب
    // AsyncNotifier — نقرأ القيمة ونستمع يدويًا.
    final seen = <int?>[];
    container.listen(_dataProvider, (prev, next) {
      seen.add(next.asData?.value);
    }, fireImmediately: true);
    await Future<void>.delayed(const Duration(milliseconds: 100));
    expect(seen, contains(42));
  });
}