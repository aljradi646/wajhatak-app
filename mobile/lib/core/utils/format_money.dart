import 'package:intl/intl.dart';

/// تنسيق المبالغ بأرقام عربية مع رمز العملة المناسب.
String formatMoney(double value, String currency) =>
    '${NumberFormat.decimalPattern('ar').format(value)} ${currencySymbolAr(currency)}';

/// رمز العملة العربي؛ تُعرض الرموز غير المعروفة كما وردت من الخادم.
String currencySymbolAr(String code) => switch (code) {
  'YER' => 'ر.ي',
  'SAR' => 'ر.س',
  'USD' => r'$',
  _ => code,
};

/// اسم العملة بالعربية.
String currencyNameAr(String code) => switch (code) {
  'YER' => 'ريال يمني',
  'SAR' => 'ريال سعودي',
  'USD' => 'دولار أمريكي',
  _ => code,
};

/// علم الدولة للعملة (للعرض في القوائم المنسدلة).
String currencyFlag(String code) => switch (code) {
  'YER' => '🇾🇪',
  'SAR' => '🇸🇦',
  'USD' => '🇺🇸',
  _ => '🏳️',
};

/// تنسيق رقم المساحة بالمتر المربع.
String formatArea(double area) =>
    '${NumberFormat.decimalPattern('ar').format(area)} م²';
