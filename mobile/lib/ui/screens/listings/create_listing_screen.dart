import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';

/// إضافة عقار: نموذج عصري بأقسام — أساسي، سعر + عملة، موقع متتالي (دولة ← محافظة ← مدينة/منطقة)، مزايا، صور.
class CreateListingScreen extends ConsumerStatefulWidget {
  const CreateListingScreen({super.key});

  @override
  ConsumerState<CreateListingScreen> createState() =>
      _CreateListingScreenState();
}

class _CreateListingScreenState extends ConsumerState<CreateListingScreen> {
  final _form = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _description = TextEditingController();
  final _price = TextEditingController();
  final _area = TextEditingController();
  final _bedrooms = TextEditingController();
  final _bathrooms = TextEditingController();
  final _parkingSpaces = TextEditingController();
  final _neighborhood = TextEditingController();
  final _address = TextEditingController();
  final _picker = ImagePicker();
  final _images = <File>[];
  final _featureIds = <int>{};
  int? _typeId;
  String _transaction = 'sale';
  String _currency = 'YER';
  bool _furnished = false;
  bool _isNew = false;
  bool _submitting = false;

  // الموقع المتتالي
  LocationItem? _country;
  LocationItem? _region;
  LocationItem? _city;
  LocationItem? _areaLocation;

  @override
  void initState() {
    super.initState();
    // العملة الافتراضية من الخادم (YER) عند الوصول.
    ref.listenManual(currenciesProvider, (prev, next) {
      final currencies = next.asData?.value;
      if (currencies != null && currencies.isNotEmpty && mounted) {
        final defaultCurrency = currencies.firstWhere(
          (c) => c.isDefault,
          orElse: () => currencies.first,
        );
        setState(() => _currency = defaultCurrency.code);
      }
    }, fireImmediately: true);
  }

  @override
  void dispose() {
    for (final controller in [
      _title,
      _description,
      _price,
      _area,
      _bedrooms,
      _bathrooms,
      _parkingSpaces,
      _neighborhood,
      _address,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _pickImages() async {
    final picked = await _picker.pickMultiImage(
      imageQuality: 82,
      maxWidth: 1920,
    );
    if (picked.isNotEmpty && mounted) {
      final remaining = 12 - _images.length;
      final toAdd = picked.take(remaining).toList();
      if (toAdd.length < picked.length && mounted) {
        util.notice(context, 'يمكنك إضافة 12 صورة كحد أقصى.');
      }
      setState(() => _images.addAll(toAdd.map((image) => File(image.path))));
    }
  }

  void _removeImage(int index) {
    setState(() => _images.removeAt(index));
  }

  Future<void> _submit() async {
    if (!(_form.currentState?.validate() ?? false) || _typeId == null) {
      if (_typeId == null) util.notice(context, 'اختر نوع العقار.');
      return;
    }
    setState(() => _submitting = true);
    try {
      await ref.read(propertyRepositoryProvider).create({
        'title': _title.text.trim(),
        'description': _description.text.trim(),
        'property_type_id': _typeId,
        'transaction_type': _transaction,
        'price': double.parse(_price.text.trim()),
        'currency': _currency,
        if (_area.text.trim().isNotEmpty)
          'area': double.parse(_area.text.trim()),
        if (_bedrooms.text.trim().isNotEmpty)
          'bedrooms': int.parse(_bedrooms.text.trim()),
        if (_bathrooms.text.trim().isNotEmpty)
          'bathrooms': int.parse(_bathrooms.text.trim()),
        if (_parkingSpaces.text.trim().isNotEmpty)
          'parking_spaces': int.parse(_parkingSpaces.text.trim()),
        'is_furnished': _furnished,
        'is_new': _isNew,
        'feature_ids': _featureIds.toList(),
        'location': {
          if (_country != null) 'country_id': _country!.id,
          if (_region != null) 'region_id': _region!.id,
          if (_city != null) 'city_id': _city!.id,
          if (_areaLocation != null) 'area_id': _areaLocation!.id,
          'city': _city?.name ?? _country?.name ?? '',
          if (_areaLocation?.name != null) 'district': _areaLocation!.name,
          if (_neighborhood.text.trim().isNotEmpty)
            'neighborhood': _neighborhood.text.trim(),
          'address': _address.text.trim(),
        },
      }, _images);
      ref.invalidate(myListingsProvider);
      ref.invalidate(propertiesProvider);
      if (mounted) {
        Navigator.of(context).pop();
        util.notice(context, 'تم إرسال العقار للمراجعة قبل النشر.');
      }
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final types = ref.watch(propertyTypesProvider);
    final features = ref.watch(featuresProvider);
    final currencies = ref.watch(currenciesProvider);
    final countries = ref.watch(countriesProvider);

    return Scaffold(
      appBar: WajhatakScreenHeader(
        title: 'إضافة عقار',
        subtitle: 'أدخل تفاصيل عقارك وسيراجعها فريقنا قبل النشر',
      ),
      body: Form(
        key: _form,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 30),
          children: [
            _FormSection(
              icon: Icons.info_rounded,
              tone: AccentTone.emerald,
              title: 'المعلومات الأساسية',
              children: [
                TextFormField(
                  controller: _title,
                  decoration: const InputDecoration(
                    labelText: 'عنوان العقار',
                    prefixIcon: Icon(Icons.title_rounded),
                  ),
                  validator: (value) =>
                      (value ?? '').trim().length >= 5
                      ? null
                      : 'أدخل عنوانًا واضحًا للعقار.',
                ),
                const SizedBox(height: 12),
                types.when(
                  data: (data) => DropdownButtonFormField<int>(
                    initialValue: _typeId,
                    isExpanded: true,
                    menuMaxHeight: 340,
                    decoration: const InputDecoration(
                      labelText: 'نوع العقار',
                      prefixIcon: Icon(Icons.category_rounded),
                    ),
                    items: [
                      for (final item in data)
                        DropdownMenuItem(value: item.id, child: Text(item.name)),
                    ],
                    onChanged: (value) => setState(() => _typeId = value),
                    validator: (value) =>
                        value == null ? 'اختر نوع العقار.' : null,
                  ),
                  loading: () => const Padding(
                    padding: EdgeInsets.symmetric(vertical: 10),
                    child: LuxSkeleton(height: 54),
                  ),
                  error: (error, _) => const Text(
                    'تعذر تحميل أنواع العقارات. تحقق من اتصالك بالشبكة ثم أعد المحاولة.',
                    style: TextStyle(color: Colors.red),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _TransactionOption(
                        label: 'للبيع',
                        icon: Icons.sell_rounded,
                        color: WajhatakColors.emerald,
                        selected: _transaction == 'sale',
                        onTap: () => setState(() => _transaction = 'sale'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _TransactionOption(
                        label: 'للإيجار',
                        icon: Icons.key_rounded,
                        color: WajhatakColors.sky,
                        selected: _transaction == 'rent',
                        onTap: () => setState(() => _transaction = 'rent'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _description,
                  maxLines: 5,
                  decoration: const InputDecoration(
                    labelText: 'وصف العقار',
                    alignLabelWithHint: true,
                    prefixIcon: Icon(Icons.description_rounded),
                  ),
                  validator: (value) =>
                      (value ?? '').trim().length >= 15
                      ? null
                      : 'اكتب وصفًا لا يقل عن 15 حرفًا.',
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _SwitchTile(
                        label: 'مفروش',
                        icon: Icons.chair_rounded,
                        value: _furnished,
                        onChanged: (v) => setState(() => _furnished = v),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _SwitchTile(
                        label: 'عقار جديد',
                        icon: Icons.auto_awesome_rounded,
                        value: _isNew,
                        onChanged: (v) => setState(() => _isNew = v),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            _FormSection(
              icon: Icons.payments_rounded,
              tone: AccentTone.amber,
              title: 'السعر والمساحة',
              children: [
                // [ حقل السعر ] [ قائمة العملة ▼ ]
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      flex: 3,
                      child: TextFormField(
                        controller: _price,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'السعر',
                          prefixIcon: Icon(Icons.attach_money_rounded),
                        ),
                        validator: (value) =>
                            double.tryParse((value ?? '').trim()) != null
                            ? null
                            : 'أدخل سعرًا صحيحًا.',
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      flex: 2,
                      child: currencies.when(
                        data: (list) => DropdownButtonFormField<String>(
                          initialValue: _currency,
                          isExpanded: true,
                          menuMaxHeight: 300,
                          decoration: const InputDecoration(
                            labelText: 'العملة',
                            prefixIcon: Icon(Icons.currency_exchange_rounded),
                          ),
                          items: [
                            for (final currency in list)
                              DropdownMenuItem(
                                value: currency.code,
                                child: Text(
                                  '${currency.flag} ${currency.symbolAr}',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                          ],
                          onChanged: (value) =>
                              setState(() => _currency = value ?? 'YER'),
                        ),
                        loading: () => const Padding(
                          padding: EdgeInsets.symmetric(vertical: 10),
                          child: LuxSkeleton(height: 54),
                        ),
                        error: (_, _) => TextFormField(
                          initialValue: _currency,
                          decoration: const InputDecoration(
                            labelText: 'العملة',
                          ),
                          onSaved: (value) => _currency = value ?? 'YER',
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _area,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'المساحة م²',
                          prefixIcon: Icon(Icons.square_foot_rounded),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _bedrooms,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'غرف النوم',
                          prefixIcon: Icon(Icons.bed_rounded),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _bathrooms,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'الحمامات',
                          prefixIcon: Icon(Icons.bathtub_rounded),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextFormField(
                        controller: _parkingSpaces,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'المواقف',
                          prefixIcon: Icon(Icons.directions_car_rounded),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            _FormSection(
              icon: Icons.location_on_rounded,
              tone: AccentTone.rose,
              title: 'الموقع',
              children: [
                // دولة ← محافظة ← مدينة ← منطقة (قوائم متتالية من الخادم)
                countries.when(
                  data: (list) => _CascadeDropdown<LocationItem>(
                    label: 'الدولة',
                    icon: Icons.public_rounded,
                    items: list,
                    value: _country,
                    onChanged: (value) {
                      setState(() {
                        _country = value;
                        _region = null;
                        _city = null;
                        _areaLocation = null;
                      });
                    },
                  ),
                  loading: () => const Padding(
                    padding: EdgeInsets.symmetric(vertical: 10),
                    child: LuxSkeleton(height: 54),
                  ),
                  error: (_, _) => const SizedBox.shrink(),
                ),
                if (_country != null) ...[
                  const SizedBox(height: 12),
                  ref.watch(regionsProvider(_country!.id)).when(
                    data: (list) => _CascadeDropdown<LocationItem>(
                      label: 'المحافظة / المنطقة',
                      icon: Icons.map_rounded,
                      items: list,
                      value: _region,
                      onChanged: (value) {
                        setState(() {
                          _region = value;
                          _city = null;
                          _areaLocation = null;
                        });
                      },
                    ),
                    loading: () => const Padding(
                      padding: EdgeInsets.symmetric(vertical: 10),
                      child: LuxSkeleton(height: 54),
                    ),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ],
                if (_region != null) ...[
                  const SizedBox(height: 12),
                  ref.watch(citiesProvider(_region!.id)).when(
                    data: (list) => _CascadeDropdown<LocationItem>(
                      label: 'المدينة',
                      icon: Icons.location_city_rounded,
                      items: list,
                      value: _city,
                      onChanged: (value) {
                        setState(() {
                          _city = value;
                          _areaLocation = null;
                        });
                      },
                    ),
                    loading: () => const Padding(
                      padding: EdgeInsets.symmetric(vertical: 10),
                      child: LuxSkeleton(height: 54),
                    ),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ],
                if (_city != null) ...[
                  const SizedBox(height: 12),
                  ref.watch(areasProvider(_city!.id)).when(
                    data: (list) => _CascadeDropdown<LocationItem>(
                      label: 'الحي / المنطقة الفرعية',
                      icon: Icons.signpost_rounded,
                      items: list,
                      value: _areaLocation,
                      onChanged: (value) =>
                          setState(() => _areaLocation = value),
                    ),
                    loading: () => const SizedBox.shrink(),
                    error: (_, _) => const SizedBox.shrink(),
                  ),
                ],
                const SizedBox(height: 12),
                TextFormField(
                  controller: _neighborhood,
                  decoration: const InputDecoration(
                    labelText: 'الحي / الشارع (يدوي - اختياري)',
                    prefixIcon: Icon(Icons.streetview_rounded),
                  ),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _address,
                  decoration: const InputDecoration(
                    labelText: 'العنوان التفصيلي',
                    prefixIcon: Icon(Icons.home_rounded),
                  ),
                  validator: (value) =>
                      (value ?? '').trim().isNotEmpty ? null : 'أدخل العنوان.',
                ),
              ],
            ),
            const SizedBox(height: 16),
            _FormSection(
              icon: Icons.star_rounded,
              tone: AccentTone.violet,
              title: 'المزايا',
              children: [
                features.when(
                  data: (data) => Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final item in data)
                        FilterChip(
                          label: Text(item.name),
                          selected: _featureIds.contains(item.id),
                          showCheckmark: true,
                          onSelected: (selected) => setState(
                            () => selected
                                ? _featureIds.add(item.id)
                                : _featureIds.remove(item.id),
                          ),
                        ),
                    ],
                  ),
                  loading: () => const Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      LuxSkeleton(width: 84, height: 34, radius: 18),
                      LuxSkeleton(width: 66, height: 34, radius: 18),
                      LuxSkeleton(width: 92, height: 34, radius: 18),
                    ],
                  ),
                  error: (error, stackTrace) => const SizedBox.shrink(),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _FormSection(
              icon: Icons.photo_library_rounded,
              tone: AccentTone.sky,
              title: 'صور العقار (حد أقصى 12)',
              children: [
                OutlinedButton.icon(
                  onPressed: _images.length >= 12 ? null : _pickImages,
                  icon: const Icon(Icons.add_photo_alternate_rounded),
                  label: Text(
                    _images.isEmpty
                        ? 'اختيار صور من الجهاز'
                        : 'إضافة المزيد (${_images.length}/12)',
                  ),
                ),
                if (_images.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 110,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _images.length,
                      separatorBuilder: (context, index) =>
                          const SizedBox(width: 8),
                      itemBuilder: (context, index) => Stack(
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(14),
                            child: Image.file(
                              _images[index],
                              width: 120,
                              height: 110,
                              fit: BoxFit.cover,
                            ),
                          ),
                          if (index == 0)
                            Positioned(
                              top: 6,
                              right: 6,
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 7,
                                  vertical: 3,
                                ),
                                decoration: BoxDecoration(
                                  color: WajhatakColors.emerald,
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Text(
                                  'غلاف',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                            ),
                          Positioned(
                            top: 4,
                            left: 4,
                            child: Material(
                              color: Colors.black.withValues(alpha: .55),
                              borderRadius: BorderRadius.circular(99),
                              child: InkWell(
                                onTap: () => _removeImage(index),
                                borderRadius: BorderRadius.circular(99),
                                child: const SizedBox(
                                  width: 26,
                                  height: 26,
                                  child: Icon(
                                    Icons.close_rounded,
                                    size: 15,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 26),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: _submitting ? null : _submit,
                icon: _submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2.2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.send_rounded, size: 20),
                label: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 13),
                  child: Text(
                    _submitting ? 'جارٍ الإرسال…' : 'إرسال للمراجعة',
                    style: const TextStyle(fontSize: 15.5),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// قسم نموذج بحدود ناعمة وأيقونة ملونة.
class _FormSection extends StatelessWidget {
  const _FormSection({
    required this.icon,
    required this.tone,
    required this.title,
    required this.children,
  });

  final IconData icon;
  final AccentTone tone;
  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              TintedIcon(icon: icon, tone: tone, size: 38, iconSize: 19),
              const SizedBox(width: 11),
              Text(
                title,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 15),
          ...children,
        ],
      ),
    );
  }
}

class _TransactionOption extends StatelessWidget {
  const _TransactionOption({
    required this.label,
    required this.icon,
    required this.color,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      decoration: BoxDecoration(
        color: selected ? color.withValues(alpha: .12) : theme.colorScheme.surfaceContainerHigh,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: selected ? color : theme.colorScheme.outline,
          width: selected ? 1.6 : 1,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 13),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, size: 17, color: color),
                const SizedBox(width: 7),
                Text(
                  label,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: selected ? FontWeight.w900 : FontWeight.w700,
                    color: selected ? color : theme.colorScheme.onSurfaceVariant,
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

class _SwitchTile extends StatelessWidget {
  const _SwitchTile({
    required this.label,
    required this.icon,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final IconData icon;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: value
            ? theme.colorScheme.primary.withValues(alpha: .09)
            : theme.colorScheme.surfaceContainerHigh,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: value
              ? theme.colorScheme.primary.withValues(alpha: .4)
              : theme.colorScheme.outline,
        ),
      ),
      child: Row(
        children: [
          Icon(
            icon,
            size: 18,
            color: value
                ? theme.colorScheme.primary
                : theme.colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              style: theme.textTheme.labelLarge?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          Switch.adaptive(value: value, onChanged: onChanged),
        ],
      ),
    );
  }
}

/// قائمة منسدلة متتالية عامة.
class _CascadeDropdown<T extends Object> extends StatelessWidget {
  const _CascadeDropdown({
    required this.label,
    required this.icon,
    required this.items,
    required this.value,
    required this.onChanged,
  });

  final String label;
  final IconData icon;
  final List<T> items;
  final T? value;
  final ValueChanged<T?> onChanged;

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<T>(
      initialValue: value,
      isExpanded: true,
      menuMaxHeight: 340,
      decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
      items: [
        for (final item in items)
          DropdownMenuItem(
            value: item,
            child: Text(
              item is LocationItem
                  ? item.name
                  : item.toString(),
              overflow: TextOverflow.ellipsis,
            ),
          ),
      ],
      onChanged: onChanged,
    );
  }
}
