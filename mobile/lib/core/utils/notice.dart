import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

void notice(BuildContext context, String message) {
  final theme = Theme.of(context);
  final normalized = message.toLowerCase();
  final isError = [
    'تعذر',
    'خطأ',
    'أدخل',
    'يجب',
    'مرفوض',
    'غير صحيح',
    'فشل',
    'انتهت',
    'مطلوب',
  ].any(normalized.contains);
  final color = isError
      ? theme.colorScheme.error
      : WajhatakColors.emeraldSoft;
  ScaffoldMessenger.of(context)
    ..clearSnackBars()
    ..showSnackBar(
      SnackBar(
        duration: const Duration(milliseconds: 1600),
        backgroundColor: theme.colorScheme.surfaceContainerHigh,
        elevation: 0,
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        content: Row(
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: color.withValues(alpha: .16),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isError
                    ? Icons.error_outline_rounded
                    : Icons.check_circle_outline_rounded,
                color: color,
                size: 20,
              ),
            ),
            const SizedBox(width: 11),
            Expanded(
              child: Text(
                message,
                style: TextStyle(
                  color: theme.colorScheme.onSurface,
                  fontWeight: FontWeight.w700,
                  height: 1.35,
                ),
              ),
            ),
          ],
        ),
      ),
    );
}
