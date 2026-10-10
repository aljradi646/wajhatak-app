import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/models/agent_report.dart';
import '../../widgets.dart';

/// Preview the real report payload in an Arabic, branded app screen.
class AgentReportPreviewScreen extends StatelessWidget {
  const AgentReportPreviewScreen({super.key, required this.report});
  final AgentReport report;

  static const _icons = <IconData>[
    Icons.analytics_rounded,
    Icons.home_work_rounded,
    Icons.payments_rounded,
    Icons.event_available_rounded,
    Icons.trending_up_rounded,
    Icons.query_stats_rounded,
  ];
  static const _colors = <Color>[
    WajhatakColors.emerald,
    Colors.blue,
    Colors.amber,
    Colors.deepPurple,
    Colors.teal,
    Colors.orange,
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: const WajhatakScreenHeader(
        title: 'معاينة التقرير',
        subtitle: 'عرض التقرير داخل التطبيق',
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 14, 16, 30),
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: theme.brightness == Brightness.dark
                    ? WajhatakColors.heroGradientDark
                    : WajhatakColors.heroGradientLight,
                borderRadius: BorderRadius.circular(24),
              ),
              child: Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    padding: const EdgeInsets.all(7),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Image.asset(
                      'assets/images/wajhatak_icon.png',
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) => const Icon(
                        Icons.home_work_rounded,
                        color: WajhatakColors.emerald,
                        size: 34,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('وجهتك', style: TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w900,
                        )),
                        const SizedBox(height: 5),
                        Text(report.heading, style: const TextStyle(
                          color: Colors.white,
                          fontSize: 15,
                          height: 1.5,
                          fontWeight: FontWeight.w800,
                        )),
                        if (report.siteName.isNotEmpty)
                          Text(report.siteName, style: TextStyle(
                            color: Colors.white.withValues(alpha: .82),
                            fontSize: 12,
                          )),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            Card(
              elevation: 0,
              child: Padding(
                padding: const EdgeInsets.all(15),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(children: [
                      Icon(Icons.schedule_rounded, color: theme.colorScheme.primary),
                      const SizedBox(width: 8),
                      Text('تاريخ إنشاء التقرير', style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                    ]),
                    const SizedBox(height: 5),
                    Text(_formatDate(report.generatedAt), style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
                    if (report.description.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Text(report.description, style: theme.textTheme.bodyMedium?.copyWith(height: 1.7)),
                    ],
                    if (report.filters.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 7, runSpacing: 7,
                        children: report.filters.map((item) => Chip(
                          avatar: const Icon(Icons.filter_alt_rounded, size: 16),
                          label: Text('${item.label}: ${item.value}'),
                          visualDensity: VisualDensity.compact,
                        )).toList(growable: false),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            if (report.summary.isNotEmpty) ...[
              const SizedBox(height: 20),
              Text('ملخص التقرير', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900)),
              const SizedBox(height: 10),
              LayoutBuilder(builder: (context, constraints) {
                final columns = constraints.maxWidth >= 820
                    ? 4
                    : constraints.maxWidth >= 560
                        ? 3
                        : constraints.maxWidth >= 300
                            ? 2
                            : 1;
                return GridView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: report.summary.length,
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: columns,
                    crossAxisSpacing: 9,
                    mainAxisSpacing: 9,
                    childAspectRatio: columns == 1 ? 3.0 : 1.45,
                  ),
                  itemBuilder: (context, index) {
                    final item = report.summary[index];
                    final color = _colors[index % _colors.length];
                    return Container(
                      padding: const EdgeInsets.all(13),
                      decoration: BoxDecoration(
                        color: color.withValues(alpha: .09),
                        borderRadius: BorderRadius.circular(17),
                        border: Border.all(color: color.withValues(alpha: .22)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Icon(_icons[index % _icons.length], color: color, size: 21),
                          Text(item.value, maxLines: 2, overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900, color: color)),
                          Text(item.label, maxLines: 2, overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.bodySmall?.copyWith(height: 1.35, fontWeight: FontWeight.w700)),
                        ],
                      ),
                    );
                  },
                );
              }),
            ],
            const SizedBox(height: 22),
            Row(children: [
              Icon(Icons.table_chart_rounded, color: theme.colorScheme.primary),
              const SizedBox(width: 8),
              Expanded(child: Text('تفاصيل السجلات', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900))),
              Text('${report.rows.length} سجل', style: theme.textTheme.labelLarge?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
            ]),
            const SizedBox(height: 10),
            if (report.rows.isEmpty)
              const EmptyState(title: 'لا توجد بيانات', body: 'لا توجد سجلات مطابقة للمرشحات المحددة.', icon: Icons.table_rows_rounded)
            else
              Card(
                margin: EdgeInsets.zero,
                clipBehavior: Clip.antiAlias,
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: DataTable(
                    headingRowColor: WidgetStatePropertyAll(theme.colorScheme.primaryContainer.withValues(alpha: .55)),
                    columnSpacing: 22,
                    dataRowMinHeight: 46,
                    dataRowMaxHeight: 70,
                    columns: report.columns.map((col) => DataColumn(
                      label: Text(col.label, style: const TextStyle(fontWeight: FontWeight.w900)),
                    )).toList(growable: false),
                    rows: List<DataRow>.generate(report.rows.length, (index) {
                      final row = report.rows[index];
                      return DataRow(
                        color: WidgetStateProperty.resolveWith((states) => index.isEven ? theme.colorScheme.surface : theme.colorScheme.surfaceContainerLow),
                        cells: report.columns.map((col) {
                          final value = row[col.key] ?? '—';
                          final raw = report.rawValueAt(index, col.key);
                          if (col.type == 'badge') {
                            final color = _statusColor(theme, col.valueColors[raw ?? ''] ?? 'gray');
                            return DataCell(Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(color: color.withValues(alpha: .13), borderRadius: BorderRadius.circular(30)),
                              child: Text(value, style: TextStyle(color: color, fontWeight: FontWeight.w800)),
                            ));
                          }
                          return DataCell(ConstrainedBox(
                            constraints: const BoxConstraints(maxWidth: 240),
                            child: Text(value, maxLines: 3, overflow: TextOverflow.ellipsis),
                          ));
                        }).toList(growable: false),
                      );
                    }),
                  ),
                ),
              ),
            const SizedBox(height: 14),
            Text('هذه معاينة داخلية مبنية على بيانات التقرير الفعلية. تصدير PDF متاح من شاشة التقارير.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant, height: 1.6)),
          ],
        ),
      ),
    );
  }

  static String _formatDate(DateTime value) {
    final date = '${value.year}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
    final time = '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
    return '$date $time';
  }

  static Color _statusColor(ThemeData theme, String value) => switch (value) {
    'green' => WajhatakColors.emerald,
    'amber' => Colors.amber,
    'red' => theme.colorScheme.error,
    'blue' => Colors.blue,
    _ => theme.colorScheme.onSurfaceVariant,
  };
}
