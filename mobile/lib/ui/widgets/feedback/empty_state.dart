import 'package:flutter/material.dart';

import '../../../core/theme/icon_badges.dart';

/// حالة فارغة — أيقونة ملونة كبيرة برسالة واضحة وإجراء اختياري.
class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.title,
    required this.body,
    this.actionLabel,
    this.onAction,
    this.icon = Icons.villa_outlined,
  });
  final String title;
  final String body;
  final String? actionLabel;
  final VoidCallback? onAction;
  final IconData icon;

  @override
  Widget build(BuildContext context) => Center(
    child: SingleChildScrollView(
      padding: const EdgeInsets.all(30),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          GradientIconBadge(icon: icon, size: 78, iconSize: 38),
          const SizedBox(height: 18),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900),
          ),
          const SizedBox(height: 8),
          Text(
            body,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.55,
            ),
          ),
          if (actionLabel != null) ...[
            const SizedBox(height: 20),
            FilledButton(onPressed: onAction, child: Text(actionLabel!)),
          ],
        ],
      ),
    ),
  );
}

class ErrorState extends StatelessWidget {
  const ErrorState({
    super.key,
    required this.message,
    this.onRetry,
    this.offline = false,
  });
  final String message;
  final VoidCallback? onRetry;
  final bool offline;

  @override
  Widget build(BuildContext context) => EmptyState(
    title: offline ? 'لا يوجد اتصال بالإنترنت' : 'تعذر إكمال العملية',
    body: offline
        ? 'لم نتمكن من الاتصال بالخادم. تأكد من اتصالك بالشبكة ثم أعد المحاولة.'
        : message,
    icon: offline ? Icons.wifi_off_rounded : Icons.cloud_off_outlined,
    actionLabel: onRetry == null ? null : 'إعادة المحاولة',
    onAction: onRetry,
  );
}

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.onMore,
  });
  final String title;
  final String? subtitle;
  final VoidCallback? onMore;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: theme.textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
              ),
              if (subtitle != null) ...[
                const SizedBox(height: 3),
                Text(
                  subtitle!,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ],
          ),
        ),
        if (onMore != null)
          TextButton(onPressed: onMore, child: const Text('عرض الكل')),
      ],
    );
  }
}
