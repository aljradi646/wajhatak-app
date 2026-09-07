import 'dart:io';

import 'package:image/image.dart' as img;

/// ضغط الصور قبل الرفع بسرعة (في الذاكرة ودون حوار).
///
/// يقلّص الصورة إلى أبعاد قصوى معيّنة ويعيد ترميزها بصيغة JPEG بجودة منخفضة،
/// فيصبح حجم كل صورة صغيرًا جدًا (عادة 150–500KB) — فيكفي تجنّب مهلة الرفع
/// على استضافة مشتركة بطيئة مثل InfinityFree.
class ImageCompressor {
  ImageCompressor({
    this.maxDimension = 1920,
    this.quality = 78,
    this.maxBytes = 600 * 1024,
  });

  final int maxDimension;
  final int quality;
  final int maxBytes;

  /// يضغط ملف الصورة ويرجع مسار ملف JPEG مؤقت جديد.
  /// إن لم تكن الصورة صالحة أعاد المسار الأصلي كما هو.
  Future<String> compress(File source) async {
    return _compress(source.path);
  }

  Future<String> _compress(String path) async {
    try {
      final bytes = await File(path).readAsBytes();
      img.Image? decoded;
      try {
        decoded = img.decodeImage(bytes);
      } catch (_) {
        return path; // ليست صورة صالحة — أعِدها كما هي.
      }
      if (decoded == null) return path;

      final width = decoded.width;
      final height = decoded.height;
      final resized = (width > maxDimension || height > maxDimension)
          ? img.copyResize(
              decoded,
              width: width >= height ? maxDimension : null,
              height: width < height ? maxDimension : null,
              interpolation: img.Interpolation.average,
            )
          : decoded;

      // خفّض الجودة تدريجيًا حتى يصبح الحجم <= maxBytes.
      var q = quality;
      var encoded = img.encodeJpg(resized, quality: q);
      while (encoded.length > maxBytes && q > 40) {
        q -= 8;
        encoded = img.encodeJpg(resized, quality: q);
      }

      final temp = Directory.systemTemp.createTempSync('wj_img');
      final out = File(
        '${temp.path}${Platform.pathSeparator}${DateTime.now().microsecondsSinceEpoch}.jpg',
      );
      await out.writeAsBytes(encoded, flush: true);
      return out.path;
    } catch (_) {
      return path;
    }
  }
}