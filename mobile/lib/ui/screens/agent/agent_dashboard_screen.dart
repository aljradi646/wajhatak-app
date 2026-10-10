import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../core/utils/responsive.dart';
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../listings/create_listing_screen.dart';
import '../listings/edit_listing_screen.dart';
import '../messages/messages_screen.dart';
import '../viewing_requests/viewing_requests_screen.dart';
import '../property/property_detail_screen.dart';
import 'agent_reports_screen.dart';

class AgentDashboardScreen extends ConsumerWidget {
  const AgentDashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final listings = ref.watch(myListingsProvider);
    final requests = ref.watch(viewingRequestsProvider);
    final conversations = ref.watch(conversationsProvider);
    final user = ref.watch(sessionProvider).asData?.value?.user;
    final error =
        listings.asError?.error ??
        requests.asError?.error ??
        conversations.asError?.error;
    final isLoading =
        listings.isLoading || requests.isLoading || conversations.isLoading;

    // حالة موحّدة للثلاثة حتى يعمل `AwaitContent` بسلاسة مع حارس الـ Skeleton.
    final combined = error != null
        ? AsyncValue<void>.error(error, StackTrace.current)
        : isLoading
        ? const AsyncValue<void>.loading()
        : const AsyncValue<void>.data(null);

    void refreshAll() {
      ref.invalidate(myListingsProvider);
      ref.invalidate(viewingRequestsProvider);
      ref.invalidate(conversationsProvider);
    }

    if (user == null || !user.isAgent) {
      return const Scaffold(
        body: SafeArea(
          child: EmptyState(
            title: 'مساحة الوكيل مخصصة للحسابات العقارية',
            body: 'سجّل بحساب وكيل عقاري للوصول إلى أدوات النشر والإدارة.',
          ),
        ),
      );
    }

    return Scaffold(
      appBar: (ModalRoute.of(context)?.canPop ?? false)
          ? WajhatakScreenHeader(
              title: 'لوحة الوكيل',
              subtitle: 'إدارة عقاراتك وطلباتك',
            )
          : null,
      body: AwaitContent<void>(
        value: combined,
        onLoading: const _AgentWorkspaceSkeleton(),
        onError: (e) => ErrorState(
          message: _readableDashboardError(e),
          offline: e is ApiFailure && e.statusCode == null,
          onRetry: refreshAll,
        ),
        onData: (_) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(myListingsProvider);
            ref.invalidate(viewingRequestsProvider);
            ref.invalidate(conversationsProvider);
            await Future.wait([
              ref.read(myListingsProvider.future),
              ref.read(viewingRequestsProvider.future),
              ref.read(conversationsProvider.future),
            ]);
          },
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 14),
                sliver: SliverToBoxAdapter(
                  child: _AgentWelcomeCard(
                    name: user.name,
                    avatarUrl: user.avatarUrl,
                    listingCount: listings.requireValue.length,
                    pendingRequests: requests.requireValue
                        .where((item) => item.status == 'pending')
                        .length,
                    conversationCount: conversations.requireValue.length,
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                sliver: SliverToBoxAdapter(
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final columns = constraints.maxWidth >= 900
                          ? 4
                          : constraints.maxWidth >= 560
                          ? 3
                          : 2;
                      final cellWidth =
                          (constraints.maxWidth - (columns - 1) * 10) /
                          columns;
                      final cellHeight =
                          (cellWidth * .46).clamp(64.0, 84.0).toDouble();
                      final actions = <Widget>[
                        _AgentQuickAction(
                          icon: Icons.calendar_month_outlined,
                          label: 'الطلبات',
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => const ViewingRequestsScreen(),
                            ),
                          ),
                        ),
                        _AgentQuickAction(
                          icon: Icons.chat_bubble_outline,
                          label: 'الرسائل',
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) =>
                                  const MessagesScreen(standalone: true),
                            ),
                          ),
                        ),
                        _AgentQuickAction(
                          icon: Icons.add_home_outlined,
                          label: 'إضافة عقار',
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => const CreateListingScreen(),
                            ),
                          ),
                        ),
                        _AgentQuickAction(
                          icon: Icons.assessment_outlined,
                          label: 'التقارير',
                          onTap: () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => const AgentReportsScreen(),
                            ),
                          ),
                        ),
                      ];

                      return GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        padding: EdgeInsets.zero,
                        itemCount: actions.length,
                        gridDelegate:
                            SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: columns,
                              mainAxisExtent: cellHeight,
                              crossAxisSpacing: 10,
                              mainAxisSpacing: 10,
                            ),
                        itemBuilder: (context, index) => actions[index],
                      );
                    },
                  ),
                ),
              ),
              const SliverPadding(
                padding: EdgeInsets.fromLTRB(20, 24, 20, 10),
                sliver: SliverToBoxAdapter(
                  child: SectionHeader(title: 'عقاراتي'),
                ),
              ),
              if (listings.requireValue.isEmpty)
                const SliverFillRemaining(
                  hasScrollBody: false,
                  child: EmptyState(
                    title: 'لم تضف عقارات بعد',
                    body: 'أضف أول عقار لإرساله للمراجعة والنشر.',
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
                  sliver: SliverLayoutBuilder(
                    builder: (context, constraints) {
                      final columns = Responsive.propertyGridColumns(
                        constraints.crossAxisExtent,
                      );
                      final cardWidth =
                          (constraints.crossAxisExtent -
                              (columns - 1) * 12) /
                          columns;
                      return SliverGrid.builder(
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: columns,
                          childAspectRatio: Responsive.propertyCardAspectRatio(
                            cardWidth,
                          ),
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: listings.requireValue.length,
                        itemBuilder: (_, index) {
                          final listing = listings.requireValue[index];
                          return PropertyCard(
                            property: listing,
                            onTap: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => PropertyDetailScreen(
                                  propertyId: listing.id,
                                ),
                              ),
                            ),
                            trailing: [_ListingActionsMenu(property: listing)],
                          );
                        },
                      );
                    },
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

String _readableDashboardError(Object error) {
  if (error is ApiFailure) return error.message;
  final raw = error.toString();
  if (raw.length > 150) {
    return 'تعذر الاتصال بالخدمة. تحقق من الشبكة ثم أعد المحاولة.';
  }
  return raw;
}

class _AgentWorkspaceSkeleton extends StatelessWidget {
  const _AgentWorkspaceSkeleton();

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final columns = constraints.maxWidth >= 900
          ? 4
          : constraints.maxWidth >= 560
          ? 3
          : 2;
      final cellWidth =
          (constraints.maxWidth - (columns - 1) * 10) / columns;
      final cellHeight =
          (cellWidth * .46).clamp(64.0, 84.0).toDouble();

      return ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const LuxSkeleton(height: 172, radius: 24),
          const SizedBox(height: 16),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: 4,
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: columns,
              mainAxisExtent: cellHeight,
              crossAxisSpacing: 10,
              mainAxisSpacing: 10,
            ),
            itemBuilder: (_, _) => const LuxSkeleton(height: 64, radius: 18),
          ),
          const SizedBox(height: 28),
          const LuxSkeleton(width: 110, height: 22),
          const SizedBox(height: 12),
          const PropertyGridSkeleton(count: 4),
        ],
      );
    },
  );
}

class _AgentWelcomeCard extends StatelessWidget {
  const _AgentWelcomeCard({
    required this.name,
    required this.avatarUrl,
    required this.listingCount,
    required this.pendingRequests,
    required this.conversationCount,
  });
  final String name;
  final String? avatarUrl;
  final int listingCount;
  final int pendingRequests;
  final int conversationCount;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: theme.colorScheme.primaryContainer,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              UserAvatar(avatarUrl: avatarUrl, name: name, radius: 26),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'أهلًا $name',
                      style: TextStyle(
                        color: theme.colorScheme.onPrimaryContainer,
                        fontWeight: FontWeight.w900,
                        fontSize: 20,
                      ),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      'تابع أعمالك العقارية واتخذ الخطوة التالية.',
                      style: TextStyle(
                        color: theme.colorScheme.onPrimaryContainer.withValues(
                          alpha: .76,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              _AgentMetric(value: '$listingCount', label: 'عقارات'),
              _AgentMetric(value: '$pendingRequests', label: 'طلبات جديدة'),
              _AgentMetric(value: '$conversationCount', label: 'محادثات'),
            ],
          ),
        ],
      ),
    );
  }
}

class _AgentMetric extends StatelessWidget {
  const _AgentMetric({required this.value, required this.label});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          value,
          style: const TextStyle(fontSize: 23, fontWeight: FontWeight.w900),
        ),
        const SizedBox(height: 2),
        Text(
          label,
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            fontSize: 11,
            color: Theme.of(
              context,
            ).colorScheme.onPrimaryContainer.withValues(alpha: .76),
          ),
        ),
      ],
    ),
  );
}

class _AgentQuickAction extends StatelessWidget {
  const _AgentQuickAction({
    required this.icon,
    required this.label,
    required this.onTap,
  });
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final compact = constraints.maxWidth < 170;
      final theme = Theme.of(context);
      return Material(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(18),
          child: Container(
            padding: EdgeInsets.symmetric(
              vertical: compact ? 7 : 12,
              horizontal: compact ? 8 : 14,
            ),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: theme.colorScheme.outlineVariant),
            ),
            child: compact
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(icon, color: WajhatakColors.emerald, size: 20),
                      const SizedBox(height: 4),
                      Text(
                        label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: theme.textTheme.labelMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  )
                : Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: WajhatakColors.emerald),
                      const SizedBox(width: 8),
                      Flexible(
                        child: Text(
                          label,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.labelMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      );
    },
  );
}

enum _ListingAction { edit, delete }

class _ListingActionsMenu extends ConsumerWidget {
  const _ListingActionsMenu({required this.property});
  final LuxProperty property;

  Future<void> _delete(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('حذف العقار؟'),
        content: const Text('سيُحذف الإعلان وصوره من حسابك نهائيًا.'),
        actions: [
          Row(
            children: [
              Expanded(
                child: TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('إلغاء'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('حذف'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) {
      return;
    }
    try {
      await ref.read(propertyRepositoryProvider).delete(property.id);
      ref.invalidate(myListingsProvider);
      ref.invalidate(propertiesProvider);
      if (context.mounted) {
        util.notice(context, 'تم حذف العقار.');
      }
    } on ApiFailure catch (error) {
      if (context.mounted) {
        util.notice(context, error.message);
      }
    }
  }

  @override
  Widget build(
    BuildContext context,
    WidgetRef ref,
  ) => PopupMenuButton<_ListingAction>(
    tooltip: 'إدارة الإعلان',
    icon: const Icon(Icons.more_horiz),
    onSelected: (action) {
      if (action == _ListingAction.edit) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => EditListingScreen(propertyId: property.id),
          ),
        );
        return;
      }
      _delete(context, ref);
    },
    itemBuilder: (context) => const [
      PopupMenuItem(value: _ListingAction.edit, child: Text('تعديل العقار')),
      PopupMenuItem(value: _ListingAction.delete, child: Text('حذف العقار')),
    ],
  );
}
