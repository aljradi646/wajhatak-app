import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/agent_report.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';

/// تقارير الوكيل داخل التطبيق — أنواع التقارير ومرشحاتها تُقرأ من الـ API
/// (لا قوائم ثابتة في التطبيق)، والبيانات كلها من قاعدة البيانات.
class AgentReportsScreen extends ConsumerStatefulWidget {
  const AgentReportsScreen({super.key});

  @override
  ConsumerState<AgentReportsScreen> createState() => _AgentReportsScreenState();
}

class _AgentReportsScreenState extends ConsumerState<AgentReportsScreen> {
  AgentReportsCatalog? _catalog;
  Object? _catalogError;
  bool _loadingCatalog = true;

  AgentReportType? _type;
  final Map<String, String> _filters = {};
  String? _dateFrom;
  String? _dateTo;

  AgentReport? _report;
  Object? _reportError;
  bool _loadingReport = false;

  @override
  void initState() {
    super.initState();
    _loadCatalog();
  }

  Future<void> _loadCatalog() async {
    setState(() {
      _loadingCatalog = true;
      _catalogError = null;
    });
    try {
      final catalog = await ref.read(agentReportRepositoryProvider).catalog();
      if (!mounted) return;
      setState(() {
        _catalog = catalog;
        _loadingCatalog = false;
        _type ??= catalog.types.isNotEmpty ? catalog.types.first : null;
      });
      if (_type != null) await _loadReport();
    } on Object catch (error) {
      if (!mounted) return;
      setState(() {
        _catalogError = error;
        _loadingCatalog = false;
      });
    }
  }

  Future<void> _loadReport() async {
    final type = _type;
    if (type == null) return;

    setState(() {
      _loadingReport = true;
      _reportError = null;
    });
    try {
      final report = await ref
          .read(agentReportRepositoryProvider)
          .fetch(
            type.key,
            filters: {
              ..._filters,
              'date_from': ?_dateFrom,
              'date_to': ?_dateTo,
            },
          );
      if (!mounted) return;
      setState(() {
        _report = report;
        _loadingReport = false;
      });
    } on Object catch (error) {
      if (!mounted) return;
      setState(() {
        _reportError = error;
        _loadingReport = false;
      });
    }
  }

  void _selectType(AgentReportType type) {
    if (_type?.key == type.key) return;
    setState(() {
      _type = type;
      _filters.clear();
      _dateFrom = null;
      _dateTo = null;
      _report = null;
    });
    _loadReport();
  }

  Future<void> _openExport({int? logId, String? format}) async {
    final repository = ref.read(agentReportRepositoryProvider);
    final Uri uri;
    if (logId != null) {
      uri = Uri.parse(await repository.exportUrl(logId));
    } else {
      final type = _type;
      if (type == null) return;
      uri = Uri.parse(
        await repository.exportTypeUrl(
          type.key,
          format ?? 'pdf',
          filters: Map.of(_filters),
        ),
      );
    }

    if (!mounted) return;
    final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted) return;
    if (!launched) {
      util.notice(context, 'تعذّر فتح الرابط على هذا الجهاز.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: (ModalRoute.of(context)?.canPop ?? false)
          ? const WajhatakScreenHeader(
              title: 'تقاريري',
              subtitle: 'تقارير عقاراتك وطلباتك',
            )
          : null,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadCatalog,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 28),
            children: [
              Text(
                'تقاريري',
                style: theme.textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'تقارير مبنية على بياناتك الحقيقية — معاينة، تصدير، وسجل كامل.',
                style: theme.textTheme.bodySmall,
              ),
              const SizedBox(height: 14),
              _buildBody(theme),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_loadingCatalog) {
      return const AgentReportSkeleton();
    }

    if (_catalogError != null) {
      return EmptyState(
        title: 'تعذّر تحميل التقارير',
        body: _messageOf(_catalogError),
        icon: Icons.cloud_off_outlined,
        actionLabel: 'إعادة المحاولة',
        onAction: _loadCatalog,
      );
    }

    final catalog = _catalog;
    if (catalog == null || catalog.types.isEmpty) {
      return const EmptyState(
        title: 'لا توجد تقارير متاحة',
        body: 'هذه الميزة متاحة لحسابات الوكلاء.',
      );
    }

    final type = _type ?? catalog.types.first;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _buildTypeSelector(catalog.types, type),
        const SizedBox(height: 12),
        _buildDateRange(theme),
        if (type.filters.isNotEmpty) ...[
          ...type.filters.map(_buildFilterRow),
          const SizedBox(height: 8),
        ],
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            FilledButton.icon(
              onPressed: _loadingReport ? null : _loadReport,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('عرض التقرير'),
            ),
            OutlinedButton.icon(
              onPressed: () => _openExport(format: 'csv'),
              icon: const Icon(Icons.grid_on_rounded, size: 18),
              label: const Text('تصدير CSV'),
            ),
            OutlinedButton.icon(
              onPressed: () => _openExport(format: 'pdf'),
              icon: const Icon(Icons.picture_as_pdf_rounded, size: 18),
              label: const Text('تصدير PDF'),
            ),
          ],
        ),
        const SizedBox(height: 16),
        _buildReport(theme),
        const SizedBox(height: 22),
        _buildHistory(theme, catalog.history),
      ],
    );
  }

  Widget _buildTypeSelector(
    List<AgentReportType> types,
    AgentReportType current,
  ) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: types
          .map(
            (type) => ChoiceChip(
              label: Text(type.label),
              selected: type.key == current.key,
              onSelected: (_) => _selectType(type),
            ),
          )
          .toList(growable: false),
    );
  }

  Future<void> _pickDate({required bool from}) async {
    final current =
        DateTime.tryParse(from ? (_dateFrom ?? '') : (_dateTo ?? '')) ??
        DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      helpText: from ? 'اختر تاريخ البداية' : 'اختر تاريخ النهاية',
      cancelText: 'إلغاء',
      confirmText: 'اختيار',
    );
    if (picked == null || !mounted) return;
    final value =
        '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
    setState(() {
      if (from) {
        _dateFrom = value;
      } else {
        _dateTo = value;
      }
    });
    _loadReport();
  }

  Widget _buildDateRange(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'الفترة الزمنية',
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 6),
        LayoutBuilder(
          builder: (context, constraints) {
            final buttonWidth = (constraints.maxWidth - 8) / 2;
            return Wrap(
              spacing: 8,
              runSpacing: 6,
              children: [
                SizedBox(
                  width: buttonWidth,
                  child: OutlinedButton.icon(
                    onPressed: () => _pickDate(from: true),
                    icon: const Icon(Icons.date_range_rounded, size: 17),
                    label: Text(
                      _dateFrom ?? 'من تاريخ',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
                SizedBox(
                  width: buttonWidth,
                  child: OutlinedButton.icon(
                    onPressed: () => _pickDate(from: false),
                    icon: const Icon(Icons.event_rounded, size: 17),
                    label: Text(
                      _dateTo ?? 'إلى تاريخ',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ),
                if (_dateFrom != null || _dateTo != null)
                  TextButton.icon(
                    onPressed: () {
                      setState(() {
                        _dateFrom = null;
                        _dateTo = null;
                      });
                      _loadReport();
                    },
                    icon: const Icon(Icons.close_rounded, size: 18),
                    label: const Text('مسح الفترة'),
                  ),
              ],
            );
          },
        ),
        const SizedBox(height: 10),
      ],
    );
  }

  Widget _buildFilterRow(AgentReportFilterDefinition definition) {
    final selected = _filters[definition.key];
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            definition.label,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: [
              FilterChip(
                label: const Text('الكل'),
                selected: selected == null,
                onSelected: (_) {
                  setState(() => _filters.remove(definition.key));
                  _loadReport();
                },
              ),
              ...definition.options.map(
                (option) => FilterChip(
                  label: Text(option.label),
                  selected: selected == option.value,
                  onSelected: (_) {
                    setState(() => _filters[definition.key] = option.value);
                    _loadReport();
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildReport(ThemeData theme) {
    if (_loadingReport) {
      return const AgentReportSkeleton();
    }

    if (_reportError != null) {
      return EmptyState(
        title: 'تعذّر توليد التقرير',
        body: _messageOf(_reportError),
        icon: Icons.cloud_off_outlined,
        actionLabel: 'إعادة المحاولة',
        onAction: _loadReport,
      );
    }

    final report = _report;
    if (report == null) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  report.heading,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 2),
                Text(report.description, style: theme.textTheme.bodySmall),
                if (report.filters.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: report.filters
                        .map(
                          (filter) => Chip(
                            label: Text('${filter.label}: ${filter.value}'),
                            visualDensity: VisualDensity.compact,
                          ),
                        )
                        .toList(growable: false),
                  ),
                ],
                const SizedBox(height: 10),
                LayoutBuilder(
                  builder: (context, constraints) {
                    final columns = constraints.maxWidth >= 700
                        ? 3
                        : constraints.maxWidth >= 340
                        ? 2
                        : 1;
                    final cardRatio = columns == 3
                        ? 2.7
                        : columns == 2
                        ? 2.2
                        : 3.0;

                    return GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: report.summary.length,
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: columns,
                        childAspectRatio: cardRatio,
                        mainAxisSpacing: 10,
                        crossAxisSpacing: 10,
                      ),
                      itemBuilder: (_, index) {
                    final item = report.summary[index];
                    return Card(
                      elevation: 0,
                      color: theme.colorScheme.surfaceContainerHighest
                          .withValues(alpha: .55),
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item.label,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.labelSmall,
                            ),
                            const Spacer(),
                            Text(
                              item.value,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                      },
                    );
                  },
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        if (report.rows.isEmpty)
          const EmptyState(
            title: 'لا توجد بيانات',
            body: 'لا توجد سجلات مطابقة للمرشحات المحددة.',
          )
        else
          _buildRowsTable(theme, report),
        const SizedBox(height: 6),
        Text(
          'وُلّد في ${_formatDate(report.generatedAt)} — ${report.siteName}',
          style: theme.textTheme.bodySmall,
        ),
      ],
    );
  }

  Widget _buildRowsTable(ThemeData theme, AgentReport report) {
    return Card(
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: DataTable(
          headingRowHeight: 46,
          dataRowMinHeight: 44,
          dataRowMaxHeight: 60,
          columns: report.columns
              .map(
                (column) => DataColumn(
                  label: Text(
                    column.label,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  numeric: column.isNumeric,
                ),
              )
              .toList(growable: false),
          rows: List<DataRow>.generate(report.rows.length, (index) {
            final row = report.rows[index];
            return DataRow(
              cells: report.columns
                  .map(
                    (column) => DataCell(
                      _cellContent(
                        theme,
                        column,
                        row[column.key],
                        index,
                        report,
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

  Widget _cellContent(
    ThemeData theme,
    AgentReportColumn column,
    String? value,
    int rowIndex,
    AgentReport report,
  ) {
    if (column.type == 'badge') {
      final raw = report.rawValueAt(rowIndex, column.key) ?? '';
      final tone = column.valueColors[raw] ?? 'gray';
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: _badgeColor(tone),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          value ?? '',
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        ),
      );
    }
    return Text(value ?? '', style: theme.textTheme.bodySmall);
  }

  Color _badgeColor(String tone) {
    switch (tone) {
      case 'green':
        return Colors.green.withValues(alpha: 0.16);
      case 'amber':
        return Colors.amber.withValues(alpha: 0.20);
      case 'red':
        return Colors.red.withValues(alpha: 0.14);
      case 'blue':
        return Colors.blue.withValues(alpha: 0.14);
      default:
        return Colors.grey.withValues(alpha: 0.18);
    }
  }

  Widget _buildHistory(ThemeData theme, List<AgentReportHistoryEntry> history) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'سجل التقارير',
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 8),
        if (history.isEmpty)
          Text('لم تولّد أي تقرير بعد.', style: theme.textTheme.bodySmall)
        else
          ...history.map(
            (entry) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                dense: true,
                leading: const Icon(Icons.description_outlined),
                title: Text(entry.label),
                subtitle: Text(
                  '${entry.formatLabel} • ${entry.rowCount} سجل • ${_formatDate(entry.createdAt)}',
                ),
                trailing: IconButton(
                  tooltip: 'إعادة التصدير',
                  icon: const Icon(Icons.open_in_new_rounded, size: 20),
                  onPressed: () => _openExport(logId: entry.id),
                ),
              ),
            ),
          ),
      ],
    );
  }

  static String _formatDate(DateTime value) {
    final date =
        '${value.year}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')}';
    final time =
        '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
    return '$date $time';
  }

  static String _messageOf(Object? error) {
    if (error is ApiFailure) return error.message;
    return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
  }
}
