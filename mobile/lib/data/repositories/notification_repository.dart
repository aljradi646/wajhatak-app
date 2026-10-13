import '../api_client.dart';
import '../models/models.dart';

/// الإشعارات وعدّاد غير المقروء.
class NotificationRepository {
  NotificationRepository(this._api);

  final LuxApiClient _api;

  Future<List<LuxNotification>> notifications() async {
    final json = await _api.get('/notifications');
    final page = json['data'];
    final values = page is Map<String, dynamic> ? page['data'] : page;
    return (values as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(LuxNotification.fromJson)
        .toList();
  }

  Future<int> unreadCount() async {
    final json = await _api.get('/notifications/unread-count');
    final data = json['data'];
    if (data is Map<String, dynamic>) {
      return (data['unread_count'] as num?)?.toInt() ?? 0;
    }
    return 0;
  }

  Future<void> markNotificationRead(String notificationId) =>
      _api.post('/notifications/$notificationId/read');
}
