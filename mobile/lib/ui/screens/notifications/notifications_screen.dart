import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/navigation/notification_navigation.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../auth/auth_screen.dart';

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);
    if (session.isLoading) {
      return Scaffold(
        appBar: WajhatakScreenHeader(title: 'الإشعارات'),
        body: const ListSkeleton(trailing: true),
      );
    }
    if (session.asData?.value == null) {
      return Scaffold(
        appBar: WajhatakScreenHeader(title: 'الإشعارات'),
        body: const AuthRequiredScreen(
          title: 'إشعاراتك هنا',
          body: 'سجّل دخولك لرؤية إشعاراتك أولًا بأول.',
          actionLabel: 'تسجيل الدخول',
        ),
      );
    }
    final notifications = ref.watch(notificationsProvider);
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'الإشعارات'),
      body: LuxAsyncView<List<LuxNotification>>(
        value: notifications,
        errorRetry: () => ref.invalidate(notificationsProvider),
        data: (items) {
          if (items.isEmpty) {
            return const EmptyState(
              title: 'لا توجد إشعارات',
              body: 'سيصلك هنا تنبيه عند ورود رسالة أو تحديث طلب معاينة.',
              icon: Icons.notifications_none_rounded,
            );
          }
          return ListView.separated(
            padding: const EdgeInsets.all(20),
            itemCount: items.length,
            separatorBuilder: (context, index) => const SizedBox(height: 10),
            itemBuilder: (context, index) => _NotificationCard(
              item: items[index],
              onTap: () => openNotificationSource(
                context,
                ProviderScope.containerOf(context),
                items[index],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  const _NotificationCard({required this.item, required this.onTap});

  final LuxNotification item;
  final VoidCallback onTap;

  AccentTone get _tone => switch (item.kind) {
    'message_received' || 'message' || 'message_sent' => AccentTone.teal,
    'viewing_request_created' ||
    'viewing_request_updated' ||
    'viewing_request' => AccentTone.orange,
    _ => AccentTone.amber,
  };

  IconData get _icon => switch (item.kind) {
    'message_received' || 'message' || 'message_sent' =>
      Icons.chat_bubble_rounded,
    'viewing_request_created' ||
    'viewing_request_updated' ||
    'viewing_request' => Icons.event_available_rounded,
    _ => Icons.notifications_rounded,
  };

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: item.isRead
          ? theme.colorScheme.surface
          : theme.colorScheme.primaryContainer.withValues(alpha: .35),
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: item.isRead
                  ? theme.colorScheme.outlineVariant
                  : theme.colorScheme.primary.withValues(alpha: .35),
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (item.actorAvatarUrl != null)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: UserAvatar(
                    avatarUrl: item.actorAvatarUrl,
                    name: item.actorName,
                    radius: 21,
                  ),
                )
              else
                TintedIcon(icon: _icon, tone: _tone, size: 42),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (item.title?.isNotEmpty == true) ...[
                      Text(
                        item.title!,
                        style: theme.textTheme.labelMedium?.copyWith(
                          color: theme.colorScheme.primary,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                    ],
                    Text(
                      item.message,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: item.isRead
                            ? FontWeight.w600
                            : FontWeight.w800,
                        height: 1.4,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      DateFormat.yMMMd('ar').add_jm().format(item.createdAt),
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
              if (!item.isRead)
                Container(
                  width: 10,
                  height: 10,
                  margin: const EdgeInsets.only(top: 6),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.primary,
                    shape: BoxShape.circle,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
