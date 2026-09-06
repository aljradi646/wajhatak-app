import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/responsive.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../auth/auth_screen.dart';
import '../property/property_detail_screen.dart';
import '../shared/toggle_favorite.dart';

class SavedScreen extends ConsumerWidget {
  const SavedScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);
    if (session.isLoading) {
      return const Center(child: PropertyGridSkeleton());
    }
    final signedIn = session.asData?.value != null;
    if (!signedIn) {
      return AuthRequiredScreen(
        title: 'مفضلاتك في مكان واحد',
        body: 'سجّل دخولك لحفظ العقارات التي تعجبك ومتابعتها في أي وقت.',
        actionLabel: 'تسجيل الدخول',
      );
    }
    final items = ref.watch(favoritesProvider);
    return Scaffold(
      appBar: (ModalRoute.of(context)?.canPop ?? false)
          ? WajhatakScreenHeader(title: 'مفضلاتك')
          : null,
      body: CustomScrollView(
        slivers: [
          const SliverPadding(
            padding: EdgeInsets.fromLTRB(20, 12, 20, 8),
            sliver: SliverToBoxAdapter(child: _SavedHeader()),
          ),
          SliverAsyncView<List<LuxProperty>>(
            value: items,
            errorRetry: () => ref.invalidate(favoritesProvider),
            data: (data) => data.isEmpty
                ? const [
                    SliverFillRemaining(
                      child: EmptyState(
                        title: 'لا توجد مفضلات بعد',
                        body: 'اضغط على القلب في أي عقار يعجبك وسيظهر هنا.',
                        icon: Icons.favorite_border_rounded,
                      ),
                    ),
                  ]
                : [
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
                      sliver: SliverLayoutBuilder(
                        builder: (context, constraints) {
                          final columns = Responsive.propertyGridColumns(
                            constraints.crossAxisExtent,
                          );
                          return SliverGrid.builder(
                            gridDelegate:
                                SliverGridDelegateWithFixedCrossAxisCount(
                                  crossAxisCount: columns,
                                  mainAxisSpacing: 14,
                                  crossAxisSpacing: 14,
                                  childAspectRatio:
                                      Responsive.propertyCardAspectRatio(
                                        constraints.crossAxisExtent / columns,
                                      ),
                                ),
                            itemCount: data.length,
                            itemBuilder: (_, index) => PropertyCard(
                              property: data[index],
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => PropertyDetailScreen(
                                    propertyId: data[index].id,
                                  ),
                                ),
                              ),
                              onFavorite: () =>
                                  toggleFavorite(context, ref, data[index]),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
          ),
        ],
      ),
    );
  }
}

class _SavedHeader extends ConsumerWidget {
  const _SavedHeader();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final count = ref.watch(favoritesProvider).asData?.value.length ?? 0;
    return Row(
      children: [
        Expanded(
          child: Text(
            'مفضلاتك',
            style: theme.textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
          decoration: BoxDecoration(
            color: theme.colorScheme.error.withValues(alpha: .1),
            borderRadius: BorderRadius.circular(99),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.favorite_rounded,
                size: 15,
                color: theme.colorScheme.error,
              ),
              const SizedBox(width: 6),
              Text(
                '$count عقار',
                style: theme.textTheme.labelLarge?.copyWith(
                  color: theme.colorScheme.error,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
