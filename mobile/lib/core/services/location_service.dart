import 'package:geolocator/geolocator.dart';

import '../utils/notice.dart' as notice;

/// الموقع الحقيقي للجهاز — يطلب صلاحية الموقع وقت الحاجة فقط
/// (عند فتح الخريطة أو عند «قريب مني») ويجلب الإحداثيات الفعلية.
class LocationService {
  const LocationService._();

  /// نتيجة طلب الموقع.
  ///
  /// [granted] هل سمح المستخدم؟
  /// [latitude]/[longitude] الإحداثيات الحقيقية إن توفرت.
  /// [message] رسالة عربية جاهزة للعرض عند عدم التمكين.
  static Future<({bool granted, double? latitude, double? longitude, String message})> requestAndLocate() async {
    // 1) هل خدمة الموقع مفعّلة على الجهاز أصلًا؟
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return (
        granted: false,
        latitude: null,
        longitude: null,
        message: 'خدمة الموقع مغلقة على جهازك — فعّلها من الإعدادات لتظهر لك على الخريطة.',
      );
    }

    // 2) طلب الصلاحية الفعلي (ينبثق نظام الطلب لأول مرة).
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied) {
      return (
        granted: false,
        latitude: null,
        longitude: null,
        message: 'لم تُمنح صلاحية الموقع — اسمح بها لتحديد موقعك الحقيقي على الخريطة.',
      );
    }
    if (permission == LocationPermission.deniedForever) {
      return (
        granted: false,
        latitude: null,
        longitude: null,
        message: 'الصلاحية مرفوضة نهائيًا — فعّلها من إعدادات التطبيق لتستخدم «قريب مني».',
      );
    }

    // 3) جلب الموقع الحقيقي بدقة عالية.
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );
      return (granted: true, latitude: position.latitude, longitude: position.longitude, message: 'تم تحديد موقعك الحالي ✓');
    } catch (_) {
      return (
        granted: true,
        latitude: null,
        longitude: null,
        message: 'تعذر جلب الموقع الآن — تأكد من فتح السماء أو أعد المحاولة.',
      );
    }
  }

  /// موقع بدون نافذة طلب إن كانت الصلاحية ممنوحة مسبقًا (لطلبات المساعد الصامتة).
  static Future<({double? latitude, double? longitude})> silentPosition() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.whileInUse || permission == LocationPermission.always) {
      try {
        final position = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(accuracy: LocationAccuracy.medium),
        );
        return (latitude: position.latitude, longitude: position.longitude);
      } catch (_) {
        return (latitude: null, longitude: null);
      }
    }
    return (latitude: null, longitude: null);
  }
}
