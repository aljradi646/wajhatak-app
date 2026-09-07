import 'package:flutter/foundation.dart';

/// عملة مدعومة — تُجلب من GET /api/v1/currencies (data-driven).
@immutable
class Currency {
  const Currency({
    required this.code,
    required this.nameAr,
    required this.symbolAr,
    this.nameEn,
    this.symbolEn,
    this.flag,
    this.decimals = 0,
    this.isDefault = false,
  });

  final String code;
  final String nameAr;
  final String symbolAr;
  final String? nameEn;
  final String? symbolEn;
  final String? flag;
  final int decimals;
  final bool isDefault;

  /// عملات احتياطية تُستخدم قبل وصول بيانات الخادم أو عند فشله.
  /// (لا تُستخدم كمصدر حقيقة — الـ API هو المصدر.)
  static const fallback = [
    Currency(code: 'YER', nameAr: 'ريال يمني', symbolAr: 'ر.ي', flag: '🇾🇪', isDefault: true),
    Currency(code: 'SAR', nameAr: 'ريال سعودي', symbolAr: 'ر.س', flag: '🇸🇦'),
    Currency(code: 'USD', nameAr: 'دولار أمريكي', symbolAr: r'$', flag: '🇺🇸', decimals: 2),
  ];

  factory Currency.fromJson(Map<String, dynamic> json) => Currency(
    code: json['code'] as String? ?? '',
    nameAr: json['name_ar'] as String? ?? json['code'] as String? ?? '',
    symbolAr: json['symbol_ar'] as String? ?? json['code'] as String? ?? '',
    nameEn: json['name_en'] as String?,
    symbolEn: json['symbol_en'] as String?,
    flag: json['flag'] as String?,
    decimals: (json['decimals'] as num?)?.toInt() ?? 0,
    isDefault: json['is_default'] as bool? ?? false,
  );

  @override
  bool operator ==(Object other) =>
      identical(this, other) || other is Currency && code == other.code;

  @override
  int get hashCode => code.hashCode;
}
