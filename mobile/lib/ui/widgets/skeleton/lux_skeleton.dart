import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../data/api_client.dart';
import '../feedback/empty_state.dart';

/// عرض غير متزامن محسّن لنظام التحميل.
///
/// يعالج `AsyncValue` بشكل موحّد مع الضمانات التالية:
/// - **تحميل** → هيكل Skeleton مطابق للتخطيط (وليس مؤشر تحميل عادي).
/// - **خطأ** → `ErrorState` مع زر إعادة المحاولة.
/// - **إعادة المحاولة** → يُظهر الـ Skeleton دائمًا لمدة لا تقل عن
///   [minLoading] حتى لو فشل الطلب على الفور (لا يُترك المستخدم شاردًا
///   "كأنه لم يضغط زر"). هذا يصلح سلوك «لا يظهر شيء عند إعادة المحاولة».
/// - **نجاح/فارغ** → يُمرَّر للمُستدعي عبر [data].
class LuxAsyncView<T> extends StatefulWidget {
  const LuxAsyncView({
    super.key,
    required this.value,
    required this.data,
    this.loading,
    this.errorRetry,
    this.error,
    this.minLoading = const Duration(milliseconds: 500),
  });

  final AsyncValue<T> value;
  final Widget Function(T data) data;
  final Widget? loading;

  /// مُمرِّر خطأ مخصّص — يوضع بدل `ErrorState` الافتراضي عند الحاجة.
  final Widget Function(Object error)? error;
  final VoidCallback? errorRetry;

  /// الحد الأدنى لعرض الـ Skeleton أثناء جلب/إعادة محاولة البيانات.
  final Duration minLoading;

  @override
  State<LuxAsyncView<T>> createState() => _LuxAsyncViewState<T>();
}

class _LuxAsyncViewState<T> extends State<LuxAsyncView<T>> {
  /// لحظة بدء التحميل — نرجع إليها لحجز الـ Skeleton فترة [minLoading]
  /// حتى لا تومض النتيجة فورًا (خصوصًا بعد إعادة المحاولة وفشل الشبكة فورًا).
  DateTime? _loadingSince;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Widget get _skeleton => widget.loading ?? const LuxContentSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;
    final now = DateTime.now();

    // أثناء التحميل: نُثبّت لحظة البدء ونعرض الـ Skeleton.
    if (value.isLoading) {
      _loadingSince ??= now;
      return _skeleton;
    }

    // نتيجة (بيانات/خطأ) وصلت — نحجز الـ Skeleton حتى انقضاء الحد الأدنى
    // ثم نكشف النتيجة حتى لو وصلت على الفور.
    final min = widget.minLoading;
    final holdUntil = _loadingSince?.add(min);
    if (holdUntil != null && now.isBefore(holdUntil)) {
      _timer ??= Timer(holdUntil.difference(now), () {
        if (mounted) setState(() {});
      });
      return _skeleton;
    }

    _timer?.cancel();
    _loadingSince = null;

    if (value.hasValue) return widget.data(value.requireValue);
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

class LuxSkeleton extends StatefulWidget {
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
  State<LuxSkeleton> createState() => _LuxSkeletonState();
}

class _LuxSkeletonState extends State<LuxSkeleton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 1100),
  );

  @override
  void initState() {
    super.initState();
    _controller.repeat(reverse: true);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final disabled = MediaQuery.of(context).disableAnimations;
    final colors = Theme.of(context).colorScheme;
    final base = colors.surfaceContainerHighest;
    final highlight = Color.alphaBlend(
      colors.surface.withValues(alpha: .68),
      base,
    );
    final shape = BorderRadius.circular(widget.radius);
    return RepaintBoundary(
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, _) => DecoratedBox(
          decoration: BoxDecoration(
            borderRadius: shape,
            gradient: disabled
                ? LinearGradient(colors: [base, base])
                : LinearGradient(
                    begin: Alignment(-1.15 + (_controller.value * 1.9), 0),
                    end: Alignment(-.15 + (_controller.value * 1.9), 0),
                    colors: [base, highlight, base],
                    stops: const [0, .48, 1],
                  ),
          ),
          child: SizedBox(width: widget.width, height: widget.height),
        ),
      ),
    );
  }
}

class LuxContentSkeleton extends StatelessWidget {
  const LuxContentSkeleton({super.key, this.lines = 5});
  final int lines;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const LuxSkeleton(width: 150, height: 22),
          const SizedBox(height: 14),
          for (var index = 0; index < lines; index++) ...[
            LuxSkeleton(
              width: index.isOdd ? 190 : double.infinity,
              height: 16,
              radius: 8,
            ),
            const SizedBox(height: 12),
          ],
        ],
      ),
    ),
  );
}

class PropertyGridSkeleton extends StatelessWidget {
  const PropertyGridSkeleton({super.key, this.count = 6, this.columns = 2});
  final int count;
  final int columns;

  @override
  Widget build(BuildContext context) => GridView.builder(
    shrinkWrap: true,
    physics: const NeverScrollableScrollPhysics(),
    padding: const EdgeInsets.all(20),
    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
      crossAxisCount: columns,
      childAspectRatio: .72,
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
    ),
    itemCount: count,
    itemBuilder: (context, index) => const PropertyCardSkeleton(),
  );
}

class SliverPropertyGridSkeleton extends StatelessWidget {
  const SliverPropertyGridSkeleton({super.key, this.count = 6});
  final int count;

  @override
  Widget build(BuildContext context) => SliverPadding(
    padding: const EdgeInsets.fromLTRB(20, 0, 20, 28),
    sliver: SliverLayoutBuilder(
      builder: (context, constraints) {
        final columns = constraints.crossAxisExtent > 700 ? 3 : 2;
        return SliverGrid.builder(
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: columns,
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: .72,
          ),
          itemCount: count,
          itemBuilder: (context, index) => const PropertyCardSkeleton(),
        );
      },
    ),
  );
}

class PropertyCardSkeleton extends StatelessWidget {
  const PropertyCardSkeleton({super.key});

  @override
  Widget build(BuildContext context) => Card(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: const [
        Expanded(
          child: LuxSkeleton(
            width: double.infinity,
            height: double.infinity,
            radius: 0,
          ),
        ),
        Padding(
          padding: EdgeInsets.all(13),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              LuxSkeleton(width: 95, height: 16, radius: 7),
              SizedBox(height: 9),
              LuxSkeleton(width: double.infinity, height: 13, radius: 7),
              SizedBox(height: 8),
              LuxSkeleton(width: 92, height: 12, radius: 7),
            ],
          ),
        ),
      ],
    ),
  );
}

/// Skeleton لصف قائمة نموذجي (أفاتار + سطرين + عنصر جانبي اختياري).
///
/// يُستخدم في المحادثات والإشعارات وطلبات المعاينة — يطابق أبعاد
/// `_ConversationTile` و `_NotificationCard` و `_ViewingRequestCard`.
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
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                LuxSkeleton(width: 130, height: 14, radius: 7),
                const SizedBox(height: 8),
                LuxSkeleton(width: double.infinity, height: 12, radius: 7),
              ],
            ),
          ),
          if (trailing) ...[
            const SizedBox(width: 12),
            LuxSkeleton(width: 22, height: 22, radius: 11),
          ],
        ],
      ),
    );
  }
}

/// Skeleton لشاشات القوائم (محادثات / إشعارات / طلبات معاينة).
class ListSkeleton extends StatelessWidget {
  const ListSkeleton({super.key, this.count = 6, this.trailing = false});
  final int count;
  final bool trailing;

  @override
  Widget build(BuildContext context) => ListView.separated(
    padding: const EdgeInsets.all(20),
    physics: const NeverScrollableScrollPhysics(),
    itemCount: count,
    separatorBuilder: (context, index) => const SizedBox(height: 10),
    itemBuilder: (context, index) => ListTileSkeleton(trailing: trailing),
  );
}

/// Skeleton لشاشة الحساب — بطاقة المستخدم المتدرّجة + صفوف القوائم.
class AccountScreenSkeleton extends StatelessWidget {
  const AccountScreenSkeleton({super.key, this.tiles = 4});

  final int tiles;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
      physics: const NeverScrollableScrollPhysics(),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: theme.colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(26),
          ),
          child: Row(
            children: [
              const LuxSkeleton(width: 64, height: 64, radius: 32),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    LuxSkeleton(width: 150, height: 18, radius: 8),
                    SizedBox(height: 9),
                    LuxSkeleton(width: 190, height: 13, radius: 7),
                    SizedBox(height: 10),
                    LuxSkeleton(width: 96, height: 22, radius: 11),
                  ],
                ),
              ),
            ],
          ),
        ),
        for (var index = 0; index < tiles; index++) ...[
          const SizedBox(height: 10),
          _AccountTileSkeleton(),
        ],
        const SizedBox(height: 28),
        LuxSkeleton(width: double.infinity, height: 50, radius: 14),
      ],
    );
  }
}

class _AccountTileSkeleton extends StatelessWidget {
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
              children: const [
                LuxSkeleton(width: 140, height: 15, radius: 7),
                SizedBox(height: 8),
                LuxSkeleton(width: 100, height: 12, radius: 6),
              ],
            ),
          ),
          const LuxSkeleton(width: 22, height: 22, radius: 11),
        ],
      ),
    );
  }
}

class ProfileScreenSkeleton extends StatelessWidget {
  const ProfileScreenSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(20),
      physics: const NeverScrollableScrollPhysics(),
      children: [
        // أفاتار في المنتصف
        const Center(child: LuxSkeleton(width: 96, height: 96, radius: 48)),
        const SizedBox(height: 16),
        const Center(child: LuxSkeleton(width: 110, height: 14, radius: 7)),
        const SizedBox(height: 28),
        for (var index = 0; index < 2; index++) ...[
          LuxSkeleton(width: 90, height: 13, radius: 6),
          const SizedBox(height: 8),
          LuxSkeleton(width: double.infinity, height: 54, radius: 14),
          const SizedBox(height: 20),
        ],
        LuxSkeleton(width: double.infinity, height: 52, radius: 14),
      ],
    );
  }
}

/// نسخة Sliver من [LuxAsyncView] لشاشات الشبكة (الرئيسية / المفضلة).
///
/// يملك نفس حارس الحد الأدنى لعرض الـ Skeleton ليتصرف بشكل صحيح عند
/// إعادة المحاولة حتى لو فشل الطلب بشكل فوري.
class SliverAsyncView<T> extends StatefulWidget {
  const SliverAsyncView({
    super.key,
    required this.value,
    required this.data,
    this.loading,
    this.errorRetry,
    this.error,
    this.emptyOverride,
    this.minLoading = const Duration(milliseconds: 500),
  });

  final AsyncValue<T> value;

  /// يبني sliver للمحتوى الجاهز.
  final List<Widget> Function(T data) data;

  /// sliver الـ Skeleton. الافتراضي شبكة عقارات.
  final Widget? loading;

  final VoidCallback? errorRetry;

  /// error مخصّص بدل `ErrorState` الافتراضي.
  final Widget Function(Object error)? error;

  /// عند الحاجة إلى فرض شاشة فارغة بدل بيانات فارغة (اختياري).
  final Widget? emptyOverride;

  final Duration minLoading;

  @override
  State<SliverAsyncView<T>> createState() => _SliverAsyncViewState<T>();
}

class _SliverAsyncViewState<T> extends State<SliverAsyncView<T>> {
  DateTime? _loadingSince;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Widget get _skeleton => widget.loading ?? const SliverPropertyGridSkeleton();

  @override
  Widget build(BuildContext context) {
    final value = widget.value;
    final now = DateTime.now();

    if (value.isLoading) {
      _loadingSince ??= now;
      return _skeleton;
    }

    final min = widget.minLoading;
    final holdUntil = _loadingSince?.add(min);
    if (holdUntil != null && now.isBefore(holdUntil)) {
      _timer ??= Timer(holdUntil.difference(now), () {
        if (mounted) setState(() {});
      });
      return _skeleton;
    }

    _timer?.cancel();
    _loadingSince = null;

    if (value.hasValue) {
      final data = value.requireValue;
      if (widget.emptyOverride != null &&
          data is List &&
          (data).isEmpty) {
        return SliverFillRemaining(child: widget.emptyOverride!);
      }
      return SliverMainAxisGroup(slivers: widget.data(data));
    }
    if (value.hasError) {
      final error = value.error!;
      return SliverFillRemaining(
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
}

String _readableError(Object error) {
  if (error is ApiFailure) return error.message;
  final raw = error.toString();
  if (raw.length > 150) {
    return 'تعذر الاتصال بالخدمة. تحقق من الشبكة ثم أعد المحاولة.';
  }
  return raw;
}

/// مساعد عام للتبديل بين تحميل/بيانات/خطأ مع حارس حد أدنى لظهور الـ Skeleton.
///
/// مفيد للشاشات التي لا تستخدم `LuxAsyncView` (مثل لوحة الوكيل) لضمان
/// ظهور الـ Skeleton بشكل صحيح عند إعادة المحاولة حتى لو فشلت الشبكة فورًا.
class AwaitContent<T> extends StatefulWidget {
  const AwaitContent({
    super.key,
    required this.value,
    required this.onLoading,
    required this.onData,
    this.onError,
    this.minLoading = const Duration(milliseconds: 500),
  });

  final AsyncValue<T> value;
  final Widget onLoading;
  final Widget Function(T data) onData;
  final Widget Function(Object error)? onError;
  final Duration minLoading;

  @override
  State<AwaitContent<T>> createState() => _AwaitContentState<T>();
}

class _AwaitContentState<T> extends State<AwaitContent<T>> {
  DateTime? _loadingSince;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final value = widget.value;
    final now = DateTime.now();

    if (value.isLoading) {
      _loadingSince ??= now;
      return widget.onLoading;
    }

    final min = widget.minLoading;
    final holdUntil = _loadingSince?.add(min);
    if (holdUntil != null && now.isBefore(holdUntil)) {
      _timer ??= Timer(holdUntil.difference(now), () {
        if (mounted) setState(() {});
      });
      return widget.onLoading;
    }

    _timer?.cancel();
    _loadingSince = null;

    if (value.hasValue) return widget.onData(value.requireValue);
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

/// هل الخطأ ناتج عن انقطاع اتصال بالخادم (وليس رفض للطلب من الخادم)؟
bool _isOffline(Object error) {
  if (error is ApiFailure) return error.statusCode == null;
  return error.toString().toLowerCase().contains('socket') ||
      error
          .toString()
          .toLowerCase()
          .contains('connection') ||
      error.toString().toLowerCase().contains('network');
}