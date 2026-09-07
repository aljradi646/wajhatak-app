import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/confirm_logout.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../agent/agent_dashboard_screen.dart';
import '../auth/auth_screen.dart';
import '../notifications/notifications_screen.dart';
import '../profile/profile_screen.dart';
import '../settings/settings_screen.dart';
import '../viewing_requests/viewing_requests_screen.dart';

class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(sessionProvider);
    if (session.isLoading) {
      return const AccountScreenSkeleton();
    }
    final user = session.asData?.value?.user;
    if (user == null) {
      return AuthRequiredScreen(
        title: 'كل ما تحتاجه في حساب واحد',
        body: 'احفظ عقاراتك، أدر طلباتك، وتواصل مع الوكلاء بأمان.',
        actionLabel: 'تسجيل الدخول',
      );
    }
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
      children: [
        // بطاقة المستخدم
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: theme2gradient(context),
            borderRadius: BorderRadius.circular(26),
            boxShadow: [
              BoxShadow(
                color: WajhatakColors.emerald.withValues(alpha: .22),
                blurRadius: 22,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Row(
            children: [
              UserAvatar(user: user, radius: 32, onWhite: true),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      user.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      user.email,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: .85),
                        fontSize: 12.5,
                      ),
                    ),
                    const SizedBox(height: 7),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: .18),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        user.isAgent ? 'وكيل عقاري معتمد' : 'حساب عميل',
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
              Material(
                color: Colors.white.withValues(alpha: .2),
                borderRadius: BorderRadius.circular(13),
                child: InkWell(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => ProfileScreen(user: user)),
                  ),
                  borderRadius: BorderRadius.circular(13),
                  child: const SizedBox(
                    width: 40,
                    height: 40,
                    child: Icon(
                      Icons.edit_rounded,
                      color: Colors.white,
                      size: 18,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        _AccountTile(
          icon: Icons.event_available_rounded,
          tone: AccentTone.orange,
          title: 'طلبات المعاينة',
          subtitle: 'مواعيدك وطلباتك الحالية',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const ViewingRequestsScreen()),
          ),
        ),
        _AccountTile(
          icon: Icons.notifications_rounded,
          tone: AccentTone.amber,
          title: 'الإشعارات',
          subtitle: 'آخر التحديثات والتنبيهات',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const NotificationsScreen()),
          ),
        ),
        if (user.isAgent)
          _AccountTile(
            icon: Icons.apartment_rounded,
            tone: AccentTone.indigo,
            title: 'لوحة الوكيل وعقاراتي',
            subtitle: 'إدارة عقاراتك وطلبات المعاينة',
            onTap: () => Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const AgentDashboardScreen()),
            ),
          ),
        _AccountTile(
          icon: Icons.manage_accounts_rounded,
          tone: AccentTone.violet,
          title: 'تعديل الملف الشخصي',
          subtitle: 'الاسم والجوال والصورة الشخصية',
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => ProfileScreen(user: user)),
          ),
        ),
        _AccountTile(
          icon: Icons.settings_rounded,
          tone: AccentTone.emerald,
          title: 'الإعدادات',
          subtitle: 'المظهر والإشعارات والخصوصية',
          onTap: () => Navigator.of(
            context,
          ).push(MaterialPageRoute(builder: (_) => const SettingsScreen())),
        ),
        const SizedBox(height: 18),
        OutlinedButton.icon(
          style: OutlinedButton.styleFrom(
            foregroundColor: Theme.of(context).colorScheme.error,
            side: BorderSide(
              color: Theme.of(context).colorScheme.error.withValues(alpha: .4),
            ),
            minimumSize: const Size.fromHeight(52),
          ),
          onPressed: () => confirmLogout(context, ref),
          icon: const Icon(Icons.logout_rounded, size: 20),
          label: const Text('تسجيل الخروج'),
        ),
      ],
    );
  }

  LinearGradient theme2gradient(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark
          ? WajhatakColors.heroGradientDark
          : WajhatakColors.heroGradientLight;
}

class _AccountTile extends StatelessWidget {
  const _AccountTile({
    required this.icon,
    required this.tone,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  final IconData icon;
  final AccentTone tone;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: theme.colorScheme.outlineVariant),
            ),
            child: Row(
              children: [
                TintedIcon(icon: icon, tone: tone, size: 46),
                const SizedBox(width: 13),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  Icons.chevron_left_rounded,
                  size: 22,
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
