import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../property/property_detail_screen.dart';
import '../shared/toggle_favorite.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key, required this.onExplore, this.onSearchSubmit});
  final VoidCallback onExplore;
  final ValueChanged<String>? onSearchSubmit;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final properties = ref.watch(propertiesProvider);
    final session = ref.watch(sessionProvider).asData?.value;
    final userName = session?.user.name.split(' ').first;

    return CustomScrollView(
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 6),
          sliver: SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _HeroSearchCard(
                  userName: userName,
                  onNavigateToExplore: onExplore,
                  onSubmit: onSearchSubmit,
                ),
                const SizedBox(height: 24),
                const SectionHeader(
                  title: 'عقارات مختارة',
                  subtitle: 'أفضل ما نعرضه لك اليوم',
                ),
                const SizedBox(height: 14),
              ],
            ),
          ),
        ),
        SliverAsyncView<List<LuxProperty>>(
          value: properties,
          errorRetry: () => ref.invalidate(propertiesProvider),
          error: (e) => ErrorState(
            message: 'تعذر تحميل العقارات',
            onRetry: () => ref.invalidate(propertiesProvider),
          ),
          data: (items) => items.isEmpty
              ? const [
                  SliverFillRemaining(
                    child: EmptyState(
                      title: 'لا توجد عقارات منشورة بعد',
                      body: 'ستظهر العقارات المنشورة والمعتمدة هنا تلقائيًا فور نشرها.',
                      icon: Icons.villa_outlined,
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
                          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: columns,
                            mainAxisSpacing: 14,
                            crossAxisSpacing: 14,
                            childAspectRatio: Responsive.propertyCardAspectRatio(
                              constraints.crossAxisExtent / columns,
                            ),
                          ),
                          itemCount: items.length,
                          itemBuilder: (_, index) => PropertyCard(
                            property: items[index],
                            onTap: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => PropertyDetailScreen(
                                  propertyId: items[index].id,
                                ),
                              ),
                            ),
                            onFavorite: () =>
                                toggleFavorite(context, ref, items[index]),
                          ),
                        );
                      },
                    ),
                  ),
                ],
        ),
      ],
    );
  }
}

/// بطاقة البحث الرئيسية — تدرج زمردي مع بحث سريع واختصارات ملونة.
class _HeroSearchCard extends StatefulWidget {
  const _HeroSearchCard({
    required this.onNavigateToExplore,
    this.onSubmit,
    this.userName,
  });

  final VoidCallback onNavigateToExplore;
  final ValueChanged<String>? onSubmit;
  final String? userName;

  @override
  State<_HeroSearchCard> createState() => _HeroSearchCardState();
}

class _HeroSearchCardState extends State<_HeroSearchCard> {
  final _controller = TextEditingController();
  final _focusNode = FocusNode();

  @override
  void initState() {
    super.initState();
    _controller.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _controller.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _submit() {
    final term = _controller.text.trim();
    _focusNode.unfocus();
    if (term.isEmpty) {
      widget.onNavigateToExplore();
      return;
    }
    if (widget.onSubmit != null) {
      widget.onSubmit!(term);
    } else {
      widget.onNavigateToExplore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dark = theme.brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
      decoration: BoxDecoration(
        gradient: dark
            ? WajhatakColors.heroGradientDark
            : WajhatakColors.heroGradientLight,
        borderRadius: BorderRadius.circular(28),
        boxShadow: [
          BoxShadow(
            color: WajhatakColors.emerald.withValues(alpha: dark ? .3 : .32),
            blurRadius: 30,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .16),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: const Icon(
                  Icons.waving_hand_rounded,
                  color: WajhatakColors.amber,
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      widget.userName != null
                          ? 'أهلًا ${widget.userName} 👋'
                          : 'أهلًا بك 👋',
                      style: theme.textTheme.titleMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      'وجهتك إلى العقار المناسب',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: Colors.white.withValues(alpha: .85),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          // حقل البحث الزجاجي
          Material(
            color: Colors.white.withValues(alpha: .97),
            borderRadius: BorderRadius.circular(18),
            child: InkWell(
              borderRadius: BorderRadius.circular(18),
              onTap: () => _focusNode.requestFocus(),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                child: Row(
                  textDirection: TextDirection.rtl,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _controller,
                        focusNode: _focusNode,
                        textInputAction: TextInputAction.search,
                        onSubmitted: (_) => _submit(),
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                        decoration: InputDecoration(
                          border: InputBorder.none,
                          enabledBorder: InputBorder.none,
                          focusedBorder: InputBorder.none,
                          filled: false,
                          prefixIcon: Icon(
                            Icons.search_rounded,
                            color: WajhatakColors.emerald,
                          ),
                          hintText: 'ابحث عن مدينة أو حي أو نوع عقار…',
                          hintStyle: TextStyle(
                            color: Colors.grey.shade500,
                            fontWeight: FontWeight.w600,
                            fontSize: 13.5,
                          ),
                        ),
                      ),
                    ),
                    _SearchButton(
                      onPressed: _submit,
                      hasText: _controller.text.isNotEmpty,
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          // اختصارات سريعة ملونة
          Row(
            children: [
              Expanded(
                child: _QuickAction(
                  icon: Icons.sell_outlined,
                  label: 'للبيع',
                  color: Colors.white.withValues(alpha: .17),
                  iconColor: Colors.white,
                  onTap: () => widget.onSubmit?.call(''),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _QuickAction(
                  icon: Icons.key_rounded,
                  label: 'للإيجار',
                  color: Colors.white.withValues(alpha: .17),
                  iconColor: Colors.white,
                  onTap: () {
                    _controller.clear();
                    widget.onNavigateToExplore();
                  },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _QuickAction(
                  icon: Icons.explore_rounded,
                  label: 'استكشاف',
                  color: WajhatakColors.amber.withValues(alpha: .95),
                  iconColor: Colors.white,
                  onTap: widget.onNavigateToExplore,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SearchButton extends StatelessWidget {
  const _SearchButton({required this.onPressed, required this.hasText});

  final VoidCallback onPressed;
  final bool hasText;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.all(4),
    child: Material(
      color: WajhatakColors.emerald,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(14),
        child: SizedBox(
          width: 52,
          height: 46,
          child: Icon(
            hasText ? Icons.arrow_back_rounded : Icons.search_rounded,
            color: Colors.white,
            size: 21,
          ),
        ),
      ),
    ),
  );
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.icon,
    required this.label,
    required this.color,
    required this.iconColor,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color color;
  final Color iconColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => Material(
    color: color,
    borderRadius: BorderRadius.circular(15),
    child: InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(15),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 16, color: iconColor),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
      ),
    ),
  );
}
