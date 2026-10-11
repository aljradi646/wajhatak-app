import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../theme/app_theme.dart';
import '../../data/api_client.dart';
import '../../state/providers.dart';
import 'notice.dart' as util;

Future<void> confirmLogout(BuildContext context, WidgetRef ref) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (ctx) => AlertDialog(
      icon: const Icon(Icons.logout_rounded, color: WajhatakColors.emerald, size: 40),
      title: const Text('تسجيل الخروج'),
      content: const Text('هل تريد تسجيل الخروج من حسابك؟'),
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
                child: const Text('تسجيل الخروج'),
              ),
            ),
          ],
        ),
      ],
    ),
  );
  if (confirmed != true || !context.mounted) return;

  // The notifier clears local auth state before its first await.
  // Do not wait for a potentially slow server response to leave the account.
  final pendingLogout = ref.read(sessionProvider.notifier).logout();
  if (!context.mounted) return;
  Navigator.of(context).popUntil((route) => route.isFirst);
  util.notice(context, 'تم تسجيل الخروج من هذا الجهاز.');

  try {
    await pendingLogout;
  } on ApiFailure catch (error) {
    if (context.mounted) {
      util.notice(
        context,
        'تم تسجيل الخروج محليًا، لكن تعذّر تأكيد إبطال جلسة الخادم: ${error.message}',
      );
    }
  } catch (_) {
    if (context.mounted) {
      util.notice(context, 'تم تسجيل الخروج محليًا، وتعذّر الاتصال بالخادم لإبطال الجلسة.');
    }
  }
}
