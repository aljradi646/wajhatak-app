import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/api_client.dart';
import 'package:wajhatak/data/models/models.dart';
import 'package:wajhatak/data/repositories/property_repository.dart';
import 'package:wajhatak/data/repositories/viewing_request_repository.dart';
import 'package:wajhatak/state/providers.dart';

class _FakeSessionController extends SessionController {
  @override
  Future<SessionData?> build() async => const SessionData(
    token: 't',
    user: LuxUser(id: 1, name: 'وكيل', email: 'a@b.c', roles: ['agent']),
  );
}

class _UnauthorizedRepo extends PropertyRepository {
  _UnauthorizedRepo()
      : super(LuxApiClient(TokenStore(const FlutterSecureStorage())));

  @override
  Future<List<LuxProperty>> mine() async {
    throw ApiFailure('غير مصرح', statusCode: 401);
  }
}

class _UnauthorizedViewingRepo extends ViewingRequestRepository {
  _UnauthorizedViewingRepo()
      : super(LuxApiClient(TokenStore(const FlutterSecureStorage())));

  @override
  Future<List<ViewingRequestItem>> viewingRequests() async {
    throw ApiFailure('غير مصرح', statusCode: 401);
  }
}

/// عدم الحلقة الدائرية: عند 401/403 أثناء بناء provider لا يعاد الدخول
/// لنفس الـ provider (يُمسح خطأ الجلسة بدل CircularDependencyError).
void main() {
  ProviderContainer buildContainer() => ProviderContainer(
    overrides: [
      sessionProvider.overrideWith(_FakeSessionController.new),
      propertyRepositoryProvider.overrideWithValue(_UnauthorizedRepo()),
      viewingRequestRepositoryProvider.overrideWithValue(
        _UnauthorizedViewingRepo(),
      ),
    ],
  );

  test('401 mid-build resolves to fallback, then session is cleared', () async {
    final container = buildContainer();
    addTearDown(container.dispose);

    final seenMy = <List<LuxProperty>>[];
    final seenErrors = <Object>[];
    container.listen(myListingsProvider, (prev, next) {
      if (next.asData?.value != null) seenMy.add(next.asData!.value);
      if (next.asError != null) seenErrors.add(next.asError!.error);
    }, fireImmediately: true);

    await Future<void>.delayed(const Duration(milliseconds: 300));

    expect(seenMy, isNotEmpty);
    expect(seenMy.last, isEmpty);
    expect(seenErrors, isEmpty);
    expect(container.read(sessionProvider).asData?.value, isNull,
        reason: 'بعد رفض التوكن تُمسح الجلسة تلقائيًا');
  });

  test('concurrent 401s across providers do not corrupt state', () async {
    final container = buildContainer();
    addTearDown(container.dispose);

    final errors = <Object>[];
    void onError(AsyncValue<List<dynamic>> v, List<Object> sink) {
      if (v.asError != null) sink.add(v.asError!.error);
    }

    container.listen(myListingsProvider, (p, n) => onError(n, errors));
    container.listen(viewingRequestsProvider, (p, n) => onError(n, errors));
    container.listen(conversationsProvider, (p, n) => onError(n, errors));

    await Future<void>.delayed(const Duration(milliseconds: 400));

    expect(errors, isEmpty);
    expect(container.read(myListingsProvider).asData?.value, isEmpty);
    expect(container.read(viewingRequestsProvider).asData?.value, isEmpty);
    expect(container.read(conversationsProvider).asData?.value, isEmpty);
  });
}
