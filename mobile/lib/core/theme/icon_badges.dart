import 'package:flutter/material.dart';

import 'app_theme.dart';

/// نظام الأيقونات الملونة — دوائر متدرجة ناعمة بأيقونات عصرية.
/// كل وظيفة لها لون مميز يسهل التمييز البصري.
enum AccentTone {
  emerald,
  sky,
  amber,
  rose,
  violet,
  teal,
  orange,
  indigo,
}

extension AccentToneData on AccentTone {
  Color color(ColorScheme scheme) => switch (this) {
    AccentTone.emerald => scheme.primary,
    AccentTone.sky => WajhatakColors.sky,
    AccentTone.amber => WajhatakColors.amber,
    AccentTone.rose => WajhatakColors.rose,
    AccentTone.violet => WajhatakColors.violet,
    AccentTone.teal => WajhatakColors.teal,
    AccentTone.orange => WajhatakColors.orange,
    AccentTone.indigo => WajhatakColors.indigo,
  };
}

/// أيقونة داخل حاوية دائرية ملونة ناعمة (soft-tinted).
class TintedIcon extends StatelessWidget {
  const TintedIcon({
    super.key,
    required this.icon,
    required this.tone,
    this.size = 44,
    this.iconSize,
  }) : assert(size > 0);

  final IconData icon;
  final AccentTone tone;
  final double size;
  final double? iconSize;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final base = tone.color(scheme);
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: base.withValues(alpha: .13),
        borderRadius: BorderRadius.circular(size * .32),
        border: Border.all(color: base.withValues(alpha: .22)),
      ),
      child: Icon(icon, color: base, size: iconSize ?? size * .5),
    );
  }
}

/// أيقونة مميزة (filled) بخلفية متدرجة — للعناصر البارزة.
class GradientIconBadge extends StatelessWidget {
  const GradientIconBadge({
    super.key,
    required this.icon,
    this.size = 46,
    this.iconSize,
    this.color,
  });

  final IconData icon;
  final double size;
  final double? iconSize;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final base = color ?? scheme.primary;
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(size * .32),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [base, Color.lerp(base, Colors.black, .22)!],
        ),
        boxShadow: [
          BoxShadow(
            color: base.withValues(alpha: .32),
            blurRadius: size * .28,
            offset: Offset(0, size * .1),
          ),
        ],
      ),
      child: Icon(
        icon,
        color: Colors.white,
        size: iconSize ?? size * .5,
      ),
    );
  }
}

/// شريحة معلومة (fact chip) — أيقونة ملونة + نص.
class FactChip extends StatelessWidget {
  const FactChip({
    super.key,
    required this.icon,
    required this.label,
    required this.tone,
    this.compact = false,
  });

  final IconData icon;
  final String label;
  final AccentTone tone;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final base = tone.color(scheme);
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: compact ? 10 : 13,
        vertical: compact ? 7 : 9,
      ),
      decoration: BoxDecoration(
        color: base.withValues(alpha: .1),
        borderRadius: BorderRadius.circular(WajhatakRadius.chip),
        border: Border.all(color: base.withValues(alpha: .18)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: compact ? 15 : 17, color: base),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: compact ? 12 : 13,
              color: scheme.onSurface,
            ),
          ),
        ],
      ),
    );
  }
}

/// زر أيقونة عائم بخلفية زجاجية (glass) — فوق الصور والخلفيات الداكنة.
class GlassIconButton extends StatelessWidget {
  const GlassIconButton({
    super.key,
    required this.icon,
    required this.onPressed,
    this.tooltip,
    this.color,
    this.iconColor,
    this.size = 42,
  });

  final IconData icon;
  final VoidCallback? onPressed;
  final String? tooltip;
  final Color? color;
  final Color? iconColor;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip ?? '',
      child: Material(
        color: color ?? Colors.black.withValues(alpha: .32),
        borderRadius: BorderRadius.circular(size * .34),
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(size * .34),
          child: SizedBox(
            width: size,
            height: size,
            child: Icon(
              icon,
              color: iconColor ?? Colors.white,
              size: size * .46,
            ),
          ),
        ),
      ),
    );
  }
}

/// خلفية زخرفية ناعمة (blobs) للشاشات الافتتاحية.
class SoftBackdrop extends StatelessWidget {
  const SoftBackdrop({super.key, this.child, this.dense = false});

  final Widget? child;
  final bool dense;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final dark = scheme.brightness == Brightness.dark;
    return Stack(
      fit: StackFit.expand,
      children: [
        // بقع ضوئية ناعمة
        Positioned(
          top: -90,
          right: -60,
          child: _Blob(
            color: scheme.primary.withValues(alpha: dark ? .16 : .12),
            size: dense ? 260 : 320,
          ),
        ),
        Positioned(
          bottom: -110,
          left: -70,
          child: _Blob(
            color: WajhatakColors.amber.withValues(alpha: dark ? .1 : .09),
            size: dense ? 220 : 300,
          ),
        ),
        child ?? const SizedBox.shrink(),
      ],
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({required this.color, required this.size});

  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) => Container(
    width: size,
    height: size,
    decoration: BoxDecoration(
      shape: BoxShape.circle,
      gradient: RadialGradient(
        colors: [color, color.withValues(alpha: 0)],
      ),
    ),
  );
}
