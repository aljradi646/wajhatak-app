import '../api_client.dart';
import '../models/models.dart';

/// طلبات المعاينة.
class ViewingRequestRepository {
  ViewingRequestRepository(this._api);

  final LuxApiClient _api;

  Future<List<ViewingRequestItem>> viewingRequests() async {
    final json = await _api.get('/viewing-requests');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(ViewingRequestItem.fromJson)
        .toList();
  }

  Future<void> requestViewing({
    required int propertyId,
    required DateTime date,
    required String time,
    String? notes,
  }) async {
    await _api.post(
      '/viewing-requests',
      data: {
        'property_id': propertyId,
        'scheduled_date':
            '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}',
        'scheduled_time': time,
        if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
      },
    );
  }

  Future<ViewingRequestItem> updateViewingRequest({
    required int requestId,
    required String status,
  }) async {
    final json = await _api.patch(
      '/viewing-requests/$requestId',
      data: {'status': status},
    );
    return ViewingRequestItem.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// تعديل موعد/ملاحظات الطلب — يسمح به النظام للطلب المفتوح فقط.
  Future<ViewingRequestItem> rescheduleViewingRequest({
    required int requestId,
    required DateTime date,
    required String time,
    String? notes,
  }) async {
    final json = await _api.patch(
      '/viewing-requests/$requestId',
      data: {
        'scheduled_date': _isoDate(date),
        'scheduled_time': time,
        if (notes != null) 'notes': notes.trim().isEmpty ? null : notes.trim(),
      },
    );
    return ViewingRequestItem.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// حذف الطلب — مسموح للمشرف دائمًا وللعميل لطلبه غير النشط.
  Future<void> deleteViewingRequest(int requestId) async {
    await _api.delete('/viewing-requests/$requestId');
  }

  /// سجل تغييرات الطلب (إنشاء/تأكيد/تعديل موعد/إلغاء).
  Future<List<ViewingRequestHistoryEntry>> history(int requestId) async {
    final json = await _api.get('/viewing-requests/$requestId/history');
    return (json['data'] as List<dynamic>? ?? const [])
        .whereType<Map<String, dynamic>>()
        .map(ViewingRequestHistoryEntry.fromJson)
        .toList(growable: false);
  }

  static String _isoDate(DateTime date) =>
      '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
}
