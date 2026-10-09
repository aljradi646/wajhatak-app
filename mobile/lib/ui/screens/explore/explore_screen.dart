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
  PropertyQuery _filters = const PropertyQuery();
  String _term = '';
  Timer? _searchDebounce;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.initialSearch ?? '');
    _term = widget.initialSearch ?? '';
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final settings = ref.read(appSettingsProvider);
      if (_filters.transactionType == null) {
        final saved = settings.lastTransactionType;
        if (saved != null) {
          setState(() => _filters = _filters.copyWith(transactionType: saved));
        }
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

  int get _activeFilterCount {
    final query = _filters;
    return [
      query.city?.trim().isNotEmpty == true,
      query.district?.trim().isNotEmpty == true,
      query.neighborhood?.trim().isNotEmpty == true,
      query.propertyType?.trim().isNotEmpty == true,
      query.minPrice != null,
      query.maxPrice != null,
      query.minArea != null,
      query.maxArea != null,
      query.bedrooms != null || query.bedroomsMin != null,
      query.bedroomsMax != null,
      query.bathrooms != null || query.bathroomsMin != null,
      query.bathroomsMax != null,
      query.parkingSpaces != null || query.parkingSpacesMin != null,
      query.parkingSpacesMax != null,
      query.isFurnished != null,
      query.isNew != null,
      query.isFeatured != null,
    ].where((active) => active).length;
  }

  Future<void> _openFilters(List<TaxonomyItem> propertyTypes) async {
    final result = await showModalBottomSheet<PropertyQuery>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Theme.of(context).colorScheme.surface,
      builder: (_) => _PropertyFiltersSheet(
        initial: _filters.copyWith(search: _term),
        propertyTypes: propertyTypes,
      ),
    );
    if (!mounted || result == null) return;
    setState(() => _filters = result.copyWith(search: _term));
  }

  void _clearFilters() {
    setState(() {
      _filters = PropertyQuery(
        search: _term,
        transactionType: _filters.transactionType,
      );
    });
  }

  void _onSortSelected(String value) {
    setState(() => _filters = _filters.copyWith(
      sort: value == 'recommended' ? null : value,
    ));
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

    final query = _filters.copyWith(search: _term);
    final propertyTypes =
        ref.watch(propertyTypesProvider).asData?.value ?? const <TaxonomyItem>[];
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
                        selected: _filters.transactionType == null,
                        onSelected: () {
                          setState(() => _filters = _filters.copyWith(transactionType: null));
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
                        selected: _filters.transactionType == 'sale',
                        onSelected: () {
                          setState(() => _filters = _filters.copyWith(transactionType: 'sale'));
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
                        selected: _filters.transactionType == 'rent',
                        onSelected: () {
                          setState(() => _filters = _filters.copyWith(transactionType: 'rent'));
                          ref
                              .read(appSettingsProvider.notifier)
                              .setLastTransactionType('rent');
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _SortMenu(
                        selected: _filters.sort ?? 'recommended',
                        onSelected: _onSortSelected,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _openFilters(propertyTypes),
                        icon: const Icon(Icons.tune_rounded),
                        label: Text(
                          _activeFilterCount == 0
                              ? 'الفلاتر'
                              : 'الفلاتر ($_activeFilterCount)',
                        ),
                        style: OutlinedButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                if (_activeFilterCount > 0) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(
                        Icons.filter_alt_rounded,
                        size: 16,
                        color: Theme.of(context).colorScheme.primary,
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          '$_activeFilterCount من الفلاتر مفعّل',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: Theme.of(context).colorScheme.onSurfaceVariant,
                              ),
                        ),
                      ),
                      TextButton.icon(
                        onPressed: _clearFilters,
                        icon: const Icon(Icons.clear_all_rounded, size: 17),
                        label: const Text('مسح الفلاتر'),
                        style: TextButton.styleFrom(
                          visualDensity: VisualDensity.compact,
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
          Expanded(
            child: LuxAsyncView<List<LuxProperty>>(
              value: results,
              loading: const ExploreSkeleton(),
              errorRetry: () => ref.invalidate(propertySearchProvider(query)),
              data: (items) => items.isEmpty
                  ? const EmptyState(
                      title: 'لا توجد نتائج مطابقة',
                      body: 'جرّب تغيير الفرز أو تعديل الفلاتر للوصول إلى نتائج أكثر.',
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


class _SortMenu extends StatelessWidget {
  const _SortMenu({required this.selected, required this.onSelected});

  final String selected;
  final ValueChanged<String> onSelected;

  static const _options = <_SortOption>[
    _SortOption('recommended', 'الترتيب المقترح', Icons.auto_awesome_rounded),
    _SortOption('newest', 'الأحدث نشرًا', Icons.schedule_rounded),
    _SortOption('oldest', 'الأقدم نشرًا', Icons.history_rounded),
    _SortOption('price_asc', 'السعر: الأقل أولًا', Icons.trending_down_rounded),
    _SortOption('price_desc', 'السعر: الأعلى أولًا', Icons.trending_up_rounded),
    _SortOption('area_asc', 'المساحة: الأصغر أولًا', Icons.south_rounded),
    _SortOption('area_desc', 'المساحة: الأكبر أولًا', Icons.north_rounded),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final current = _options.firstWhere(
      (option) => option.value == selected,
      orElse: () => _options.first,
    );
    return PopupMenuButton<String>(
      tooltip: 'فرز النتائج',
      onSelected: onSelected,
      itemBuilder: (context) => _options
          .map(
            (option) => PopupMenuItem<String>(
              value: option.value,
              child: Row(
                children: [
                  Icon(
                    option.icon,
                    size: 19,
                    color: option.value == selected
                        ? theme.colorScheme.primary
                        : theme.colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 10),
                  Expanded(child: Text(option.label)),
                  if (option.value == selected)
                    Icon(Icons.check_rounded, size: 18, color: theme.colorScheme.primary),
                ],
              ),
            ),
          )
          .toList(growable: false),
      child: Container(
        height: 48,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: theme.colorScheme.outline),
        ),
        child: Row(
          children: [
            Icon(Icons.sort_rounded, color: theme.colorScheme.primary, size: 20),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                current.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: theme.textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
              ),
            ),
            const SizedBox(width: 4),
            const Icon(Icons.expand_more_rounded, size: 20),
          ],
        ),
      ),
    );
  }
}

class _SortOption {
  const _SortOption(this.value, this.label, this.icon);

  final String value;
  final String label;
  final IconData icon;
}

class _PropertyFiltersSheet extends StatefulWidget {
  const _PropertyFiltersSheet({
    required this.initial,
    required this.propertyTypes,
  });

  final PropertyQuery initial;
  final List<TaxonomyItem> propertyTypes;

  @override
  State<_PropertyFiltersSheet> createState() => _PropertyFiltersSheetState();
}

class _PropertyFiltersSheetState extends State<_PropertyFiltersSheet> {
  late final TextEditingController _city;
  late final TextEditingController _district;
  late final TextEditingController _neighborhood;
  late final TextEditingController _minPrice;
  late final TextEditingController _maxPrice;
  late final TextEditingController _minArea;
  late final TextEditingController _maxArea;
  late final TextEditingController _bedroomsMin;
  late final TextEditingController _bedroomsMax;
  late final TextEditingController _bathroomsMin;
  late final TextEditingController _bathroomsMax;
  late final TextEditingController _parkingMin;
  late final TextEditingController _parkingMax;

  String? _propertyType;
  bool? _furnished;
  bool? _isNew;
  bool? _isFeatured;
  String? _validationMessage;

  List<TaxonomyItem> get _availableTypes => widget.propertyTypes
      .where((item) => item.slug?.trim().isNotEmpty == true)
      .fold(<String, TaxonomyItem>{}, (map, item) {
        map.putIfAbsent(item.slug!, () => item);
        return map;
      })
      .values
      .toList(growable: false);

  @override
  void initState() {
    super.initState();
    final query = widget.initial;
    _city = TextEditingController(text: query.city ?? '');
    _district = TextEditingController(text: query.district ?? '');
    _neighborhood = TextEditingController(text: query.neighborhood ?? '');
    _minPrice = TextEditingController(text: query.minPrice?.toString() ?? '');
    _maxPrice = TextEditingController(text: query.maxPrice?.toString() ?? '');
    _minArea = TextEditingController(text: query.minArea?.toString() ?? '');
    _maxArea = TextEditingController(text: query.maxArea?.toString() ?? '');
    _bedroomsMin = TextEditingController(
      text: (query.bedroomsMin ?? query.bedrooms)?.toString() ?? '',
    );
    _bedroomsMax = TextEditingController(text: query.bedroomsMax?.toString() ?? '');
    _bathroomsMin = TextEditingController(
      text: (query.bathroomsMin ?? query.bathrooms)?.toString() ?? '',
    );
    _bathroomsMax = TextEditingController(text: query.bathroomsMax?.toString() ?? '');
    _parkingMin = TextEditingController(
      text: (query.parkingSpacesMin ?? query.parkingSpaces)?.toString() ?? '',
    );
    _parkingMax = TextEditingController(text: query.parkingSpacesMax?.toString() ?? '');
    _propertyType = _availableTypes.any((item) => item.slug == query.propertyType)
        ? query.propertyType
        : null;
    _furnished = query.isFurnished;
    _isNew = query.isNew;
    _isFeatured = query.isFeatured;
  }

  @override
  void dispose() {
    for (final controller in [
      _city,
      _district,
      _neighborhood,
      _minPrice,
      _maxPrice,
      _minArea,
      _maxArea,
      _bedroomsMin,
      _bedroomsMax,
      _bathroomsMin,
      _bathroomsMax,
      _parkingMin,
      _parkingMax,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  double? _doubleValue(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : double.tryParse(value);
  }

  int? _intValue(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : int.tryParse(value);
  }

  String? _textValue(TextEditingController controller) {
    final value = controller.text.trim();
    return value.isEmpty ? null : value;
  }

  bool? _booleanValue(String? value) => switch (value) {
        'yes' => true,
        'no' => false,
        _ => null,
      };

  String _booleanChoice(bool? value) => value == null ? 'all' : value ? 'yes' : 'no';

  bool _validateRanges() {
    final minPrice = _doubleValue(_minPrice);
    final maxPrice = _doubleValue(_maxPrice);
    final minArea = _doubleValue(_minArea);
    final maxArea = _doubleValue(_maxArea);
    final bedroomsMin = _intValue(_bedroomsMin);
    final bedroomsMax = _intValue(_bedroomsMax);
    final bathroomsMin = _intValue(_bathroomsMin);
    final bathroomsMax = _intValue(_bathroomsMax);
    final parkingMin = _intValue(_parkingMin);
    final parkingMax = _intValue(_parkingMax);

    if (_hasText(_minPrice) && minPrice == null ||
        _hasText(_maxPrice) && maxPrice == null ||
        _hasText(_minArea) && minArea == null ||
        _hasText(_maxArea) && maxArea == null) {
      _validationMessage = 'أدخل السعر والمساحة بأرقام صحيحة.';
      return false;
    }
    if (_hasText(_bedroomsMin) && bedroomsMin == null ||
        _hasText(_bedroomsMax) && bedroomsMax == null ||
        _hasText(_bathroomsMin) && bathroomsMin == null ||
        _hasText(_bathroomsMax) && bathroomsMax == null ||
        _hasText(_parkingMin) && parkingMin == null ||
        _hasText(_parkingMax) && parkingMax == null) {
      _validationMessage = 'أدخل أعداد الغرف والحمامات والمواقف كأعداد صحيحة.';
      return false;
    }
    if (minPrice != null && maxPrice != null && minPrice > maxPrice) {
      _validationMessage = 'الحد الأعلى للسعر يجب أن يكون أكبر من الحد الأدنى.';
      return false;
    }
    if (minArea != null && maxArea != null && minArea > maxArea) {
      _validationMessage = 'المساحة القصوى يجب أن تكون أكبر من المساحة الدنيا.';
      return false;
    }
    if (bedroomsMin != null && bedroomsMax != null && bedroomsMin > bedroomsMax ||
        bathroomsMin != null && bathroomsMax != null && bathroomsMin > bathroomsMax ||
        parkingMin != null && parkingMax != null && parkingMin > parkingMax) {
      _validationMessage = 'تحقق من الحدود الدنيا والعليا للأعداد.';
      return false;
    }
    if ([bedroomsMin, bedroomsMax, bathroomsMin, bathroomsMax]
        .whereType<int>()
        .any((value) => value < 0 || value > 20) ||
        [parkingMin, parkingMax].whereType<int>().any((value) => value < 0 || value > 50)) {
      _validationMessage = 'عدد الغرف والحمامات بين 0 و20 والمواقف بين 0 و50.';
      return false;
    }
    if ([minPrice, maxPrice, minArea, maxArea].whereType<double>().any((value) => value < 0)) {
      _validationMessage = 'لا يمكن إدخال قيم سالبة.';
      return false;
    }
    _validationMessage = null;
    return true;
  }

  bool _hasText(TextEditingController controller) => controller.text.trim().isNotEmpty;

  PropertyQuery _buildQuery() => widget.initial.copyWith(
        city: _textValue(_city),
        district: _textValue(_district),
        neighborhood: _textValue(_neighborhood),
        propertyType: _propertyType,
        minPrice: _doubleValue(_minPrice),
        maxPrice: _doubleValue(_maxPrice),
        minArea: _doubleValue(_minArea),
        maxArea: _doubleValue(_maxArea),
        bedrooms: null,
        bathrooms: null,
        parkingSpaces: null,
        bedroomsMin: _intValue(_bedroomsMin),
        bedroomsMax: _intValue(_bedroomsMax),
        bathroomsMin: _intValue(_bathroomsMin),
        bathroomsMax: _intValue(_bathroomsMax),
        parkingSpacesMin: _intValue(_parkingMin),
        parkingSpacesMax: _intValue(_parkingMax),
        isFurnished: _furnished,
        isNew: _isNew,
        isFeatured: _isFeatured,
      );

  void _apply() {
    if (!_validateRanges()) {
      setState(() {});
      return;
    }
    Navigator.of(context).pop(_buildQuery());
  }

  void _clear() {
    setState(() {
      for (final controller in [
        _city,
        _district,
        _neighborhood,
        _minPrice,
        _maxPrice,
        _minArea,
        _maxArea,
        _bedroomsMin,
        _bedroomsMax,
        _bathroomsMin,
        _bathroomsMax,
        _parkingMin,
        _parkingMax,
      ]) {
        controller.clear();
      }
      _propertyType = null;
      _furnished = null;
      _isNew = null;
      _isFeatured = null;
      _validationMessage = null;
    });
  }

  InputDecoration _decoration(String label, IconData icon) => InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 19),
        isDense: true,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
      );

  Widget _textField(TextEditingController controller, String label, IconData icon) =>
      TextField(
        controller: controller,
        textDirection: TextDirection.rtl,
        decoration: _decoration(label, icon),
        textInputAction: TextInputAction.next,
      );

  Widget _numberField(
    TextEditingController controller,
    String label, {
    required bool decimal,
  }) =>
      TextField(
        controller: controller,
        keyboardType: TextInputType.numberWithOptions(decimal: decimal),
        textDirection: TextDirection.ltr,
        decoration: InputDecoration(
          labelText: label,
          isDense: true,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        ),
      );

  Widget _booleanDropdown({
    required String label,
    required bool? value,
    required ValueChanged<bool?> onChanged,
  }) =>
      DropdownButtonFormField<String>(
        initialValue: _booleanChoice(value),
        isExpanded: true,
        decoration: _decoration(label, Icons.tune_rounded),
        items: const [
          DropdownMenuItem(value: 'all', child: Text('الكل')),
          DropdownMenuItem(value: 'yes', child: Text('نعم')),
          DropdownMenuItem(value: 'no', child: Text('لا')),
        ],
        onChanged: (choice) => onChanged(_booleanValue(choice)),
      );

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    final availableTypes = _availableTypes;
    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(bottom: bottomInset),
        child: SizedBox(
          height: MediaQuery.sizeOf(context).height * .88,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 14, 12, 8),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        'تصفية العقارات',
                        style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      tooltip: 'إغلاق الفلاتر',
                      icon: const Icon(Icons.close_rounded),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      DropdownButtonFormField<String>(
                        initialValue: _propertyType ?? '',
                        isExpanded: true,
                        decoration: _decoration('نوع العقار', Icons.home_work_rounded),
                        items: [
                          const DropdownMenuItem(value: '', child: Text('جميع الأنواع')),
                          ...availableTypes.map(
                            (item) => DropdownMenuItem(
                              value: item.slug!,
                              child: Text(item.name, overflow: TextOverflow.ellipsis),
                            ),
                          ),
                        ],
                        onChanged: (value) => setState(
                          () => _propertyType = value == null || value.isEmpty ? null : value,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(child: _textField(_city, 'المدينة', Icons.location_city_rounded)),
                          const SizedBox(width: 10),
                          Expanded(child: _textField(_district, 'الحي / المديرية', Icons.map_outlined)),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _textField(_neighborhood, 'المنطقة الفرعية', Icons.place_outlined),
                      const SizedBox(height: 16),
                      Text('نطاق السعر', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _numberField(_minPrice, 'من', decimal: true)),
                          const SizedBox(width: 10),
                          Expanded(child: _numberField(_maxPrice, 'إلى', decimal: true)),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text('المساحة (م²)', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _numberField(_minArea, 'من', decimal: true)),
                          const SizedBox(width: 10),
                          Expanded(child: _numberField(_maxArea, 'إلى', decimal: true)),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text('غرف النوم', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _numberField(_bedroomsMin, 'الحد الأدنى', decimal: false)),
                          const SizedBox(width: 10),
                          Expanded(child: _numberField(_bedroomsMax, 'الحد الأعلى', decimal: false)),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text('الحمامات', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _numberField(_bathroomsMin, 'الحد الأدنى', decimal: false)),
                          const SizedBox(width: 10),
                          Expanded(child: _numberField(_bathroomsMax, 'الحد الأعلى', decimal: false)),
                        ],
                      ),
                      const SizedBox(height: 14),
                      Text('مواقف السيارات', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(child: _numberField(_parkingMin, 'الحد الأدنى', decimal: false)),
                          const SizedBox(width: 10),
                          Expanded(child: _numberField(_parkingMax, 'الحد الأعلى', decimal: false)),
                        ],
                      ),
                      const SizedBox(height: 14),
                      _booleanDropdown(
                        label: 'التأثيث',
                        value: _furnished,
                        onChanged: (value) => setState(() => _furnished = value),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: _booleanDropdown(
                              label: 'عقار جديد',
                              value: _isNew,
                              onChanged: (value) => setState(() => _isNew = value),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: _booleanDropdown(
                              label: 'مميز',
                              value: _isFeatured,
                              onChanged: (value) => setState(() => _isFeatured = value),
                            ),
                          ),
                        ],
                      ),
                      if (_validationMessage != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          _validationMessage!,
                          style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 16),
                child: Row(
                  children: [
                    TextButton.icon(
                      onPressed: _clear,
                      icon: const Icon(Icons.restart_alt_rounded),
                      label: const Text('مسح الفلاتر'),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: _apply,
                        icon: const Icon(Icons.check_rounded),
                        label: const Text('عرض النتائج'),
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
