import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../data/api_client.dart';
import '../feedback/empty_state.dart';

/// ===============================================================
/// Async state helpers
/// ===============================================================

/// طبقة مشتركة لإدارة الحد الأدنى لزمن التحميل.
abstract class _MinimumLoadingState<W extends StatefulWidget>
    extends State<W> {
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

class _LuxShimmerScope extends InheritedWidget {
  const _LuxShimmerScope({
    required this.animation,
    required super.child,
  });

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

class _LuxShimmer extends StatefulWidget {
  const _LuxShimmer({
    required this.child,
  });

  final Widget child;
  static const Duration duration = Duration(milliseconds: 1350);

  @override
  State<_LuxShimmer> createState() => _LuxShimmerState();
}

class _LuxShimmerState extends State<_LuxShimmer>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: _LuxShimmer.duration,
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
    return _LuxShimmerScope(
      animation: _controller,
      child: widget.child,
    );
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

class _LuxAsyncViewState<T>
    extends _MinimumLoadingState<LuxAsyncView<T>> {
  @override
  AsyncValue<dynamic> get _loadingValue => widget.value;

  @override
  Duration get _minimumLoadingDuration => widget.minLoading;

  Widget get _skeleton =>
      widget.loading ?? const LuxContentSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;

    if (_isMinimumLoadingActive) {
      return _skeleton;
    }

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
                  child: LinearProgressIndicator(
                    minHeight: 2,
                  ),
                ),
              ),
            ),
          ],
        );
      }

      return content;
    }

    if (value.isLoading || value.retrying) {
      return _skeleton;
    }

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

    return _LuxShimmer(
      child: _LuxSkeletonBody(
        width: width,
        height: height,
        radius: radius,
      ),
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
      return SizedBox(
        width: width,
        height: height,
      );
    }

    if (animation == null || disabled) {
      return ExcludeSemantics(
        child: RepaintBoundary(
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: base,
              borderRadius: shape,
            ),
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

            final progress = Curves.easeInOut.transform(
              animation!.value,
            );

            final shift = rtl
                ? 1.25 - (progress * 2.5)
                : -1.25 + (progress * 2.5);

            return DecoratedBox(
              decoration: BoxDecoration(
                borderRadius: shape,
                gradient: LinearGradient(
                  begin: Alignment(shift, 0),
                  end: Alignment(
                    shift + 0.95,
                    0,
                  ),
                  colors: [
                    base,
                    base,
                    highlight,
                    highlight,
                    base,
                    base,
                  ],
                  stops: const [
                    0.00,
                    0.27,
                    0.42,
                    0.53,
                    0.68,
                    1.00,
                  ],
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

/// ===============================================================
/// Content Skeleton
/// ===============================================================

class LuxContentSkeleton extends StatelessWidget {
  const LuxContentSkeleton({
    super.key,
    this.lines = 5,
  });

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
              const LuxSkeleton(
                width: 160,
                height: 22,
                radius: 9,
              ),
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

              const LuxSkeleton(
                width: 125,
                height: 42,
                radius: 13,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class PropertyGridSkeleton extends StatelessWidget {
  const PropertyGridSkeleton({
    super.key,
    this.count = 6,
    this.columns = 2,
  });

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

class SliverPropertyGridSkeleton extends StatelessWidget {
  const SliverPropertyGridSkeleton({
    super.key,
    this.count = 6,
  });

  final int count;

  @override
  Widget build(BuildContext context) {
    return _LuxShimmer(
      child: SliverPadding(
        padding: const EdgeInsets.fromLTRB(
          20,
          0,
          20,
          28,
        ),
        sliver: SliverLayoutBuilder(
          builder: (context, constraints) {
            final width = constraints.crossAxisExtent;

            final columns = width >= 1100
                ? 4
                : width >= 700
                    ? 3
                    : 2;

            return SliverGrid.builder(
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: columns,
                mainAxisSpacing: 14,
                crossAxisSpacing: 14,
                childAspectRatio: 0.70,
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

class PropertyCardSkeleton extends StatelessWidget {
  const PropertyCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 0,
      margin: EdgeInsets.zero,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: BorderSide(
          color: theme.colorScheme.outlineVariant,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 10,
            child: Stack(
              fit: StackFit.expand,
              children: [
                const LuxSkeleton(
                  width: double.infinity,
                  height: double.infinity,
                  radius: 0,
                ),
                Positioned(
                  top: 12,
                  right: 12,
                  child: const LuxSkeleton(
                    width: 38,
                    height: 38,
                    radius: 19,
                  ),
                ),
                Positioned(
                  top: 12,
                  left: 12,
                  child: const LuxSkeleton(
                    width: 76,
                    height: 26,
                    radius: 13,
                  ),
                ),
                Positioned(
                  left: 12,
                  right: 12,
                  bottom: 12,
                  child: Row(
                    children: [
                      const LuxSkeleton(
                        width: 54,
                        height: 20,
                        radius: 10,
                      ),
                      const Spacer(),
                      const LuxSkeleton(
                        width: 30,
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
            flex: 7,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(
                13,
                10,
                13,
                10,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const LuxSkeleton(
                    width: 104,
                    height: 19,
                    radius: 8,
                  ),
                  const SizedBox(height: 8),
                  const LuxSkeleton(
                    width: double.infinity,
                    height: 15,
                    radius: 7,
                  ),
                  const SizedBox(height: 6),
                  const LuxSkeleton(
                    width: 155,
                    height: 13,
                    radius: 7,
                  ),
                  const Spacer(),
                  Row(
                    children: [
                      Expanded(
                        child: const LuxSkeleton(
                          height: 22,
                          radius: 11,
                        ),
                      ),
                      const SizedBox(width: 7),
                      Expanded(
                        child: const LuxSkeleton(
                          height: 22,
                          radius: 11,
                        ),
                      ),
                      const SizedBox(width: 7),
                      Expanded(
                        child: const LuxSkeleton(
                          height: 22,
                          radius: 11,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

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
        border: Border.all(
          color: theme.colorScheme.outlineVariant,
        ),
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
                const LuxSkeleton(
                  width: 135,
                  height: 15,
                  radius: 7,
                ),
                const SizedBox(height: 8),
                const LuxSkeleton(
                  width: double.infinity,
                  height: 12,
                  radius: 7,
                ),
                const SizedBox(height: 9),
                Row(
                  children: [
                    const LuxSkeleton(
                      width: 48,
                      height: 18,
                      radius: 9,
                    ),
                    const SizedBox(width: 6),
                    const LuxSkeleton(
                      width: 56,
                      height: 18,
                      radius: 9,
                    ),
                  ],
                ),
              ],
            ),
          ),
          if (trailing) ...[
            const SizedBox(width: 12),
            const LuxSkeleton(
              width: 32,
              height: 32,
              radius: 16,
            ),
          ],
        ],
      ),
    );
  }
}

class ListSkeleton extends StatelessWidget {
  const ListSkeleton({
    super.key,
    this.count = 6,
    this.trailing = false,
  });

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
          return ListTileSkeleton(
            trailing: trailing,
          );
        },
      ),
    );
  }
}

class AccountScreenSkeleton extends StatelessWidget {
  const AccountScreenSkeleton({
    super.key,
    this.tiles = 4,
  });

  final int tiles;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return _LuxShimmer(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(
          20,
          12,
          20,
          28,
        ),
        physics: const NeverScrollableScrollPhysics(),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceContainerHighest,
              borderRadius: BorderRadius.circular(26),
              border: Border.all(
                color: theme.colorScheme.outlineVariant,
              ),
            ),
            child: Row(
              children: [
                const LuxSkeleton(
                  width: 68,
                  height: 68,
                  radius: 34,
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: const [
                      LuxSkeleton(
                        width: 155,
                        height: 18,
                        radius: 8,
                      ),
                      SizedBox(height: 9),
                      LuxSkeleton(
                        width: 180,
                        height: 13,
                        radius: 7,
                      ),
                      SizedBox(height: 11),
                      LuxSkeleton(
                        width: 100,
                        height: 24,
                        radius: 12,
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
          const LuxSkeleton(
            width: double.infinity,
            height: 52,
            radius: 15,
          ),
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
        border: Border.all(
          color: theme.colorScheme.outlineVariant,
        ),
      ),
      child: Row(
        children: [
          const LuxSkeleton(
            width: 46,
            height: 46,
            radius: 14,
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                LuxSkeleton(
                  width: 140,
                  height: 15,
                  radius: 7,
                ),
                SizedBox(height: 8),
                LuxSkeleton(
                  width: 100,
                  height: 12,
                  radius: 6,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          const LuxSkeleton(
            width: 30,
            height: 30,
            radius: 15,
          ),
        ],
      ),
    );
  }
}

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
          const Center(
            child: LuxSkeleton(
              width: 100,
              height: 100,
              radius: 50,
            ),
          ),
          const SizedBox(height: 16),
          const Center(
            child: LuxSkeleton(
              width: 125,
              height: 15,
              radius: 7,
            ),
          ),
          const SizedBox(height: 7),
          const Center(
            child: LuxSkeleton(
              width: 165,
              height: 12,
              radius: 6,
            ),
          ),
          const SizedBox(height: 30),
          for (var index = 0; index < 2; index++) ...[
            const LuxSkeleton(
              width: 90,
              height: 13,
              radius: 6,
            ),
            const SizedBox(height: 8),
            Container(
              decoration: BoxDecoration(
                color: theme.colorScheme.surface,
                borderRadius: BorderRadius.circular(15),
                border: Border.all(
                  color: theme.colorScheme.outlineVariant,
                ),
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
          const LuxSkeleton(
            width: double.infinity,
            height: 52,
            radius: 15,
          ),
        ],
      ),
    );
  }
}

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

class _AwaitContentState<T>
    extends _MinimumLoadingState<AwaitContent<T>> {
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
                  child: LinearProgressIndicator(
                    minHeight: 2,
                  ),
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

  Widget get _skeleton =>
      widget.loading ?? const SliverPropertyGridSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;

    if (_isMinimumLoadingActive) {
      return _skeleton;
    }

    if (value.hasValue) {
      final data = value.requireValue;

      final empty =
          widget.emptyOverride != null && _isEmptyCollection(data);

      if (empty) {
        final sliver = SliverFillRemaining(
          hasScrollBody: false,
          child: widget.emptyOverride!,
        );

        if (_isBackgroundUpdating(value)) {
          return SliverMainAxisGroup(
            slivers: [
              _refreshProgressSliver,
              sliver,
            ],
          );
        }

        return sliver;
      }

      final contentSlivers = widget.data(data);

      if (_isBackgroundUpdating(value)) {
        return SliverMainAxisGroup(
          slivers: [
            _refreshProgressSliver,
            ...contentSlivers,
          ],
        );
      }

      return SliverMainAxisGroup(
        slivers: contentSlivers,
      );
    }

    if (value.isLoading || value.retrying) {
      return _skeleton;
    }

    if (value.hasError) {
      final error = value.error!;

      return SliverFillRemaining(
        hasScrollBody: false,
        child: widget.error?.call(error) ??
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
      child: SizedBox(
        height: 2,
        child: LinearProgressIndicator(
          minHeight: 2,
        ),
      ),
    );
  }
}

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

  return networkPatterns.any(
    normalized.contains,
  );
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
