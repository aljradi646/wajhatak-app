import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../../data/api_client.dart';
import '../../data/models/models.dart';
import '../config/firebase_options.dart';
import 'lux_notification_service.dart';

/// Optional Firebase Cloud Messaging delivery. Laravel database notifications
/// remain the source of truth; this service adds delivery to the OS tray when
/// the app is backgrounded or closed.
class FirebasePushMessagingService {
  FirebasePushMessagingService(this._api, this._tokenStore, this._localNotifications);

  final LuxApiClient _api;
  final TokenStore _tokenStore;
  final LuxNotificationService _localNotifications;

  FirebaseMessaging? _messaging;
  StreamSubscription<RemoteMessage>? _foregroundSubscription;
  StreamSubscription<RemoteMessage>? _openedSubscription;
  StreamSubscription<String>? _tokenSubscription;
  Future<void>? _initializing;
  bool _launchMessageHandled = false;

  Future<void> Function(String? notificationId)? onNotificationOpened;
  Future<void> Function()? onRoleChanged;

  bool get _isSupported =>
      !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.android ||
          defaultTargetPlatform == TargetPlatform.iOS);

  Future<void> initializeAndRegister() async {
    if (!_isSupported || WajhatakFirebaseOptions.currentPlatform == null) {
      return;
    }
    if (_initializing != null) {
      await _initializing;
      return;
    }
    if (_messaging != null) {
      await registerCurrentDevice();
      return;
    }

    _initializing = _initialize();
    try {
      await _initializing;
    } finally {
      _initializing = null;
    }
  }

  Future<void> _initialize() async {
    try {
      if (Firebase.apps.isEmpty) {
        await Firebase.initializeApp(
          options: WajhatakFirebaseOptions.currentPlatform,
        );
      }

      final messaging = FirebaseMessaging.instance;
      _messaging = messaging;
      await messaging.requestPermission(alert: true, badge: true, sound: true);

      _foregroundSubscription ??= FirebaseMessaging.onMessage.listen((message) {
        unawaited(_showForegroundMessage(message));
      });
      _openedSubscription ??= FirebaseMessaging.onMessageOpenedApp.listen((message) {
        unawaited(_openFromRemoteMessage(message));
      });
      _tokenSubscription ??= messaging.onTokenRefresh.listen((token) {
        unawaited(_registerToken(token));
      });

      if (!_launchMessageHandled) {
        _launchMessageHandled = true;
        final initial = await messaging.getInitialMessage();
        if (initial != null) {
          unawaited(Future<void>.delayed(
            const Duration(milliseconds: 350),
            () => _openFromRemoteMessage(initial),
          ));
        }
      }

      await registerCurrentDevice();
    } catch (_) {
      // Missing native Firebase configuration must not block app startup.
      debugPrint('Remote notifications are not configured for this build.');
    }
  }

  Future<void> registerCurrentDevice() async {
    final messaging = _messaging;
    if (messaging == null) return;

    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) return;

    try {
      final pushToken = await messaging.getToken();
      if (pushToken == null || pushToken.length < 16) return;
      await _registerToken(pushToken);
    } catch (_) {
      // Retry on token rotation and next authenticated app start.
    }
  }

  Future<void> _registerToken(String pushToken) async {
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) return;

    final deviceId = await _tokenStore.readOrCreateDeviceId();
    final platform = defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android';
    try {
      await _api.post('/me/devices', data: {
        'device_id': deviceId,
        'platform': platform,
        'push_token': pushToken,
      });
    } on Object {
      // A network outage must not block the signed-in experience.
    }
  }

  Future<void> unregisterCurrentDevice() async {
    if (!_isSupported || WajhatakFirebaseOptions.currentPlatform == null) return;
    final token = await _tokenStore.read();
    if (token == null || token.isEmpty) return;

    try {
      final deviceId = await _tokenStore.readOrCreateDeviceId();
      await _api.delete('/me/devices/$deviceId');
    } on Object {
      // Local logout remains immediate if the server is unavailable.
    }
  }

  Future<void> _showForegroundMessage(RemoteMessage message) async {
    if (message.data['kind']?.toString() == 'user_role_changed') {
      await onRoleChanged?.call();
      return;
    }
    final id = message.data['notification_id']?.toString();
    if (id == null || id.isEmpty) return;

    final item = LuxNotification(
      id: id,
      title: message.notification?.title ?? message.data['title']?.toString(),
      message: message.notification?.body ??
          message.data['message']?.toString() ??
          'لديك إشعار جديد في وجهتك.',
      kind: message.data['kind']?.toString(),
      supportTicketId: int.tryParse(message.data['support_ticket_id']?.toString() ?? ''),
      createdAt: DateTime.now(),
    );
    await _localNotifications.show(item);
  }

  Future<void> _openFromRemoteMessage(RemoteMessage message) async {
    if (message.data['kind']?.toString() == 'user_role_changed') {
      await onRoleChanged?.call();
    }
    final id = message.data['notification_id']?.toString();
    if (id == null || id.isEmpty) return;
    await onNotificationOpened?.call(id);
  }

  Future<void> dispose() async {
    await _foregroundSubscription?.cancel();
    await _openedSubscription?.cancel();
    await _tokenSubscription?.cancel();
  }
}
