import 'dart:io';

import 'package:flutter/material.dart';
import 'package:pdfx/pdfx.dart';

import '../../../core/services/report_file_service.dart';

class AgentPdfReportScreen extends StatefulWidget {
  const AgentPdfReportScreen({
    super.key,
    required this.filePath,
    required this.title,
  });

  final String filePath;
  final String title;

  @override
  State<AgentPdfReportScreen> createState() => _AgentPdfReportScreenState();
}

class _AgentPdfReportScreenState extends State<AgentPdfReportScreen> {
  late final PdfControllerPinch _controller;
  bool _saving = false;
  bool _failed = false;
  int _page = 1;
  int? _pagesCount;

  @override
  void initState() {
    super.initState();
    _controller = PdfControllerPinch(
      document: PdfDocument.openFile(widget.filePath),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _saveAndShare() async {
    if (_saving) return;
    setState(() => _saving = true);
    try {
      final saved = await ReportFileService.saveFile(
        File(widget.filePath),
        fileName: 'wajhatak_report_${DateTime.now().millisecondsSinceEpoch}.pdf',
      );
      await ReportFileService.shareFile(
        saved.file,
        title: widget.title,
        message: 'تقرير صادر من تطبيق وجهتك.',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            saved.inDownloads
                ? 'تم حفظ نسخة في مجلد التنزيلات.'
                : 'تم حفظ نسخة داخل مساحة التطبيق. يمكنك اختيار مكان آخر من المشاركة.',
          ),
        ),
      );
    } on Object {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تعذّر حفظ التقرير. حاول مرة أخرى.')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w900,
              ),
            ),
            const Text(
              'معاينة PDF داخل التطبيق',
              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'حفظ أو مشاركة PDF',
            onPressed: _saving ? null : _saveAndShare,
            icon: _saving
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.save_alt_rounded),
          ),
        ],
      ),
      body: ColoredBox(
        color: theme.colorScheme.surfaceContainerHighest,
        child: _failed
            ? Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.picture_as_pdf_outlined,
                        size: 44,
                        color: theme.colorScheme.error,
                      ),
                      const SizedBox(height: 10),
                      const Text('تعذّرت قراءة ملف PDF'),
                      const SizedBox(height: 8),
                      Text(
                        'ارجع وأعد إنشاء التقرير ثم حاول المعاينة مرة أخرى.',
                        textAlign: TextAlign.center,
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
              )
            : PdfViewPinch(
                controller: _controller,
                padding: 10,
                onPageChanged: (page) {
                  if (mounted) setState(() => _page = page);
                },
                onDocumentLoaded: (document) {
                  if (mounted) {
                    setState(() => _pagesCount = document.pagesCount);
                  }
                },
                onDocumentError: (_) {
                  if (mounted) setState(() => _failed = true);
                },
                builders: PdfViewPinchBuilders<DefaultBuilderOptions>(
                  options: const DefaultBuilderOptions(),
                  documentLoaderBuilder: (_) => const Center(
                    child: CircularProgressIndicator(),
                  ),
                  pageLoaderBuilder: (_) => const Center(
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  errorBuilder: (_, error) => const Center(
                    child: Text('تعذّر تحميل صفحة من التقرير.'),
                  ),
                ),
              ),
      ),
      bottomNavigationBar: SafeArea(
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 9, 16, 12),
          color: theme.colorScheme.surface,
          child: Row(
            children: [
              Icon(
                Icons.description_rounded,
                color: theme.colorScheme.primary,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _pagesCount == null
                      ? 'يتم تحميل صفحات التقرير…'
                      : 'صفحة $_page من $_pagesCount',
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              OutlinedButton.icon(
                onPressed: _saving ? null : _saveAndShare,
                icon: const Icon(Icons.ios_share_rounded, size: 18),
                label: const Text('حفظ / مشاركة'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
