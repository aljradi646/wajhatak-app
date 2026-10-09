import 'dart:io';

import 'package:permission_handler/permission_handler.dart';

/// طلب صلاحية الصور الرسمية قبل فتح منتقي الصور.
class MediaPermissionService {
  const MediaPermissionService._();

  static Future<bool> requestPhotoAccess() async {
    if (Platform.isIOS) {
      final status = await Permission.photos.request();
      return status.isGranted || status.isLimited;
    }

    if (Platform.isAndroid) {
      final status = await Permission.photos.request();
      if (status.isGranted || status.isLimited) return true;

      final legacy = await Permission.storage.request();
      return legacy.isGranted;
    }

    return true;
  }
}
