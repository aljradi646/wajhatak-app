import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../core/theme/app_theme.dart';

/// شعار وجهتك — قوس معماري (و) مع نقطة موقع، بأسلوب عصري فاخر.
class WajhatakBrandMark extends StatelessWidget {
  const WajhatakBrandMark({
    super.key,
    this.size = 64,
    this.progress = 1,
    this.animate = false,
  });

  final double size;
  final double progress;
  final bool animate;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final value = Curves.easeOutCubic.transform(progress.clamp(0, 1));
    final dark = theme.brightness == Brightness.dark;
    return SizedBox.square(
      dimension: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            decoration: BoxDecoration(
              gradient: dark
                  ? WajhatakColors.heroGradientDark
                  : WajhatakColors.heroGradientLight,
              borderRadius: BorderRadius.circular(size * .3),
              boxShadow: [
                BoxShadow(
                  color: WajhatakColors.emerald.withValues(alpha: animate ? .38 * value : .3),
                  blurRadius: size * .3,
                  offset: Offset(0, size * .12),
                ),
              ],
            ),
          ),
          Transform.translate(
            offset: Offset(0, animate ? (1 - value) * size * .22 : 0),
            child: Opacity(
              opacity: animate ? value : 1,
              child: CustomPaint(
                size: Size.square(size * .58),
                painter: _WajhatakMarkPainter(
                  stroke: Colors.white,
                  accent: WajhatakColors.amber,
                  progress: value,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// لوحة الشعار (lockup) — الاسم الإنجليزي فوق العربي كما في الهوية.
class WajhatakLogoLockup extends StatelessWidget {
  const WajhatakLogoLockup({
    super.key,
    this.markSize = 46,
    this.compact = false,
    this.showTagline = true,
  });

  final double markSize;
  final bool compact;
  final bool showTagline;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        WajhatakBrandMark(size: markSize),
        const SizedBox(width: 11),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'WAJHATAK',
              style: theme.textTheme.labelSmall?.copyWith(
                fontWeight: FontWeight.w900,
                letterSpacing: 3.5,
                color: WajhatakColors.amber,
                height: 1,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              'وجهتك',
              style: theme.textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w900,
                height: 1,
                fontSize: compact ? 20 : 23,
              ),
            ),
            if (showTagline && !compact) ...[
              const SizedBox(height: 3),
              Text(
                'وجهتك إلى العقار المناسب.',
                style: theme.textTheme.labelSmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ],
        ),
      ],
    );
  }
}

class _WajhatakMarkPainter extends CustomPainter {
  const _WajhatakMarkPainter({
    required this.stroke,
    required this.accent,
    required this.progress,
  });

  final Color stroke;
  final Color accent;
  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final strokePaint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * .13
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..color = stroke;
    final accentPaint = Paint()
      ..style = PaintingStyle.fill
      ..color = accent;
    final left = size.width * .17;
    final baseline = size.height * .79;
    final archTop = size.height * .20;
    final archRight = size.width * .84;
    final path = Path()
      ..moveTo(left, archTop + (1 - progress) * size.height * .18)
      ..lineTo(left, baseline)
      ..lineTo(size.width * .58, baseline)
      ..moveTo(left, archTop)
      ..quadraticBezierTo(
        size.width * .50,
        size.height * .02,
        archRight,
        archTop,
      )
      ..lineTo(archRight, baseline);
    canvas.drawPath(path, strokePaint);
    canvas.drawCircle(
      Offset(size.width * .58, baseline),
      size.width * .075 * math.max(.15, progress),
      accentPaint,
    );
  }

  @override
  bool shouldRepaint(covariant _WajhatakMarkPainter oldDelegate) =>
      oldDelegate.stroke != stroke ||
      oldDelegate.accent != accent ||
      oldDelegate.progress != progress;
}
