import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/repositories/account_verification_repository.dart' as verification;
import '../../../state/providers.dart';
import '../../widgets.dart';

/// شاشة بيانات توثيق الوكيل — كل ما تحتاجه الإدارة لقبول الحساب.
/// تُفتح من الملف الشخصي لحسابات الوكلاء غير الموثقة.
class AgentVerificationScreen extends ConsumerStatefulWidget {
  const AgentVerificationScreen({super.key});

  @override
  ConsumerState<AgentVerificationScreen> createState() =>
      _AgentVerificationScreenState();
}

class _AgentVerificationScreenState
    extends ConsumerState<AgentVerificationScreen> {
  final _form = GlobalKey<FormState>();
  final _agencyName = TextEditingController();
  final _jobTitle = TextEditingController();
  final _phone = TextEditingController();
  final _whatsapp = TextEditingController();
  final _city = TextEditingController();
  final _nationalId = TextEditingController();
  final _experienceYears = TextEditingController();
  final _address = TextEditingController();
  final _bio = TextEditingController();
  final _licenseNumber = TextEditingController();

  File? _photo;
  File? _idDocument;
  File? _licenseDocument;
  bool _loading = true;
  bool _saving = false;
  verification.AgentProfileData? _profile;
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final controller in [
      _agencyName, _jobTitle, _phone, _whatsapp, _city,
      _nationalId, _experienceYears, _address, _bio, _licenseNumber,
    ]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    try {
      final profile = await ref.read(accountRepositoryProvider).agentProfile();
      if (!mounted) return;
      setState(() {
        _profile = profile;
        _agencyName.text = profile.agencyName ?? '';
        _jobTitle.text = profile.jobTitle ?? '';
        _phone.text = profile.phone ?? '';
        _whatsapp.text = profile.whatsapp ?? '';
        _city.text = profile.city ?? '';
        _nationalId.text = profile.nationalId ?? '';
        _experienceYears.text = profile.experienceYears?.toString() ?? '';
        _address.text = profile.address ?? '';
        _bio.text = profile.bio ?? '';
        _licenseNumber.text = profile.licenseNumber ?? '';
        _loading = false;
      });
    } on ApiFailure catch (error) {
      if (mounted) {
        setState(() => _loading = false);
        util.notice(context, error.message);
      }
    }
  }

  Future<void> _pickImage(ImageSource source, String purpose) async {
    final picked = await _picker.pickImage(source: source, imageQuality: 85, maxWidth: 1600);
    if (picked == null || !mounted) return;
    setState(() {
      if (purpose == 'photo') _photo = File(picked.path);
      if (purpose == 'id') _idDocument = File(picked.path);
      if (purpose == 'license') _licenseDocument = File(picked.path);
    });
  }

  Future<void> _save() async {
    if (!(_form.currentState?.validate() ?? false) || _saving) return;
    setState(() => _saving = true);
    try {
      final updated = await ref.read(accountRepositoryProvider).saveAgentProfile(
            data: {
              'agency_name': _agencyName.text.trim(),
              'job_title': _jobTitle.text.trim(),
              'phone': _phone.text.trim(),
              'whatsapp': _whatsapp.text.trim(),
              'city': _city.text.trim(),
              'national_id': _nationalId.text.trim(),
              if (_experienceYears.text.trim().isNotEmpty)
                'experience_years': int.tryParse(_experienceYears.text.trim()),
              'address': _address.text.trim(),
              'bio': _bio.text.trim(),
              'license_number': _licenseNumber.text.trim(),
            },
            idDocument: _idDocument,
            licenseDocument: _licenseDocument,
            photo: _photo,
          );
      if (mounted) {
        setState(() => _profile = updated);
        util.notice(
          context,
          updated.isApproved
              ? 'حسابك موثق ✓'
              : 'تم إرسال بياناتك للإدارة — ستراجع قريبًا.',
        );
      }
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'توثيق حساب الوكيل'),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _form,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 30),
                children: [
                  // شريط الحالة.
                  if (_profile != null && _profile!.isRejected)
                    _StatusBanner(
                      tone: AccentTone.rose,
                      icon: Icons.cancel_rounded,
                      title: 'تم رفض التوثيق',
                      body: _profile!.rejectionReason ?? 'راجع بياناتك وأعد الإرسال.',
                    )
                  else if (_profile != null && _profile!.isPending)
                    _StatusBanner(
                      tone: AccentTone.amber,
                      icon: Icons.hourglass_top_rounded,
                      title: 'قيد مراجعة الإدارة',
                      body: 'لا يمكنك نشر العقارات حتى يتم توثيق حسابك. أكمل بياناتك لزيادة فرصة القبول.',
                    )
                  else if (_profile != null && _profile!.isApproved)
                    _StatusBanner(
                      tone: AccentTone.emerald,
                      icon: Icons.verified_rounded,
                      title: 'حسابك موثق ✓',
                      body: 'يمكنك إضافة ونشر العقارات بحرية الآن.',
                    ),

                  _Section(
                    title: 'بيانات المكتب العقاري',
                    children: [
                      _Field(controller: _agencyName, label: 'اسم المكتب / الشركة *', icon: Icons.business_rounded, validator: true),
                      _Field(controller: _jobTitle, label: 'المسمى الوظيفي * (مثال: مسوق عقاري)', icon: Icons.badge_rounded, validator: true),
                      _Field(controller: _phone, label: 'رقم الجوال للتواصل *', icon: Icons.phone_rounded, keyboardType: TextInputType.phone, validator: true),
                      _Field(controller: _whatsapp, label: 'رقم واتساب (اختياري)', icon: Icons.chat_rounded, keyboardType: TextInputType.phone),
                      _Field(controller: _city, label: 'مدينة العمل الأساسية *', icon: Icons.location_city_rounded, validator: true),
                      _Field(controller: _address, label: 'العنوان الوطني (اختياري)', icon: Icons.home_work_rounded),
                      _Field(controller: _experienceYears, label: 'سنوات الخبرة', icon: Icons.work_history_rounded, keyboardType: TextInputType.number),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _Section(
                    title: 'التوثيق الرسمي',
                    children: [
                      _Field(controller: _nationalId, label: 'الرقم الوطني / رقم الهوية *', icon: Icons.credit_card_rounded, validator: true),
                      _Field(controller: _licenseNumber, label: 'رقم رخصة الوساطة (إن وُجد)', icon: Icons.policy_rounded),
                      _DocumentTile(
                        label: 'صورة الهوية / البطاقة',
                        icon: Icons.badge_rounded,
                        file: _idDocument,
                        existingUrl: _profile?.idDocumentUrl,
                        onPick: () => _pickImage(ImageSource.gallery, 'id'),
                      ),
                      _DocumentTile(
                        label: 'صورة رخصة الوساطة',
                        icon: Icons.receipt_long_rounded,
                        file: _licenseDocument,
                        existingUrl: _profile?.licenseDocumentUrl,
                        onPick: () => _pickImage(ImageSource.gallery, 'license'),
                      ),
                      _DocumentTile(
                        label: 'صورتك الشخصية (تظهر في ملفك)',
                        icon: Icons.person_rounded,
                        file: _photo,
                        existingUrl: _profile?.photoUrl,
                        onPick: () => _pickImage(ImageSource.gallery, 'photo'),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _Section(
                    title: 'نبذة تعريفية',
                    children: [
                      TextFormField(
                        controller: _bio,
                        maxLines: 4,
                        decoration: const InputDecoration(
                          labelText: 'نبذة عنك وخبراتك العقارية',
                          alignLabelWithHint: true,
                          prefixIcon: Icon(Icons.description_rounded),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  FilledButton.icon(
                    onPressed: _saving ? null : _save,
                    icon: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white),
                          )
                        : const Icon(Icons.send_rounded, size: 20),
                    label: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      child: Text(
                        _saving ? 'جارٍ الحفظ…' : 'حفظ وإرسال للمراجعة',
                        style: const TextStyle(fontSize: 15.5),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

class _StatusBanner extends StatelessWidget {
  const _StatusBanner({
    required this.tone,
    required this.icon,
    required this.title,
    required this.body,
  });

  final AccentTone tone;
  final IconData icon;
  final String title;
  final String body;

  @override
  Widget build(BuildContext context) {
    final color = tone.color(Theme.of(context).colorScheme);
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: .4)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: TextStyle(fontWeight: FontWeight.w900, color: color)),
                const SizedBox(height: 3),
                Text(body, style: const TextStyle(fontSize: 12.5, height: 1.4)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});

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
          Text(title, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 14),
          ...children,
        ],
      ),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.validator = false,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool validator;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        decoration: InputDecoration(labelText: label, prefixIcon: Icon(icon)),
        validator: validator
            ? (value) => (value ?? '').trim().isNotEmpty ? null : 'هذا الحقل مطلوب.'
            : null,
      ),
    );
  }
}

class _DocumentTile extends StatelessWidget {
  const _DocumentTile({
    required this.label,
    required this.icon,
    required this.file,
    required this.onPick,
    this.existingUrl,
  });

  final String label;
  final IconData icon;
  final File? file;
  final String? existingUrl;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    final hasNew = file != null;
    final hasExisting = existingUrl != null;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: hasNew
            ? WajhatakColors.emerald.withValues(alpha: .08)
            : Theme.of(context).colorScheme.surfaceContainerHigh,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: hasNew ? WajhatakColors.emerald : Theme.of(context).colorScheme.outline,
        ),
      ),
      child: ListTile(
        leading: hasNew
            ? ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.file(file!, width: 40, height: 40, fit: BoxFit.cover),
              )
            : Icon(icon, color: hasExisting ? WajhatakColors.emerald : null),
        title: Text(label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
        subtitle: Text(
          hasNew ? 'جاهزة للرفع ✓' : (hasExisting ? 'مرفوعة مسبقًا — اضغط للاستبدال' : 'لم تُرفع بعد'),
          style: const TextStyle(fontSize: 11.5),
        ),
        trailing: const Icon(Icons.attach_file_rounded, size: 20),
        onTap: onPick,
      ),
    );
  }
}
