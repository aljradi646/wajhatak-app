import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/config/app_config.dart';
import '../data/api_client.dart';
import '../data/models/models.dart';
import '../data/preview_fixtures.dart';
import '../data/repositories/repositories.dart';

// ---------------------------------------------------------------------------
// البنية التحتية
// ---------------------------------------------------------------------------

final tokenStoreProvider = Provider<TokenStore>((ref) {
  const storage = FlutterSecureStorage(
    wOptions: WindowsOptions(useBackwardCompatibility: false),
  );
  return TokenStore(storage);
});

final apiClientProvider = Provider<LuxApiClient>(
  (ref) => LuxApiClient(ref.watch(tokenStoreProvider)),
);

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(
    ref.watch(apiClientProvider),
    ref.watch(tokenStoreProvider),
  ),
);

final propertyRepositoryProvider = Provider<PropertyRepository>(
  (ref) => PropertyRepository(ref.watch(apiClientProvider)),
);

final conversationRepositoryProvider = Provider<ConversationRepository>(
  (ref) => ConversationRepository(ref.watch(apiClientProvider)),
);

final viewingRequestRepositoryProvider = Provider<ViewingRequestRepository>(
  (ref) => ViewingRequestRepository(ref.watch(apiClientProvider)),
);

final notificationRepositoryProvider = Provider<NotificationRepository>(
  (ref) => NotificationRepository(ref.watch(apiClientProvider)),
);

final taxonomyRepositoryProvider = Provider<TaxonomyRepository>(
  (ref) => TaxonomyRepository(ref.watch(apiClientProvider)),
);

final previewFixtureRepositoryProvider =
    FutureProvider<PreviewFixtureRepository>(
      (ref) => PreviewFixtureRepository.load(),
    );

// ---------------------------------------------------------------------------
// الجلسة — مصدر حقيقة واحد للمصادقة
// ---------------------------------------------------------------------------

class SessionController extends AsyncNotifier<SessionData?> {
  @override
  Future<SessionData?> build() async {
    if (AppConfig.isUiPreview) {
      final fixtures = await ref.read(previewFixtureRepositoryProvider.future);
      return fixtures.sessionForRole(AppConfig.previewRole);
    }
    return ref.read(authRepositoryProvider).restore();
  }

  Future<void> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    state = const AsyncLoading();
    try {
      final session = await (() async {
        if (AppConfig.isUiPreview) {
          return (await ref.read(
            previewFixtureRepositoryProvider.future,
          )).sessionForRole(AppConfig.previewRole);
        }
        return ref
            .read(authRepositoryProvider)
            .login(email: email, password: password, deviceName: deviceName);
      })();
      state = AsyncData(session);
      _afterSessionChanged();
    } catch (error, stackTrace) {
      state = AsyncError(error, stackTrace);
      rethrow;
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String accountType,
    String? phone,
  }) async {
    state = const AsyncLoading();
    try {
      final session = await (() async {
        if (AppConfig.isUiPreview) {
          return (await ref.read(
            previewFixtureRepositoryProvider.future,
          )).sessionForRole(AppConfig.previewRole);
        }
        return ref
            .read(authRepositoryProvider)
            .register(
              name: name,
              email: email,
              password: password,
              accountType: accountType,
              phone: phone,
            );
      })();
      state = AsyncData(session);
      _afterSessionChanged();
    } catch (error, stackTrace) {
      state = AsyncError(error, stackTrace);
      rethrow;
    }
  }

  /// تسجيل الخروج: API → حذف الرمز → مسح كل الحالة المرتبطة بالمستخدم →
  /// إعادة الواجهة لشاشة الدخول. كل ذلك فور نجاح العملية دون إعادة تشغيل.
  Future<void> logout() async {
    Object? logoutError;
    StackTrace? logoutStackTrace;
    try {
      if (AppConfig.isUiPreview) {
        await ref.read(previewFixtureRepositoryProvider.future);
      } else {
        await ref.read(authRepositoryProvider).logout();
      }
    } catch (error, stackTrace) {
      logoutError = error;
      logoutStackTrace = stackTrace;
    } finally {
      await _clearAuthenticatedState();
    }

    if (logoutError != null) {
      Error.throwWithStackTrace(
        logoutError,
        logoutStackTrace ?? StackTrace.current,
      );
    }
  }

  Future<void> clearExpiredSession() => _clearAuthenticatedState();

  Future<void> _clearAuthenticatedState() async {
    if (!AppConfig.isUiPreview) {
      await ref.read(tokenStoreProvider).clear();
    }
    state = const AsyncData(null);
    _afterSessionChanged();
  }

  /// تنظيف الحالات النقية غير المرتبطة بالجلسة.
  ///
  /// لا يُبطل هذا التابع بيانات provider تعتمد على الجلسة: كلها تراقب
  /// `sessionProvider` فتُعاد تلقائيًا عند تغيّر الحالة. إبطالُ اعتماد تابع
  /// من داخل باني مصدره نفسه (مثل error dependency في Riverpod) يُطلق
  /// `CircularDependencyError` ويتجمد التطبيق — لذلك نقتصر على إعادة ضبط
  /// حالات الواجهة العابرة فقط.
  void _afterSessionChanged() {
    ref.invalidate(favoriteOverridesProvider);
    ref.invalidate(exploreSearchProvider);
  }

  Future<void> updateProfile({
    required String name,
    String? phone,
    String? locale,
  }) async {
    final current = state.asData?.value;
    if (current == null || AppConfig.isUiPreview) return;
    state = const AsyncLoading();
    try {
      final result = await ref
          .read(authRepositoryProvider)
          .updateProfile(
            session: current,
            name: name,
            phone: phone,
            locale: locale,
          );
      state = AsyncData(result);
    } on ApiFailure {
      state = AsyncData(current);
      rethrow;
    }
  }

  Future<void> uploadAvatar(String imagePath) async {
    final current = state.asData?.value;
    if (current == null || AppConfig.isUiPreview) {
      return;
    }
    state = const AsyncLoading();
    try {
      final result = await ref
          .read(authRepositoryProvider)
          .uploadAvatar(session: current, imagePath: imagePath);
      state = AsyncData(result);
    } on ApiFailure {
      state = AsyncData(current);
      rethrow;
    }
  }
}

final sessionProvider = AsyncNotifierProvider<SessionController, SessionData?>(
  SessionController.new,
);

// ---------------------------------------------------------------------------
// مساعدات داخلية
// ---------------------------------------------------------------------------

Future<PreviewFixtureRepository> _preview(Ref ref) =>
    ref.read(previewFixtureRepositoryProvider.future);

bool _isUnauthorized(ApiFailure error) =>
    error.statusCode == 401 || error.statusCode == 403;

Future<T> _readForSession<T>(
  Ref ref,
  T fallback,
  Future<T> Function() request,
) async {
  final session = ref.watch(sessionProvider).asData?.value;
  if (session == null) return fallback;
  try {
    return await request();
  } on ApiFailure catch (error) {
    // 401/403: مسح الجلسة بعد إنهاء الطلب الحالي. لا نستدعي `ref` بعد
    // فجوة async — فالـ provider قد يُعاد بناؤه فحُرّف الـ ref — لذلك
    // نلتقط notifier الجلسة قبل أي await، ولا نُبطل providers تعتمد
    // على الجلسة وهي قيد البناء (Prev CircularDependencyError).
    if (_isUnauthorized(error)) {
      unawaited(ref.read(sessionProvider.notifier).clearExpiredSession());
      return fallback;
    }
    rethrow;
  }
}

// ---------------------------------------------------------------------------
// العقارات
// ---------------------------------------------------------------------------

final propertiesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).list();
  ref.watch(sessionProvider);
  return ref.read(propertyRepositoryProvider).list();
});

final propertySearchProvider =
    FutureProvider.family<List<LuxProperty>, PropertyQuery>((ref, query) async {
      if (AppConfig.isUiPreview) return (await _preview(ref)).list(query);
      ref.watch(sessionProvider);
      return ref.read(propertyRepositoryProvider).list(query);
    });

final propertyDetailProvider = FutureProvider.family<LuxProperty, int>((
  ref,
  id,
) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).detail(id);
  ref.watch(sessionProvider);
  return ref.read(propertyRepositoryProvider).detail(id);
});

final favoritesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).favorites();
  return _readForSession(
    ref,
    const <LuxProperty>[],
    () => ref.read(propertyRepositoryProvider).favorites(),
  );
});

final myListingsProvider = FutureProvider<List<LuxProperty>>((ref) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).mine();
  return _readForSession(
    ref,
    const <LuxProperty>[],
    () => ref.read(propertyRepositoryProvider).mine(),
  );
});

// ---------------------------------------------------------------------------
// ملف الوكيل
// ---------------------------------------------------------------------------

class AgentProfileData {
  const AgentProfileData({
    required this.agent,
    required this.properties,
    required this.hasMore,
    required this.nextPage,
  });

  final PropertyAgent agent;
  final List<LuxProperty> properties;
  final bool hasMore;
  final int nextPage;
}

class AgentProfileController extends AsyncNotifier<AgentProfileData> {
  AgentProfileController(this._agentId);

  final int _agentId;

  @override
  Future<AgentProfileData> build() async {
    if (AppConfig.isUiPreview) {
      final all = await (await _preview(ref)).list();
      final first = all.firstWhere(
        (p) => p.agent != null,
        orElse: () => all.first,
      );
      final agent =
          first.agent ?? PropertyAgent(id: _agentId, name: 'وكيل معتمد');
      final mine = all
          .where((p) => p.agent?.id == agent.id)
          .toList(growable: false);
      return AgentProfileData(
        agent: agent,
        properties: mine,
        hasMore: false,
        nextPage: 1,
      );
    }
    final result = await ref
        .read(propertyRepositoryProvider)
        .agentProfile(_agentId);
    return AgentProfileData(
      agent: result.agent,
      properties: result.page.items,
      hasMore: result.page.hasMore,
      nextPage: result.page.nextPage,
    );
  }

  /// تحميل الصفحة التالية من منشورات الوكيل وإلحاقها بالقائمة.
  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore) return;
    try {
      final more = await ref
          .read(propertyRepositoryProvider)
          .agentProperties(_agentId, page: current.nextPage);
      state = AsyncData(
        AgentProfileData(
          agent: current.agent,
          properties: [...current.properties, ...more.items],
          hasMore: more.hasMore,
          nextPage: more.nextPage,
        ),
      );
    } on Object {
      // فشل التحميل الإضافي لا يُسقط المنشورات المعروضة.
    }
  }
}

final agentProfileProvider =
    AsyncNotifierProvider.family<AgentProfileController, AgentProfileData, int>(
      (int agentId) => AgentProfileController(agentId),
    );

// ---------------------------------------------------------------------------
// التصنيفات والمواقع والعملات (data-driven من الخادم)
// ---------------------------------------------------------------------------

final propertyTypesProvider = FutureProvider<List<TaxonomyItem>>(
  (ref) async => AppConfig.isUiPreview
      ? (await _preview(ref)).propertyTypes()
      : ref.watch(taxonomyRepositoryProvider).propertyTypes(),
);

final featuresProvider = FutureProvider<List<TaxonomyItem>>(
  (ref) async => AppConfig.isUiPreview
      ? (await _preview(ref)).features()
      : ref.watch(taxonomyRepositoryProvider).features(),
);

/// العملات المدعومة — تُجلب من API عند توفره مع نسخة احتياطية.
final currenciesProvider = FutureProvider<List<Currency>>((ref) async {
  if (AppConfig.isUiPreview) return Currency.fallback;
  try {
    return await ref.watch(taxonomyRepositoryProvider).currencies();
  } on ApiFailure {
    // اتصال الشبكة غير متاح حاليًا للكتالوج العام — نسخة احتياطية آمنة.
    return Currency.fallback;
  }
});

final countriesProvider = FutureProvider<List<LocationItem>>(
  (ref) async => AppConfig.isUiPreview
      ? const <LocationItem>[]
      : ref.watch(taxonomyRepositoryProvider).countries(),
);

final regionsProvider = FutureProvider.family<List<LocationItem>, int>(
  (ref, countryId) async => AppConfig.isUiPreview
      ? const <LocationItem>[]
      : ref.watch(taxonomyRepositoryProvider).regions(countryId),
);

final citiesProvider = FutureProvider.family<List<LocationItem>, int>(
  (ref, regionId) async => AppConfig.isUiPreview
      ? const <LocationItem>[]
      : ref.watch(taxonomyRepositoryProvider).cities(regionId),
);

final areasProvider = FutureProvider.family<List<LocationItem>, int>(
  (ref, cityId) async => AppConfig.isUiPreview
      ? const <LocationItem>[]
      : ref.watch(taxonomyRepositoryProvider).areas(cityId),
);

// ---------------------------------------------------------------------------
// المحادثات والرسائل وطلبات المعاينة
// ---------------------------------------------------------------------------

final conversationsProvider = FutureProvider<List<ConversationItem>>((
  ref,
) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).conversations();
  return _readForSession(
    ref,
    const <ConversationItem>[],
    () => ref.read(conversationRepositoryProvider).conversations(),
  );
});

final viewingRequestsProvider = FutureProvider<List<ViewingRequestItem>>((
  ref,
) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).viewingRequests();
  return _readForSession(
    ref,
    const <ViewingRequestItem>[],
    () => ref.read(viewingRequestRepositoryProvider).viewingRequests(),
  );
});

final messagesProvider =
    AsyncNotifierProvider.family<ChatMessagesNotifier, List<ChatMessage>, int>(
      (conversationId) => ChatMessagesNotifier(conversationId),
    );

/// رسائل المحادثة — حالة قابلة للتحديث: تحديث خلفي بدون اهتزاز، إرسال فوري،
/// وتحديث عند فتح المحادثة مباشرة (كيفما تُحدَّث الإشعارات في الجرس).
class ChatMessagesNotifier extends AsyncNotifier<List<ChatMessage>> {
  ChatMessagesNotifier(this._conversationId);

  final int _conversationId;

  @override
  Future<List<ChatMessage>> build() => _load();

  Future<List<ChatMessage>> _load() async {
    if (AppConfig.isUiPreview) {
      return (await _preview(ref)).messages(_conversationId);
    }
    return _readForSession(
      ref,
      const <ChatMessage>[],
      () => ref.read(conversationRepositoryProvider).messages(_conversationId),
    );
  }

  /// تحديث خلفي للرسائل الجديدة دون إظهار شاشة تحميل (يبقي القائمة الحالية).
  Future<void> refresh() async {
    try {
      state = AsyncData(await _load());
    } on Object {
      // فشل التحديث الخلفي لا يُسقط الرسائل المعروضة.
    }
  }

  /// إرسال رسالة — تظهر فورًا في المحادثة، ثم يتأكد التحديث الخلفي.
  Future<void> send(String body) async {
    if (AppConfig.isUiPreview) {
      final user = ref.read(sessionProvider).asData?.value?.user;
      final message = ChatMessage(
        id: -DateTime.now().microsecondsSinceEpoch,
        body: body,
        senderId: user?.id ?? 0,
        createdAt: DateTime.now(),
      );
      state = AsyncData([message, ...(state.value ?? const <ChatMessage>[])]);
      ref.invalidate(conversationsProvider);
      return;
    }
    final message = await ref
        .read(conversationRepositoryProvider)
        .sendMessage(_conversationId, body);
    state = AsyncData([
      message,
      ...(state.value ?? const <ChatMessage>[]).where(
        (existing) => existing.id != message.id,
      ),
    ]);
    ref.invalidate(conversationsProvider);
    unawaited(refresh());
  }
}

// ---------------------------------------------------------------------------
// الإشعارات
// ---------------------------------------------------------------------------

final notificationsProvider = FutureProvider<List<LuxNotification>>((
  ref,
) async {
  if (!AppConfig.isUiPreview &&
      ref.watch(sessionProvider).asData?.value == null) {
    return const [];
  }
  return AppConfig.isUiPreview
      ? (await _preview(ref)).notifications()
      : ref.watch(notificationRepositoryProvider).notifications();
});

// ---------------------------------------------------------------------------
// البحث وحالة المفضلة
// ---------------------------------------------------------------------------

final exploreSearchProvider = NotifierProvider<ExploreSearchNotifier, String?>(
  ExploreSearchNotifier.new,
);

class ExploreSearchNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void setSearch(String? term) => state = term;
}

/// تجاوزات المفضلة — تحديث فوري للواجهات فور تأكيد الخادم.
/// مصدر الحقيقة هو قاعدة البيانات؛ هذا فقط طبقة تزامن مؤقتة للواجهة.
class FavoriteOverrides extends Notifier<Map<int, bool>> {
  @override
  Map<int, bool> build() => {};

  void set(int id, bool isFavorited) => state = {...state, id: isFavorited};

  void remove(int id) {
    final next = Map<int, bool>.from(state);
    next.remove(id);
    state = next;
  }

  void reset() => state = {};
}

final favoriteOverridesProvider =
    NotifierProvider<FavoriteOverrides, Map<int, bool>>(FavoriteOverrides.new);
