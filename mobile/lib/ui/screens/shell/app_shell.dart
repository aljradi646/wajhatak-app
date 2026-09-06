import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/navigation/notification_navigation.dart';
import '../../../core/services/lux_notification_service.dart';
import '../../../data/models/models.dart';
import '../../../state/app_settings_controller.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../account/account_screen.dart';
import '../agent/agent_dashboard_screen.dart';
import '../auth/auth_screen.dart';
import '../explore/explore_screen.dart';
import '../home/home_screen.dart';
import '../messages/messages_screen.dart';
import '../notifications/notifications_screen.dart';
import '../saved/saved_screen.dart';
import '../settings/settings_screen.dart';
import '../viewing_requests/viewing_requests_screen.dart';

/// الهيكل الرئيسي: هيدر موحد + شريط سفلي متجاوب + قائمة جانبية.
class AppShell extends ConsumerStatefulWidget {
  const AppShell({super.key});

  @override
  ConsumerState<AppShell> createState() => _AppShellState();
}

class _AppShellState extends ConsumerState<AppShell> {
  var _index = 0;
  final _shellKey = GlobalKey<ScaffoldState>();
  final Set<String> _observedNotificationIds = <String>{};
  Timer? _notificationTimer;
  bool _notificationsPrimed = false;

  void _openDrawer() => _shellKey.currentState?.openDrawer();

  ProviderContainer get _container => ProviderScope.containerOf(context);

  @override
  void initState() {
    super.initState();
    final service = ref.read(localNotificationServiceProvider);
    service.onNotificationResponse = _openFromSystemNotification;
    unawaited(service.initialize());
    unawaited(_handleLaunchNotification());
    // Polling محسوب للإشعارات والمحادثات — 25 ثانية، فقط عند وجود جلسة.
    _notificationTimer = Timer.periodic(const Duration(seconds: 25), (_) {
      if (ref.read(sessionProvider).asData?.value != null) {
        ref.invalidate(notificationsProvider);
        ref.invalidate(conversationsProvider);
      }
    });
  }

  @override
  void dispose() {
    _notificationTimer?.cancel();
    super.dispose();
  }

  /// فتح المصدر من إشعار نظام (التطبيق في الخلفية).
  Future<void> _openFromSystemNotification(String? notificationId) async {
    if (notificationId == null) return;
    if (ref.read(sessionProvider).asData?.value == null) {
      await ref.read(sessionProvider.future);
    }
    await openNotificationSourceById(_container, notificationId);
  }

  /// إشعار يُفتح به التطبيق بينما كان مغلقًا (بدء بارد).
  Future<void> _handleLaunchNotification() async {
    final launchId = await ref
        .read(localNotificationServiceProvider)
        .launchNotificationId();
    if (launchId == null) return;
    if (ref.read(sessionProvider).asData?.value == null) {
      await ref.read(sessionProvider.future);
    }
    await openNotificationSourceById(_container, launchId);
  }

  void _handleNotifications(List<LuxNotification> items, AppSettings settings) {
    if (!_notificationsPrimed) {
      _observedNotificationIds.addAll(items.map((item) => item.id));
      _notificationsPrimed = true;
      return;
    }
    for (final item in items.where((item) => !item.isRead)) {
      if (!_observedNotificationIds.add(item.id) ||
          !_isNotificationEnabled(item, settings)) {
        continue;
      }
      unawaited(ref.read(localNotificationServiceProvider).show(item));
    }
  }

  bool _isNotificationEnabled(
    LuxNotification notification,
    AppSettings settings,
  ) => switch (notification.kind) {
    'message_received' ||
    'message' ||
    'message_sent' => settings.messageNotifications,
    'viewing_request_created' ||
    'viewing_request_updated' ||
    'viewing_request' => settings.viewingNotifications,
    _ => settings.propertyUpdates,
  };

  void _push(Widget page) =>
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => page));

  void _handleSearchFromHome(String term) {
    ref.read(exploreSearchProvider.notifier).setSearch(term);
    setState(() => _index = 1);
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(sessionProvider).asData?.value?.user;
    final settings = ref.watch(appSettingsProvider);
    final notifications =
        ref.watch(notificationsProvider).asData?.value ??
        const <LuxNotification>[];
    final unreadCount = notifications.where((item) => !item.isRead).length;
    ref.listen<AsyncValue<List<LuxNotification>>>(
      notificationsProvider,
      (_, next) =>
          next.whenData((items) => _handleNotifications(items, settings)),
    );
    final isAgent = user?.isAgent ?? false;
    final pages = isAgent
        ? [
            HomeScreen(
              onExplore: () => setState(() => _index = 1),
              onSearchSubmit: _handleSearchFromHome,
            ),
            const AgentDashboardScreen(),
            const ViewingRequestsScreen(),
            const MessagesScreen(),
            const AccountScreen(),
          ]
        : [
            HomeScreen(
              onExplore: () => setState(() => _index = 1),
              onSearchSubmit: _handleSearchFromHome,
            ),
            const ExploreScreen(),
            const SavedScreen(),
            const MessagesScreen(),
            const AccountScreen(),
          ];
    return Scaffold(
      key: _shellKey,
      appBar: WajhatakHeader(
        onMenuTap: _openDrawer,
        onNotificationsTap: user != null
            ? () => _push(const NotificationsScreen())
            : () => _push(const AuthScreen()),
        unreadCount: unreadCount,
      ),
      drawer: AppDrawer(
        onTabSelected: (index) => setState(() => _index = index),
        onExplore: isAgent
            ? () => _push(const ExploreScreen())
            : () => setState(() => _index = 1),
        onFavorites: isAgent
            ? () => _push(const SavedScreen())
            : () => setState(() => _index = 2),
        onViewingRequests: () => _push(const ViewingRequestsScreen()),
        onNotifications: () => _push(const NotificationsScreen()),
        onAgentWorkspace: () => _push(const AgentDashboardScreen()),
        onSettings: () => _push(const SettingsScreen()),
        onProfile: () => _push(const AuthScreen()),
      ),
      body: IndexedStack(
        index: _index,
        children: [
          for (final page in pages)
            MediaQuery(
              // الحشوة الرأسية للهيدر داخل كل صفحة
              data: MediaQuery.of(context),
              child: page,
            ),
        ],
      ),
      bottomNavigationBar: WajhatakBottomNavBar(
        selectedIndex: _index,
        onDestinationSelected: (index) => setState(() => _index = index),
        destinations: isAgent
            ? [
                const WajhatakNavDestination(
                  icon: Icons.home_outlined,
                  selectedIcon: Icons.home_rounded,
                  label: 'الرئيسية',
                ),
                const WajhatakNavDestination(
                  icon: Icons.apartment_outlined,
                  selectedIcon: Icons.apartment_rounded,
                  label: 'عقاراتي',
                ),
                const WajhatakNavDestination(
                  icon: Icons.event_available_outlined,
                  selectedIcon: Icons.event_available_rounded,
                  label: 'الطلبات',
                ),
                const WajhatakNavDestination(
                  icon: Icons.chat_bubble_outline_rounded,
                  selectedIcon: Icons.chat_bubble_rounded,
                  label: 'الرسائل',
                ),
                ProfileNavDestination(
                  avatarUrl: user?.avatarUrl,
                  label: 'حسابي',
                ),
              ]
            : [
                const WajhatakNavDestination(
                  icon: Icons.home_outlined,
                  selectedIcon: Icons.home_rounded,
                  label: 'الرئيسية',
                ),
                const WajhatakNavDestination(
                  icon: Icons.explore_outlined,
                  selectedIcon: Icons.explore_rounded,
                  label: 'استكشاف',
                ),
                const WajhatakNavDestination(
                  icon: Icons.favorite_border_rounded,
                  selectedIcon: Icons.favorite_rounded,
                  label: 'المفضلة',
                ),
                const WajhatakNavDestination(
                  icon: Icons.chat_bubble_outline_rounded,
                  selectedIcon: Icons.chat_bubble_rounded,
                  label: 'الرسائل',
                ),
                ProfileNavDestination(
                  avatarUrl: user?.avatarUrl,
                  label: 'حسابي',
                ),
              ],
      ),
    );
  }
}
