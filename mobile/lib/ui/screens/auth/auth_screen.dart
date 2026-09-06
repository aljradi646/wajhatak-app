import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../state/providers.dart';
import '../../brand.dart';
import '../../widgets.dart';

class AuthRequiredScreen extends StatelessWidget {
  const AuthRequiredScreen({
    super.key,
    required this.title,
    required this.body,
    required this.actionLabel,
  });
  final String title;
  final String body;
  final String actionLabel;

  @override
  Widget build(BuildContext context) => SafeArea(
    child: EmptyState(
      title: title,
      body: body,
      actionLabel: actionLabel,
      onAction: () => Navigator.of(
        context,
      ).push(MaterialPageRoute(builder: (_) => const AuthScreen())),
    ),
  );
}

class AuthScreen extends ConsumerStatefulWidget {
  const AuthScreen({super.key});

  @override
  ConsumerState<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends ConsumerState<AuthScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _register = false;
  bool _busy = false;
  bool _obscurePassword = true;
  String _accountType = 'client';

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_form.currentState?.validate() ?? false)) return;
    setState(() => _busy = true);
    try {
      if (_register) {
        await ref
            .read(sessionProvider.notifier)
            .register(
              name: _name.text.trim(),
              email: _email.text.trim(),
              password: _password.text,
              accountType: _accountType,
              phone: _phone.text.trim(),
            );
      } else {
        await ref
            .read(sessionProvider.notifier)
            .login(
              email: _email.text.trim(),
              password: _password.text,
              deviceName: 'Wajhatak Flutter',
            );
      }
      if (mounted) {
        Navigator.of(context).popUntil((route) => route.isFirst);
        util.notice(context, 'تم تسجيل الدخول بنجاح.');
      }
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    final dark = theme.brightness == Brightness.dark;
    return Scaffold(
      resizeToAvoidBottomInset: true,
      body: GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
        child: SoftBackdrop(
          child: SafeArea(
            child: LayoutBuilder(
              builder: (context, constraints) => Form(
                key: _form,
                child: SingleChildScrollView(
                  keyboardDismissBehavior:
                      ScrollViewKeyboardDismissBehavior.onDrag,
                  padding: EdgeInsets.fromLTRB(20, 16, 20, 24 + bottomInset),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight: constraints.maxHeight - 40,
                    ),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 470),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const SizedBox(height: 12),
                            // الشعار
                            const WajhatakLogoLockup(markSize: 62),
                            const SizedBox(height: 26),
                            // بطاقة النموذج
                            Container(
                              padding: const EdgeInsets.fromLTRB(
                                22,
                                26,
                                22,
                                22,
                              ),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.surface,
                                borderRadius: BorderRadius.circular(28),
                                border: Border.all(
                                  color: theme.colorScheme.outlineVariant,
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: dark
                                        ? Colors.black.withValues(alpha: .3)
                                        : WajhatakColors.emerald.withValues(
                                            alpha: .1,
                                          ),
                                    blurRadius: 30,
                                    offset: const Offset(0, 14),
                                  ),
                                ],
                              ),
                              child: AutofillGroup(
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.stretch,
                                  children: [
                                    // مبدّل تبويب دخول/تسجيل
                                    Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: BoxDecoration(
                                        color: theme
                                            .colorScheme
                                            .surfaceContainerHigh,
                                        borderRadius: BorderRadius.circular(16),
                                      ),
                                      child: Row(
                                        children: [
                                          Expanded(
                                            child: _AuthTabButton(
                                              label: 'تسجيل الدخول',
                                              icon: Icons.login_rounded,
                                              selected: !_register,
                                              onTap: () => setState(
                                                () => _register = false,
                                              ),
                                            ),
                                          ),
                                          Expanded(
                                            child: _AuthTabButton(
                                              label: 'حساب جديد',
                                              icon:
                                                  Icons.person_add_alt_rounded,
                                              selected: _register,
                                              onTap: () => setState(
                                                () => _register = true,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    const SizedBox(height: 22),
                                    Text(
                                      _register
                                          ? 'أنشئ حسابك في وجهتك'
                                          : 'مرحبًا بعودتك',
                                      textAlign: TextAlign.center,
                                      style: theme.textTheme.headlineSmall
                                          ?.copyWith(
                                            fontWeight: FontWeight.w900,
                                          ),
                                    ),
                                    const SizedBox(height: 7),
                                    Text(
                                      _register
                                          ? 'ابدأ رحلتك كعميل أو وكيل عقاري في دقائق.'
                                          : 'سجّل دخولك لمتابعة عقاراتك وطلباتك ورسائلك.',
                                      textAlign: TextAlign.center,
                                      style: TextStyle(
                                        color: theme
                                            .colorScheme
                                            .onSurfaceVariant,
                                        height: 1.45,
                                      ),
                                    ),
                                    const SizedBox(height: 24),
                                    if (_register) ...[
                                      TextFormField(
                                        controller: _name,
                                        textInputAction: TextInputAction.next,
                                        autofillHints: const [
                                          AutofillHints.name,
                                        ],
                                        decoration: const InputDecoration(
                                          labelText: 'الاسم الكامل',
                                          prefixIcon: Icon(
                                            Icons.person_outline_rounded,
                                          ),
                                        ),
                                        validator: (value) =>
                                            (value ?? '').trim().length < 2
                                            ? 'أدخل الاسم الكامل كما يظهر في حسابك.'
                                            : null,
                                      ),
                                      const SizedBox(height: 16),
                                      // اختيار نوع الحساب
                                      const Text(
                                        'اختر طريقة استخدامك لوجهتك',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                      const SizedBox(height: 10),
                                      Row(
                                        children: [
                                          Expanded(
                                            child: _AccountTypeCard(
                                              selected:
                                                  _accountType == 'client',
                                              icon: Icons.person_rounded,
                                              tone: AccentTone.sky,
                                              title: 'عميل',
                                              body: 'استكشف واحفظ وتواصل',
                                              onTap: () => setState(
                                                () =>
                                                    _accountType = 'client',
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 10),
                                          Expanded(
                                            child: _AccountTypeCard(
                                              selected:
                                                  _accountType == 'agent',
                                              icon:
                                                  Icons.real_estate_agent_rounded,
                                              tone: AccentTone.emerald,
                                              title: 'وكيل عقار',
                                              body: 'انشر وأدر عقاراتك',
                                              onTap: () => setState(
                                                () => _accountType = 'agent',
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 16),
                                    ],
                                    TextFormField(
                                      controller: _email,
                                      keyboardType:
                                          TextInputType.emailAddress,
                                      textDirection: ui.TextDirection.ltr,
                                      textInputAction: TextInputAction.next,
                                      autofillHints: const [
                                        AutofillHints.email,
                                      ],
                                      decoration: const InputDecoration(
                                        labelText: 'البريد الإلكتروني',
                                        prefixIcon: Icon(
                                          Icons.alternate_email_rounded,
                                        ),
                                      ),
                                      validator: (value) {
                                        final email = (value ?? '').trim();
                                        return email.contains('@') &&
                                                email
                                                    .split('@')
                                                    .last
                                                    .contains('.')
                                            ? null
                                            : 'أدخل بريدًا إلكترونيًا صحيحًا.';
                                      },
                                    ),
                                    if (_register) ...[
                                      const SizedBox(height: 12),
                                      TextFormField(
                                        controller: _phone,
                                        keyboardType: TextInputType.phone,
                                        textDirection: ui.TextDirection.ltr,
                                        textInputAction: TextInputAction.next,
                                        autofillHints: const [
                                          AutofillHints.telephoneNumber,
                                        ],
                                        decoration: const InputDecoration(
                                          labelText: 'رقم الجوال (اختياري)',
                                          prefixIcon: Icon(
                                            Icons.phone_outlined,
                                          ),
                                        ),
                                        validator: (value) {
                                          final phone = (value ?? '').trim();
                                          if (phone.isEmpty) {
                                            return null;
                                          }
                                          return phone.length >= 7
                                              ? null
                                              : 'أدخل رقم جوال صحيحًا أو اتركه فارغًا.';
                                        },
                                      ),
                                    ],
                                    const SizedBox(height: 12),
                                    TextFormField(
                                      controller: _password,
                                      obscureText: _obscurePassword,
                                      textDirection: ui.TextDirection.ltr,
                                      textInputAction: TextInputAction.done,
                                      onFieldSubmitted: (_) => _submit(),
                                      autofillHints: [
                                        _register
                                            ? AutofillHints.newPassword
                                            : AutofillHints.password,
                                      ],
                                      decoration: InputDecoration(
                                        labelText: 'كلمة المرور',
                                        helperText: _register
                                            ? '10 أحرف على الأقل.'
                                            : null,
                                        prefixIcon: const Icon(
                                          Icons.lock_outline_rounded,
                                        ),
                                        suffixIcon: IconButton(
                                          tooltip: _obscurePassword
                                              ? 'إظهار كلمة المرور'
                                              : 'إخفاء كلمة المرور',
                                          onPressed: () => setState(
                                            () => _obscurePassword =
                                                !_obscurePassword,
                                          ),
                                          icon: Icon(
                                            _obscurePassword
                                                ? Icons
                                                      .visibility_outlined
                                                : Icons
                                                      .visibility_off_outlined,
                                          ),
                                        ),
                                      ),
                                      validator: (value) =>
                                          (value ?? '').length >= 8
                                          ? null
                                          : 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
                                    ),
                                    const SizedBox(height: 24),
                                    FilledButton.icon(
                                      onPressed: _busy ? null : _submit,
                                      icon: _busy
                                          ? const SizedBox(
                                              width: 18,
                                              height: 18,
                                              child:
                                                  CircularProgressIndicator(
                                                    strokeWidth: 2.2,
                                                    color: Colors.white,
                                                  ),
                                            )
                                          : Icon(
                                              _register
                                                  ? Icons
                                                        .person_add_alt_rounded
                                                  : Icons.login_rounded,
                                              size: 20,
                                            ),
                                      label: Text(
                                        _busy
                                            ? 'جارٍ التحقق بأمان…'
                                            : (_register
                                                  ? 'إنشاء الحساب'
                                                  : 'تسجيل الدخول'),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => setState(
                                      () => _register = !_register,
                                    ),
                              child: Text(
                                _register
                                    ? 'لدي حساب بالفعل — تسجيل الدخول'
                                    : 'ليس لديك حساب؟ أنشئ حسابًا جديدًا',
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _AuthTabButton extends StatelessWidget {
  const _AuthTabButton({
    required this.label,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      decoration: BoxDecoration(
        color: selected ? theme.colorScheme.surface : Colors.transparent,
        borderRadius: BorderRadius.circular(13),
        boxShadow: selected
            ? [
                BoxShadow(
                  color: Colors.black.withValues(alpha: .06),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ]
            : null,
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(13),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 10),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  icon,
                  size: 16,
                  color: selected
                      ? theme.colorScheme.primary
                      : theme.colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 6),
                Text(
                  label,
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: selected ? FontWeight.w900 : FontWeight.w600,
                    color: selected
                        ? theme.colorScheme.primary
                        : theme.colorScheme.onSurfaceVariant,
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

class _AccountTypeCard extends StatelessWidget {
  const _AccountTypeCard({
    required this.selected,
    required this.icon,
    required this.tone,
    required this.title,
    required this.body,
    required this.onTap,
  });

  final bool selected;
  final IconData icon;
  final AccentTone tone;
  final String title;
  final String body;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final base = tone.color(theme.colorScheme);
    return Material(
      color: selected
          ? base.withValues(alpha: theme.brightness == Brightness.dark ? .24 : .12)
          : theme.colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          constraints: const BoxConstraints(minHeight: 122),
          padding: const EdgeInsets.all(13),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? base : theme.colorScheme.outline,
              width: selected ? 1.6 : 1,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: selected ? base : base.withValues(alpha: .1),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(
                  icon,
                  size: 17,
                  color: selected ? Colors.white : base,
                ),
              ),
              const SizedBox(height: 24),
              Text(title, style: const TextStyle(fontWeight: FontWeight.w900)),
              const SizedBox(height: 3),
              Text(
                body,
                maxLines: 2,
                style: TextStyle(
                  fontSize: 11,
                  height: 1.25,
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
