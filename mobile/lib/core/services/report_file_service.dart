import 'dart:io';
import 'dart:typed_data';

import 'package:cross_file/cross_file.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

class SavedReportFile {
  const SavedReportFile({
    required this.file,
    required this.inDownloads,
  });

  final File file;
  final bool inDownloads;
}

class ReportFileService {
  const ReportFileService._();

  static Future<SavedReportFile> saveBytes(
    Uint8List bytes, {
    required String fileName,
  }) async {
    final downloads = await _tryDownloadsDirectory();
    if (downloads != null) {
      try {
        await downloads.create(recursive: true);
        final file = File('${downloads.path}/$fileName');
        await file.writeAsBytes(bytes, flush: true);
        return SavedReportFile(file: file, inDownloads: true);
      } on Object {
        // Scoped-storage/device restrictions may block public Downloads.
      }
    }

    final documents = await getApplicationDocumentsDirectory();
    final reports = Directory('${documents.path}/reports');
    await reports.create(recursive: true);
    final file = File('${reports.path}/$fileName');
    await file.writeAsBytes(bytes, flush: true);
    return SavedReportFile(file: file, inDownloads: false);
  }

  static Future<SavedReportFile> saveFile(
    File source, {
    required String fileName,
  }) async {
    return saveBytes(await source.readAsBytes(), fileName: fileName);
  }

  static Future<File> writeTemporaryPdf(
    Uint8List bytes, {
    required String fileName,
  }) async {
    final directory = await getTemporaryDirectory();
    final file = File('${directory.path}/$fileName');
    await file.writeAsBytes(bytes, flush: true);
    return file;
  }

  static Future<void> shareFile(
    File file, {
    required String title,
    String? message,
  }) async {
    final extension = file.path.split('.').last.toLowerCase();
    final mimeType = switch (extension) {
      'pdf' => 'application/pdf',
      'csv' => 'text/csv',
      'json' => 'application/json',
      _ => 'application/octet-stream',
    };
    await Share.shareXFiles(
      [XFile(file.path, mimeType: mimeType)],
      subject: title,
      text: message,
    );
  }

  static Future<Directory?> _tryDownloadsDirectory() async {
    try {
      return await getDownloadsDirectory();
    } on Object {
      return null;
    }
  }
}
