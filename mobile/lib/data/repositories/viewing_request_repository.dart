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
}
