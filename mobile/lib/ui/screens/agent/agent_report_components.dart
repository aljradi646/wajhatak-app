import 'package:flutter/material.dart';

import '../../../core/theme/app_theme.dart';
import '../../../data/models/agent_report.dart';

class AgentReportDocumentHeader extends StatelessWidget {
  const AgentReportDocumentHeader({super.key, required this.report});

  final AgentReport report;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: theme.brightness == Brightness.dark
            ? WajhatakColors.heroGradientDark
            : WajhatakColors.heroGradientLight,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: WajhatakColors.emerald.withValues(alpha: .12),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 54,
            height: 54,
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Image.asset(
              'assets/images/wajhatak_icon.png',
              fit: BoxFit.contain,
              errorBuilder: (_, _, _) => const Icon(
                Icons.home_work_rounded,
                color: WajhatakColors.emerald,
                size: 32,
              ),
            ),
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'وجهتك',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  report.heading,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                    height: 1.45,
                  ),
                ),
                if (report.siteName.isNotEmpty) ...[
                  const SizedBox(height: 3),
                  Text(
                    report.siteName,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: .82),
                      fontSize: 12,
                    ),
                  ),
                ],
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  children: [
                    _HeaderPill(
                      icon: Icons.schedule_rounded,
                      label: _formatDate(report.generatedAt),
                    ),
                    _HeaderPill(
                      icon: Icons.table_rows_rounded,
                      label: '${report.rows.length} سجل',
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static String _formatDate(DateTime value) {
    final date =
        '${value.year}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
    final time =
        '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
    return date + ' ' + time;
  }
}

class _HeaderPill extends StatelessWidget {
  const _HeaderPill({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: .14),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(color: Colors.white.withValues(alpha: .2)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 13),
          const SizedBox(width: 5),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class AgentReportSummaryGrid extends StatelessWidget {
  const AgentReportSummaryGrid({super.key, required this.report});

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
    Color(0xFF3478D4),
    Color(0xFFD8901D),
    Color(0xFF8055C7),
    Color(0xFF168C91),
    Color(0xFFC96C42),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (report.summary.isEmpty) return const SizedBox.shrink();

    return LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth >= 1000
            ? 4
            : constraints.maxWidth >= 640
                ? 3
                : constraints.maxWidth >= 240
                    ? 2
                    : 1;
        final ratio = constraints.maxWidth < 390 ? 1.18 : 1.55;
        return GridView.builder(
          itemCount: report.summary.length,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            childAspectRatio: ratio,
          ),
          itemBuilder: (context, index) {
            final item = report.summary[index];
            final tone = _colors[index % _colors.length];
            return Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: tone.withValues(alpha: .075),
                borderRadius: BorderRadius.circular(17),
                border: Border.all(color: tone.withValues(alpha: .2)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 31,
                        height: 31,
                        decoration: BoxDecoration(
                          color: tone.withValues(alpha: .13),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Icon(
                          _icons[index % _icons.length],
                          size: 18,
                          color: tone,
                        ),
                      ),
                      const SizedBox(width: 7),
                      Expanded(
                        child: Text(
                          item.label,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.labelSmall?.copyWith(
                            height: 1.3,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const Spacer(),
                  Text(
                    item.value,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: tone,
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}

class AgentReportRecords extends StatelessWidget {
  const AgentReportRecords({super.key, required this.report});

  final AgentReport report;

  @override
  Widget build(BuildContext context) {
    if (report.rows.isEmpty) return const _ReportNoRows();
    if (report.columns.isEmpty) return const SizedBox.shrink();

    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth < 620) {
          return _buildMobileRows(context, constraints.maxWidth);
        }
        return _buildDataTable(context);
      },
    );
  }

  Widget _buildMobileRows(BuildContext context, double width) {
    final theme = Theme.of(context);
    var primary = report.columns.first;
    AgentReportColumn? status;
    for (final column in report.columns) {
      if (column.key == 'title' ||
          column.key == 'property' ||
          column.key == 'name') {
        primary = column;
        break;
      }
    }
    for (final column in report.columns) {
      if (column.type == 'badge') {
        status = column;
        break;
      }
    }

    final details = report.columns
        .where((column) => column.key != primary.key && column.key != status?.key)
        .toList(growable: false);
    final detailWidth = ((width - 44) / 2).clamp(100.0, 260.0).toDouble();
    return Column(
      children: List<Widget>.generate(report.rows.length, (index) {
        final row = report.rows[index];
        final title = row[primary.key]?.trim();
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          elevation: 0,
          color: theme.colorScheme.surface,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(17),
            side: BorderSide(color: theme.colorScheme.outlineVariant),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primaryContainer,
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Icon(
                        Icons.description_rounded,
                        size: 18,
                        color: theme.colorScheme.onPrimaryContainer,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child: Text(
                          title == null || title.isEmpty
                              ? 'سجل ${index + 1}'
                              : title,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w900,
                            height: 1.4,
                          ),
                        ),
                      ),
                    ),
                    if (status != null)
                      Padding(
                        padding: const EdgeInsetsDirectional.only(start: 6),
                        child: _cell(context, index, status, row[status.key]),
                      ),
                  ],
                ),
                if (details.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Divider(height: 1, color: theme.colorScheme.outlineVariant),
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 8,
                    runSpacing: 10,
                    children: details.map((column) {
                      return SizedBox(
                        width: detailWidth,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              column.label,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.labelSmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 3),
                            _cell(context, index, column, row[column.key]),
                          ],
                        ),
                      );
                    }).toList(growable: false),
                  ),
                ],
              ],
            ),
          ),
        );
      }),
    );
  }

  Widget _buildDataTable(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: DataTable(
          headingRowColor: WidgetStatePropertyAll(
            theme.colorScheme.primaryContainer.withValues(alpha: .6),
          ),
          headingRowHeight: 48,
          dataRowMinHeight: 46,
          dataRowMaxHeight: 72,
          columns: report.columns
              .map(
                (column) => DataColumn(
                  label: Text(
                    column.label,
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                  numeric: column.isNumeric,
                ),
              )
              .toList(growable: false),
          rows: List<DataRow>.generate(report.rows.length, (index) {
            final row = report.rows[index];
            return DataRow(
              color: WidgetStateProperty.resolveWith(
                (states) => index.isEven
                    ? theme.colorScheme.surface
                    : theme.colorScheme.surfaceContainerLow,
              ),
              cells: report.columns
                  .map(
                    (column) => DataCell(
                      ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 260),
                        child: _cell(context, index, column, row[column.key]),
                      ),
                    ),
                  )
                  .toList(growable: false),
            );
          }),
        ),
      ),
    );
  }

  Widget _cell(
    BuildContext context,
    int rowIndex,
    AgentReportColumn column,
    String? value,
  ) {
    final theme = Theme.of(context);
    if (column.type == 'badge') {
      final raw = report.rawValueAt(rowIndex, column.key) ?? '';
      final tone = column.valueColors[raw] ?? 'gray';
      final color = _statusColor(theme, tone);
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
        decoration: BoxDecoration(
          color: color.withValues(alpha: .12),
          borderRadius: BorderRadius.circular(30),
        ),
        child: Text(
          value ?? '—',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          style: TextStyle(
            color: color,
            fontSize: 12,
            fontWeight: FontWeight.w800,
          ),
        ),
      );
    }
    return Text(
      value == null || value.isEmpty ? '—' : value,
      maxLines: 3,
      overflow: TextOverflow.ellipsis,
      style: theme.textTheme.bodySmall?.copyWith(height: 1.45),
    );
  }

  Color _statusColor(ThemeData theme, String tone) => switch (tone) {
        'green' => WajhatakColors.emerald,
        'amber' => WajhatakColors.amberDeep,
        'red' => theme.colorScheme.error,
        'blue' => const Color(0xFF3478D4),
        _ => theme.colorScheme.onSurfaceVariant,
      };
}

class _ReportNoRows extends StatelessWidget {
  const _ReportNoRows();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 32),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerLow,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Column(
        children: [
          Icon(
            Icons.inbox_rounded,
            size: 36,
            color: theme.colorScheme.primary,
          ),
          const SizedBox(height: 10),
          Text(
            'لا توجد بيانات مطابقة',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 5),
          Text(
            'غيّر المرشحات أو الفترة الزمنية ثم أعد إنشاء التقرير.',
            textAlign: TextAlign.center,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
              height: 1.6,
            ),
          ),
        ],
      ),
    );
  }
}
