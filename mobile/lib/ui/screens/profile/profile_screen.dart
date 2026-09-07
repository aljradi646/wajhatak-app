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

/// تعديل الملف الشخصي — كل الحقول المدعومة فعليًا في الـ API.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key, required this.user});
  final LuxUser user;

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  late final TextEditingController _name;
  late final TextEditingController _phone;
  bool _saving = false;
  XFile? _selectedAvatar;

  static const int _maxAvatarBytes = 2 * 1024 * 1024;
  static const Set<String> _allowedMimes = {'image/jpeg', 'image/png'};

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.user.name);
    _phone = TextEditingController(text: widget.user.phone ?? '');
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _name.text.trim();
    if (name.length < 2) {
      util.notice(context, 'الاسم يجب أن يحتوي على حرفين على الأقل.');
      return;
    }

    if (_selectedAvatar != null) {
      final file = File(_selectedAvatar!.path);
      final sizeBytes = await file.length();
      if (sizeBytes > _maxAvatarBytes) {
        if (mounted) {
          util.notice(context, 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.');
        }
        return;
      }
      final mimeType = await _getMimeType(_selectedAvatar!.path);
      if (mimeType != null && !_allowedMimes.contains(mimeType)) {
        if (mounted) {
          util.notice(context, 'نوع الصورة غير مدعوم. استخدم JPEG أو PNG.');
        }
        return;
      }
    }

    setState(() => _saving = true);
    try {
      if (_selectedAvatar != null) {
        try {
          await ref
              .read(sessionProvider.notifier)
              .uploadAvatar(_selectedAvatar!.path);
        } on ApiFailure catch (e) {
          if (mounted) util.notice(context, e.message);
          return;
        }
      }
      try {
        await ref
            .read(sessionProvider.notifier)
            .updateProfile(name: name, phone: _phone.text.trim(), locale: 'ar');
      } on ApiFailure catch (e) {
        if (mounted) util.notice(context, e.message);
        return;
      }
      if (!mounted) return;
      Navigator.of(context).pop();
      util.notice(context, 'تم حفظ بيانات الملف الشخصي.');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<String?> _getMimeType(String path) async {
    try {
      final bytes = await File(path).openRead(0, 4).toList();
      if (bytes.isEmpty) return null;
      final header = bytes[0];
      if (header.length >= 4) {
        if (header[0] == 0xFF && header[1] == 0xD8) return 'image/jpeg';
        if (header[0] == 0x89 &&
            header[1] == 0x50 &&
            header[2] == 0x4E &&
            header[3] == 0x47) {
          return 'image/png';
        }
      }
    } catch (_) {
      // فحص الرأس فشل — اترك الحسم للخادم (هو المرجع النهائي).
    }
    return null;
  }

  Future<void> _chooseAvatar() async {
    try {
      final file = await ImagePicker().pickImage(
        source: ImageSource.gallery,
        maxWidth: 1600,
        maxHeight: 1600,
        imageQuality: 88,
        requestFullMetadata: false,
      );
      if (file != null && mounted) {
        final sizeBytes = await File(file.path).length();
        if (sizeBytes > _maxAvatarBytes) {
          if (mounted) {
            util.notice(context, 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.');
          }
          return;
        }
        setState(() => _selectedAvatar = file);
      }
    } on Exception {
      if (mounted) {
        util.notice(context, 'تعذر فتح منتقي الصور. حاول مرة أخرى.');
      }
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: WajhatakScreenHeader(title: 'تعديل الملف الشخصي'),
    body: SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          // بطاقة الأفاتار
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
                colors: [
                  Theme.of(context).colorScheme.primaryContainer,
                  Theme.of(context).colorScheme.surface,
                ],
              ),
              borderRadius: BorderRadius.circular(26),
              border: Border.all(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            child: Column(
              children: [
                Stack(
                  children: [
                    Container(
                      width: 108,
                      height: 108,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Theme.of(context).colorScheme.primary,
                          width: 2.5,
                        ),
                      ),
                      child: CircleAvatar(
                        radius: 51,
                        backgroundColor: Theme.of(
                          context,
                        ).colorScheme.surfaceContainerHigh,
                        backgroundImage: _selectedAvatar == null
                            ? null
                            : FileImage(File(_selectedAvatar!.path)),
                        child: _selectedAvatar == null
                            ? UserAvatar(user: widget.user, radius: 48)
                            : null,
                      ),
                    ),
                    PositionedDirectional(
                      bottom: 0,
                      end: 0,
                      child: Material(
                        color: Theme.of(context).colorScheme.primary,
                        borderRadius: BorderRadius.circular(17),
                        child: InkWell(
                          onTap: _saving ? null : _chooseAvatar,
                          borderRadius: BorderRadius.circular(17),
                          child: SizedBox(
                            width: 38,
                            height: 38,
                            child: Icon(
                              _selectedAvatar == null
                                  ? Icons.photo_camera_rounded
                                  : Icons.check_rounded,
                              color: Theme.of(context).colorScheme.onPrimary,
                              size: 19,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Text(
                  'اختر صورة شخصية',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'PNG أو JPEG — الحد الأقصى 2 ميجابايت',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 22),
          _FieldTile(
            icon: Icons.person_rounded,
            label: 'الاسم الكامل',
            child: TextField(
              controller: _name,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                filled: false,
              ),
            ),
          ),
          const SizedBox(height: 12),
          _FieldTile(
            icon: Icons.phone_rounded,
            label: 'رقم الجوال',
            child: TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                filled: false,
                hintText: 'أضف رقمك لتسهيل التواصل',
              ),
            ),
          ),
          const SizedBox(height: 12),
          _FieldTile(
            icon: Icons.alternate_email_rounded,
            label: 'البريد الإلكتروني (غير قابل للتعديل)',
            child: Text(
              widget.user.email,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 12),
          _FieldTile(
            icon: Icons.verified_user_rounded,
            label: 'نوع الحساب',
            child: Text(
              widget.user.isAgent ? 'وكيل عقاري' : 'عميل',
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(height: 26),
          FilledButton.icon(
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.save_rounded, size: 20),
            label: Text(_saving ? 'جارٍ الحفظ…' : 'حفظ التعديلات'),
          ),
        ],
      ),
    ),
  );
}

class _FieldTile extends StatelessWidget {
  const _FieldTile({
    required this.icon,
    required this.label,
    required this.child,
  });

  final IconData icon;
  final String label;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.fromLTRB(13, 12, 13, 5),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(WajhatakRadius.input),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              TintedIcon(icon: icon, tone: AccentTone.emerald, size: 34, iconSize: 17),
              const SizedBox(width: 10),
              Text(
                label,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          Padding(
            padding: const EdgeInsets.only(right: 44, top: 2, bottom: 8),
            child: child,
          ),
        ],
      ),
    );
  }
}
