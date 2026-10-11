import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/models/agent_report.dart';
import 'package:wajhatak/ui/screens/agent/agent_report_components.dart';

AgentReport _report() => AgentReport(
  type: 'agent_properties',
  heading: 'تقرير عقاراتي',
  description: 'بيانات اختبارية للاختبار فقط.',
  currency: 'YER',
  generatedAt: DateTime(2026, 10, 11, 12),
  siteName: 'وجهتك',
  filters: const [],
  columns: const [
    AgentReportColumn(
      key: 'title',
      label: 'العقار',
      type: 'text',
      valueLabels: {},
      valueColors: {},
    ),
    AgentReportColumn(
      key: 'price',
      label: 'السعر',
      type: 'money',
      valueLabels: {},
      valueColors: {},
    ),
    AgentReportColumn(
      key: 'status',
      label: 'الحالة',
      type: 'badge',
      valueLabels: {'published': 'منشور'},
      valueColors: {'published': 'green'},
    ),
  ],
  summary: const [
    AgentReportSummaryItem(label: 'إجمالي العقارات', value: '12'),
    AgentReportSummaryItem(label: 'منشورة', value: '8'),
    AgentReportSummaryItem(label: 'قيد المراجعة', value: '3'),
    AgentReportSummaryItem(label: 'قيمة المحفظة', value: '2,400,000 ر.ي'),
  ],
  rows: const [
    {'title': 'شقة سكنية', 'price': '400000', 'status': 'منشور'},
    {'title': 'فيلا', 'price': '800000', 'status': 'منشور'},
  ],
  rawRows: const [
    {'status': 'published'},
    {'status': 'published'},
  ],
);

void main() {
  for (final width in <double>[280, 320, 360, 430, 768]) {
    testWidgets('report cards fit at ${width.toInt()}px', (tester) async {
      tester.view.physicalSize = Size(width, 900);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: ListView(
              padding: const EdgeInsets.all(12),
              children: [
                AgentReportSummaryGrid(report: _report()),
                AgentReportRecords(report: _report()),
              ],
            ),
          ),
        ),
      );
      await tester.pump();
      expect(tester.takeException(), isNull);
    });
  }
}
