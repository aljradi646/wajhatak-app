import 'package:flutter/material.dart';

/// نظام الألوان — وجهتك.
/// زمردة عميقة + كهرماني دافئ: هوية عقارية عربية عصرية فاخرة.
class WajhatakColors {
  const WajhatakColors._();

  // ---- الأساس ----
  static const emerald = Color(0xFF0E8A6D); // #0E8A6D
  static const emeraldDark = Color(0xFF075E4A);
  static const emeraldDeep = Color(0xFF04523F);
  static const emeraldSoft = Color(0xFF35C39E);
  static const amber = Color(0xFFEDA83C); // #EDA83C
  static const amberDeep = Color(0xFFB97D1B);
  static const terracotta = Color(0xFFD65A4A);
  static const rose = Color(0xFFE4416D);
  static const sky = Color(0xFF2B8FD6);
  static const violet = Color(0xFF7C5CF0);
  static const teal = Color(0xFF12A5A0);
  static const orange = Color(0xFFF0762B);
  static const indigo = Color(0xFF4F63E8);

  // ---- الأسطح (فاتح) ----
  static const canvasLight = Color(0xFFF5F8F7);
  static const surfaceLight = Color(0xFFFFFFFF);
  static const surfaceAltLight = Color(0xFFEDF3F1);
  static const surfaceHighLight = Color(0xFFE2ECE9);
  static const inkLight = Color(0xFF0B211C);
  static const inkMutedLight = Color(0xFF5D7470);

  // ---- الأسطح (داكن) ----
  static const canvasDark = Color(0xFF0A1512);
  static const surfaceDark = Color(0xFF10201B);
  static const surfaceAltDark = Color(0xFF16281F);
  static const surfaceHighDark = Color(0xFF1D3329);
  static const inkDark = Color(0xFFEAF6F1);
  static const inkMutedDark = Color(0xFF9DB8AF);

  // ---- تدرجات ----
  static const heroGradientLight = LinearGradient(
    begin: Alignment.topRight,
    end: Alignment.bottomLeft,
    colors: [Color(0xFF075E4A), Color(0xFF0E8A6D), Color(0xFF35C39E)],
  );

  static const heroGradientDark = LinearGradient(
    begin: Alignment.topRight,
    end: Alignment.bottomLeft,
    colors: [Color(0xFF03251D), Color(0xFF075E4A), Color(0xFF0E8A6D)],
  );

  static const amberGradient = LinearGradient(
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
    colors: [Color(0xFFF6C363), Color(0xFFEDA83C)],
  );
}

/// نظام المسافات والانحناءات — قيم ناعمة متناسقة.
class WajhatakRadius {
  const WajhatakRadius._();

  static const input = 16.0;
  static const button = 18.0;
  static const chip = 14.0;
  static const card = 22.0;
  static const sheet = 28.0;
  static const panel = 26.0;
  static const pill = 99.0;
}

class WajhatakSpacing {
  const WajhatakSpacing._();

  static const page = 20.0;
  static const pageWide = 28.0;
  static const section = 24.0;
  static const compact = 12.0;
  static const tight = 8.0;
}

/// عائلة الخط الموحدة: Cairo.
class WajhatakTypography {
  const WajhatakTypography._();

  static const String family = 'Cairo';
}

ThemeData buildWajhatakTheme([Brightness brightness = Brightness.light]) {
  final dark = brightness == Brightness.dark;
  final background = dark
      ? WajhatakColors.canvasDark
      : WajhatakColors.canvasLight;
  final surface = dark
      ? WajhatakColors.surfaceDark
      : WajhatakColors.surfaceLight;
  final text = dark ? WajhatakColors.inkDark : WajhatakColors.inkLight;
  final muted = dark
      ? WajhatakColors.inkMutedDark
      : WajhatakColors.inkMutedLight;
  final outline = dark
      ? const Color(0xFF274238)
      : const Color(0xFFDDE8E4);

  final primary = dark ? WajhatakColors.emeraldSoft : WajhatakColors.emerald;
  final onPrimary = dark ? WajhatakColors.emeraldDeep : Colors.white;

  final scheme = ColorScheme.fromSeed(
    seedColor: WajhatakColors.emerald,
    brightness: brightness,
    error: WajhatakColors.terracotta,
  ).copyWith(
    primary: primary,
    onPrimary: onPrimary,
    primaryContainer: dark
        ? const Color(0xFF0C3D30)
        : const Color(0xFFD5F3E9),
    onPrimaryContainer: dark
        ? WajhatakColors.emeraldSoft
        : WajhatakColors.emeraldDeep,
    secondary: dark ? WajhatakColors.amber : WajhatakColors.amberDeep,
    onSecondary: dark ? const Color(0xFF2B1B02) : Colors.white,
    secondaryContainer: dark
        ? const Color(0xFF3D2A08)
        : const Color(0xFFFCEED2),
    onSecondaryContainer: dark ? WajhatakColors.amber : Color(0xFF6B4A0E),
    tertiary: WajhatakColors.sky,
    surface: surface,
    onSurface: text,
    surfaceContainerLowest: background,
    surfaceContainerLow: dark
        ? const Color(0xFF0D1B16)
        : const Color(0xFFFAFCFB),
    surfaceContainer: dark ? surface : const Color(0xFFF1F6F4),
    surfaceContainerHigh: dark
        ? WajhatakColors.surfaceHighDark
        : const Color(0xFFEAF1EF),
    surfaceContainerHighest: dark
        ? const Color(0xFF24453A)
        : const Color(0xFFE0EAE7),
    onSurfaceVariant: muted,
    outline: outline,
    outlineVariant: dark ? const Color(0xFF1E352C) : const Color(0xFFE8F0EE),
  );

  final inputBorder = OutlineInputBorder(
    borderRadius: BorderRadius.circular(WajhatakRadius.input),
    borderSide: BorderSide(color: outline),
  );

  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    colorScheme: scheme,
    scaffoldBackgroundColor: background,
    dividerColor: outline.withValues(alpha: .7),
    fontFamily: WajhatakTypography.family,
    splashFactory: InkSparkle.splashFactory,
    visualDensity: VisualDensity.standard,
    appBarTheme: AppBarTheme(
      backgroundColor: background,
      foregroundColor: text,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: true,
      titleTextStyle: TextStyle(
        color: text,
        fontWeight: FontWeight.w800,
        fontSize: 20,
        fontFamily: WajhatakTypography.family,
      ),
      iconTheme: IconThemeData(color: text),
    ),
    cardTheme: CardThemeData(
      color: surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.card),
        side: BorderSide(color: outline.withValues(alpha: .55)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: dark
          ? WajhatakColors.surfaceAltDark
          : WajhatakColors.surfaceLight,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      hintStyle: TextStyle(
        color: muted,
        fontFamily: WajhatakTypography.family,
      ),
      labelStyle: TextStyle(
        color: muted,
        fontWeight: FontWeight.w600,
        fontFamily: WajhatakTypography.family,
      ),
      border: inputBorder,
      enabledBorder: inputBorder,
      focusedBorder: inputBorder.copyWith(
        borderSide: BorderSide(color: primary, width: 1.8),
      ),
      errorBorder: inputBorder.copyWith(
        borderSide: const BorderSide(color: WajhatakColors.terracotta),
      ),
      focusedErrorBorder: inputBorder.copyWith(
        borderSide: const BorderSide(color: WajhatakColors.terracotta, width: 1.8),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: onPrimary,
        disabledBackgroundColor: primary.withValues(alpha: .4),
        minimumSize: const Size.fromHeight(54),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(WajhatakRadius.button),
        ),
        textStyle: const TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: 15,
          fontFamily: WajhatakTypography.family,
        ),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: primary,
        minimumSize: const Size.fromHeight(52),
        side: BorderSide(color: primary.withValues(alpha: .55), width: 1.4),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(WajhatakRadius.button),
        ),
        textStyle: const TextStyle(
          fontWeight: FontWeight.w800,
          fontFamily: WajhatakTypography.family,
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: primary,
        textStyle: const TextStyle(
          fontWeight: FontWeight.w800,
          fontFamily: WajhatakTypography.family,
        ),
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: surface,
        foregroundColor: text,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(WajhatakRadius.button),
        ),
      ),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: dark
          ? WajhatakColors.surfaceAltDark
          : const Color(0xFFEEF5F2),
      selectedColor: primary.withValues(alpha: dark ? .3 : .15),
      checkmarkColor: primary,
      side: BorderSide(color: outline.withValues(alpha: .6)),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.chip),
      ),
      labelStyle: TextStyle(
        color: text,
        fontWeight: FontWeight.w700,
        fontFamily: WajhatakTypography.family,
      ),
    ),
    snackBarTheme: SnackBarThemeData(
      backgroundColor: dark ? const Color(0xFFEAF6F1) : WajhatakColors.emeraldDeep,
      contentTextStyle: TextStyle(
        color: dark ? WajhatakColors.inkLight : Colors.white,
        fontWeight: FontWeight.w700,
        fontFamily: WajhatakTypography.family,
      ),
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.chip),
      ),
    ),
    bottomSheetTheme: BottomSheetThemeData(
      backgroundColor: surface,
      modalBackgroundColor: surface,
      showDragHandle: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(WajhatakRadius.sheet),
        ),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: dark ? WajhatakColors.surfaceDark : surface,
      indicatorColor: primary.withValues(alpha: dark ? .28 : .14),
      height: 74,
      elevation: 0,
      labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
      labelTextStyle: WidgetStatePropertyAll(
        TextStyle(
          fontSize: 11.5,
          fontWeight: FontWeight.w800,
          fontFamily: WajhatakTypography.family,
        ),
      ),
    ),
    progressIndicatorTheme: ProgressIndicatorThemeData(
      color: primary,
      linearTrackColor: primary.withValues(alpha: .16),
    ),
    dividerTheme: DividerThemeData(
      color: outline.withValues(alpha: .7),
      thickness: 1,
      space: 1,
    ),
    listTileTheme: ListTileThemeData(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.chip),
      ),
      iconColor: primary,
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.panel),
      ),
      titleTextStyle: TextStyle(
        color: text,
        fontSize: 18,
        fontWeight: FontWeight.w800,
        fontFamily: WajhatakTypography.family,
      ),
    ),
    floatingActionButtonTheme: FloatingActionButtonThemeData(
      backgroundColor: primary,
      foregroundColor: onPrimary,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(WajhatakRadius.button),
      ),
    ),
    textTheme: ThemeData(brightness: brightness).textTheme.apply(
      bodyColor: text,
      displayColor: text,
      fontFamily: WajhatakTypography.family,
    ),
  );
}
