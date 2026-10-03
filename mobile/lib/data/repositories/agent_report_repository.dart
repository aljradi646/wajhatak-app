import '../../core/config/app_config.dart';
import '../api_client.dart';
import '../models/agent_report.dart';

/// مستودع تقارير الوكيل — كل البيانات من Laravel، ولا شيء محلي أو مُخمَّن.
class AgentReportRepository {
  AgentReportRepository(this._api, this._tokens);

  final LuxApiClient _api;
  final TokenStore _tokens;

  /// الأنواع المتاحة + آخر التقارير المُولَّدة (المصدر: الخادم).
  Future<AgentReportsCatalog> catalog() async {
    final json = await _api.get('/agent/reports');
    return AgentReportsCatalog.fromJson(
      json['data'] as Map<String, dynamic>? ?? const {},
    );
  }

  /// جلب بيانات تقرير مع نوع ومرشحات مختارة — القيم الحقيقية من قاعدة البيانات.
  Future<AgentReport> fetch(String type, {Map<String, String> filters = const {}}) async {
    final json = await _api.get(
      '/agent/reports/$type',
      query: filters.isEmpty ? null : Map<String, dynamic>.from(filters),
    );
    return AgentReport.fromJson(
      json['data'] as Map<String, dynamic>? ?? const {},
    );
  }

  /// رابط تصدير مُصرَّح (الرمز في الاستعلام لأن المتصفح لا يرسل رؤوس المصادقة).
  Future<String> exportUrl(int logId) async {
    final token = await _tokens.read() ?? '';
    return _absolute(
      '/agent/reports/history/$logId/download',
    ).replace(queryParameters: {'token': token}).toString();
  }

  /// رابط تصدير مباشر لنوع ومرشحات — يُسجَّل على الخادم كنفس أي توليد.
  Future<String> exportTypeUrl(
    String type,
    String format, {
    Map<String, String> filters = const {},
  }) async {
    final token = await _tokens.read() ?? '';
    return _absolute('/agent/reports/$type').replace(
      queryParameters: {'format': format, ...filters, 'token': token},
    ).toString();
  }

  Uri _absolute(String path) {
    final base = AppConfig.apiBaseUrl.endsWith('/')
        ? AppConfig.apiBaseUrl.substring(0, AppConfig.apiBaseUrl.length - 1)
        : AppConfig.apiBaseUrl;
    return Uri.parse('$base$path');
  }
}
