import 'package:flutter/material.dart';

/// نقاط التوقف المتجاوبة — مبنية على مقاسات Material 3 الحقيقية.
enum WindowSize { compact, medium, expanded }

extension WindowSizeData on WindowSize {
  bool get isCompact => this == WindowSize.compact;
  bool get isMedium => this == WindowSize.medium;
  bool get isExpanded => this == WindowSize.expanded;
}

/// يحوّل عرض الشاشة الحقيقي إلى فئة تخطيط.
WindowSize windowSizeFromWidth(double width) {
  if (width < 600) return WindowSize.compact;
  if (width < 1024) return WindowSize.medium;
  return WindowSize.expanded;
}

/// أدوات تجاوب مشتركة لكل الواجهات.
class Responsive {
  const Responsive._();

  /// عدد أعمدة شبكة العقارات حسب العرض المتاح.
  /// نحافظ على عمودين في الهواتف المعتادة، ونستخدم عمودًا واحدًا فقط
  /// في المساحات الضيقة جدًا حتى لا تصبح بطاقة العقار غير قابلة للقراءة.
  static int propertyGridColumns(double crossAxisExtent) {
    if (crossAxisExtent >= 1240) return 4;
    if (crossAxisExtent >= 900) return 3;
    if (crossAxisExtent >= 600) return 2;
    if (crossAxisExtent < 340) return 1;
    return 2;
  }

  /// نسبة الارتفاع للبطاقة حسب العرض.
  static double propertyCardAspectRatio(double crossAxisExtent) {
    if (crossAxisExtent >= 900) return .78;
    if (crossAxisExtent >= 600) return .74;
    return .7;
  }

  /// أقصى عرض لمحتوى مركزي (نماذج/مقالات).
  static const double contentMaxWidth = 520;

  /// أقصى عرض لقائمة دردشة مركزية.
  static const double chatMaxWidth = 760;

  /// حشوة الصفحة حسب العرض.
  static EdgeInsets pagePadding(double width) => EdgeInsets.symmetric(
    horizontal: width >= 1024 ? 32 : (width >= 600 ? 26 : 20),
    vertical: width >= 1024 ? 22 : 16,
  );
}
