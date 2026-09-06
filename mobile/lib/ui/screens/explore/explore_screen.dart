import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/responsive.dart';
import '../../../data/models/models.dart';
import '../../../state/app_settings_controller.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../property/property_detail_screen.dart';
import '../property/property_map_screen.dart';
import '../shared/toggle_favorite.dart';

class ExploreScreen extends ConsumerStatefulWidget {
  const ExploreScreen({super.key, this.initialSearch});
  final String? initialSearch;

  @override
  ConsumerState<ExploreScreen> createState() => _ExploreScreenState();
}

class _ExploreScreenState extends ConsumerState<ExploreScreen> {
  late final TextEditingController _controller;
  String? _transaction;
  String _term = '';
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.initialSearch ?? '');
    _term = widget.initialSearch ?? '';
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final settings = ref.read(appSettingsProvider);
      if (_transaction == null) {
        final saved = settings.lastTransactionType;
        if (saved != null) setState(() => _transaction = saved);
      }
    });
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 350), () {
      if (!mounted) return;
      setState(() => _term = value);
    });
  }

  void _onSearchSubmitted(String value) {
    _searchDebounce?.cancel();
    setState(() => _term = value);
    ref.read(appSettingsProvider.notifier).addSearchTerm(value);
    FocusManager.instance.primaryFocus?.unfocus();
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<String?>(exploreSearchProvider, (prev, next) {
      if (next != null && next.isNotEmpty && next != prev) {
        ref.read(exploreSearchProvider.notifier).setSearch(null);
        _searchDebounce?.cancel();
        _controller.text = next;
        setState(() => _term = next);
      }
    });

    final query = PropertyQuery(search: _term, transactionType: _transaction);
    final results = ref.watch(propertySearchProvider(query));
    final mappedProperties = results.asData?.value ?? const <LuxProperty>[];

    return Scaffold(
      appBar: (ModalRoute.of(context)?.canPop ?? false)
          ? WajhatakScreenHeader(title: 'استكشف العقارات')
          : null,
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'استكشف العقارات',
                        style: Theme.of(context).textTheme.headlineSmall
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    _MapButton(
                      enabled: mappedProperties.isNotEmpty,
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) =>
                              PropertyMapScreen(properties: mappedProperties),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _controller,
                  textInputAction: TextInputAction.search,
                  onChanged: _onSearchChanged,
                  onSubmitted: _onSearchSubmitted,
                  decoration: InputDecoration(
                    prefixIcon: Icon(
                      Icons.search_rounded,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    hintText: 'ابحث باسم الحي أو المدينة',
                    suffixIcon: IconButton(
                      onPressed: () {
                        _searchDebounce?.cancel();
                        _controller.clear();
                        setState(() => _term = '');
                      },
                      icon: const Icon(Icons.close_rounded),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                // رقائق نوع العملية — بأيقونات ملونة
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _TransactionChip(
                        label: 'الكل',
                        icon: Icons.apps_rounded,
                        tone: AccentTone.violet,
                        selected: _transaction == null,
                        onSelected: () {
                          setState(() => _transaction = null);
                          ref
                              .read(appSettingsProvider.notifier)
                              .setLastTransactionType(null);
                        },
                      ),
                      const SizedBox(width: 8),
                      _TransactionChip(
                        label: 'للبيع',
                        icon: Icons.sell_rounded,
                        tone: AccentTone.emerald,
                        selected: _transaction == 'sale',
                        onSelected: () {
                          setState(() => _transaction = 'sale');
                          ref
                              .read(appSettingsProvider.notifier)
                              .setLastTransactionType('sale');
                        },
                      ),
                      const SizedBox(width: 8),
                      _TransactionChip(
                        label: 'للإيجار',
                        icon: Icons.key_rounded,
                        tone: AccentTone.sky,
                        selected: _transaction == 'rent',
                        onSelected: () {
                          setState(() => _transaction = 'rent');
                          ref
                              .read(appSettingsProvider.notifier)
                              .setLastTransactionType('rent');
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: LuxAsyncView<List<LuxProperty>>(
              value: results,
              errorRetry: () => ref.invalidate(propertySearchProvider(query)),
              data: (items) => items.isEmpty
                  ? const EmptyState(
                      title: 'لا توجد نتائج مطابقة',
                      body: 'جرّب تعديل عبارة البحث أو نوع العملية.',
                      icon: Icons.search_off_rounded,
                    )
                  : LayoutBuilder(
                      builder: (_, constraints) => GridView.builder(
                        padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: Responsive.propertyGridColumns(
                            constraints.maxWidth,
                          ),
                          mainAxisSpacing: 14,
                          crossAxisSpacing: 14,
                          childAspectRatio: Responsive.propertyCardAspectRatio(
                            constraints.maxWidth /
                                Responsive.propertyGridColumns(
                                  constraints.maxWidth,
                                ),
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
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MapButton extends StatelessWidget {
  const _MapButton({required this.enabled, required this.onPressed});

  final bool enabled;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: enabled
          ? WajhatakColors.sky.withValues(alpha: .12)
          : theme.colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: enabled ? onPressed : null,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.map_rounded,
                size: 18,
                color: enabled
                    ? WajhatakColors.sky
                    : theme.colorScheme.onSurfaceVariant,
              ),
              const SizedBox(width: 7),
              Text(
                'الخريطة',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 13,
                  color: enabled
                      ? WajhatakColors.sky
                      : theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TransactionChip extends StatelessWidget {
  const _TransactionChip({
    required this.label,
    required this.icon,
    required this.tone,
    required this.selected,
    required this.onSelected,
  });

  final String label;
  final IconData icon;
  final AccentTone tone;
  final bool selected;
  final VoidCallback onSelected;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final base = tone.color(theme.colorScheme);
    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      decoration: BoxDecoration(
        color: selected
            ? base.withValues(alpha: .14)
            : theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: selected ? base : theme.colorScheme.outline,
          width: selected ? 1.6 : 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onSelected,
          borderRadius: BorderRadius.circular(14),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 16, color: base),
                const SizedBox(width: 6),
                Text(
                  label,
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: selected ? FontWeight.w900 : FontWeight.w700,
                    color: selected ? base : theme.colorScheme.onSurfaceVariant,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
