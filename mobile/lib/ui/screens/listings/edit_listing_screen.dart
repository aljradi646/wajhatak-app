import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';

class EditListingScreen extends ConsumerWidget {
  const EditListingScreen({super.key, required this.propertyId});
  final int propertyId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final property = ref.watch(propertyDetailProvider(propertyId));
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'تعديل العقار', subtitle: 'حدّث بيانات وصور عقارك'),
      body: LuxAsyncView<LuxProperty>(
        value: property,
        errorRetry: () => ref.invalidate(propertyDetailProvider(propertyId)),
        loading: const _EditListingSkeleton(),
        data: (item) => _EditListingForm(property: item),
      ),
    );
  }
}

class _EditListingSkeleton extends StatelessWidget {
  const _EditListingSkeleton();

  @override
  Widget build(BuildContext context) => ListView(
    padding: const EdgeInsets.all(20),
    children: const [
      LuxSkeleton(height: 54),
      SizedBox(height: 14),
      LuxSkeleton(height: 130),
      SizedBox(height: 14),
      LuxSkeleton(height: 54),
      SizedBox(height: 14),
      LuxSkeleton(height: 54),
      SizedBox(height: 22),
      LuxSkeleton(height: 52),
    ],
  );
}

class _EditListingForm extends ConsumerStatefulWidget {
  const _EditListingForm({required this.property});
  final LuxProperty property;

  @override
  ConsumerState<_EditListingForm> createState() => _EditListingFormState();
}

class _EditListingFormState extends ConsumerState<_EditListingForm> {
  final _form = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _description;
  late final TextEditingController _price;
  late final TextEditingController _area;
  late final TextEditingController _bedrooms;
  late final TextEditingController _bathrooms;
  late final TextEditingController _parkingSpaces;
  late final TextEditingController _city;
  late final TextEditingController _district;
  late final TextEditingController _address;
  late String _transaction;
  late String _currency;
  late int? _typeId;
  late bool _furnished;
  late bool _isNew;
  late final Set<int> _featureIds;
  bool _saving = false;
  bool _uploadingImage = false;

  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    final item = widget.property;
    _title = TextEditingController(text: item.title);
    _description = TextEditingController(text: item.description ?? '');
    _price = TextEditingController(text: item.price.toStringAsFixed(0));
    _area = TextEditingController(text: item.area?.toStringAsFixed(0) ?? '');
    _bedrooms = TextEditingController(
      text: item.bedrooms?.toString() ?? '',
    );
    _bathrooms = TextEditingController(
      text: item.bathrooms?.toString() ?? '',
    );
    _parkingSpaces = TextEditingController(
      text: item.parkingSpaces?.toString() ?? '',
    );
    _city = TextEditingController(text: item.location?.city ?? '');
    _district = TextEditingController(text: item.location?.district ?? '');
    _address = TextEditingController(text: item.location?.address ?? '');
    _transaction = item.transactionType;
    _currency = item.currency;
    _typeId = item.typeId;
    _furnished = item.isFurnished;
    _isNew = item.isNew;
    _featureIds = Set<int>.from(item.featureIds);
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
      _city,
      _district,
      _address,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!(_form.currentState?.validate() ?? false)) return;
    setState(() => _saving = true);
    try {
      await ref.read(propertyRepositoryProvider).update(widget.property.id, {
        'title': _title.text.trim(),
        'description': _description.text.trim(),
        'price': double.parse(_price.text.trim()),
        'currency': _currency,
        'transaction_type': _transaction,
        if (_typeId != null) 'property_type_id': _typeId,
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
          'city': _city.text.trim(),
          if (_district.text.trim().isNotEmpty)
            'district': _district.text.trim(),
          'address': _address.text.trim(),
        },
      });
      ref.invalidate(myListingsProvider);
      ref.invalidate(propertyDetailProvider(widget.property.id));
      ref.invalidate(propertiesProvider);
      if (mounted) {
        Navigator.of(context).pop();
        util.notice(context, 'تم حفظ تعديلات العقار.');
      }
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _uploadImage() async {
    final picked = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 82,
      maxWidth: 1920,
    );
    if (picked == null || !mounted) return;
    setState(() => _uploadingImage = true);
    try {
      await ref
          .read(propertyRepositoryProvider)
          .uploadImage(widget.property.id, File(picked.path));
      ref.invalidate(propertyDetailProvider(widget.property.id));
      if (mounted) util.notice(context, 'تم رفع الصورة بنجاح.');
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _uploadingImage = false);
    }
  }

  Future<void> _deleteImage(PropertyImage image) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('حذف الصورة'),
        content: const Text('هل تريد حذف هذه الصورة؟ لا يمكن التراجع.'),
        actions: [
          Row(
            children: [
              Expanded(
                child: TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: const Text('إلغاء'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  onPressed: () => Navigator.pop(ctx, true),
                  child: const Text('حذف'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref
          .read(propertyRepositoryProvider)
          .deleteImage(widget.property.id, image.id);
      ref.invalidate(propertyDetailProvider(widget.property.id));
      if (mounted) util.notice(context, 'تم حذف الصورة.');
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    }
  }

  Future<void> _setCover(PropertyImage image) async {
    try {
      await ref
          .read(propertyRepositoryProvider)
          .setCoverImage(widget.property.id, image.id);
      ref.invalidate(propertyDetailProvider(widget.property.id));
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final types = ref.watch(propertyTypesProvider);
    final features = ref.watch(featuresProvider);
    final images = widget.property.images;
    final theme = Theme.of(context);
    return SafeArea(
      child: Form(
        key: _form,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              'المعلومات الأساسية',
              style: theme.textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _title,
              decoration: const InputDecoration(labelText: 'عنوان العقار'),
              validator: (value) => (value ?? '').trim().length < 4
                  ? 'أدخل عنوانًا واضحًا.'
                  : null,
            ),
            const SizedBox(height: 12),
            types.when(
              data: (data) => DropdownButtonFormField<int>(
                initialValue: _typeId,
                isExpanded: true,
                decoration: const InputDecoration(labelText: 'نوع العقار'),
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
            DropdownButtonFormField<String>(
              initialValue: _transaction,
              decoration: const InputDecoration(labelText: 'نوع العملية'),
              items: const [
                DropdownMenuItem(value: 'sale', child: Text('للبيع')),
                DropdownMenuItem(value: 'rent', child: Text('للإيجار')),
              ],
              onChanged: (value) {
                if (value != null) setState(() => _transaction = value);
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _price,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'السعر',
                prefixIcon: Icon(Icons.attach_money_rounded),
              ),
              validator: (value) => (double.tryParse(value ?? '') ?? 0) > 0
                  ? null
                  : 'أدخل سعرًا صحيحًا.',
            ),
            const SizedBox(height: 12),
            _CurrencyField(
              value: _currency,
              onChanged: (value) => setState(() => _currency = value),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _area,
                    keyboardType: TextInputType.number,
                    decoration:
                        const InputDecoration(labelText: 'المساحة م²'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _bedrooms,
                    keyboardType: TextInputType.number,
                    decoration:
                        const InputDecoration(labelText: 'غرف النوم'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: TextFormField(
                    controller: _bathrooms,
                    keyboardType: TextInputType.number,
                    decoration:
                        const InputDecoration(labelText: 'الحمامات'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _parkingSpaces,
              keyboardType: TextInputType.number,
              decoration:
                  const InputDecoration(labelText: 'مواقف السيارات'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _description,
              minLines: 4,
              maxLines: 7,
              decoration: const InputDecoration(labelText: 'الوصف'),
              validator: (value) => (value ?? '').trim().length < 10
                  ? 'أضف وصفًا أوضح للعقار.'
                  : null,
            ),
            const SizedBox(height: 10),
            SwitchListTile.adaptive(
              value: _furnished,
              onChanged: (value) => setState(() => _furnished = value),
              title: const Text('مفروش'),
              contentPadding: EdgeInsets.zero,
            ),
            SwitchListTile.adaptive(
              value: _isNew,
              onChanged: (value) => setState(() => _isNew = value),
              title: const Text('عقار جديد'),
              contentPadding: EdgeInsets.zero,
            ),
            const SizedBox(height: 24),
            Text(
              'الموقع',
              style: theme.textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _city,
              decoration: const InputDecoration(labelText: 'المدينة'),
              validator: (value) =>
                  (value ?? '').trim().isEmpty ? 'أدخل المدينة.' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _district,
              decoration: const InputDecoration(labelText: 'الحي'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _address,
              decoration: const InputDecoration(labelText: 'العنوان'),
              validator: (value) =>
                  (value ?? '').trim().isEmpty ? 'أدخل العنوان.' : null,
            ),
            const SizedBox(height: 24),
            Text(
              'المزايا',
              style: theme.textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            features.when(
              data: (data) => Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final item in data)
                    FilterChip(
                      label: Text(item.name),
                      selected: _featureIds.contains(item.id),
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
              error: (error, _) => const SizedBox.shrink(),
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'صور العقار',
                    style: theme.textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w800),
                  ),
                ),
                TextButton.icon(
                  onPressed: _uploadingImage ? null : _uploadImage,
                  icon: _uploadingImage
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.add_photo_alternate_outlined, size: 20),
                  label: Text(_uploadingImage ? 'جارٍ الرفع…' : 'إضافة صورة'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (images.isEmpty)
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade300),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Icon(Icons.image_outlined, size: 48, color: Colors.grey.shade400),
                    const SizedBox(height: 8),
                    Text(
                      'لا توجد صور حالياً',
                      style: TextStyle(color: Colors.grey.shade600),
                    ),
                  ],
                ),
              )
            else
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  crossAxisSpacing: 8,
                  mainAxisSpacing: 8,
                ),
                itemCount: images.length,
                itemBuilder: (context, index) {
                  final image = images[index];
                  return ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.network(image.url, fit: BoxFit.cover),
                        if (image.isCover)
                          Positioned(
                            top: 4,
                            right: 4,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.black54,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text(
                                'غلاف',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                ),
                              ),
                            ),
                          ),
                        Positioned(
                          top: 2,
                          left: 2,
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (!image.isCover)
                                GestureDetector(
                                  onTap: () => _setCover(image),
                                  child: Container(
                                    padding: const EdgeInsets.all(4),
                                    decoration: const BoxDecoration(
                                      color: Colors.black54,
                                      shape: BoxShape.circle,
                                    ),
                                    child: const Icon(
                                      Icons.star_border,
                                      color: Colors.white,
                                      size: 14,
                                    ),
                                  ),
                                ),
                              const SizedBox(width: 4),
                              GestureDetector(
                                onTap: () => _deleteImage(image),
                                child: Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(
                                    color: Colors.black54,
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(
                                    Icons.close,
                                    color: Colors.white,
                                    size: 14,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            const SizedBox(height: 28),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _saving ? null : _save,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  child: Text(
                    _saving ? 'جارٍ الحفظ…' : 'حفظ التعديلات',
                    style: const TextStyle(fontSize: 16),
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


/// حقل اختيار العملة — data-driven من GET /api/v1/currencies.
class _CurrencyField extends ConsumerWidget {
  const _CurrencyField({required this.value, required this.onChanged});

  final String value;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currencies = ref.watch(currenciesProvider);
    return currencies.when(
      data: (list) => DropdownButtonFormField<String>(
        initialValue: list.any((c) => c.code == value) ? value : (list.where((c) => c.isDefault).isEmpty ? list.first.code : list.where((c) => c.isDefault).first.code),
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
                '${currency.flag} ${currency.nameAr}',
                overflow: TextOverflow.ellipsis,
              ),
            ),
        ],
        onChanged: (v) {
          if (v != null) onChanged(v);
        },
      ),
      loading: () => const Padding(
        padding: EdgeInsets.symmetric(vertical: 10),
        child: LuxSkeleton(height: 54),
      ),
      error: (_, _) => const SizedBox.shrink(),
    );
  }
}
