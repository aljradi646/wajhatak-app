import 'dart:typed_data';

import '../api_client.dart';
import '../models/agent_report.dart';

/// تقارير الوكيل: المعاينة والملفات تمر عبر Laravel وبمصادقة التطبيق.
class AgentReportRepository {
  AgentReportRepository(this._api);

  final LuxApiClient _api;

  Future<AgentReportsCatalog> catalog() async {
    final json = await _api.get('/agent/reports');
    return AgentReportsCatalog.fromJson(
      json['data'] as Map<String, dynamic>? ?? const {},
    );
  }

  Future<AgentReport> fetch(
    String type, {
    Map<String, String> filters = const {},
  }) async {
    final json = await _api.get(
      '/agent/reports/$type',
      query: filters.isEmpty ? null : Map<String, dynamic>.from(filters),
    );
    return AgentReport.fromJson(
      json['data'] as Map<String, dynamic>? ?? const {},
    );
  }

  Future<Uint8List> exportType(
    String type,
    String format, {
    Map<String, String> filters = const {},
  }) {
    return _api.getBytes(
      '/agent/reports/$type',
      query: <String, dynamic>{'format': format, ...filters},
    );
  }

  Future<Uint8List> exportHistory(int logId) {
    return _api.getBytes('/agent/reports/history/$logId/download');
  }
}
