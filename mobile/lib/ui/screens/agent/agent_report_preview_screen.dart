import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/models/agent_report.dart';
import '../../widgets.dart';
import 'agent_report_components.dart';

class AgentReportPreviewScreen extends StatelessWidget {
  const AgentReportPreviewScreen({
    super.key,
    required this.report,
    this.onPreviewPdf,
    this.onSavePdf,
    this.onSaveCsv,
  });

  final AgentReport report;
  final VoidCallback? onPreviewPdf;
  final VoidCallback? onSavePdf;
  final VoidCallback? onSaveCsv;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: const WajhatakScreenHeader(
        title: 'معاينة التقرير',
        subtitle: 'معاينة البيانات داخل التطبيق',
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 30),
          children: [
            AgentReportDocumentHeader(report: report),
            const SizedBox(height: 14),
            Card(
              margin: EdgeInsets.zero,
              elevation: 0,
              child: Padding(
                padding: const EdgeInsets.all(15),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Icon(Icons.info_outline_rounded, color: theme.colorScheme.primary),
                        const SizedBox(width: 8),
                        Text(
                          'عن التقرير',
                          style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w900),
                        ),
                      ],
                    ),
                    if (report.description.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        report.description,
                        style: theme.textTheme.bodyMedium?.copyWith(height: 1.7),
                      ),
                    ],
                    if (report.filters.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 7,
                        runSpacing: 7,
                        children: report.filters.map((item) => Chip(
                          avatar: const Icon(Icons.filter_alt_rounded, size: 16),
                          label: Text(item.label + ': ' + item.value),
                          visualDensity: VisualDensity.compact,
                        )).toList(growable: false),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            _sectionTitle(
              theme,
              icon: Icons.insights_rounded,
              title: 'مؤشرات التقرير',
              subtitle: 'بطاقات ملونة في شبكة تستفيد من عرض الشاشة.',
            ),
            const SizedBox(height: 10),
            AgentReportSummaryGrid(report: report),
            const SizedBox(height: 22),
            _sectionTitle(
              theme,
              icon: Icons.table_rows_rounded,
              title: 'تفاصيل السجلات',
              subtitle: report.rows.length.toString() + ' سجل — البيانات من الخادم.',
            ),
            const SizedBox(height: 10),
            AgentReportRecords(report: report),
            if (onPreviewPdf != null || onSavePdf != null || onSaveCsv != null) ...[
              const SizedBox(height: 22),
              Card(
                margin: EdgeInsets.zero,
                color: theme.colorScheme.surfaceContainerLow,
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'حفظ التقرير أو تصديره',
                        style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          if (onPreviewPdf != null)
                            OutlinedButton.icon(
                              onPressed: onPreviewPdf,
                              icon: const Icon(Icons.picture_as_pdf_rounded),
                              label: const Text('فتح نسخة PDF'),
                            ),
                          if (onSavePdf != null)
                            FilledButton.tonalIcon(
                              onPressed: onSavePdf,
                              icon: const Icon(Icons.save_alt_rounded),
                              label: const Text('حفظ / مشاركة PDF'),
                            ),
                          if (onSaveCsv != null)
                            OutlinedButton.icon(
                              onPressed: onSaveCsv,
                              icon: const Icon(Icons.grid_on_rounded),
                              label: const Text('تصدير CSV'),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
            const SizedBox(height: 14),
            Text(
              'هذه معاينة بيانات أصلية من API وجهتك. لعرض ملف الطباعة النهائي، افتح معاينة PDF داخل التطبيق.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
                height: 1.65,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(
    ThemeData theme, {
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Row(
      children: [
        Container(
          width: 39,
          height: 39,
          decoration: BoxDecoration(
            color: theme.colorScheme.primaryContainer,
            borderRadius: BorderRadius.circular(13),
          ),
          child: Icon(icon, color: theme.colorScheme.onPrimaryContainer),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
              ),
              const SizedBox(height: 3),
              Text(
                subtitle,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
