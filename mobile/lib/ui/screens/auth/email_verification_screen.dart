import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../state/providers.dart';
import '../../brand.dart';
import '../../widgets.dart';

/// شاشة توثيق البريد الإلكتروني: رمز 6 أرقام يصل إلى الصندوق الفعلي.
/// تُفتح بعد التسجيل أو من الملف الشخصي عند كون البريد غير موثق.
class EmailVerificationScreen extends ConsumerStatefulWidget {
  const EmailVerificationScreen({super.key, this.autoSend = true});

  /// هل يُرسل الرمز تلقائيًا عند الفتح؟ (بعد التسجيل مباشرة = نعم)
  final bool autoSend;

  @override
  ConsumerState<EmailVerificationScreen> createState() =>
      _EmailVerificationScreenState();
}

class _EmailVerificationScreenState
    extends ConsumerState<EmailVerificationScreen> {
  final _codeController = TextEditingController();
  final _form = GlobalKey<FormState>();
  bool _busy = false;
  int _resendIn = 0;
  Timer? _ticker;
  String? _email;

  @override
  void initState() {
    super.initState();
    _email = ref.read(sessionProvider).asData?.value?.user.email;
    if (widget.autoSend) {
      // إرسال تلقائي بعد فتح الشاشة بقليل (بعد بناء الواجهة).
      WidgetsBinding.instance.addPostFrameCallback((_) => _sendCode());
    }
  }

  @override
  void dispose() {
    _ticker?.cancel();
    _codeController.dispose();
    super.dispose();
  }

  void _startResendTimer(int seconds) {
    setState(() => _resendIn = seconds);
    _ticker?.cancel();
    _ticker = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted || _resendIn <= 1) {
        timer.cancel();
        if (mounted) setState(() => _resendIn = 0);
        return;
      }
      setState(() => _resendIn -= 1);
    });
  }

  Future<void> _sendCode() async {
    if (_busy) return;
    setState(() => _busy = true);
    try {
      final result =
          await ref.read(accountRepositoryProvider).sendVerificationCode();
      if (!mounted) return;
      util.notice(context, result.message);
      if (result.resendIn > 0) _startResendTimer(result.resendIn);
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _confirm() async {
    if (!(_form.currentState?.validate() ?? false) || _busy) return;
    setState(() => _busy = true);
    try {
      final result = await ref
          .read(accountRepositoryProvider)
          .confirmCode(_codeController.text.trim());
      if (!mounted) return;
      if (result.ok) {
        util.notice(context, result.message);
        // تحديث بيانات الجلسة (email_verified) ثم الرجوع.
        await ref
            .read(sessionProvider.notifier)
            .restoreSessionAfterVerification();
        if (mounted) Navigator.of(context).pop(true);
      } else {
        util.notice(context, result.message);
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
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'توثيق البريد الإلكتروني'),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 20, 24, 30),
          child: Form(
            key: _form,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 10),
                const WajhatakLogoLockup(markSize: 54),
                const SizedBox(height: 20),
                Text(
                  'أدخل رمز التحقق',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.headlineSmall
                      ?.copyWith(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 8),
                Text(
                  'أرسلنا رمزًا من 6 أرقام إلى:\n${_email ?? 'بريدك المسجّل'}',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color: theme.colorScheme.onSurfaceVariant,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 26),
                TextFormField(
                  controller: _codeController,
                  keyboardType: TextInputType.number,
                  textDirection: TextDirection.ltr,
                  textAlign: TextAlign.center,
                  maxLength: 6,
                  autofocus: !widget.autoSend,
                  style: const TextStyle(
                      fontSize: 24, letterSpacing: 8, fontWeight: FontWeight.w800),
                  decoration: const InputDecoration(
                    counterText: '',
                    hintText: '••••••',
                    prefixIcon: Icon(Icons.lock_outline_rounded),
                  ),
                  validator: (value) =>
                      (value ?? '').trim().length == 6
                          ? null
                          : 'أدخل الرمز المكوّن من 6 أرقام.',
                  onFieldSubmitted: (_) => _confirm(),
                ),
                const SizedBox(height: 18),
                FilledButton.icon(
                  onPressed: _busy ? null : _confirm,
                  icon: _busy
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                              strokeWidth: 2.2, color: Colors.white),
                        )
                      : const Icon(Icons.verified_rounded, size: 20),
                  label: const Padding(
                    padding: EdgeInsets.symmetric(vertical: 13),
                    child: Text('تأكيد وتوثيق البريد',
                        style: TextStyle(fontSize: 15.5)),
                  ),
                ),
                const SizedBox(height: 10),
                TextButton(
                  onPressed: (_busy || _resendIn > 0) ? null : _sendCode,
                  child: Text(
                    _resendIn > 0
                        ? 'إعادة إرسال الرمز بعد $_resendIn ثانية'
                        : 'لم يصلك الرمز؟ إعادة الإرسال',
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
