// نماذج تقارير الوكيل — كل القيم تأتي من الـ API فعليًا (لا قوائم ثابتة).

class AgentReportOption {
  const AgentReportOption({required this.value, required this.label});

  final String value;
  final String label;

  factory AgentReportOption.fromJson(Map<String, dynamic> json) =>
      AgentReportOption(
        value: json['value'] as String? ?? '',
        label: json['label'] as String? ?? '',
      );
}

class AgentReportFilterDefinition {
  const AgentReportFilterDefinition({
    required this.key,
    required this.label,
    required this.options,
  });

  final String key;
  final String label;
  final List<AgentReportOption> options;

  factory AgentReportFilterDefinition.fromJson(Map<String, dynamic> json) =>
      AgentReportFilterDefinition(
        key: json['key'] as String? ?? '',
        label: json['label'] as String? ?? '',
        options: (json['options'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(AgentReportOption.fromJson)
            .toList(growable: false),
      );
}

class AgentReportType {
  const AgentReportType({
    required this.key,
    required this.label,
    required this.description,
    required this.formats,
    required this.filters,
  });

  final String key;
  final String label;
  final String description;
  final List<String> formats;
  final List<AgentReportFilterDefinition> filters;

  factory AgentReportType.fromJson(Map<String, dynamic> json) =>
      AgentReportType(
        key: json['key'] as String? ?? '',
        label: json['label'] as String? ?? '',
        description: json['description'] as String? ?? '',
        formats: (json['formats'] as List<dynamic>? ?? const [])
            .whereType<String>()
            .toList(growable: false),
        filters: (json['filters'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(AgentReportFilterDefinition.fromJson)
            .toList(growable: false),
      );
}

class AgentReportHistoryEntry {
  const AgentReportHistoryEntry({
    required this.id,
    required this.type,
    required this.label,
    required this.format,
    required this.formatLabel,
    required this.rowCount,
    required this.createdAt,
    required this.downloadUrl,
  });

  final int id;
  final String type;
  final String label;
  final String format;
  final String formatLabel;
  final int rowCount;
  final DateTime createdAt;
  final String downloadUrl;

  factory AgentReportHistoryEntry.fromJson(Map<String, dynamic> json) =>
      AgentReportHistoryEntry(
        id: (json['id'] as num?)?.toInt() ?? 0,
        type: json['type'] as String? ?? '',
        label: json['label'] as String? ?? '',
        format: json['format'] as String? ?? '',
        formatLabel: json['format_label'] as String? ?? '',
        rowCount: (json['row_count'] as num?)?.toInt() ?? 0,
        createdAt:
            DateTime.tryParse(json['created_at'] as String? ?? '') ??
            DateTime.now(),
        downloadUrl: json['download_url'] as String? ?? '',
      );
}

class AgentReportsCatalog {
  const AgentReportsCatalog({
    required this.types,
    required this.history,
    required this.agentName,
  });

  final List<AgentReportType> types;
  final List<AgentReportHistoryEntry> history;
  final String agentName;

  factory AgentReportsCatalog.fromJson(Map<String, dynamic> json) =>
      AgentReportsCatalog(
        types: (json['types'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(AgentReportType.fromJson)
            .toList(growable: false),
        history: (json['history'] as List<dynamic>? ?? const [])
            .whereType<Map<String, dynamic>>()
            .map(AgentReportHistoryEntry.fromJson)
            .toList(growable: false),
        agentName:
            (json['agent'] as Map<String, dynamic>? ?? const {})['name']
                as String? ??
            '',
      );
}

class AgentReportColumn {
  const AgentReportColumn({
    required this.key,
    required this.label,
    required this.type,
    required this.valueLabels,
    required this.valueColors,
  });

  final String key;
  final String label;
  final String type;
  final Map<String, String> valueLabels;
  final Map<String, String> valueColors;

  bool get isNumeric => type == 'number' || type == 'money' || type == 'rating';

  factory AgentReportColumn.fromJson(Map<String, dynamic> json) {
    final values = (json['values'] as Map<String, dynamic>? ?? const {})
        .map((key, value) => MapEntry(key, value?.toString() ?? ''));
    final colors = (json['colors'] as Map<String, dynamic>? ?? const {})
        .map((key, value) => MapEntry(key, value?.toString() ?? ''));
    return AgentReportColumn(
      key: json['key'] as String? ?? '',
      label: json['label'] as String? ?? '',
      type: json['type'] as String? ?? 'text',
      valueLabels: values,
      valueColors: colors,
    );
  }
}

class AgentReportSummaryItem {
  const AgentReportSummaryItem({required this.label, required this.value});

  final String label;
  final String value;

  factory AgentReportSummaryItem.fromJson(Map<String, dynamic> json) =>
      AgentReportSummaryItem(
        label: json['label']?.toString() ?? '',
        value: json['value']?.toString() ?? '',
      );
}

class AgentReport {
  const AgentReport({
    required this.type,
    required this.heading,
    required this.description,
    required this.currency,
    required this.generatedAt,
    required this.siteName,
    required this.filters,
    required this.columns,
    required this.summary,
    required this.rows,
    required this.rawRows,
  });

  final String type;
  final String heading;
  final String description;
  final String currency;
  final DateTime generatedAt;
  final String siteName;
  final List<AgentReportSummaryItem> filters;
  final List<AgentReportColumn> columns;
  final List<AgentReportSummaryItem> summary;

  /// قيم جاهزة للعرض (نصوص مُهيّأة).
  final List<Map<String, String>> rows;

  /// قيم خام للبرمجة (مثل حالة العقار لاختيار لون الشارة).
  final List<Map<String, dynamic>> rawRows;

  factory AgentReport.fromJson(Map<String, dynamic> json) {
    final site = json['site'] as Map<String, dynamic>? ?? const {};
    return AgentReport(
      type: json['type'] as String? ?? '',
      heading: json['heading'] as String? ?? '',
      description: json['description'] as String? ?? '',
      currency: json['currency'] as String? ?? '',
      generatedAt:
          DateTime.tryParse(json['generated_at'] as String? ?? '') ??
          DateTime.now(),
      siteName: site['name'] as String? ?? '',
      filters: (json['filters'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(AgentReportSummaryItem.fromJson)
          .toList(growable: false),
      columns: (json['columns'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(AgentReportColumn.fromJson)
          .toList(growable: false),
      summary: (json['summary'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(AgentReportSummaryItem.fromJson)
          .toList(growable: false),
      rows: (json['rows'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .map(
            (row) => row.map(
              (key, value) => MapEntry(key, value?.toString() ?? ''),
            ),
          )
          .toList(growable: false),
      rawRows: (json['raw_rows'] as List<dynamic>? ?? const [])
          .whereType<Map<String, dynamic>>()
          .toList(growable: false),
    );
  }

  /// قيمة خام لخلية (لاختيار لون الشارة) بالاعتماد على نفس فهرس الصف.
  String? rawValueAt(int rowIndex, String key) {
    if (rowIndex < 0 || rowIndex >= rawRows.length) return null;
    return rawRows[rowIndex][key]?.toString();
  }
}
