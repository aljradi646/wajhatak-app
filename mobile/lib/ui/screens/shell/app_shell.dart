import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/navigation/active_chat_context.dart';
import '../../../core/navigation/notification_navigation.dart';
import '../../../core/services/lux_notification_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/models.dart';
import '../../../state/app_settings_controller.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../ai/ai_assistant_sheet.dart';
import '../account/account_screen.dart';
import '../agent/agent_dashboard_screen.dart';
import '../agent/agent_reports_screen.dart';
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
  Timer? _sessionRefreshTimer;
  bool _notificationsPrimed = false;

  void _openDrawer() => _shellKey.currentState?.openDrawer();

  ProviderContainer get _container => ProviderScope.containerOf(context);

  @override
  void initState() {
    super.initState();
    final realtime = ref.read(realtimeServiceProvider);
    realtime.onEvent = _handleRealtimeEvent;
    if (ref.read(sessionProvider).asData?.value != null)
      unawaited(realtime.start());
    final service = ref.read(localNotificationServiceProvider);
    service.onNotificationResponse = _openFromSystemNotification;
    unawaited(service.initialize());
    unawaited(_handleLaunchNotification());
    final push = ref.read(firebasePushMessagingServiceProvider);
    push.onNotificationOpened = _openFromSystemNotification;
    push.onMessageReceived = _handlePushMessage;
    unawaited(push.initializeAndRegister());
    // Notifications refresh from push/WebSocket events; avoid global polling
    // that repeatedly invalidates whole screens and destroys their state.
    _sessionRefreshTimer = Timer.periodic(const Duration(minutes: 5), (_) {
      if (mounted && ref.read(sessionProvider).asData?.value != null) {
        unawaited(ref.read(sessionProvider.notifier).refreshCurrentUser());
      }
    });
  }

  @override
  void dispose() {
    _sessionRefreshTimer?.cancel();
    unawaited(ref.read(realtimeServiceProvider).stop());
    super.dispose();
  }

  void _handleRealtimeEvent(String event, Map<String, dynamic> data) {
    if (!mounted) return;
    if (event == 'message.received' || event == 'notifications.updated') {
      unawaited(ref.read(notificationsProvider.notifier).refresh());
      unawaited(ref.read(unreadNotificationCountProvider.notifier).refresh());
      unawaited(ref.read(conversationsProvider.notifier).refresh());
      final id = int.tryParse(data['conversation_id']?.toString() ?? '');
      if (id != null && id == ActiveChatContext.conversationId) {
        unawaited(ref.read(messagesProvider(id).notifier).refresh());
      }
    } else if (event == 'conversation.read') {
      unawaited(ref.read(conversationsProvider.notifier).refresh());
      final id = int.tryParse(data['conversation_id']?.toString() ?? '');
      if (id != null) ref.invalidate(messagesProvider(id));
    }
  }

  Future<void> _handlePushMessage(Map<String, dynamic> data) async {
    if (!mounted) return;
    unawaited(ref.read(notificationsProvider.notifier).refresh());
    unawaited(ref.read(unreadNotificationCountProvider.notifier).refresh());
    unawaited(ref.read(conversationsProvider.notifier).refresh());
    final conversationId = int.tryParse(
      data['conversation_id']?.toString() ?? '',
    );
    if (conversationId != null &&
        conversationId == ActiveChatContext.conversationId) {
      unawaited(ref.read(messagesProvider(conversationId).notifier).refresh());
    }
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
          !_isNotificationEnabled(item, settings) ||
          (item.isMessage &&
              item.conversationId != null &&
              item.conversationId == ActiveChatContext.conversationId) ||
          !ActiveChatContext.markNotificationShown(item.id)) {
        continue;
      }
      unawaited(ref.read(localNotificationServiceProvider).show(item));
    }
  }

  bool _isNotificationEnabled(
    LuxNotification notification,
    AppSettings settings,
  ) => switch (notification.kind) {
    'support_ticket_reply' => true,
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

  void _navigateToExplore() {
    final isAgent =
        ref.read(sessionProvider).asData?.value?.user.isAgent == true;
    if (isAgent) {
      // The agent's index 1 is the dashboard, not ExploreScreen.
      _push(const ExploreScreen());
      return;
    }
    setState(() => _index = 1);
  }

  void _handleSearchFromHome(String term) {
    ref.read(exploreSearchProvider.notifier).setSearch(term);
    _navigateToExplore();
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(sessionProvider).asData?.value?.user;
    final settings = ref.watch(appSettingsProvider);
    final notifications =
        ref.watch(notificationsProvider).asData?.value ??
        const <LuxNotification>[];
    final unreadCount =
        ref.watch(unreadNotificationCountProvider).asData?.value ??
        notifications.where((item) => !item.isRead).length;
    ref.listen<AsyncValue<List<LuxNotification>>>(
      notificationsProvider,
      (_, next) =>
          next.whenData((items) => _handleNotifications(items, settings)),
    );
    ref.listen<AsyncValue<SessionData?>>(sessionProvider, (previous, next) {
      final previousUserId = previous?.asData?.value?.user.id;
      final nextUserId = next.asData?.value?.user.id;
      if (previousUserId == nextUserId) return;

      if (nextUserId != null) {
        unawaited(
          ref
              .read(firebasePushMessagingServiceProvider)
              .initializeAndRegister(),
        );
        unawaited(ref.read(realtimeServiceProvider).start());
      } else {
        unawaited(ref.read(realtimeServiceProvider).stop());
      }
    });
    final isAgent = user?.isAgent ?? false;
    final pages = isAgent
        ? [
            HomeScreen(
              onExplore: _navigateToExplore,
              onSearchSubmit: _handleSearchFromHome,
            ),
            const AgentDashboardScreen(),
            const ViewingRequestsScreen(),
            const MessagesScreen(),
            const AccountScreen(),
          ]
        : [
            HomeScreen(
              onExplore: _navigateToExplore,
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
        onAgentReports: () => _push(const AgentReportsScreen()),
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
      // الزر العائم للمساعد الذكي — أيقونة فقط بدون نص، واضع فوق الشريط
      // السفلي يسارًا حتى لا يغطي عناصر التنقل المهمة.
      floatingActionButton: user != null
          ? FloatingActionButton(
              heroTag: 'wajhatak_ai_assistant',
              onPressed: () => showAiAssistant(context),
              backgroundColor: WajhatakColors.emeraldDeep,
              foregroundColor: Colors.white,
              tooltip: 'المساعد الذكي',
              shape: const CircleBorder(),
              child: const Icon(Icons.auto_awesome_rounded, size: 24),
            )
          : null,
      floatingActionButtonLocation: FloatingActionButtonLocation.startFloat,
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
