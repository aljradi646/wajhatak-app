import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/utils/responsive.dart';
import '../../../data/api_client.dart';
import '../feedback/empty_state.dart';

/// ===============================================================
/// Async state helpers
/// ===============================================================

/// طبقة مشتركة لإدارة الحد الأدنى لزمن التحميل.
///
/// المبدأ:
/// - لا نعرض Skeleton أثناء refresh إذا كانت البيانات القديمة موجودة.
/// - لا نعدل State من داخل build().
/// - يمنع الوميض عند الطلبات السريعة.
/// - يعيد تشغيل الحارس فقط عند بدء Loading جديد فعليًا.
abstract class _MinimumLoadingState<W extends StatefulWidget> extends State<W> {
  Timer? _loadingTimer;

  bool _wasBlockingLoading = false;
  bool _minimumLoadingActive = false;

  AsyncValue<dynamic> get _loadingValue;

  Duration get _minimumLoadingDuration;

  bool get _isMinimumLoadingActive => _minimumLoadingActive;

  @override
  void initState() {
    super.initState();

    final blocking = _isBlockingLoading(_loadingValue);
    _wasBlockingLoading = blocking;

    if (blocking) {
      _startMinimumLoading();
    }
  }

  @override
  void didUpdateWidget(covariant W oldWidget) {
    super.didUpdateWidget(oldWidget);

    final blocking = _isBlockingLoading(_loadingValue);

    if (blocking && !_wasBlockingLoading) {
      _startMinimumLoading();
    }

    _wasBlockingLoading = blocking;
  }

  void _startMinimumLoading() {
    _loadingTimer?.cancel();
    _loadingTimer = null;

    final duration = _minimumLoadingDuration;

    if (duration <= Duration.zero) {
      _minimumLoadingActive = false;
      return;
    }

    _minimumLoadingActive = true;

    _loadingTimer = Timer(duration, () {
      _loadingTimer = null;
      _minimumLoadingActive = false;

      if (mounted) {
        setState(() {});
      }
    });
  }

  @override
  void dispose() {
    _loadingTimer?.cancel();
    _loadingTimer = null;
    super.dispose();
  }
}

/// Loading حاجب فقط عندما لا توجد بيانات يمكن عرضها.
bool _isBlockingLoading(AsyncValue<dynamic> value) {
  if (value.hasValue) {
    return false;
  }

  return value.isLoading || value.retrying;
}

/// Refresh/reload في الخلفية مع الاحتفاظ بالبيانات السابقة.
bool _isBackgroundUpdating(AsyncValue<dynamic> value) {
  if (!value.hasValue) {
    return false;
  }

  return value.isLoading ||
      value.isRefreshing ||
      value.isReloading ||
      value.retrying;
}

/// ===============================================================
/// Shared Shimmer engine
/// ===============================================================

/// Scope داخلي يشارك نفس Animation بين جميع عناصر Skeleton.
class _LuxShimmerScope extends InheritedWidget {
  const _LuxShimmerScope({required this.animation, required super.child});

  final Animation<double> animation;

  static Animation<double>? maybeOf(BuildContext context) {
    return context
        .dependOnInheritedWidgetOfExactType<_LuxShimmerScope>()
        ?.animation;
  }

  @override
  bool updateShouldNotify(_LuxShimmerScope oldWidget) {
    return oldWidget.animation != animation;
  }
}

/// محرك Shimmer موحد.
///
/// يمكن استخدامه مع Widgets العادية وكذلك Slivers.
/// لأن هذا Widget لا ينتج RenderObject بنفسه، وإنما يمرر
/// الـInherited scope إلى child.
class _LuxShimmer extends StatefulWidget {
  const _LuxShimmer({required this.child});

  final Widget child;

  @override
  State<_LuxShimmer> createState() => _LuxShimmerState();
}

class _LuxShimmerState extends State<_LuxShimmer>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1350),
  );

  @override
  void initState() {
    super.initState();
    _controller.repeat();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();

    final disabled = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    final tickerEnabled = TickerMode.valuesOf(context).enabled;

    if (disabled || !tickerEnabled) {
      if (_controller.isAnimating) {
        _controller.stop(canceled: false);
      }
    } else if (!_controller.isAnimating) {
      _controller.repeat();
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return _LuxShimmerScope(animation: _controller, child: widget.child);
  }
}

/// ===============================================================
/// LuxAsyncView
/// ===============================================================

class LuxAsyncView<T> extends StatefulWidget {
  const LuxAsyncView({
    super.key,
    required this.value,
    required this.data,
    this.loading,
    this.errorRetry,
    this.error,
    this.minLoading = const Duration(milliseconds: 320),
  });

  final AsyncValue<T> value;
  final Widget Function(T data) data;
  final Widget? loading;

  final Widget Function(Object error)? error;

  final VoidCallback? errorRetry;

  final Duration minLoading;

  @override
  State<LuxAsyncView<T>> createState() => _LuxAsyncViewState<T>();
}

class _LuxAsyncViewState<T> extends _MinimumLoadingState<LuxAsyncView<T>> {
  @override
  AsyncValue<dynamic> get _loadingValue => widget.value;

  @override
  Duration get _minimumLoadingDuration => widget.minLoading;

  Widget get _skeleton => widget.loading ?? const LuxContentSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;

    /// التحميل الأول أو retry بدون بيانات.
    if (_isMinimumLoadingActive) {
      return _skeleton;
    }

    /// البيانات الحالية لها الأولوية.
    ///
    /// حتى لو كان Riverpod يعيد تحميلها في الخلفية،
    /// لا نستبدل الصفحة كاملة بالـSkeleton.
    if (value.hasValue) {
      final content = widget.data(value.requireValue);

      if (_isBackgroundUpdating(value)) {
        return Stack(
          fit: StackFit.expand,
          children: [
            content,
            const Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: IgnorePointer(
                child: SizedBox(
                  height: 2,
                  child: LinearProgressIndicator(minHeight: 2),
                ),
              ),
            ),
          ],
        );
      }

      return content;
    }

    /// تحميل بدون بيانات.
    if (value.isLoading || value.retrying) {
      return _skeleton;
    }

    /// خطأ.
    if (value.hasError) {
      final error = value.error!;

      return widget.error?.call(error) ??
          ErrorState(
            message: _readableError(error),
            onRetry: widget.errorRetry,
            offline: _isOffline(error),
          );
    }

    return _skeleton;
  }
}

/// ===============================================================
/// Base Skeleton
/// ===============================================================

class LuxSkeleton extends StatelessWidget {
  const LuxSkeleton({
    super.key,
    this.width,
    required this.height,
    this.radius = 14,
  });

  final double? width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final animation = _LuxShimmerScope.maybeOf(context);

    if (animation != null) {
      return _LuxSkeletonBody(
        width: width,
        height: height,
        radius: radius,
        animation: animation,
      );
    }

    /// دعم استخدام LuxSkeleton منفردًا.
    return _LuxShimmer(
      child: _LuxSkeletonBody(width: width, height: height, radius: radius),
    );
  }
}

class _LuxSkeletonBody extends StatelessWidget {
  const _LuxSkeletonBody({
    this.width,
    required this.height,
    required this.radius,
    this.animation,
  });

  final double? width;
  final double height;
  final double radius;
  final Animation<double>? animation;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    final base = scheme.surfaceContainerHighest;

    final highlight = Color.alphaBlend(
      theme.brightness == Brightness.dark
          ? scheme.onSurface.withValues(alpha: 0.07)
          : scheme.surface.withValues(alpha: 0.82),
      base,
    );

    final disabled = MediaQuery.maybeOf(context)?.disableAnimations ?? false;

    final shape = BorderRadius.circular(radius);

    Widget child() {
      return SizedBox(width: width, height: height);
    }

    if (animation == null || disabled) {
      return ExcludeSemantics(
        child: RepaintBoundary(
          child: DecoratedBox(
            decoration: BoxDecoration(color: base, borderRadius: shape),
            child: child(),
          ),
        ),
      );
    }

    return ExcludeSemantics(
      child: RepaintBoundary(
        child: AnimatedBuilder(
          animation: animation!,
          builder: (context, _) {
            final rtl = Directionality.of(context) == TextDirection.rtl;

            final progress = Curves.easeInOut.transform(animation!.value);

            final shift = rtl
                ? 1.25 - (progress * 2.5)
                : -1.25 + (progress * 2.5);

            return DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: shape,
                gradient: LinearGradient(
                  begin: Alignment(shift, 0),
                  end: Alignment(shift + 0.95, 0),
                  colors: [base, base, highlight, highlight, base, base],
                  stops: const [0.00, 0.27, 0.42, 0.53, 0.68, 1.00],
                ),
              ),
              child: child(),
            );
          },
        ),
      ),
    );
  }
}

/// Skeleton مطابق لتخطيط تفاصيل العقار الفعلي، بما فيه معرض الصور العلوي.
class PropertyDetailsSkeleton extends StatelessWidget {
  const PropertyDetailsSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final expandedHeight = width >= 1024
        ? 460.0
        : width >= 600
        ? 400.0
        : 330.0;
    final contentWidth = (width - 40).clamp(0.0, width).toDouble();

    return _LuxShimmer(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: expandedHeight,
            pinned: true,
            backgroundColor: Theme.of(context).colorScheme.surface,
            leading: const Padding(
              padding: EdgeInsets.only(right: 10, top: 4),
              child: LuxSkeleton(width: 40, height: 40, radius: 20),
            ),
            actions: const [
              Padding(
                padding: EdgeInsets.only(left: 10, top: 4),
                child: LuxSkeleton(width: 40, height: 40, radius: 20),
              ),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: LuxSkeleton(height: expandedHeight, radius: 0),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 24),
            sliver: SliverList.list(
              children: [
                LuxSkeleton(
                  width: contentWidth * .92,
                  height: 30,
                  radius: 9,
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    LuxSkeleton(
                      width: contentWidth >= 260 ? 150 : contentWidth * .58,
                      height: 38,
                      radius: 14,
                    ),
                    LuxSkeleton(
                      width: contentWidth >= 260 ? 100 : contentWidth * .34,
                      height: 30,
                      radius: 14,
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: const [
                    LuxSkeleton(width: 18, height: 18, radius: 9),
                    SizedBox(width: 6),
                    Expanded(child: LuxSkeleton(height: 18, radius: 8)),
                    SizedBox(width: 56),
                    LuxSkeleton(width: 72, height: 34, radius: 12),
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  children: const [
                    Expanded(child: LuxSkeleton(height: 54, radius: 16)),
                    SizedBox(width: 10),
                    Expanded(child: LuxSkeleton(height: 54, radius: 16)),
                  ],
                ),
                const SizedBox(height: 24),
                const LuxSkeleton(width: 120, height: 20, radius: 8),
                const SizedBox(height: 10),
                const LuxSkeleton(height: 88, radius: 16),
                const SizedBox(height: 24),
                const LuxSkeleton(width: 100, height: 20, radius: 8),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: const [
                    LuxSkeleton(width: 86, height: 32, radius: 14),
                    LuxSkeleton(width: 104, height: 32, radius: 14),
                    LuxSkeleton(width: 76, height: 32, radius: 14),
                  ],
                ),
                const SizedBox(height: 24),
                const LuxSkeleton(height: 112, radius: 18),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Skeleton لقائمة المحادثات مطابق لبطاقة الطرف الآخر والمعاينة الزمنية.
class ConversationListSkeleton extends StatelessWidget {
  const ConversationListSkeleton({super.key, this.showTitle = true});

  final bool showTitle;

  @override
  Widget build(BuildContext context) {
    final list = _LuxShimmer(
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
        itemCount: 6,
        separatorBuilder: (_, _) => const SizedBox(height: 6),
        itemBuilder: (_, _) => const _ConversationTileSkeleton(),
      ),
    );

    if (!showTitle) return list;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(20, 12, 20, 6),
          child: LuxSkeleton(width: 100, height: 30, radius: 9),
        ),
        Expanded(child: list),
      ],
    );
  }
}

class _ConversationTileSkeleton extends StatelessWidget {
  const _ConversationTileSkeleton();

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.all(13),
    decoration: BoxDecoration(
      borderRadius: BorderRadius.circular(20),
      border: Border.all(color: Theme.of(context).colorScheme.outlineVariant),
    ),
    child: Row(
      children: const [
        LuxSkeleton(width: 52, height: 52, radius: 17),
        SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(child: LuxSkeleton(height: 16, radius: 7)),
                  SizedBox(width: 20),
                  LuxSkeleton(width: 38, height: 12, radius: 6),
                ],
              ),
              SizedBox(height: 10),
              LuxSkeleton(width: 170, height: 14, radius: 7),
            ],
          ),
        ),
      ],
    ),
  );
}

/// Skeleton لمنطقة الرسائل، يحاكي فقاعات المستخدم وبطاقة العقار.
class ChatConversationSkeleton extends StatelessWidget {
  const ChatConversationSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: ListView(
      reverse: true,
      padding: const EdgeInsets.all(16),
      children: const [
        Align(
          alignment: Alignment.centerRight,
          child: LuxSkeleton(width: 190, height: 56, radius: 18),
        ),
        SizedBox(height: 10),
        Align(
          alignment: Alignment.centerLeft,
          child: LuxSkeleton(width: 230, height: 72, radius: 18),
        ),
        SizedBox(height: 10),
        Align(
          alignment: Alignment.centerRight,
          child: LuxSkeleton(width: 270, height: 150, radius: 18),
        ),
        SizedBox(height: 10),
        Align(
          alignment: Alignment.centerLeft,
          child: LuxSkeleton(width: 150, height: 54, radius: 18),
        ),
      ],
    ),
  );
}

/// Skeleton مطابق لشبكة صفحة الاستكشاف.
class ExploreSkeleton extends StatelessWidget {
  const ExploreSkeleton({super.key});

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final columns = Responsive.propertyGridColumns(constraints.maxWidth);
      final cardWidth =
          (constraints.maxWidth - (columns - 1) * 14) / columns;

      return _LuxShimmer(
        child: GridView.builder(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
          physics: const AlwaysScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            mainAxisSpacing: 14,
            crossAxisSpacing: 14,
            childAspectRatio: Responsive.propertyCardAspectRatio(cardWidth),
          ),
          itemCount: 6,
          itemBuilder: (_, _) => const PropertyCardSkeleton(),
        ),
      );
    },
  );
}

class NotificationListSkeleton extends StatelessWidget {
  const NotificationListSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: ListView.separated(
      padding: const EdgeInsets.all(20),
      itemCount: 6,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, _) => Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
        child: const Row(
          children: [
            LuxSkeleton(width: 44, height: 44, radius: 14),
            SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  LuxSkeleton(width: 180, height: 16, radius: 7),
                  SizedBox(height: 9),
                  LuxSkeleton(height: 13, radius: 7),
                  SizedBox(height: 7),
                  LuxSkeleton(width: 76, height: 11, radius: 6),
                ],
              ),
            ),
          ],
        ),
      ),
    ),
  );
}

class ViewingRequestsSkeleton extends StatelessWidget {
  const ViewingRequestsSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: ListView.separated(
      padding: const EdgeInsets.all(20),
      itemCount: 5,
      separatorBuilder: (_, _) => const SizedBox(height: 10),
      itemBuilder: (_, _) => const LuxSkeleton(height: 164, radius: 20),
    ),
  );
}

class AgentProfileSkeleton extends StatelessWidget {
  const AgentProfileSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Container(
            margin: const EdgeInsets.fromLTRB(8, 8, 8, 12),
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const LuxSkeleton(width: 80, height: 80, radius: 40),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          FractionallySizedBox(
                            widthFactor: .76,
                            alignment: AlignmentDirectional.centerStart,
                            child: const LuxSkeleton(height: 22, radius: 8),
                          ),
                          const SizedBox(height: 8),
                          FractionallySizedBox(
                            widthFactor: .48,
                            alignment: AlignmentDirectional.centerStart,
                            child: const LuxSkeleton(height: 15, radius: 7),
                          ),
                          const SizedBox(height: 8),
                          FractionallySizedBox(
                            widthFactor: .58,
                            alignment: AlignmentDirectional.centerStart,
                            child: const LuxSkeleton(height: 13, radius: 7),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Expanded(
                      child: Row(
                        children: const [
                          LuxSkeleton(width: 24, height: 20, radius: 7),
                          SizedBox(width: 7),
                          LuxSkeleton(width: 52, height: 15, radius: 7),
                        ],
                      ),
                    ),
                    Expanded(
                      child: Row(
                        children: const [
                          LuxSkeleton(width: 24, height: 20, radius: 7),
                          SizedBox(width: 7),
                          LuxSkeleton(width: 52, height: 15, radius: 7),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                const SizedBox(
                  width: double.infinity,
                  child: LuxSkeleton(height: 60, radius: 12),
                ),
                const SizedBox(height: 16),
                Row(
                  children: const [
                    Expanded(child: LuxSkeleton(height: 48, radius: 14)),
                    SizedBox(width: 10),
                    Expanded(child: LuxSkeleton(height: 48, radius: 14)),
                  ],
                ),
              ],
            ),
          ),
        ),
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
          sliver: SliverLayoutBuilder(
            builder: (context, constraints) {
              final columns = Responsive.propertyGridColumns(
                constraints.crossAxisExtent,
              );
              final cardWidth =
                  (constraints.crossAxisExtent - (columns - 1) * 10) /
                  columns;
              return SliverGrid.builder(
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: columns,
                  mainAxisSpacing: 10,
                  crossAxisSpacing: 10,
                  childAspectRatio: Responsive.propertyCardAspectRatio(
                    cardWidth,
                  ),
                ),
                itemCount: 4,
                itemBuilder: (_, _) => const PropertyCardSkeleton(),
              );
            },
          ),
        ),
      ],
    ),
  );
}

/// Skeleton لتقرير الوكيل مع KPI وجدول، بدون بيانات وهمية.
class AgentReportSkeleton extends StatelessWidget {
  const AgentReportSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: LayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.maxWidth >= 700
            ? 3
            : constraints.maxWidth >= 460
            ? 2
            : 1;
        final cardWidth =
            (constraints.maxWidth - (columns - 1) * 10) / columns;
        final ratio = cardWidth < 260 ? 2.0 : 2.7;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const LuxSkeleton(height: 92),
            const SizedBox(height: 12),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: 4,
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                childAspectRatio: ratio,
                crossAxisSpacing: 10,
                mainAxisSpacing: 10,
              ),
              itemBuilder: (_, _) => const LuxSkeleton(
                height: 72,
                radius: 16,
              ),
            ),
            const SizedBox(height: 12),
            const LuxSkeleton(height: 300),
          ],
        );
      },
    ),
  );
}

/// ===============================================================
/// Content Skeleton
/// ===============================================================

class LuxContentSkeleton extends StatelessWidget {
  const LuxContentSkeleton({super.key, this.lines = 5});

  final int lines;

  @override
  Widget build(BuildContext context) {
    return _LuxShimmer(
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const LuxSkeleton(width: 160, height: 22, radius: 9),
              const SizedBox(height: 16),

              for (var index = 0; index < lines; index++) ...[
                LuxSkeleton(
                  width: index % 3 == 1 ? 185 : double.infinity,
                  height: index == 0 ? 16 : 14,
                  radius: 8,
                ),
                const SizedBox(height: 11),
              ],

              const SizedBox(height: 7),

              LuxSkeleton(width: 125, height: 42, radius: 13),
            ],
          ),
        ),
      ),
    );
  }
}

/// ===============================================================
/// Property Grid Skeleton
/// ===============================================================

class PropertyGridSkeleton extends StatelessWidget {
  const PropertyGridSkeleton({super.key, this.count = 6, this.columns = 2});

  final int count;
  final int columns;

  @override
  Widget build(BuildContext context) {
    final safeColumns = columns.clamp(1, 6);

    return _LuxShimmer(
      child: GridView.builder(
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        padding: const EdgeInsets.all(20),
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: safeColumns,
          childAspectRatio: 0.70,
          mainAxisSpacing: 14,
          crossAxisSpacing: 14,
        ),
        itemCount: count,
        itemBuilder: (context, index) {
          return const PropertyCardSkeleton();
        },
      ),
    );
  }
}

/// ===============================================================
/// Sliver Property Grid Skeleton
/// ===============================================================

class SliverPropertyGridSkeleton extends StatelessWidget {
  const SliverPropertyGridSkeleton({super.key, this.count = 6});

  final int count;

  @override
  Widget build(BuildContext context) {
    return _LuxShimmer(
      child: SliverPadding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.crossAxisExtent;

            final columns = Responsive.propertyGridColumns(width);

            return SliverGrid.builder(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                mainAxisSpacing: 14,
                crossAxisSpacing: 14,
                childAspectRatio: Responsive.propertyCardAspectRatio(
                  (width - (columns - 1) * 14) / columns,
                ),
              ),
              itemCount: count,
              itemBuilder: (context, index) {
                return const PropertyCardSkeleton();
              },
            );
          },
        ),
      ),
    );
  }
}

/// Skeleton لقائمة المفضلة: عنوان وعدّاد ثم شبكة البطاقات الفعلية.
class SavedScreenSkeleton extends StatelessWidget {
  const SavedScreenSkeleton({super.key});

  @override
  Widget build(BuildContext context) => _LuxShimmer(
    child: CustomScrollView(
      slivers: [
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
          sliver: SliverToBoxAdapter(
            child: Row(
              children: const [
                Expanded(child: LuxSkeleton(width: 150, height: 28, radius: 9)),
                LuxSkeleton(width: 48, height: 30, radius: 15),
              ],
            ),
          ),
        ),
        const SliverPropertyGridSkeleton(count: 6),
      ],
    ),
  );
}

/// ===============================================================
/// Advanced Property Card Skeleton
/// ===============================================================

class PropertyCardSkeleton extends StatelessWidget {
  const PropertyCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final veryCompact =
          constraints.maxWidth < 120 || constraints.maxHeight < 170;
      final compact =
          veryCompact || constraints.maxWidth < 160 || constraints.maxHeight < 225;
      final priceWidth = compact
          ? (constraints.maxWidth - (veryCompact ? 12 : 16))
                .clamp(veryCompact ? 50.0 : 60.0, veryCompact ? 72.0 : 88.0)
                .toDouble()
          : 104.0;

      return Card(
        elevation: 0,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: BorderSide(
            color: Theme.of(context).colorScheme.outlineVariant,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              flex: compact ? 8 : 10,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  const LuxSkeleton(
                    width: double.infinity,
                    height: double.infinity,
                    radius: 0,
                  ),
                  Positioned(
                    top: compact ? 8 : 12,
                    right: compact ? 8 : 12,
                    child: LuxSkeleton(width: 38, height: 38, radius: 19),
                  ),
                  Positioned(
                    top: compact ? 8 : 12,
                    left: compact ? 8 : 12,
                    child: LuxSkeleton(
                      width: veryCompact ? 48 : (compact ? 60 : 76),
                      height: 26,
                      radius: 13,
                    ),
                  ),
                  Positioned(
                    left: compact ? 8 : 12,
                    right: compact ? 8 : 12,
                    bottom: compact ? 8 : 12,
                    child: Row(
                      children: [
                        LuxSkeleton(
                          width: veryCompact ? 28 : (compact ? 36 : 54),
                          height: 20,
                          radius: 10,
                        ),
                        const Spacer(),
                        LuxSkeleton(
                          width: veryCompact ? 20 : (compact ? 24 : 30),
                          height: 20,
                          radius: 10,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              flex: compact ? 5 : 8,
              child: Padding(
                padding: EdgeInsets.fromLTRB(
                  veryCompact ? 6 : (compact ? 8 : 13),
                  compact ? 6 : 10,
                  veryCompact ? 6 : (compact ? 8 : 13),
                  compact ? 6 : 10,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    LuxSkeleton(
                      width: priceWidth,
                      height: compact ? 15 : 19,
                      radius: 8,
                    ),
                    SizedBox(height: compact ? 4 : 8),
                    LuxSkeleton(
                      width: double.infinity,
                      height: compact ? 12 : 15,
                      radius: 7,
                    ),
                    if (!compact) ...[
                      const SizedBox(height: 6),
                      const LuxSkeleton(width: 155, height: 13, radius: 7),
                      const Spacer(),
                      Row(
                        children: [
                          Expanded(
                            child: LuxSkeleton(height: 22, radius: 11),
                          ),
                          const SizedBox(width: 7),
                          Expanded(
                            child: LuxSkeleton(height: 22, radius: 11),
                          ),
                          const SizedBox(width: 7),
                          Expanded(
                            child: LuxSkeleton(height: 22, radius: 11),
                          ),
                        ],
                      ),
                    ] else if (!veryCompact) ...[
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          Expanded(
                            child: LuxSkeleton(height: 17, radius: 9),
                          ),
                          const SizedBox(width: 6),
                          Expanded(
                            child: LuxSkeleton(height: 17, radius: 9),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      );
    },
  );
}

/// ===============================================================
/// List Tile Skeleton
/// ===============================================================

class ListTileSkeleton extends StatelessWidget {
  const ListTileSkeleton({
    super.key,
    this.avatarSize = 52,
    this.trailing = false,
    this.radius = 20,
  });

  final double avatarSize;
  final bool trailing;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Row(
        children: [
          LuxSkeleton(
            width: avatarSize,
            height: avatarSize,
            radius: avatarSize / 2,
          ),

          const SizedBox(width: 13),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FractionallySizedBox(
                  widthFactor: trailing ? .72 : .85,
                  alignment: AlignmentDirectional.centerStart,
                  child: const LuxSkeleton(height: 15, radius: 7),
                ),
                const SizedBox(height: 8),
                const LuxSkeleton(
                  width: double.infinity,
                  height: 12,
                  radius: 7,
                ),
                const SizedBox(height: 9),
                Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: [
                    LuxSkeleton(width: 48, height: 18, radius: 9),
                    LuxSkeleton(width: 56, height: 18, radius: 9),
                  ],
                ),
              ],
            ),
          ),

          if (trailing) ...[
            const SizedBox(width: 12),
            LuxSkeleton(width: 32, height: 32, radius: 16),
          ],
        ],
      ),
    );
  }
}

/// ===============================================================
/// List Skeleton
/// ===============================================================

class ListSkeleton extends StatelessWidget {
  const ListSkeleton({super.key, this.count = 6, this.trailing = false});

  final int count;
  final bool trailing;

  @override
  Widget build(BuildContext context) {
    return _LuxShimmer(
      child: ListView.separated(
        shrinkWrap: true,
        padding: const EdgeInsets.all(20),
        physics: const NeverScrollableScrollPhysics(),
        itemCount: count,
        separatorBuilder: (context, index) {
          return const SizedBox(height: 10);
        },
        itemBuilder: (context, index) {
          return ListTileSkeleton(trailing: trailing);
        },
      ),
    );
  }
}

/// ===============================================================
/// Account Skeleton
/// ===============================================================

class AccountScreenSkeleton extends StatelessWidget {
  const AccountScreenSkeleton({super.key, this.tiles = 4});

  final int tiles;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return _LuxShimmer(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
        physics: const NeverScrollableScrollPhysics(),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(26),
              border: Border.all(color: theme.colorScheme.outlineVariant),
            ),
            child: Row(
              children: [
                const LuxSkeleton(width: 68, height: 68, radius: 34),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      FractionallySizedBox(
                        widthFactor: .8,
                        alignment: AlignmentDirectional.centerStart,
                        child: const LuxSkeleton(height: 18, radius: 8),
                      ),
                      const SizedBox(height: 9),
                      FractionallySizedBox(
                        widthFactor: .95,
                        alignment: AlignmentDirectional.centerStart,
                        child: const LuxSkeleton(height: 13, radius: 7),
                      ),
                      const SizedBox(height: 11),
                      FractionallySizedBox(
                        widthFactor: .58,
                        alignment: AlignmentDirectional.centerStart,
                        child: const LuxSkeleton(height: 24, radius: 12),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          for (var index = 0; index < tiles; index++) ...[
            const SizedBox(height: 10),
            const _AccountTileSkeleton(),
          ],

          const SizedBox(height: 26),

          const LuxSkeleton(width: double.infinity, height: 52, radius: 15),
        ],
      ),
    );
  }
}

class _AccountTileSkeleton extends StatelessWidget {
  const _AccountTileSkeleton();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Row(
        children: [
          const LuxSkeleton(width: 46, height: 46, radius: 14),

          const SizedBox(width: 13),

          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                FractionallySizedBox(
                  widthFactor: .8,
                  alignment: AlignmentDirectional.centerStart,
                  child: const LuxSkeleton(height: 15, radius: 7),
                ),
                const SizedBox(height: 8),
                FractionallySizedBox(
                  widthFactor: .62,
                  alignment: AlignmentDirectional.centerStart,
                  child: const LuxSkeleton(height: 12, radius: 6),
                ),
              ],
            ),
          ),

          const SizedBox(width: 12),

          const LuxSkeleton(width: 30, height: 30, radius: 15),
        ],
      ),
    );
  }
}

/// ===============================================================
/// Profile Skeleton
/// ===============================================================

class ProfileScreenSkeleton extends StatelessWidget {
  const ProfileScreenSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return _LuxShimmer(
      child: ListView(
        padding: const EdgeInsets.all(20),
        physics: const NeverScrollableScrollPhysics(),
        children: [
          const Center(child: LuxSkeleton(width: 100, height: 100, radius: 50)),

          const SizedBox(height: 16),

          const Center(child: LuxSkeleton(width: 125, height: 15, radius: 7)),

          const SizedBox(height: 7),

          const Center(child: LuxSkeleton(width: 165, height: 12, radius: 6)),

          const SizedBox(height: 30),

          for (var index = 0; index < 2; index++) ...[
            const LuxSkeleton(width: 90, height: 13, radius: 6),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(
                color: theme.colorScheme.surface,
                borderRadius: BorderRadius.circular(15),
                border: Border.all(color: theme.colorScheme.outlineVariant),
              ),
              padding: const EdgeInsets.all(1),
              child: const LuxSkeleton(
                width: double.infinity,
                height: 54,
                radius: 14,
              ),
            ),
            const SizedBox(height: 20),
          ],

          const LuxSkeleton(width: double.infinity, height: 52, radius: 15),
        ],
      ),
    );
  }
}

/// ===============================================================
/// AwaitContent
/// ===============================================================

class AwaitContent<T> extends StatefulWidget {
  const AwaitContent({
    super.key,
    required this.value,
    required this.onLoading,
    required this.onData,
    this.onError,
    this.minLoading = const Duration(milliseconds: 320),
  });

  final AsyncValue<T> value;

  final Widget onLoading;

  final Widget Function(T data) onData;

  final Widget Function(Object error)? onError;

  final Duration minLoading;

  @override
  State<AwaitContent<T>> createState() => _AwaitContentState<T>();
}

class _AwaitContentState<T> extends _MinimumLoadingState<AwaitContent<T>> {
  @override
  AsyncValue<dynamic> get _loadingValue => widget.value;

  @override
  Duration get _minimumLoadingDuration => widget.minLoading;

  @override
  Widget build(BuildContext context) {
    final value = widget.value;

    if (_isMinimumLoadingActive) {
      return widget.onLoading;
    }

    if (value.hasValue) {
      final content = widget.onData(value.requireValue);

      if (_isBackgroundUpdating(value)) {
        return Stack(
          fit: StackFit.expand,
          children: [
            content,
            const Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: IgnorePointer(
                child: SizedBox(
                  height: 2,
                  child: LinearProgressIndicator(minHeight: 2),
                ),
              ),
            ),
          ],
        );
      }

      return content;
    }

    if (value.isLoading || value.retrying) {
      return widget.onLoading;
    }

    if (value.hasError) {
      final error = value.error!;

      return widget.onError?.call(error) ??
          ErrorState(
            message: _readableError(error),
            offline: _isOffline(error),
            onRetry: null,
          );
    }

    return widget.onLoading;
  }
}

/// ===============================================================
/// Sliver Async View
/// ===============================================================

class SliverAsyncView<T> extends StatefulWidget {
  const SliverAsyncView({
    super.key,
    required this.value,
    required this.data,
    this.loading,
    this.errorRetry,
    this.error,
    this.emptyOverride,
    this.minLoading = const Duration(milliseconds: 320),
  });

  final AsyncValue<T> value;

  final List<Widget> Function(T data) data;

  final Widget? loading;

  final VoidCallback? errorRetry;

  final Widget Function(Object error)? error;

  final Widget? emptyOverride;

  final Duration minLoading;

  @override
  State<SliverAsyncView<T>> createState() => _SliverAsyncViewState<T>();
}

class _SliverAsyncViewState<T>
    extends _MinimumLoadingState<SliverAsyncView<T>> {
  @override
  AsyncValue<dynamic> get _loadingValue => widget.value;

  @override
  Duration get _minimumLoadingDuration => widget.minLoading;

  Widget get _skeleton => widget.loading ?? const SliverPropertyGridSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;

    if (_isMinimumLoadingActive) {
      return _skeleton;
    }

    if (value.hasValue) {
      final data = value.requireValue;

      final empty = widget.emptyOverride != null && _isEmptyCollection(data);

      if (empty) {
        final sliver = SliverFillRemaining(
          hasScrollBody: false,
          child: widget.emptyOverride!,
        );

        if (_isBackgroundUpdating(value)) {
          return SliverMainAxisGroup(slivers: [_refreshProgressSliver, sliver]);
        }

        return sliver;
      }

      final contentSlivers = widget.data(data);

      if (_isBackgroundUpdating(value)) {
        return SliverMainAxisGroup(
          slivers: [_refreshProgressSliver, ...contentSlivers],
        );
      }

      return SliverMainAxisGroup(slivers: contentSlivers);
    }

    if (value.isLoading || value.retrying) {
      return _skeleton;
    }

    if (value.hasError) {
      final error = value.error!;

      return SliverFillRemaining(
        hasScrollBody: false,
        child:
            widget.error?.call(error) ??
            ErrorState(
              message: _readableError(error),
              onRetry: widget.errorRetry,
              offline: _isOffline(error),
            ),
      );
    }

    return _skeleton;
  }

  Widget get _refreshProgressSliver {
    return const SliverToBoxAdapter(
      child: SizedBox(height: 2, child: LinearProgressIndicator(minHeight: 2)),
    );
  }
}

/// ===============================================================
/// Error / Network helpers
/// ===============================================================

String _readableError(Object error) {
  if (error is ApiFailure) {
    final message = error.message.trim();

    if (message.isNotEmpty) {
      return message;
    }

    return 'تعذر تنفيذ الطلب. حاول مرة أخرى.';
  }

  if (error is TimeoutException) {
    return 'انتهت مهلة الاتصال. تحقق من الشبكة ثم حاول مرة أخرى.';
  }

  final raw = error.toString().trim();

  if (raw.isEmpty) {
    return 'حدث خطأ غير متوقع. حاول مرة أخرى.';
  }

  final normalized = raw.toLowerCase();

  const technicalPatterns = <String>[
    'socketexception',
    'connection refused',
    'connection reset',
    'connection closed',
    'failed host lookup',
    'network is unreachable',
    'networkexception',
    'dioexception',
    'httpexception',
    'clientexception',
    'handshakeexception',
    'xmlhttprequest',
  ];

  for (final pattern in technicalPatterns) {
    if (normalized.contains(pattern)) {
      return 'تعذر الاتصال بالخدمة. تحقق من الشبكة ثم أعد المحاولة.';
    }
  }

  if (raw.length > 150) {
    return 'تعذر تنفيذ الطلب. حاول مرة أخرى.';
  }

  if (raw.startsWith('Exception: ')) {
    return raw.substring('Exception: '.length).trim();
  }

  return raw;
}

bool _isOffline(Object error) {
  if (error is ApiFailure) {
    return error.statusCode == null;
  }

  if (error is TimeoutException) {
    return true;
  }

  final normalized = error.toString().toLowerCase();

  const networkPatterns = <String>[
    'socket',
    'connection',
    'network',
    'failed host lookup',
    'connection refused',
    'connection reset',
    'connection closed',
    'handshake',
    'xmlhttprequest',
  ];

  return networkPatterns.any(normalized.contains);
}

bool _isEmptyCollection(Object? value) {
  if (value is Iterable) {
    return value.isEmpty;
  }

  if (value is Map) {
    return value.isEmpty;
  }

  if (value is String) {
    return value.isEmpty;
  }

  return false;
}
