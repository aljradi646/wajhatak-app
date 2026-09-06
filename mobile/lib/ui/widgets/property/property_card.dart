import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/format_money.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../skeleton/lux_skeleton.dart';

/// بطاقة العقار — الترتيب: اسم العقار أولًا ثم السعر + العملة ثم الموقع.
/// تتضمن دوّار صور تلقائيًا (interval منطقي) مع مؤشرات ومصدر مفضلة موحد.
class PropertyCard extends ConsumerStatefulWidget {
  const PropertyCard({
    super.key,
    required this.property,
    required this.onTap,
    this.onFavorite,
    this.trailing = const [],
    this.compact = false,
  });

  final LuxProperty property;
  final VoidCallback onTap;
  final VoidCallback? onFavorite;
  final List<Widget> trailing;
  final bool compact;

  @override
  ConsumerState<PropertyCard> createState() => _PropertyCardState();
}

class _PropertyCardState extends ConsumerState<PropertyCard> {
  late final PageController _pageController;
  late final _CarouselPreloader _preloader;
  Timer? _autoPlayTimer;
  int _currentPage = 0;
  bool _visible = true;
  bool _advancing = false;

  List<PropertyImage> get _images => widget.property.images;
  bool get _hasMultipleImages => _images.length > 1;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    _preloader = _CarouselPreloader(
      _images.map((image) => image.url).toList(growable: false),
      context,
    );
    WidgetsBinding.instance.addPostFrameCallback((_) => _prewarmNearby(0));
    if (_hasMultipleImages) {
      _startAutoPlay();
    }
  }

  @override
  void didUpdateWidget(covariant PropertyCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.property.id != widget.property.id) {
      _currentPage = 0;
      if (_pageController.hasClients) {
        _pageController.jumpToPage(0);
      }
      _stopAutoPlay();
      _preloader = _CarouselPreloader(
        _images.map((image) => image.url).toList(growable: false),
        context,
      );
      WidgetsBinding.instance.addPostFrameCallback((_) => _prewarmNearby(0));
      if (_hasMultipleImages) _startAutoPlay();
    }
  }

  @override
  void dispose() {
    _stopAutoPlay();
    _pageController.dispose();
    super.dispose();
  }

  /// جلب مسبق للصفحة الحالية والصفحتين التاليتين كي تكون جاهزة فورًا
  /// عند الوصول إليها — يمنع الهيكل/الأيقونة أثناء التبديل.
  void _prewarmNearby(int index) {
    if (!mounted || !_hasMultipleImages) return;
    _preloader.ensure(index);
    _preloader.ensure(index + 1);
    _preloader.ensure(index + 2);
  }

  void _startAutoPlay() {
    _stopAutoPlay();
    _autoPlayTimer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => _autoplayTick(),
    );
  }

  Future<void> _autoplayTick() async {
    if (!mounted ||
        !_pageController.hasClients ||
        !_visible ||
        !_hasMultipleImages ||
        _advancing) {
      return;
    }
    _advancing = true;
    try {
      final target = (_currentPage + 1) % _images.length;
      // لا ننتقل مطلقًا قبل أن تُحمَّل الصورة التالية بنجاح. عند فشل التحميل
      // نبقى على الصفحة الحالية ونعيد المحاولة في الدورة التالية.
      final loaded = await _preloader
          .ensure(target)
          .timeout(const Duration(seconds: 8), onTimeout: () => false);
      if (!mounted || !_pageController.hasClients || !_visible) return;
      if (!loaded) return;
      // إعادة الحساب بعد أي تمرير يدوي أثناء الانتظار.
      final next = (_currentPage + 1) % _images.length;
      await _pageController.animateToPage(
        next,
        duration: const Duration(milliseconds: 420),
        curve: Curves.easeOutCubic,
      );
    } finally {
      _advancing = false;
    }
  }

  void _stopAutoPlay() {
    _autoPlayTimer?.cancel();
    _autoPlayTimer = null;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    // مصدر حقيقة موحد للمفضلة: تجاوز فوري من الحالة المشتركة ثم الخادم.
    final overrides = ref.watch(favoriteOverridesProvider);
    final isFavorited =
        overrides[widget.property.id] ?? widget.property.isFavorited;

    return Semantics(
      button: true,
      label: widget.property.title,
      child: Card(
        child: InkWell(
          onTap: widget.onTap,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: _VisibilityTracker(
                  onChanged: (visible) {
                    if (_visible != visible) {
                      _visible = visible;
                      if (visible) _prewarmNearby(_currentPage);
                    }
                  },
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      _ImageCarousel(
                        images: _images,
                        pageController: _pageController,
                        onPageChanged: (index) {
                          if (mounted) setState(() => _currentPage = index);
                          _prewarmNearby(index);
                        },
                      ),
                      // تدرج سفلي لقراءة أفضل
                      Positioned(
                        bottom: 0,
                        left: 0,
                        right: 0,
                        height: 64,
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.black.withValues(alpha: 0),
                                Colors.black.withValues(alpha: .28),
                              ],
                            ),
                          ),
                        ),
                      ),
                      // شارة نوع المعاملة — أعلى اليمين
                      Positioned(
                        top: 10,
                        right: 10,
                        child: _TransactionPill(property: widget.property),
                      ),
                      // زر المفضلة — أعلى اليسار
                      if (widget.onFavorite != null)
                        Positioned(
                          top: 8,
                          left: 8,
                          child: _FavoriteButton(
                            isFavorited: isFavorited,
                            onTap: widget.onFavorite!,
                          ),
                        ),
                      if (_hasMultipleImages)
                        Positioned(
                          bottom: 9,
                          left: 0,
                          right: 0,
                          child: _ImageIndicator(
                            count: _images.length,
                            current: _currentPage,
                          ),
                        ),
                      // شارة "جديد"
                      if (widget.property.isNew)
                        Positioned(
                          bottom: 9,
                          right: 10,
                          child: _BadgePill(
                            icon: Icons.auto_awesome_rounded,
                            label: 'جديد',
                            color: WajhatakColors.amber,
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: EdgeInsets.fromLTRB(13, 11, 13, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // 1) اسم العقار أولًا
                    Text(
                      widget.property.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                        fontSize: widget.compact ? 13 : 14,
                      ),
                    ),
                    const SizedBox(height: 4),
                    // 2) السعر + العملة
                    Text(
                      formatMoney(
                        widget.property.price,
                        widget.property.currency,
                      ),
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        fontSize: widget.compact ? 14.5 : 16,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    // 3) الموقع
                    Row(
                      children: [
                        Icon(
                          Icons.location_on_rounded,
                          size: 14,
                          color: WajhatakColors.terracotta,
                        ),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            widget.property.location?.shortLabel.isNotEmpty ==
                                    true
                                ? widget.property.location!.shortLabel
                                : 'الموقع غير محدد',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        ...widget.trailing,
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// يحمّل صور الدوّار مسبقًا إلى كاش الصور ويراقب نجاح تحميل كل صفحة فعليًا.
/// لا يُعدّ أي فهرس "جاهزًا" إلا بعد اكتمال فك تشفير الصورة بنجاح؛ عند الفشل
/// يعيد false حتى لا ينتقل الدوّار التلقائي إلى صفحة فارغة أو أيقونة.
class _CarouselPreloader {
  _CarouselPreloader(this.urls, this.context);

  final List<String> urls;
  final BuildContext context;
  final Map<int, Future<bool>> _loading = {};
  final Set<int> _ready = {};

  bool isReady(int index) => _ready.contains(index);

  Future<bool> ensure(int index) {
    if (index < 0 || index >= urls.length || urls[index].isEmpty) {
      return Future.value(false);
    }
    if (_ready.contains(index)) return Future.value(true);
    final existing = _loading[index];
    if (existing != null) return existing;

    final completer = Completer<bool>();
    _loading[index] = completer.future;
    var failed = false;
    precacheImage(
      CachedNetworkImageProvider(urls[index]),
      context,
      size: const Size(640, 480),
      onError: (_, _) => failed = true,
    ).whenComplete(() {
      final ok = !failed;
      if (ok) _ready.add(index);
      completer.complete(ok);
    });
    return completer.future;
  }
}

/// يتتبع ظهور البطاقة ضمن viewport لإيقاف/استئناف الدوّار.
class _VisibilityTracker extends StatefulWidget {
  const _VisibilityTracker({required this.onChanged, required this.child});

  final ValueChanged<bool> onChanged;
  final Widget child;

  @override
  State<_VisibilityTracker> createState() => _VisibilityTrackerState();
}

class _VisibilityTrackerState extends State<_VisibilityTracker> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _check());
  }

  void _check() {
    if (!mounted) return;
    final box = context.findRenderObject() as RenderBox?;
    if (box == null || !box.attached || !box.hasSize) return;
    final reveal = box.localToGlobal(Offset.zero) & box.size;
    widget.onChanged(reveal.top < MediaQuery.sizeOf(context).height);
  }

  @override
  Widget build(BuildContext context) => widget.child;
}

class _ImageCarousel extends StatelessWidget {
  const _ImageCarousel({
    required this.images,
    required this.pageController,
    required this.onPageChanged,
  });

  final List<PropertyImage> images;
  final PageController pageController;
  final ValueChanged<int> onPageChanged;

  @override
  Widget build(BuildContext context) {
    if (images.isEmpty) {
      return const _FallbackImage();
    }
    if (images.length == 1) {
      return _CachedPropertyImage(url: images.first.url);
    }
    return PageView.builder(
      controller: pageController,
      itemCount: images.length,
      onPageChanged: onPageChanged,
      itemBuilder: (_, index) => _CachedPropertyImage(url: images[index].url),
    );
  }
}

class _CachedPropertyImage extends StatelessWidget {
  const _CachedPropertyImage({required this.url});
  final String url;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) return const _FallbackImage();
    return CachedNetworkImage(
      imageUrl: url,
      fit: BoxFit.cover,
      memCacheWidth: 640,
      placeholder: (_, _) => const LuxSkeleton(
        width: double.infinity,
        height: double.infinity,
        radius: 0,
      ),
      errorWidget: (_, _, _) => const _FallbackImage(),
    );
  }
}

class _FallbackImage extends StatelessWidget {
  const _FallbackImage();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            theme.colorScheme.primaryContainer.withValues(alpha: .5),
            theme.colorScheme.surfaceContainerHigh,
          ],
        ),
      ),
      child: Center(
        child: Icon(
          Icons.villa_rounded,
          size: 42,
          color: theme.colorScheme.primary.withValues(alpha: .7),
        ),
      ),
    );
  }
}

class _ImageIndicator extends StatelessWidget {
  const _ImageIndicator({required this.count, required this.current});
  final int count;
  final int current;

  @override
  Widget build(BuildContext context) {
    final activeColor = Theme.of(context).colorScheme.surface;
    final inactiveColor = Theme.of(
      context,
    ).colorScheme.surface.withValues(alpha: .5);
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(
        count,
        (i) => AnimatedContainer(
          duration: const Duration(milliseconds: 250),
          margin: const EdgeInsets.symmetric(horizontal: 2),
          width: i == current ? 16 : 6,
          height: 6,
          decoration: BoxDecoration(
            color: i == current ? activeColor : inactiveColor,
            borderRadius: BorderRadius.circular(3),
          ),
        ),
      ),
    );
  }
}

class _FavoriteButton extends StatelessWidget {
  const _FavoriteButton({required this.isFavorited, required this.onTap});

  final bool isFavorited;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: theme.colorScheme.surface.withValues(alpha: .93),
      borderRadius: BorderRadius.circular(13),
      elevation: 0,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(13),
        child: SizedBox(
          width: 36,
          height: 36,
          child: AnimatedScale(
            duration: const Duration(milliseconds: 160),
            scale: isFavorited ? 1.06 : 1,
            child: Icon(
              isFavorited
                  ? Icons.favorite_rounded
                  : Icons.favorite_border_rounded,
              size: 19,
              color: isFavorited
                  ? theme.colorScheme.error
                  : theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
      ),
    );
  }
}

class _TransactionPill extends StatelessWidget {
  const _TransactionPill({required this.property});
  final LuxProperty property;

  @override
  Widget build(BuildContext context) {
    final rent = property.isRent;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: rent
            ? WajhatakColors.sky.withValues(alpha: .92)
            : WajhatakColors.emerald.withValues(alpha: .92),
        borderRadius: BorderRadius.circular(99),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            rent ? Icons.key_rounded : Icons.sell_rounded,
            size: 12,
            color: Colors.white,
          ),
          const SizedBox(width: 4),
          Text(
            property.transactionLabel,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10.5,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _BadgePill extends StatelessWidget {
  const _BadgePill({
    required this.icon,
    required this.label,
    required this.color,
  });

  final IconData icon;
  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
    decoration: BoxDecoration(
      color: color.withValues(alpha: .95),
      borderRadius: BorderRadius.circular(99),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 11, color: Colors.white),
        const SizedBox(width: 3),
        Text(
          label,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 10,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    ),
  );
}
