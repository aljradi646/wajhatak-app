import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/models.dart';
import '../theme/app_theme.dart';

final localNotificationServiceProvider = Provider<LuxNotificationService>(
  (ref) => LuxNotificationService(),
);

class LuxNotificationService {
  static const _channel = AndroidNotificationChannel(
    'lux_updates',
    'تحديثات وجهة',
    description: 'رسائل وطلبات معاينة وتحديثات مرتبطة بحسابك.',
    importance: Importance.high,
  );

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();
  bool _initialized = false;

  /// يُستدعى عند النقر على إشعار نظام — payload هو معرّف الإشعار.
  Future<void> Function(String? notificationId)? onNotificationResponse;

  /// الإشعارات المحلية مدعومة على Android/iOS فقط؛ على باقي المنصات
  /// (Windows/Web/…) لا يوجد تطبيق مسجّل للمكوّن فنعود فورًا.
  bool get _isSupported {
    if (kIsWeb) return false;
    return defaultTargetPlatform == TargetPlatform.android ||
        defaultTargetPlatform == TargetPlatform.iOS;
  }

  Future<void> initialize() async {
    if (!_isSupported || _initialized) {
      return;
    }
    const settings = InitializationSettings(
      android: AndroidInitializationSettings('ic_stat_wajhatak'),
    );
    await _plugin.initialize(
      settings,
      onDidReceiveNotificationResponse: (response) {
        final callback = onNotificationResponse;
        if (callback != null && response.payload != null) {
          callback(response.payload);
        }
      },
    );
    await _plugin
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(_channel);
    _initialized = true;
  }

  /// إشعار يُفتح به التطبيق عندما كان مغلقًا.
  Future<String?> launchNotificationId() async {
    if (!_isSupported) return null;
    final details = await _plugin.getNotificationAppLaunchDetails();
    if (details?.didNotificationLaunchApp != true) return null;
    return details?.notificationResponse?.payload;
  }

  Future<bool> requestAuthorization() async {
    if (!_isSupported) return true;
    await initialize();
    return await _plugin
            .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin
            >()
            ?.requestNotificationsPermission() ??
        true;
  }

  Future<void> show(LuxNotification notification) async {
    if (!_isSupported) return;
    await initialize();
    final title = (notification.title?.isNotEmpty == true)
        ? notification.title!
        : _fallbackTitle(notification.kind);
    await _plugin.show(
      notification.id.hashCode,
      title,
      notification.message,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'lux_updates',
          'تحديثات وجهة',
          channelDescription: 'رسائل وطلبات معاينة وتحديثات حساب وجهة.',
          icon: 'ic_stat_wajhatak',
          color: WajhatakColors.amber,
          importance: Importance.high,
          priority: Priority.high,
        ),
      ),
      payload: notification.id,
    );
  }

  String _fallbackTitle(String? kind) => switch (kind) {
    'message_received' || 'message' || 'message_sent' => 'رسالة جديدة في وجهة',
    'viewing_request_created' ||
    'viewing_request_updated' ||
    'viewing_request' => 'تحديث طلب معاينة',
    _ => 'تحديث من وجهة',
  };
}