import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/services/report_file_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/agent_report.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import 'agent_pdf_report_screen.dart';
import 'agent_report_components.dart';
import 'agent_report_preview_screen.dart';

/// تقارير الوكيل: مرشحات واضحة، معاينة داخل التطبيق، وحفظ الملفات دون فتح المتصفح.
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
  bool _filtersDirty = false;
  String? _exporting;

  @override
  void initState() {
    super.initState();
    _loadCatalog();
  }

  Map<String, String> get _currentFilters => {
    ..._filters,
    if (_dateFrom != null) 'date_from': _dateFrom!,
    if (_dateTo != null) 'date_to': _dateTo!,
  };

  Future<void> _loadCatalog() async {
    setState(() {
      _loadingCatalog = _catalog == null;
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
    if (type == null || _loadingReport) return;
    setState(() {
      _loadingReport = true;
      _reportError = null;
    });
    try {
      final report = await ref
          .read(agentReportRepositoryProvider)
          .fetch(type.key, filters: _currentFilters);
      if (!mounted) return;
      setState(() {
        _report = report;
        _loadingReport = false;
        _filtersDirty = false;
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
      _reportError = null;
      _filtersDirty = false;
    });
  }

  void _setDatePreset(int days) {
    final now = DateTime.now();
    setState(() {
      _dateFrom = _dateString(now.subtract(Duration(days: days - 1)));
      _dateTo = _dateString(now);
      _filtersDirty = true;
    });
  }

  void _setCurrentMonth() {
    final now = DateTime.now();
    setState(() {
      _dateFrom = _dateString(DateTime(now.year, now.month));
      _dateTo = _dateString(now);
      _filtersDirty = true;
    });
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
    setState(() {
      if (from) {
        _dateFrom = _dateString(picked);
      } else {
        _dateTo = _dateString(picked);
      }
      _filtersDirty = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    final hasRouteAppBar = ModalRoute.of(context)?.canPop ?? false;
    return Scaffold(
      appBar: hasRouteAppBar
          ? const WajhatakScreenHeader(
              title: 'تقاريري',
              subtitle: 'تقارير عقاراتك وطلباتك',
            )
          : null,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadCatalog,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 30),
            children: [_buildBody(Theme.of(context))],
          ),
        ),
      ),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_loadingCatalog) return const AgentReportSkeleton();
    if (_catalogError != null && _catalog == null) {
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
        Row(
          children: [
            Expanded(child: _buildTypeDropdown(theme, catalog.types, type)),
            const SizedBox(width: 8),
            IconButton.filledTonal(
              tooltip: 'فلاتر التقرير',
              onPressed: _loadingReport ? null : () => _openFiltersSheet(type),
              icon: Badge(
                isLabelVisible: _activeFilterCount > 0,
                label: Text('$_activeFilterCount'),
                child: const Icon(Icons.filter_alt_rounded),
              ),
            ),
            const SizedBox(width: 4),
            IconButton(
              tooltip: 'تحديث أنواع التقارير',
              onPressed: _loadingCatalog ? null : _loadCatalog,
              icon: const Icon(Icons.refresh_rounded),
            ),
          ],
        ),
        if (_filtersDirty) ...[
          const SizedBox(height: 8),
          Text(
            'الفلاتر تغيرت. أنشئ التقرير لتطبيقها.',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.primary,
            ),
          ),
        ],
        const SizedBox(height: 12),
        SizedBox(
          height: 46,
          child: FilledButton.icon(
            onPressed: _loadingReport ? null : _loadReport,
            icon: _loadingReport
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.play_arrow_rounded, size: 19),
            label: Text(
              _loadingReport ? 'جارٍ إنشاء التقرير…' : 'إنشاء التقرير',
            ),
          ),
        ),
        if (_report != null) ...[
          const SizedBox(height: 14),
          _buildActionBar(theme, type),
        ],
        if (_report != null) ...[
          const SizedBox(height: 16),
          _buildReport(theme),
        ],
        const SizedBox(height: 20),
        _buildHistory(theme, catalog.history),
      ],
    );
  }

  int get _activeFilterCount =>
      _filters.length + (_dateFrom != null ? 1 : 0) + (_dateTo != null ? 1 : 0);

  Widget _buildTypeDropdown(
    ThemeData theme,
    List<AgentReportType> types,
    AgentReportType current,
  ) {
    return DropdownButtonFormField<String>(
      initialValue: current.key,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: 'نوع التقرير',
        prefixIcon: const Icon(Icons.description_outlined),
        filled: true,
        fillColor: theme.colorScheme.surfaceContainerLow,
      ),
      items: types
          .map(
            (item) => DropdownMenuItem<String>(
              value: item.key,
              child: Text(
                item.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(growable: false),
      onChanged: _loadingReport
          ? null
          : (key) {
              final selected = types.firstWhere((item) => item.key == key);
              _selectType(selected);
            },
    );
  }

  Future<void> _openFiltersSheet(AgentReportType type) async {
    final draftFilters = Map<String, String>.from(_filters);
    var draftFrom = _dateFrom;
    var draftTo = _dateTo;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) {
          Future<void> pickDate({required bool from}) async {
            final current =
                DateTime.tryParse(from ? (draftFrom ?? '') : (draftTo ?? '')) ??
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
            if (picked == null) return;
            setSheetState(() {
              if (from) {
                draftFrom = _dateString(picked);
              } else {
                draftTo = _dateString(picked);
              }
            });
          }

          void setPreset(int days) {
            final now = DateTime.now();
            setSheetState(() {
              draftFrom = _dateString(now.subtract(Duration(days: days - 1)));
              draftTo = _dateString(now);
            });
          }

          return Padding(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              bottom: MediaQuery.viewInsetsOf(context).bottom + 16,
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    'فلاتر التقرير',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'حدد الفترة والحالة ثم طبّقها عند إنشاء التقرير.',
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                  const SizedBox(height: 18),
                  Text(
                    'الفترة الزمنية',
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final days in [7, 30, 90])
                        ChoiceChip(
                          label: Text('$days يوم'),
                          selected: false,
                          onSelected: (_) => setPreset(days),
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => pickDate(from: true),
                          icon: const Icon(Icons.date_range_rounded, size: 17),
                          label: Text(draftFrom ?? 'من تاريخ'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => pickDate(from: false),
                          icon: const Icon(Icons.event_rounded, size: 17),
                          label: Text(draftTo ?? 'إلى تاريخ'),
                        ),
                      ),
                    ],
                  ),
                  for (final definition in type.filters) ...[
                    const SizedBox(height: 18),
                    Text(
                      definition.label,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        FilterChip(
                          label: const Text('الكل'),
                          selected: !draftFilters.containsKey(definition.key),
                          onSelected: (_) => setSheetState(
                            () => draftFilters.remove(definition.key),
                          ),
                        ),
                        for (final option in definition.options)
                          FilterChip(
                            label: Text(option.label),
                            selected:
                                draftFilters[definition.key] == option.value,
                            onSelected: (_) => setSheetState(() {
                              draftFilters[definition.key] = option.value;
                            }),
                          ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 22),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () {
                            setSheetState(() {
                              draftFilters.clear();
                              draftFrom = null;
                              draftTo = null;
                            });
                          },
                          child: const Text('مسح'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        flex: 2,
                        child: FilledButton(
                          onPressed: () {
                            setState(() {
                              _filters
                                ..clear()
                                ..addAll(draftFilters);
                              _dateFrom = draftFrom;
                              _dateTo = draftTo;
                              _filtersDirty = true;
                            });
                            Navigator.of(sheetContext).pop();
                          },
                          child: const Text('تطبيق الفلاتر'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _sectionHeading(
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
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
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

  Widget _buildTypeSelector(
    List<AgentReportType> types,
    AgentReportType current,
  ) {
    return LayoutBuilder(
      builder: (context, constraints) {
        const spacing = 9.0;
        final columns = constraints.maxWidth >= 820 ? 3 : 2;
        final cardWidth =
            (constraints.maxWidth - spacing * (columns - 1)) / columns;
        return Wrap(
          spacing: spacing,
          runSpacing: spacing,
          children: types
              .map((type) {
                final selected = type.key == current.key;
                final theme = Theme.of(context);
                final tone = _typeColor(type.key);
                return SizedBox(
                  width: cardWidth,
                  child: Material(
                    color: selected
                        ? tone.withValues(alpha: .09)
                        : theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(18),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(18),
                      onTap: _loadingReport ? null : () => _selectType(type),
                      child: Container(
                        constraints: const BoxConstraints(minHeight: 116),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: selected
                                ? tone
                                : theme.colorScheme.outlineVariant,
                            width: selected ? 1.6 : 1,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Container(
                                  width: 36,
                                  height: 36,
                                  decoration: BoxDecoration(
                                    color: tone.withValues(alpha: .14),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Icon(
                                    _typeIcon(type.key),
                                    color: tone,
                                    size: 20,
                                  ),
                                ),
                                const Spacer(),
                                if (selected)
                                  Icon(
                                    Icons.check_circle_rounded,
                                    color: tone,
                                    size: 19,
                                  ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Text(
                              type.label,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.w900,
                                height: 1.35,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              type.description,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodySmall?.copyWith(
                                fontSize: 11,
                                height: 1.4,
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              })
              .toList(growable: false),
        );
      },
    );
  }

  IconData _typeIcon(String key) => switch (key) {
    'properties' => Icons.home_work_rounded,
    'viewing_requests' => Icons.event_available_rounded,
    'performance' => Icons.query_stats_rounded,
    _ => Icons.analytics_rounded,
  };

  Color _typeColor(String key) => switch (key) {
    'properties' => WajhatakColors.emerald,
    'viewing_requests' => const Color(0xFF3478D4),
    'performance' => const Color(0xFF8055C7),
    _ => WajhatakColors.amberDeep,
  };

  Widget _buildDateRange(ThemeData theme) {
    final now = DateTime.now();
    final presets = <({String label, int days})>[
      (label: '7 أيام', days: 7),
      (label: '30 يومًا', days: 30),
      (label: '90 يومًا', days: 90),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'الفترة الزمنية',
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 7),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              ...presets.map(
                (preset) => Padding(
                  padding: const EdgeInsetsDirectional.only(end: 7),
                  child: ChoiceChip(
                    label: Text(preset.label),
                    selected:
                        _dateFrom ==
                            _dateString(
                              now.subtract(Duration(days: preset.days - 1)),
                            ) &&
                        _dateTo == _dateString(now),
                    onSelected: (_) => _setDatePreset(preset.days),
                    visualDensity: VisualDensity.compact,
                  ),
                ),
              ),
              ChoiceChip(
                label: const Text('هذا الشهر'),
                selected:
                    _dateFrom == _dateString(DateTime(now.year, now.month)) &&
                    _dateTo == _dateString(now),
                onSelected: (_) => _setCurrentMonth(),
                visualDensity: VisualDensity.compact,
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
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
              ],
            );
          },
        ),
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
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 7),
          Wrap(
            spacing: 8,
            runSpacing: 6,
            children: [
              FilterChip(
                label: const Text('الكل'),
                selected: selected == null,
                onSelected: (_) {
                  setState(() {
                    _filters.remove(definition.key);
                    _filtersDirty = true;
                  });
                },
              ),
              ...definition.options.map(
                (option) => FilterChip(
                  label: Text(option.label),
                  selected: selected == option.value,
                  onSelected: (_) {
                    setState(() {
                      if (selected == option.value) {
                        _filters.remove(definition.key);
                      } else {
                        _filters[definition.key] = option.value;
                      }
                      _filtersDirty = true;
                    });
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionBar(ThemeData theme, AgentReportType type) {
    final report = _report;
    final busy = _exporting != null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'إجراءات التقرير',
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _ReportActionButton(
              icon: Icons.visibility_rounded,
              label: 'عرض التقرير',
              onPressed: report == null || busy
                  ? null
                  : () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => AgentReportPreviewScreen(
                          report: report,
                          onPreviewPdf: () => _previewPdf(),
                          onSavePdf: () => _exportReport(format: 'pdf'),
                          onSaveCsv: () => _exportReport(format: 'csv'),
                        ),
                      ),
                    ),
            ),
            if (type.formats.contains('pdf'))
              _ReportActionButton(
                icon: Icons.picture_as_pdf_rounded,
                label: 'معاينة PDF',
                onPressed: report == null || busy ? null : _previewPdf,
              ),
            if (type.formats.contains('pdf'))
              _ReportActionButton(
                icon: Icons.save_alt_rounded,
                label: 'حفظ PDF',
                onPressed: report == null || busy
                    ? null
                    : () => _exportReport(format: 'pdf'),
                busy: busy && _exporting == 'pdf',
              ),
            if (type.formats.contains('csv'))
              _ReportActionButton(
                icon: Icons.grid_on_rounded,
                label: 'تصدير CSV',
                onPressed: report == null || busy
                    ? null
                    : () => _exportReport(format: 'csv'),
                busy: busy && _exporting == 'csv',
              ),
          ],
        ),
      ],
    );
  }

  Future<Uint8List> _exportBytes({int? logId, String format = 'pdf'}) {
    final repository = ref.read(agentReportRepositoryProvider);
    if (logId != null) return repository.exportHistory(logId);
    final type = _type;
    if (type == null) {
      return Future<Uint8List>.error(StateError('لم يتم اختيار نوع التقرير.'));
    }
    return repository.exportType(type.key, format, filters: _currentFilters);
  }

  Future<void> _previewPdf({int? logId, String? title}) async {
    if (_exporting != null) return;
    setState(() => _exporting = 'preview');
    try {
      final bytes = await _exportBytes(logId: logId, format: 'pdf');
      final file = await ReportFileService.writeTemporaryPdf(
        bytes,
        fileName:
            'wajhatak_preview_' +
            DateTime.now().millisecondsSinceEpoch.toString() +
            '.pdf',
      );
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => AgentPdfReportScreen(
            filePath: file.path,
            title: title ?? _report?.heading ?? _type?.label ?? 'تقرير وجهتك',
          ),
        ),
      );
    } on Object catch (error) {
      if (!mounted) return;
      util.notice(context, _messageOf(error));
    } finally {
      if (mounted) setState(() => _exporting = null);
    }
  }

  Future<void> _exportReport({
    int? logId,
    required String format,
    String? title,
  }) async {
    if (_exporting != null) return;
    setState(() => _exporting = format);
    try {
      final bytes = await _exportBytes(logId: logId, format: format);
      final extension = switch (format) {
        'pdf' => 'pdf',
        'csv' => 'csv',
        _ => 'json',
      };
      final stamp = DateTime.now().millisecondsSinceEpoch.toString();
      final saved = await ReportFileService.saveBytes(
        bytes,
        fileName:
            'wajhatak_' +
            (_type?.key ?? 'report') +
            '_' +
            stamp +
            '.' +
            extension,
      );
      await ReportFileService.shareFile(
        saved.file,
        title: title ?? _report?.heading ?? 'تقرير وجهتك',
        message: 'تقرير مولّد من تطبيق وجهتك.',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            saved.inDownloads
                ? 'تم حفظ نسخة في مجلد التنزيلات وفتح خيارات المشاركة.'
                : 'حُفظت نسخة داخل مساحة التطبيق وفتحت خيارات المشاركة لحفظها في المكان الذي تختاره.',
          ),
        ),
      );
      final catalog = await ref.read(agentReportRepositoryProvider).catalog();
      if (mounted) setState(() => _catalog = catalog);
    } on Object catch (error) {
      if (!mounted) return;
      util.notice(context, _messageOf(error));
    } finally {
      if (mounted) setState(() => _exporting = null);
    }
  }

  Widget _buildReport(ThemeData theme) {
    final report = _report;
    if (_loadingReport && report == null) {
      return const AgentReportSkeleton();
    }
    if (_reportError != null && report == null) {
      return EmptyState(
        title: 'تعذّر توليد التقرير',
        body: _messageOf(_reportError),
        icon: Icons.cloud_off_outlined,
        actionLabel: 'إعادة المحاولة',
        onAction: _loadReport,
      );
    }
    if (report == null) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionHeading(
          theme,
          icon: Icons.summarize_rounded,
          title: 'نتيجة التقرير',
          subtitle: 'ملخص وسجلات من بيانات الخادم وفق المرشحات المطبّقة.',
        ),
        if (_loadingReport) ...[
          const SizedBox(height: 8),
          const LinearProgressIndicator(minHeight: 2),
        ],
        if (_filtersDirty) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: theme.colorScheme.tertiaryContainer.withValues(alpha: .65),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Text(
              'التقرير المعروض يستخدم آخر مرشحات مطبّقة. أنشئ التقرير مجددًا لتحديثه.',
              style: TextStyle(fontSize: 12, height: 1.5),
            ),
          ),
        ],
        const SizedBox(height: 10),
        Card(
          margin: EdgeInsets.zero,
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AgentReportDocumentHeader(report: report),
              Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (report.description.isNotEmpty)
                      Text(
                        report.description,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          height: 1.65,
                        ),
                      ),
                    if (report.filters.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 7,
                        runSpacing: 7,
                        children: report.filters
                            .map(
                              (filter) => Chip(
                                avatar: const Icon(
                                  Icons.filter_alt_rounded,
                                  size: 16,
                                ),
                                label: Text(filter.label + ': ' + filter.value),
                                visualDensity: VisualDensity.compact,
                              ),
                            )
                            .toList(growable: false),
                      ),
                    ],
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Icon(
                          Icons.insights_rounded,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 7),
                        Text(
                          'مؤشرات التقرير',
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    AgentReportSummaryGrid(report: report),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        _sectionHeading(
          theme,
          icon: Icons.table_rows_rounded,
          title: 'تفاصيل السجلات',
          subtitle:
              report.rows.length.toString() + ' سجل — عرض يتكيف مع حجم الشاشة.',
        ),
        const SizedBox(height: 10),
        AgentReportRecords(report: report),
        const SizedBox(height: 10),
        Text(
          'وُلّد في ' +
              _formatDate(report.generatedAt) +
              ' — ' +
              report.siteName,
          textAlign: TextAlign.end,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }

  Widget _buildHistory(ThemeData theme, List<AgentReportHistoryEntry> history) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          margin: EdgeInsets.zero,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(18),
            side: BorderSide(color: theme.colorScheme.outlineVariant),
          ),
          child: ExpansionTile(
            initiallyExpanded: false,
            leading: Icon(
              Icons.history_rounded,
              color: theme.colorScheme.primary,
            ),
            title: const Text(
              'سجل التقارير',
              style: TextStyle(fontWeight: FontWeight.w900),
            ),
            subtitle: Text(
              history.isEmpty
                  ? 'لا توجد تقارير محفوظة بعد'
                  : '${history.length} تقرير محفوظ',
            ),
            children: history.isEmpty
                ? const [
                    Padding(
                      padding: EdgeInsets.fromLTRB(16, 0, 16, 16),
                      child: Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: Text('ستظهر التقارير هنا بعد إنشاء أول تقرير.'),
                      ),
                    ),
                  ]
                : history
                      .map(
                        (entry) => Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(17),
                            side: BorderSide(
                              color: theme.colorScheme.outlineVariant,
                            ),
                          ),
                          child: ListTile(
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 13,
                              vertical: 5,
                            ),
                            leading: Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                color: _formatColor(
                                  entry.format,
                                ).withValues(alpha: .12),
                                borderRadius: BorderRadius.circular(13),
                              ),
                              child: Icon(
                                _formatIcon(entry.format),
                                color: _formatColor(entry.format),
                              ),
                            ),
                            title: Text(
                              entry.label,
                              style: const TextStyle(
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                            subtitle: Text(
                              entry.formatLabel +
                                  ' • ' +
                                  entry.rowCount.toString() +
                                  ' سجل • ' +
                                  _formatDate(entry.createdAt),
                              style: theme.textTheme.bodySmall?.copyWith(
                                height: 1.5,
                              ),
                            ),
                            trailing: PopupMenuButton<String>(
                              tooltip: 'إجراءات التقرير',
                              onSelected: (action) {
                                if (action == 'preview') {
                                  _previewPdf(
                                    logId: entry.id,
                                    title: entry.label,
                                  );
                                } else {
                                  _exportReport(
                                    logId: entry.id,
                                    format: entry.format,
                                    title: entry.label,
                                  );
                                }
                              },
                              itemBuilder: (context) => [
                                if (entry.format == 'pdf')
                                  const PopupMenuItem(
                                    value: 'preview',
                                    child: ListTile(
                                      dense: true,
                                      leading: Icon(
                                        Icons.picture_as_pdf_rounded,
                                      ),
                                      title: Text('معاينة PDF داخل التطبيق'),
                                    ),
                                  ),
                                PopupMenuItem(
                                  value: 'save',
                                  child: ListTile(
                                    dense: true,
                                    leading: const Icon(Icons.save_alt_rounded),
                                    title: Text(
                                      'حفظ / مشاركة ' + entry.formatLabel,
                                    ),
                                  ),
                                ),
                              ],
                              icon: const Icon(Icons.more_vert_rounded),
                            ),
                            onTap: entry.format == 'pdf'
                                ? () => _previewPdf(
                                    logId: entry.id,
                                    title: entry.label,
                                  )
                                : () => _exportReport(
                                    logId: entry.id,
                                    format: entry.format,
                                    title: entry.label,
                                  ),
                          ),
                        ),
                      )
                      .toList(growable: false),
          ),
        ),
      ],
    );
  }

  Color _formatColor(String format) => switch (format) {
    'pdf' => const Color(0xFFC94A4A),
    'csv' => const Color(0xFF21875F),
    _ => const Color(0xFF3478D4),
  };

  IconData _formatIcon(String format) => switch (format) {
    'pdf' => Icons.picture_as_pdf_rounded,
    'csv' => Icons.grid_on_rounded,
    _ => Icons.data_object_rounded,
  };

  static String _dateString(DateTime value) =>
      value.year.toString().padLeft(4, '0') +
      '-' +
      value.month.toString().padLeft(2, '0') +
      '-' +
      value.day.toString().padLeft(2, '0');

  static String _formatDate(DateTime value) {
    final date =
        value.year.toString() +
        '-' +
        value.month.toString().padLeft(2, '0') +
        '-' +
        value.day.toString().padLeft(2, '0');
    final time =
        value.hour.toString().padLeft(2, '0') +
        ':' +
        value.minute.toString().padLeft(2, '0');
    return date + ' ' + time;
  }

  static String _messageOf(Object? error) {
    if (error is ApiFailure) return error.message;
    return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
  }
}

class _ReportActionButton extends StatelessWidget {
  const _ReportActionButton({
    required this.icon,
    required this.label,
    required this.onPressed,
    this.busy = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback? onPressed;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: onPressed,
      icon: busy
          ? const SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(icon, size: 17),
      label: Text(label),
      style: OutlinedButton.styleFrom(
        visualDensity: VisualDensity.compact,
        padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 9),
      ),
    );
  }
}
