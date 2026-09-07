import 'package:flutter/material.dart';

import '../../brand.dart';

/// الهيدر الموحد لكل التطبيق (RTL):
/// أقصى اليمين: ☰ القائمة — بجانبه اسم التطبيق — أقصى اليسار: 🔔 الإشعارات.
class WajhatakHeader extends StatelessWidget implements PreferredSizeWidget {
  const WajhatakHeader({
    super.key,
    this.onMenuTap,
    this.onNotificationsTap,
    this.unreadCount = 0,
    this.showTitleLockup = true,
    this.title,
    this.automaticallyImplyLeading = false,
    this.onBackPressed,
  });

  final VoidCallback? onMenuTap;
  final VoidCallback? onNotificationsTap;
  final int unreadCount;
  final bool showTitleLockup;
  final String? title;
  final bool automaticallyImplyLeading;
  final VoidCallback? onBackPressed;

  @override
  Size get preferredSize => const Size.fromHeight(74);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bool canPop = ModalRoute.of(context)?.canPop ?? false;
    final showBack = automaticallyImplyLeading && canPop;

    return SafeArea(
      bottom: false,
      child: SizedBox(
        height: 74,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 6, 12, 4),
          child: Row(
            textDirection: TextDirection.rtl,
            children: [
              // أقصى اليمين: القائمة أو رجوع
              _HeaderIconButton(
                icon: showBack
                    ? Icons.arrow_forward_ios_rounded
                    : Icons.menu_rounded,
                onPressed: showBack
                    ? (onBackPressed ?? () => Navigator.of(context).maybePop())
                    : onMenuTap,
                tooltip: showBack ? 'رجوع' : 'القائمة',
                tinted: !showBack,
              ),
              const SizedBox(width: 8),
              // اسم التطبيق / عنوان الشاشة
              Expanded(
                child: title != null
                    ? Text(
                        title!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      )
                    : showTitleLockup
                    ? const Align(
                        alignment: Alignment.centerRight,
                        child: _HeaderBrand(),
                      )
                    : const SizedBox.shrink(),
              ),
              // أقصى اليسار: الإشعارات مع شارة غير المقروء
              _NotificationsBell(
                onTap: onNotificationsTap,
                unreadCount: unreadCount,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HeaderBrand extends StatelessWidget {
  const _HeaderBrand();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      mainAxisSize: MainAxisSize.min,
      textDirection: TextDirection.rtl,
      children: [
        WajhatakBrandMark(size: 40),
        const SizedBox(width: 9),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'WAJHATAK',
              style: theme.textTheme.labelSmall?.copyWith(
                fontWeight: FontWeight.w900,
                letterSpacing: 3,
                color: theme.colorScheme.tertiary,
                height: 1,
                fontSize: 9.5,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'وجهتك',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w900,
                height: 1,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _HeaderIconButton extends StatelessWidget {
  const _HeaderIconButton({
    required this.icon,
    required this.onPressed,
    required this.tooltip,
    this.tinted = true,
  });

  final IconData icon;
  final VoidCallback? onPressed;
  final String tooltip;
  final bool tinted;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: tinted
          ? theme.colorScheme.primary.withValues(alpha: .1)
          : theme.colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(14),
        child: Tooltip(
          message: tooltip,
          child: SizedBox(
            width: 44,
            height: 44,
            child: Icon(
              icon,
              size: tinted ? 22 : 18,
              color: tinted
                  ? theme.colorScheme.primary
                  : theme.colorScheme.onSurface,
            ),
          ),
        ),
      ),
    );
  }
}

class _NotificationsBell extends StatelessWidget {
  const _NotificationsBell({required this.onTap, required this.unreadCount});

  final VoidCallback? onTap;
  final int unreadCount;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: unreadCount > 0
          ? theme.colorScheme.secondaryContainer
          : theme.colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Tooltip(
          message: 'الإشعارات',
          child: SizedBox(
            width: 44,
            height: 44,
            child: Stack(
              alignment: Alignment.center,
              children: [
                Icon(
                  unreadCount > 0
                      ? Icons.notifications_active_rounded
                      : Icons.notifications_none_rounded,
                  size: 22,
                  color: unreadCount > 0
                      ? theme.colorScheme.secondary
                      : theme.colorScheme.onSurface,
                ),
                if (unreadCount > 0)
                  Positioned(
                    top: 7,
                    left: 7,
                    child: Container(
                      padding: const EdgeInsets.all(3.5),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.error,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: theme.colorScheme.surface,
                          width: 1.5,
                        ),
                      ),
                      constraints: const BoxConstraints(
                        minWidth: 16,
                        minHeight: 16,
                      ),
                      child: Center(
                        child: Text(
                          unreadCount > 99 ? '99+' : '$unreadCount',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 8.5,
                            fontWeight: FontWeight.w900,
                            height: 1,
                          ),
                        ),
                      ),
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

/// هيدر شاشات داخلية (رجوع + عنوان + إجراء اختياري).
class WajhatakScreenHeader extends StatelessWidget
    implements PreferredSizeWidget {
  const WajhatakScreenHeader({
    super.key,
    required this.title,
    this.subtitle,
    this.actions = const [],
    this.onBackPressed,
  });

  final String title;
  final String? subtitle;
  final List<Widget> actions;
  final VoidCallback? onBackPressed;

  @override
  Size get preferredSize => const Size.fromHeight(72);

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SafeArea(
      bottom: false,
      child: SizedBox(
        height: 72,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 6, 8, 4),
          child: Row(
            textDirection: TextDirection.rtl,
            children: [
              Material(
                color: theme.colorScheme.surfaceContainerHigh,
                borderRadius: BorderRadius.circular(14),
                child: InkWell(
                  onTap:
                      onBackPressed ??
                      () => Navigator.of(context).maybePop(),
                  borderRadius: BorderRadius.circular(14),
                  child: const SizedBox(
                    width: 44,
                    height: 44,
                    child: Icon(
                      Icons.arrow_forward_ios_rounded,
                      size: 18,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if (subtitle != null)
                      Text(
                        subtitle!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                  ],
                ),
              ),
              ...actions,
            ],
          ),
        ),
      ),
    );
  }
}
