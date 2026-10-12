import '../api_client.dart';
import '../models/support_ticket.dart';
class SupportTicketRepository {
  SupportTicketRepository(this._api);
  final LuxApiClient _api;
  Future<List<SupportTicket>> list() async {
    final json=await _api.get('/support-tickets'); final payload=json['data'];
    final rows=payload is Map<String,dynamic>?payload['data']:payload;
    return (rows as List<dynamic>? ?? const []).whereType<Map<String,dynamic>>().map(SupportTicket.fromJson).toList();
  }
  Future<SupportTicket> show(int id) async { final json=await _api.get('/support-tickets/$id'); return SupportTicket.fromJson(json['data'] as Map<String,dynamic>); }
  Future<SupportTicket> create({required String subject,required String category,required String message}) async {
    final json=await _api.post('/support-tickets',data:{'subject':subject.trim(),'category':category,'message':message.trim()});
    return SupportTicket.fromJson(json['data'] as Map<String,dynamic>);
  }
  Future<SupportTicket> reply(int id,String message) async {
    final json=await _api.post('/support-tickets/$id/reply',data:{'message':message.trim()});
    return SupportTicket.fromJson(json['data'] as Map<String,dynamic>);
  }
}
