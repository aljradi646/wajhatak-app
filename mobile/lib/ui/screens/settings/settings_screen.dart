import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_config.dart';
import '../../../core/services/lux_notification_service.dart';
import '../../../core/utils/confirm_logout.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../state/app_settings_controller.dart';
import '../../../state/appearance_controller.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../profile/profile_screen.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  Future<void> _setNotificationPreference(
    BuildContext context,
    WidgetRef ref,
    Future<void> Function() updateLocal,
    bool enableSystemPermission,
  ) async {
    await updateLocal();
    if (enableSystemPermission) {
      final granted = await ref
          .read(localNotificationServiceProvider)
          .requestAuthorization();
      if (!granted && context.mounted) {
        util.notice(context, 'لم تمنح صلاحية إشعارات النظام بعد.');
      }
    }
    final session = ref.read(sessionProvider).asData?.value;
    if (session == null || AppConfig.isUiPreview) {
      return;
    }
    final values = ref.read(appSettingsProvider);
    try {
      await ref.read(authRepositoryProvider).updateNotificationPreferences({
        'message_notifications': values.messageNotifications,
        'viewing_notifications': values.viewingNotifications,
        'property_updates': values.propertyUpdates,
      });
    } on ApiFailure {
      if (context.mounted) {
        util.notice(
          context,
          'حُفظ الإعداد على هذا الجهاز وسيُزامن عند توفر الاتصال بالخادم.',
        );
      }
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final settings = ref.watch(appSettingsProvider);
    final themeMode = ref.watch(themeModeProvider);
    final session = ref.watch(sessionProvider).asData?.value;
    return Scaffold(
      appBar: WajhatakScreenHeader(title: 'الإعدادات'),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
        children: [
          Text(
            'المظهر',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 10),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: SegmentedButton<ThemeMode>(
                showSelectedIcon: false,
                segments: const [
                  ButtonSegment(
                    value: ThemeMode.system,
                    icon: Icon(Icons.brightness_auto_outlined),
                    label: Text('النظام'),
                  ),
                  ButtonSegment(
                    value: ThemeMode.light,
                    icon: Icon(Icons.light_mode_outlined),
                    label: Text('فاتح'),
                  ),
                  ButtonSegment(
                    value: ThemeMode.dark,
                    icon: Icon(Icons.dark_mode_outlined),
                    label: Text('داكن'),
                  ),
                ],
                selected: {themeMode},
                onSelectionChanged: (modes) =>
                    ref.read(themeModeProvider.notifier).setMode(modes.first),
              ),
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'الإشعارات',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 10),
          Card(
            child: Column(
              children: [
                _SettingsNotificationToggle(
                  icon: Icons.chat_bubble_outline_rounded,
                  title: 'رسائل جديدة',
                  body: 'تنبيه عند وصول رسالة جديدة',
                  value: settings.messageNotifications,
                  onChanged: (value) => _setNotificationPreference(
                    context,
                    ref,
                    () => ref
                        .read(appSettingsProvider.notifier)
                        .setMessageNotifications(value),
                    value,
                  ),
                ),
                const Divider(height: 1),
                _SettingsNotificationToggle(
                  icon: Icons.calendar_month_outlined,
                  title: 'طلبات المعاينة',
                  body: 'تغييرات حالة الطلبات والمواعيد',
                  value: settings.viewingNotifications,
                  onChanged: (value) => _setNotificationPreference(
                    context,
                    ref,
                    () => ref
                        .read(appSettingsProvider.notifier)
                        .setViewingNotifications(value),
                    value,
                  ),
                ),
                const Divider(height: 1),
                _SettingsNotificationToggle(
                  icon: Icons.home_work_outlined,
                  title: 'تحديثات العقارات',
                  body: 'تحديثات العناصر والعقارات المحفوظة',
                  value: settings.propertyUpdates,
                  onChanged: (value) => _setNotificationPreference(
                    context,
                    ref,
                    () => ref
                        .read(appSettingsProvider.notifier)
                        .setPropertyUpdates(value),
                    value,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Text(
            'الحساب والخصوصية',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 10),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.manage_accounts_outlined),
                  title: const Text('إدارة الملف الشخصي'),
                  subtitle: Text(
                    session?.user.email ?? 'سجّل الدخول لإدارة حسابك',
                  ),
                  trailing: const Icon(Icons.chevron_left),
                  onTap: session == null
                      ? null
                      : () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => ProfileScreen(user: session.user),
                          ),
                        ),
                ),
                const Divider(height: 1),
                ListTile(
                  leading: const Icon(Icons.privacy_tip_outlined),
                  title: const Text('الخصوصية والبيانات'),
                  subtitle: const Text(
                    'تحفظ رموز الجلسة في التخزين الآمن على جهازك.',
                  ),
                  onTap: () => util.notice(
                    context,
                    'تُدار صلاحيات الوصول والبيانات الحساسة من الخادم.',
                  ),
                ),
              ],
            ),
          ),
          if (session != null) ...[
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: () => confirmLogout(context, ref),
              icon: const Icon(Icons.logout_rounded),
              label: const Text('تسجيل الخروج'),
            ),
          ],
        ],
      ),
    );
  }
}

class _SettingsNotificationToggle extends StatelessWidget {
  const _SettingsNotificationToggle({
    required this.icon,
    required this.title,
    required this.body,
    required this.value,
    required this.onChanged,
  });

  final IconData icon;
  final String title;
  final String body;
  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) => SwitchListTile.adaptive(
    value: value,
    onChanged: onChanged,
    secondary: Icon(icon),
    title: Text(title),
    subtitle: Text(body),
  );
}
