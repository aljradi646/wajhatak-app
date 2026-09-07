import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../auth/auth_screen.dart';
import '../chat/chat_screen.dart';

/// قائمة المحادثات — عنوان كل محادثة هو اسم الوكيل (وليس اسم العقار).
class MessagesScreen extends ConsumerWidget {
  const MessagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);
    if (session.isLoading) {
      return const ListSkeleton();
    }
    if (session.asData?.value == null) {
      return AuthRequiredScreen(
        title: 'رسائلك الخاصة',
        body: 'سجّل دخولك لمراسلة الوكلاء وحفظ محادثاتك.',
        actionLabel: 'تسجيل الدخول',
      );
    }
    final conversations = ref.watch(conversationsProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 6),
          child: Text(
            'الرسائل',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        Expanded(
          child: LuxAsyncView<List<ConversationItem>>(
            value: conversations,
            errorRetry: () => ref.invalidate(conversationsProvider),
            data: (data) => data.isEmpty
                ? const EmptyState(
                    title: 'لا توجد محادثات',
                    body: 'ابدأ محادثة من صفحة تفاصيل أي عقار عبر "مراسلة الوكيل".',
                    icon: Icons.chat_bubble_outline_rounded,
                  )
                : ListView.separated(
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                    itemCount: data.length,
                    separatorBuilder: (context, index) =>
                        const SizedBox(height: 6),
                    itemBuilder: (context, index) =>
                        _ConversationTile(item: data[index]),
                  ),
          ),
        ),
      ],
    );
  }
}

class _ConversationTile extends ConsumerWidget {
  const _ConversationTile({required this.item});

  final ConversationItem item;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isAgentView = ref.watch(sessionProvider).asData?.value?.user.isAgent == true;
    // الاسم المعروض: الطرف الآخر في المحادثة.
    final displayName = (isAgentView ? item.clientName : item.agentName) ?? item.title;
    // صورة الطرف الآخر الحقيقية (الوكيل للعميل / العميل للوكيل).
    final otherAvatarUrl = isAgentView
        ? item.clientAvatarUrl
        : item.agentAvatarUrl;
    final hasRecentActivity =
        item.lastMessageAt != null &&
        DateTime.now().difference(item.lastMessageAt!).inHours < 24;

    return Material(
      color: theme.colorScheme.surface,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: () async {
          await Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => ChatScreen(conversation: item)),
          );
          ref.invalidate(conversationsProvider);
        },
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.all(13),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: theme.colorScheme.outlineVariant),
          ),
          child: Row(
            children: [
              // أفاتار الطرف الآخر — صورته الحقيقية أو الحرف الأول
              ClipRRect(
                borderRadius: BorderRadius.circular(17),
                child: SizedBox(
                  width: 52,
                  height: 52,
                  child: UserAvatar(
                    avatarUrl: otherAvatarUrl,
                    name: displayName,
                    radius: 26,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            displayName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        if (item.lastMessageAt != null)
                          Text(
                            DateFormat.Hm('ar').format(item.lastMessageAt!),
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: hasRecentActivity
                                  ? theme.colorScheme.primary
                                  : theme.colorScheme.onSurfaceVariant,
                              fontWeight: hasRecentActivity
                                  ? FontWeight.w800
                                  : FontWeight.w600,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        if (item.previewIsPropertyCard) ...[
                          Icon(
                            Icons.villa_rounded,
                            size: 13,
                            color: WajhatakColors.amberDeep,
                          ),
                          const SizedBox(width: 4),
                        ],
                        Expanded(
                          child: Text(
                            item.previewIsPropertyCard
                                ? 'عرض تفاصيل العقار'
                                : (item.preview?.isNotEmpty == true
                                      ? item.preview!
                                      : 'ابدأ المحادثة الآن'),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 6),
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (item.unreadCount > 0) ...[
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primary,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        '${item.unreadCount}',
                        style: theme.textTheme.labelSmall?.copyWith(
                          color: theme.colorScheme.onPrimary,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                  ],
                  Icon(
                    Icons.chevron_left_rounded,
                    size: 22,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
