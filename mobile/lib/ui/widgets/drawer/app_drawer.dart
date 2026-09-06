import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/confirm_logout.dart';
import '../../../data/models/models.dart';
import '../../../state/appearance_controller.dart';
import '../../../state/providers.dart';

/// القائمة الجانبية — تصميم عصري بأيقونات ملونة وأقسام واضحة.
class AppDrawer extends ConsumerWidget {
  const AppDrawer({
    super.key,
    required this.onTabSelected,
    required this.onExplore,
    required this.onFavorites,
    required this.onViewingRequests,
    required this.onNotifications,
    required this.onAgentWorkspace,
    required this.onSettings,
    required this.onProfile,
  });

  final ValueChanged<int> onTabSelected;
  final VoidCallback onExplore;
  final VoidCallback onFavorites;
  final VoidCallback onViewingRequests;
  final VoidCallback onNotifications;
  final VoidCallback onAgentWorkspace;
  final VoidCallback onSettings;
  final VoidCallback onProfile;

  void _closeThen(BuildContext context, VoidCallback action) {
    Navigator.of(context).pop();
    action();
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final session = ref.watch(sessionProvider).asData?.value;
    final user = session?.user;
    return Drawer(
      backgroundColor: theme.scaffoldBackgroundColor,
      width: 330,
      child: SafeArea(
        child: Column(
          children: [
            // رأس القائمة — بطاقة المستخدم بتدرج زمردي
            Container(
              width: double.infinity,
              margin: const EdgeInsets.fromLTRB(14, 10, 14, 12),
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: theme.brightness == Brightness.dark
                    ? WajhatakColors.heroGradientDark
                    : WajhatakColors.heroGradientLight,
                borderRadius: BorderRadius.circular(26),
                boxShadow: [
                  BoxShadow(
                    color: WajhatakColors.emerald.withValues(alpha: .25),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      UserAvatar(user: user, radius: 27, onWhite: true),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              user?.name ?? 'مرحبًا بك في وجهتك',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                                fontSize: 16,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 3),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: .18),
                                borderRadius: BorderRadius.circular(99),
                              ),
                              child: Text(
                                user == null
                                    ? 'زائر'
                                    : (user.isAgent ? 'وكيل عقاري' : 'عميل'),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (user == null) ...[
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        style: FilledButton.styleFrom(
                          backgroundColor: Colors.white,
                          foregroundColor: WajhatakColors.emeraldDark,
                          minimumSize: const Size.fromHeight(44),
                        ),
                        onPressed: onProfile,
                        icon: const Icon(Icons.login_rounded, size: 18),
                        label: const Text(
                          'تسجيل الدخول',
                          style: TextStyle(fontSize: 13.5),
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (AppConfig.isUiPreview)
              Container(
                width: double.infinity,
                margin: const EdgeInsets.fromLTRB(18, 0, 18, 10),
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 9,
                ),
                decoration: BoxDecoration(
                  color: WajhatakColors.amber.withValues(alpha: .14),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Row(
                  children: [
                    Icon(
                      Icons.visibility_outlined,
                      size: 17,
                      color: WajhatakColors.amberDeep,
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'وضع معاينة التصميم — بيانات محلية فقط',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(14, 0, 14, 12),
                children: [
                  const _DrawerGroupLabel(label: 'التنقل'),
                  _DrawerEntry(
                    icon: Icons.home_rounded,
                    tone: AccentTone.emerald,
                    label: 'الرئيسية',
                    onTap: () => _closeThen(context, () => onTabSelected(0)),
                  ),
                  _DrawerEntry(
                    icon: Icons.explore_rounded,
                    tone: AccentTone.sky,
                    label: 'استكشاف العقارات',
                    onTap: () => _closeThen(context, onExplore),
                  ),
                  _DrawerEntry(
                    icon: Icons.favorite_rounded,
                    tone: AccentTone.rose,
                    label: 'المفضلة',
                    onTap: () => _closeThen(context, onFavorites),
                  ),
                  _DrawerEntry(
                    icon: Icons.chat_bubble_rounded,
                    tone: AccentTone.teal,
                    label: 'الرسائل',
                    onTap: () => _closeThen(context, () => onTabSelected(3)),
                  ),
                  if (user != null) ...[
                    const SizedBox(height: 6),
                    const _DrawerGroupLabel(label: 'خدماتي'),
                    _DrawerEntry(
                      icon: Icons.event_available_rounded,
                      tone: AccentTone.orange,
                      label: 'طلبات المعاينة',
                      onTap: () => _closeThen(context, onViewingRequests),
                    ),
                    _DrawerEntry(
                      icon: Icons.notifications_rounded,
                      tone: AccentTone.amber,
                      label: 'الإشعارات',
                      onTap: () => _closeThen(context, onNotifications),
                    ),
                    if (user.isAgent)
                      _DrawerEntry(
                        icon: Icons.real_estate_agent_rounded,
                        tone: AccentTone.indigo,
                        label: 'مساحة الوكيل وعقاراتي',
                        onTap: () => _closeThen(context, onAgentWorkspace),
                      ),
                  ],
                  const SizedBox(height: 6),
                  const _DrawerGroupLabel(label: 'الحساب'),
                  _DrawerEntry(
                    icon: Icons.person_rounded,
                    tone: AccentTone.violet,
                    label: 'حسابي',
                    onTap: () => _closeThen(context, () => onTabSelected(4)),
                  ),
                  _DrawerEntry(
                    icon: Icons.settings_rounded,
                    tone: AccentTone.emerald,
                    label: 'الإعدادات',
                    onTap: () => _closeThen(context, onSettings),
                  ),
                  const SizedBox(height: 10),
                  const _DrawerGroupLabel(label: 'المظهر'),
                  _ThemeModeSelector(
                    mode: ref.watch(themeModeProvider),
                    onChanged: (mode) =>
                        ref.read(themeModeProvider.notifier).setMode(mode),
                  ),
                ],
              ),
            ),
            if (user != null)
              Padding(
                padding: const EdgeInsets.fromLTRB(18, 4, 18, 16),
                child: OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: theme.colorScheme.error,
                    side: BorderSide(
                      color: theme.colorScheme.error.withValues(alpha: .4),
                    ),
                    minimumSize: const Size.fromHeight(48),
                  ),
                  onPressed: () {
                    Navigator.of(context).pop();
                    confirmLogout(context, ref);
                  },
                  icon: const Icon(Icons.logout_rounded, size: 19),
                  label: const Text('تسجيل الخروج'),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// أفاتار المستخدم — يُستخدم في كل التطبيق (مصدر الحقيقة: avatar_url).
/// يقبل مستخدمًا كاملًا أو صورة واسمًا مباشرة (للوكيل والعميل في المحادثات).
class UserAvatar extends StatelessWidget {
  const UserAvatar({
    super.key,
    this.user,
    this.avatarUrl,
    this.name,
    this.radius = 24,
    this.onWhite = false,
  });

  final LuxUser? user;
  final String? avatarUrl;
  final String? name;
  final double radius;
  final bool onWhite;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final url = avatarUrl ?? user?.avatarUrl;
    final displayName = name ?? user?.name;
    final fallbackColor = onWhite
        ? Colors.white.withValues(alpha: .24)
        : theme.colorScheme.primaryContainer;
    final fallbackIcon = onWhite ? Colors.white : theme.colorScheme.primary;

    if (url != null && url.isNotEmpty) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: fallbackColor,
        foregroundImage: CachedNetworkImageProvider(url),
        onForegroundImageError: (_, _) {},
        child: Icon(Icons.person_rounded, size: radius, color: fallbackIcon),
      );
    }
    return CircleAvatar(
      radius: radius,
      backgroundColor: fallbackColor,
      child: Text(
        _initials(displayName),
        style: TextStyle(
          fontWeight: FontWeight.w900,
          fontSize: radius * .78,
          color: fallbackIcon,
        ),
      ),
    );
  }

  String _initials(String? name) {
    if (name == null || name.trim().isEmpty) return '؟';
    final parts = name.trim().split(RegExp(r'\s+'));
    final first = String.fromCharCodes(parts.first.runes.take(1));
    if (parts.length == 1) return first;
    final last = String.fromCharCodes(parts.last.runes.take(1));
    return '$first$last';
  }
}

class _DrawerGroupLabel extends StatelessWidget {
  const _DrawerGroupLabel({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsets.fromLTRB(14, 9, 14, 5),
    child: Text(
      label,
      style: TextStyle(
        color: Theme.of(context).colorScheme.onSurfaceVariant,
        fontSize: 11.5,
        fontWeight: FontWeight.w800,
        letterSpacing: .4,
      ),
    ),
  );
}

class _DrawerEntry extends StatelessWidget {
  const _DrawerEntry({
    required this.icon,
    required this.tone,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final AccentTone tone;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final base = tone.color(theme.colorScheme);
    return Padding(
      padding: const EdgeInsets.only(bottom: 3),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
            child: Row(
              children: [
                Container(
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    color: base.withValues(alpha: .12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, size: 19, color: base),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    label,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                Icon(
                  Icons.chevron_left_rounded,
                  size: 20,
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ThemeModeSelector extends StatelessWidget {
  const _ThemeModeSelector({required this.mode, required this.onChanged});
  final ThemeMode mode;
  final ValueChanged<ThemeMode> onChanged;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(4),
    decoration: BoxDecoration(
      color: Theme.of(context).colorScheme.surfaceContainerHigh,
      borderRadius: BorderRadius.circular(16),
    ),
    child: Row(
      children: [
        _ThemeButton(
          icon: Icons.brightness_auto_rounded,
          label: 'النظام',
          selected: mode == ThemeMode.system,
          onTap: () => onChanged(ThemeMode.system),
        ),
        _ThemeButton(
          icon: Icons.light_mode_rounded,
          label: 'فاتح',
          selected: mode == ThemeMode.light,
          onTap: () => onChanged(ThemeMode.light),
        ),
        _ThemeButton(
          icon: Icons.dark_mode_rounded,
          label: 'داكن',
          selected: mode == ThemeMode.dark,
          onTap: () => onChanged(ThemeMode.dark),
        ),
      ],
    ),
  );
}

class _ThemeButton extends StatelessWidget {
  const _ThemeButton({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Expanded(
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        decoration: BoxDecoration(
          color: selected
              ? theme.colorScheme.primary.withValues(alpha: .13)
              : Colors.transparent,
          borderRadius: BorderRadius.circular(13),
        ),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(13),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Column(
              children: [
                Icon(
                  icon,
                  size: 18,
                  color: selected
                      ? theme.colorScheme.primary
                      : theme.colorScheme.onSurfaceVariant,
                ),
                const SizedBox(height: 3),
                Text(
                  label,
                  style: theme.textTheme.labelSmall?.copyWith(
                    fontWeight: selected ? FontWeight.w900 : FontWeight.w600,
                    color: selected
                        ? theme.colorScheme.primary
                        : theme.colorScheme.onSurfaceVariant,
                    fontSize: 10.5,
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
