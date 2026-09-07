import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../state/providers.dart';
import '../../../core/theme/app_theme.dart';
import 'app_shell.dart';

/// بوابة تمنع عرض التطبيق حتى تُستكمل استعادة الجلسة.
///
/// بينما `sessionProvider` في حالة تحميل (أول ثانية بعد تشغيل التطبيق)،
/// نعرض شاشة انتظار بالشعار بدلاً من واجهة المستخدم僎 (التي تظهر للضيف).
///一旦 الجلسة جاهزة (سواء من الخادم أو من الكاش المحلي) ننتقل إلى `AppShell`.
class SessionGate extends ConsumerWidget {
  const SessionGate({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sessionAsync = ref.watch(sessionProvider);

    if (sessionAsync is AsyncLoading) {
      return const _SplashView();
    }

    return const AppShell();
  }
}

class _SplashView extends StatelessWidget {
  const _SplashView();

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: isDark
                ? const [Color(0xFF0A1512), Color(0xFF0E2A1F)]
                : const [Color(0xFFF5F8F7), Colors.white],
          ),
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Image.asset(
                'assets/images/splash_logo.png',
                width: 110,
                height: 110,
              ),
              const SizedBox(height: 24),
              Text(
                'وجهتك',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w700,
                  color: isDark
                      ? WajhatakColors.emeraldSoft
                      : WajhatakColors.emerald,
                ),
              ),
              const SizedBox(height: 28),
              SizedBox(
                width: 28,
                height: 28,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  color: isDark
                      ? WajhatakColors.emeraldSoft
                      : WajhatakColors.emerald,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
