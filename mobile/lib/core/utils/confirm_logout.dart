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

  var serverSynced = true;
  String? logoutMessage;
  try {
    await ref.read(sessionProvider.notifier).logout();
  } on ApiFailure catch (error) {
    serverSynced = false;
    logoutMessage = error.message;
  }
  if (!context.mounted) return;

  // Pop any pushed screens until we reach the root shell
  Navigator.of(context).popUntil((route) => route.isFirst);
  util.notice(
    context,
    serverSynced
        ? 'تم تسجيل الخروج بنجاح.'
        : 'تم تسجيل الخروج محليًا. تعذر إنهاء الجلسة على الخادم: $logoutMessage',
  );
}
