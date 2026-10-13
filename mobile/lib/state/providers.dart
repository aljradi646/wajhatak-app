import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/config/app_config.dart';
import '../core/services/firebase_push_messaging_service.dart';
import '../core/services/lux_notification_service.dart';
import '../core/services/realtime_service.dart';
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

final accountRepositoryProvider = Provider<AccountRepository>(
  (ref) => AccountRepository(ref.watch(apiClientProvider)),
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

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  final service = RealtimeService(ref.watch(apiClientProvider));
  ref.onDispose(() => unawaited(service.stop()));
  return service;
});

final firebasePushMessagingServiceProvider =
    Provider<FirebasePushMessagingService>(
      (ref) => FirebasePushMessagingService(
        ref.watch(apiClientProvider),
        ref.watch(tokenStoreProvider),
        ref.watch(localNotificationServiceProvider),
      ),
    );

final supportTicketRepositoryProvider = Provider<SupportTicketRepository>(
  (ref) => SupportTicketRepository(ref.watch(apiClientProvider)),
);

final taxonomyRepositoryProvider = Provider<TaxonomyRepository>(
  (ref) => TaxonomyRepository(ref.watch(apiClientProvider)),
);

final agentReportRepositoryProvider = Provider<AgentReportRepository>(
  (ref) => AgentReportRepository(ref.watch(apiClientProvider)),
);

final previewFixtureRepositoryProvider =
    FutureProvider<PreviewFixtureRepository>(
      (ref) => PreviewFixtureRepository.load(),
    );

// ---------------------------------------------------------------------------
// الجلسة — مصدر حقيقة واحد للمصادقة
// ---------------------------------------------------------------------------

class SessionController extends AsyncNotifier<SessionData?> {
  bool _refreshingCurrentUser = false;

  Future<void> refreshCurrentUser() async {
    final current = state.asData?.value;
    if (current == null || AppConfig.isUiPreview || _refreshingCurrentUser)
      return;
    _refreshingCurrentUser = true;
    try {
      final user = await ref.read(authRepositoryProvider).refreshCurrentUser();
      final latest = state.asData?.value;
      if (latest != null && latest.user.id == current.user.id) {
        state = AsyncData(latest.copyWith(user: user));
      }
    } on ApiFailure catch (error) {
      if (error.statusCode == 401) {
        await ref.read(tokenStoreProvider).clear();
        state = const AsyncData(null);
        _afterSessionChanged();
      }
    } catch (_) {
      // Keep the current session during a transient network failure.
    } finally {
      _refreshingCurrentUser = false;
    }
  }

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
    // Agent fields
    String? agencyName,
    String? jobTitle,
    String? agentPhone,
    String? whatsapp,
    String? agentCity,
    String? nationalId,
    int? experienceYears,
    String? address,
    String? bio,
    String? licenseNumber,
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
              agencyName: agencyName,
              jobTitle: jobTitle,
              agentPhone: agentPhone,
              whatsapp: whatsapp,
              agentCity: agentCity,
              nationalId: nationalId,
              experienceYears: experienceYears,
              address: address,
              bio: bio,
              licenseNumber: licenseNumber,
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
    // Update app state immediately; remote token revocation is best-effort and
    // must never hold the user on a signed-in screen.
    state = const AsyncData(null);
    _afterSessionChanged();

    if (AppConfig.isUiPreview) {
      await ref.read(previewFixtureRepositoryProvider.future);
      return;
    }

    try {
      await ref
          .read(firebasePushMessagingServiceProvider)
          .unregisterCurrentDevice()
          .timeout(const Duration(seconds: 2));
    } on Object {
      // Do not delay sign-out if FCM or the network is unavailable.
    }

    final token = await ref.read(tokenStoreProvider).read();
    await ref.read(tokenStoreProvider).clear();
    if (token == null || token.isEmpty) return;

    await ref.read(authRepositoryProvider).logout(accessToken: token);
  }

  Future<void> clearExpiredSession() => _clearAuthenticatedState();

  /// إعادة تحميل بيانات الجلسة من الخادم (بعد توثيق البريد مثلًا).
  Future<void> restoreSessionAfterVerification() async {
    if (AppConfig.isUiPreview) return;
    try {
      final refreshed = await ref.read(authRepositoryProvider).restore();
      if (refreshed != null) {
        state = AsyncData(refreshed);
      }
    } on Object {
      // فشل التحديث لا يُسقط الجلسة الحالية.
    }
  }

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
    try {
      final result = await ref
          .read(authRepositoryProvider)
          .updateProfile(
            session: current,
            name: name,
            phone: phone,
            locale: locale,
          );
      final latest = state.asData?.value;
      if (latest != null && latest.user.id == current.user.id) {
        state = AsyncData(result);
      }
    } on ApiFailure {
      rethrow;
    }
  }

  Future<void> uploadAvatar(String imagePath) async {
    final current = state.asData?.value;
    if (current == null || AppConfig.isUiPreview) {
      return;
    }
    try {
      final result = await ref
          .read(authRepositoryProvider)
          .uploadAvatar(session: current, imagePath: imagePath);
      final latest = state.asData?.value;
      if (latest != null && latest.user.id == current.user.id) {
        state = AsyncData(result);
      }
    } on ApiFailure {
      rethrow;
    }
  }
}

final sessionProvider = AsyncNotifierProvider<SessionController, SessionData?>(
  SessionController.new,
);

/// Stable auth dependency for data providers. Profile refreshes must not
/// invalidate every screen; only a change of authenticated user ID does.
final sessionUserIdProvider = Provider<int?>(
  (ref) => ref.watch(
    sessionProvider.select((session) => session.asData?.value?.user.id),
  ),
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
  final sessionUserId = ref.read(sessionUserIdProvider);
  if (sessionUserId == null) return fallback;
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

/// Async controller that refreshes in place, retaining the last successful
/// value during network revalidation. Use invalidation only for first-load
/// recovery or when the identity/query itself changes.
abstract class BackgroundRefreshController<T> extends AsyncNotifier<T> {
  bool _refreshInFlight = false;
  bool _refreshQueued = false;

  Future<T> loadValue();

  @override
  Future<T> build() {
    ref.watch(sessionUserIdProvider);
    return loadValue();
  }

  Future<void> refresh() async {
    if (_refreshInFlight) {
      _refreshQueued = true;
      return;
    }

    // If an event arrives during the first load, wait for that request before
    // issuing a background refresh so the event cannot be lost to a stale read.
    if (state.value == null) {
      try {
        await future;
      } catch (_) {
        return; // The normal first-load error UI owns retry behavior.
      }
    }

    final previous = state.value;
    if (previous == null || _refreshInFlight) return;
    final requestedUserId = ref.read(sessionUserIdProvider);
    _refreshInFlight = true;
    try {
      final refreshed = await loadValue();
      if (ref.mounted && requestedUserId == ref.read(sessionUserIdProvider)) {
        state = AsyncData(refreshed);
      }
    } catch (_) {
      // A transient refresh failure must not erase usable on-screen data.
      if (ref.mounted && requestedUserId == ref.read(sessionUserIdProvider)) {
        state = AsyncData(previous);
      }
    } finally {
      _refreshInFlight = false;
      if (ref.mounted && _refreshQueued) {
        _refreshQueued = false;
        unawaited(refresh());
      }
    }
  }
}

/// Bump after a successful property mutation or authoritative realtime event.
/// Active catalog/detail queries revalidate in place; their previous value stays
/// visible through LuxAsyncView/SliverAsyncView, preserving scroll and form state.
class PropertyCatalogRevisionController extends Notifier<int> {
  @override
  int build() => 0;

  void bump() => state++;
}

final propertyCatalogRevisionProvider =
    NotifierProvider<PropertyCatalogRevisionController, int>(
  PropertyCatalogRevisionController.new,
);

// ---------------------------------------------------------------------------
// العقارات
// ---------------------------------------------------------------------------

final propertiesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  ref.watch(propertyCatalogRevisionProvider);
  if (AppConfig.isUiPreview) return (await _preview(ref)).list();
  ref.watch(sessionUserIdProvider);
  return ref.read(propertyRepositoryProvider).list();
});

final propertySearchProvider =
    FutureProvider.family<List<LuxProperty>, PropertyQuery>((ref, query) async {
      ref.watch(propertyCatalogRevisionProvider);
      if (AppConfig.isUiPreview) return (await _preview(ref)).list(query);
      ref.watch(sessionUserIdProvider);
      return ref.read(propertyRepositoryProvider).list(query);
    });

/// نتيجة بحث الاستكشاف مع بيانات الصفحات وحالة تحميل الصفحة الإضافية.
class ExploreSearchResult {
  const ExploreSearchResult({
    required this.items,
    required this.hasMore,
    required this.nextPage,
    this.isLoadingMore = false,
    this.loadMoreError = false,
  });

  final List<LuxProperty> items;
  final bool hasMore;
  final int nextPage;
  final bool isLoadingMore;
  final bool loadMoreError;

  ExploreSearchResult copyWith({
    List<LuxProperty>? items,
    bool? hasMore,
    int? nextPage,
    bool? isLoadingMore,
    bool? loadMoreError,
  }) => ExploreSearchResult(
    items: items ?? this.items,
    hasMore: hasMore ?? this.hasMore,
    nextPage: nextPage ?? this.nextPage,
    isLoadingMore: isLoadingMore ?? this.isLoadingMore,
    loadMoreError: loadMoreError ?? this.loadMoreError,
  );
}

class ExplorePropertySearchController
    extends AsyncNotifier<ExploreSearchResult> {
  ExplorePropertySearchController(this._query);

  final PropertyQuery _query;
  bool _requestInFlight = false;

  @override
  Future<ExploreSearchResult> build() async {
    ref.watch(propertyCatalogRevisionProvider);
    if (AppConfig.isUiPreview) {
      final items = await (await _preview(ref)).list(_query);
      return ExploreSearchResult(items: items, hasMore: false, nextPage: 1);
    }

    ref.watch(sessionUserIdProvider);
    final page = await ref.read(propertyRepositoryProvider).searchPage(_query);
    return ExploreSearchResult(
      items: page.items,
      hasMore: page.hasMore,
      nextPage: page.nextPage,
    );
  }

  /// تحميل وإلحاق الصفحة التالية مع إبقاء النتائج الحالية عند فشل الشبكة.
  Future<void> loadMore({bool retry = false}) async {
    final current = state.asData?.value;
    if (_requestInFlight ||
        current == null ||
        !current.hasMore ||
        current.isLoadingMore ||
        (current.loadMoreError && !retry)) {
      return;
    }

    _requestInFlight = true;
    state = AsyncData(
      current.copyWith(isLoadingMore: true, loadMoreError: false),
    );
    try {
      final more = await ref
          .read(propertyRepositoryProvider)
          .searchPage(_query, page: current.nextPage);
      final latest = state.asData?.value ?? current;
      final seenIds = latest.items.map((item) => item.id).toSet();
      final appended = more.items.where((item) => seenIds.add(item.id));
      state = AsyncData(
        latest.copyWith(
          items: [...latest.items, ...appended],
          hasMore: more.hasMore,
          nextPage: more.nextPage,
          isLoadingMore: false,
          loadMoreError: false,
        ),
      );
    } on Object {
      final latest = state.asData?.value ?? current;
      state = AsyncData(
        latest.copyWith(isLoadingMore: false, loadMoreError: true),
      );
    } finally {
      _requestInFlight = false;
    }
  }
}

final explorePropertySearchProvider =
    AsyncNotifierProvider.family<
      ExplorePropertySearchController,
      ExploreSearchResult,
      PropertyQuery
    >((query) => ExplorePropertySearchController(query));

final propertyDetailProvider = FutureProvider.family<LuxProperty, int>((
  ref,
  id,
) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).detail(id);
  ref.watch(sessionUserIdProvider);
  return ref.read(propertyRepositoryProvider).detail(id);
});

final favoritesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  if (AppConfig.isUiPreview) return (await _preview(ref)).favorites();
  ref.watch(sessionUserIdProvider);
  return _readForSession(
    ref,
    const <LuxProperty>[],
    () => ref.read(propertyRepositoryProvider).favorites(),
  );
});

class MyListingsController
    extends BackgroundRefreshController<List<LuxProperty>> {
  @override
  Future<List<LuxProperty>> loadValue() async {
    if (AppConfig.isUiPreview) return (await _preview(ref)).mine();
    return _readForSession(
      ref,
      const <LuxProperty>[],
      () => ref.read(propertyRepositoryProvider).mine(),
    );
  }
}

final myListingsProvider =
    AsyncNotifierProvider<MyListingsController, List<LuxProperty>>(
      MyListingsController.new,
    );

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

class ConversationsController
    extends BackgroundRefreshController<List<ConversationItem>> {
  @override
  Future<List<ConversationItem>> loadValue() async {
    if (AppConfig.isUiPreview) return (await _preview(ref)).conversations();
    return _readForSession(
      ref,
      const <ConversationItem>[],
      () => ref.read(conversationRepositoryProvider).conversations(),
    );
  }
}

final conversationsProvider =
    AsyncNotifierProvider<ConversationsController, List<ConversationItem>>(
      ConversationsController.new,
    );

class ViewingRequestsController
    extends BackgroundRefreshController<List<ViewingRequestItem>> {
  @override
  Future<List<ViewingRequestItem>> loadValue() async {
    if (AppConfig.isUiPreview) return (await _preview(ref)).viewingRequests();
    return _readForSession(
      ref,
      const <ViewingRequestItem>[],
      () => ref.read(viewingRequestRepositoryProvider).viewingRequests(),
    );
  }
}

final viewingRequestsProvider =
    AsyncNotifierProvider<ViewingRequestsController, List<ViewingRequestItem>>(
      ViewingRequestsController.new,
    );

final messagesProvider =
    AsyncNotifierProvider.family<ChatMessagesNotifier, List<ChatMessage>, int>(
      (conversationId) => ChatMessagesNotifier(conversationId),
    );

/// رسائل المحادثة — حالة قابلة للتحديث: تحديث خلفي بدون اهتزاز، إرسال فوري،
/// وتحديث عند فتح المحادثة مباشرة (كيفما تُحدَّث الإشعارات في الجرس).
class ChatMessagesNotifier extends AsyncNotifier<List<ChatMessage>> {
  ChatMessagesNotifier(this._conversationId);

  final int _conversationId;
  bool _refreshInFlight = false;

  @override
  Future<List<ChatMessage>> build() {
    ref.watch(sessionUserIdProvider);
    return _load();
  }

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
    if (_refreshInFlight) return;
    final requestedUserId = ref.read(sessionUserIdProvider);
    _refreshInFlight = true;
    try {
      final latest = await _load();
      if (ref.mounted && requestedUserId == ref.read(sessionUserIdProvider)) {
        state = AsyncData(latest);
      }
    } on Object {
      // A transient refresh failure must not erase visible messages.
    } finally {
      _refreshInFlight = false;
    }
  }

  /// إرسال رسالة — تظهر فورًا في المحادثة، ثم يتأكد التحديث الخلفي.
  Future<void> send(
    String body, {
    String messageType = 'text',
    int? propertyId,
  }) async {
    if (AppConfig.isUiPreview) {
      final user = ref.read(sessionProvider).asData?.value?.user;
      final message = ChatMessage(
        id: -DateTime.now().microsecondsSinceEpoch,
        body: body,
        senderId: user?.id ?? 0,
        createdAt: DateTime.now(),
      );
      state = AsyncData([message, ...(state.value ?? const <ChatMessage>[])]);
      unawaited(ref.read(conversationsProvider.notifier).refresh());
      return;
    }
    final senderUserId = ref.read(sessionUserIdProvider);
    final message = await ref
        .read(conversationRepositoryProvider)
        .sendMessage(
          _conversationId,
          body,
          messageType: messageType,
          propertyId: propertyId,
        );
    if (!ref.mounted || senderUserId != ref.read(sessionUserIdProvider)) {
      return;
    }
    state = AsyncData([
      message,
      ...(state.value ?? const <ChatMessage>[]).where(
        (existing) => existing.id != message.id,
      ),
    ]);
    unawaited(ref.read(conversationsProvider.notifier).refresh());
    unawaited(refresh());
  }
}

// ---------------------------------------------------------------------------
// البلاغات والدعم
// ---------------------------------------------------------------------------
final supportTicketsProvider = FutureProvider<List<SupportTicket>>((ref) async {
  if (!AppConfig.isUiPreview && ref.read(sessionUserIdProvider) == null)
    return const [];
  if (AppConfig.isUiPreview) return const [];
  return ref.watch(supportTicketRepositoryProvider).list();
});

// ---------------------------------------------------------------------------
// الإشعارات
// ---------------------------------------------------------------------------

class NotificationsController
    extends BackgroundRefreshController<List<LuxNotification>> {
  @override
  Future<List<LuxNotification>> loadValue() async {
    if (!AppConfig.isUiPreview && ref.read(sessionUserIdProvider) == null) {
      return const [];
    }
    return AppConfig.isUiPreview
        ? (await _preview(ref)).notifications()
        : ref.read(notificationRepositoryProvider).notifications();
  }
}

final notificationsProvider =
    AsyncNotifierProvider<NotificationsController, List<LuxNotification>>(
      NotificationsController.new,
    );

class UnreadNotificationCountController
    extends BackgroundRefreshController<int> {
  @override
  Future<int> loadValue() async {
    if (!AppConfig.isUiPreview && ref.read(sessionUserIdProvider) == null) {
      return 0;
    }
    if (AppConfig.isUiPreview) {
      final items = await (await _preview(ref)).notifications();
      return items.where((item) => !item.isRead).length;
    }
    return ref.read(notificationRepositoryProvider).unreadCount();
  }
}

final unreadNotificationCountProvider =
    AsyncNotifierProvider<UnreadNotificationCountController, int>(
      UnreadNotificationCountController.new,
    );

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
