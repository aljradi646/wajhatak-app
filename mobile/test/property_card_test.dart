import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/core/theme/app_theme.dart';
import 'package:wajhatak/data/models/models.dart';
import 'package:wajhatak/core/utils/responsive.dart';
import 'package:wajhatak/ui/widgets/property/property_card.dart';

Widget _wrap(Widget child, {Size size = const Size(390, 844)}) {
  return ProviderScope(
    child: MediaQuery(
      data: MediaQueryData(size: size),
      child: MaterialApp(
        theme: buildWajhatakTheme(),
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (_, child) => Directionality(
          textDirection: TextDirection.rtl,
          child: child!,
        ),
        home: Scaffold(body: child),
      ),
    ),
  );
}

const _property = LuxProperty(
  id: 1,
  title: 'شقة عصرية في حدأ',
  price: 45000000,
  currency: 'YER',
  transactionType: 'sale',
  location: PropertyLocation(city: 'صنعاء', district: 'حدأ'),
  images: [
    PropertyImage(id: 1, url: 'https://example.com/1.jpg', isCover: true),
    PropertyImage(id: 2, url: 'https://example.com/2.jpg'),
    PropertyImage(id: 3, url: 'https://example.com/3.jpg'),
  ],
);

void main() {
  testWidgets('PropertyCard shows name first, then price+currency, then location',
      (tester) async {
    await tester.pumpWidget(
      _wrap(
        SizedBox(
          width: 320,
          height: 420,
          child: PropertyCard(
            property: _property,
            onTap: () {},
            onFavorite: () {},
          ),
        ),
      ),
    );
    await tester.pump(const Duration(milliseconds: 400));

    // اسم العقار أولًا
    expect(find.text('شقة عصرية في حدأ'), findsOneWidget);
    // السعر + العملة (ر.ي)
    expect(find.textContaining('ر.ي'), findsOneWidget);
    // الموقع
    expect(find.textContaining('حدأ'), findsWidgets);
    // شارة للبيع
    expect(find.text('للبيع'), findsOneWidget);
    // زر المفضلة موجود
    expect(find.byIcon(Icons.favorite_border_rounded), findsOneWidget);
  });

  testWidgets('PropertyCard renders rent pill for rent listings',
      (tester) async {
    const rentProperty = LuxProperty(
      id: 2,
      title: 'استوديو للإيجار',
      price: 150000,
      currency: 'SAR',
      transactionType: 'rent',
    );
    await tester.pumpWidget(
      _wrap(
        SizedBox(
          width: 320,
          height: 420,
          child: PropertyCard(
            property: rentProperty,
            onTap: () {},
          ),
        ),
      ),
    );
    await tester.pump(const Duration(milliseconds: 400));

    expect(find.text('للإيجار'), findsOneWidget);
    expect(find.textContaining('ر.س'), findsOneWidget);
  });

  test('Theme applies Cairo font family and emerald primary', () {
    final theme = buildWajhatakTheme();
    expect(theme.textTheme.bodyMedium?.fontFamily, 'Cairo');
    expect(theme.colorScheme.primary, WajhatakColors.emerald);
    expect(theme.useMaterial3, isTrue);
  });

  test('Dark theme keeps emerald palette with dark surfaces', () {
    final dark = buildWajhatakTheme(Brightness.dark);
    expect(dark.colorScheme.brightness, Brightness.dark);
    expect(dark.colorScheme.primary, WajhatakColors.emeraldSoft);
    expect(dark.scaffoldBackgroundColor, WajhatakColors.canvasDark);
  });

test('Responsive grid columns adapt to real screen widths', () {
    // شاشة ضيقة جدًا
    expect(Responsive.propertyGridColumns(300), 1);
    // هاتف صغير — عمودان كما هو مطلوب
    expect(Responsive.propertyGridColumns(390), 2);
    // هاتف عريض
    expect(Responsive.propertyGridColumns(430), 2);
    // تابلت
    expect(Responsive.propertyGridColumns(700), 2);
    // تابلت كبير
    expect(Responsive.propertyGridColumns(950), 3);
    // سطح مكتب
    expect(Responsive.propertyGridColumns(1300), 4);
  });

  test('windowSizeFromWidth maps Material breakpoints', () {
    expect(windowSizeFromWidth(400), WindowSize.compact);
    expect(windowSizeFromWidth(800), WindowSize.medium);
    expect(windowSizeFromWidth(1200), WindowSize.expanded);
    expect(WindowSize.compact.isCompact, isTrue);
    expect(WindowSize.expanded.isExpanded, isTrue);
  });
}
