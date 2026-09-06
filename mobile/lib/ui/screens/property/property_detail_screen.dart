import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/theme/icon_badges.dart';
import '../../../core/utils/format_money.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../shared/toggle_favorite.dart';
import '../agent/agent_profile_screen.dart';
import 'property_map_screen.dart';
import 'show_viewing_sheet.dart';

/// تفاصيل العقار: معرض صور بسحب + دوّار، اسم أولًا ثم السعر، مزايا، وكيل، إجراءات.
class PropertyDetailScreen extends ConsumerWidget {
  const PropertyDetailScreen({super.key, required this.propertyId});
  final int propertyId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final property = ref.watch(propertyDetailProvider(propertyId));
    final overrides = ref.watch(favoriteOverridesProvider);
    return Scaffold(
      body: LuxAsyncView<LuxProperty>(
        value: property,
        errorRetry: () => ref.invalidate(propertyDetailProvider(propertyId)),
        data: (item) {
          final isFav = overrides[item.id] ?? item.isFavorited;
          final width = MediaQuery.sizeOf(context).width;
          final expandedHeight = width >= 1024
              ? 460.0
              : width >= 600
              ? 400.0
              : 330.0;
          return CustomScrollView(
            slivers: [
              SliverAppBar(
                expandedHeight: expandedHeight,
                pinned: true,
                backgroundColor: WajhatakColors.emeraldDeep,
                foregroundColor: Colors.white,
                leading: Padding(
                  padding: const EdgeInsets.only(right: 10, top: 4),
                  child: GlassIconButton(
                    icon: Icons.arrow_forward_ios_rounded,
                    onPressed: () => Navigator.of(context).maybePop(),
                    tooltip: 'رجوع',
                    size: 40,
                  ),
                ),
                actions: [
                  Padding(
                    padding: const EdgeInsets.only(left: 10, top: 4),
                    child: GlassIconButton(
                      icon: isFav
                          ? Icons.favorite_rounded
                          : Icons.favorite_border_rounded,
                      onPressed: () => toggleFavorite(context, ref, item),
                      tooltip: isFav ? 'إزالة من المفضلة' : 'إضافة للمفضلة',
                      iconColor: isFav ? WajhatakColors.rose : Colors.white,
                      size: 40,
                    ),
                  ),
                ],
                flexibleSpace: FlexibleSpaceBar(
                  background: _DetailGallery(images: item.images),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 22, 20, 150),
                sliver: SliverList.list(
                  children: [
                    // 1) اسم العقار أولًا
                    Text(
                      item.title,
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w900, height: 1.3),
                    ),
                    const SizedBox(height: 8),
                    // 2) السعر + العملة
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 13,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(
                              context,
                            ).colorScheme.primaryContainer,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Text(
                            formatMoney(item.price, item.currency),
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(
                                  fontWeight: FontWeight.w900,
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.onPrimaryContainer,
                                ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        if (item.typeName != null)
                          FactChip(
                            icon: Icons.category_rounded,
                            label: item.typeName!,
                            tone: AccentTone.violet,
                            compact: true,
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    // 3) الموقع
                    Row(
                      children: [
                        const Icon(
                          Icons.location_on_rounded,
                          size: 17,
                          color: WajhatakColors.terracotta,
                        ),
                        const SizedBox(width: 5),
                        Expanded(
                          child: Text(
                            item.location?.shortLabel.isNotEmpty == true
                                ? item.location!.shortLabel
                                : 'الموقع غير محدد',
                            style: TextStyle(
                              color: Theme.of(
                                context,
                              ).colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                        if (item.location?.hasCoordinates == true)
                          TextButton.icon(
                            onPressed: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) =>
                                    PropertyMapScreen(properties: [item]),
                              ),
                            ),
                            icon: const Icon(Icons.map_rounded, size: 16),
                            label: const Text('الخريطة'),
                          ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    _FactsRow(property: item),
                    const SizedBox(height: 24),
                    _SectionTitle(
                      icon: Icons.description_rounded,
                      tone: AccentTone.emerald,
                      title: 'عن العقار',
                    ),
                    const SizedBox(height: 10),
                    Text(
                      item.description?.isNotEmpty == true
                          ? item.description!
                          : 'لا يوجد وصف تفصيلي لهذا العقار.',
                      style: TextStyle(
                        height: 1.8,
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                        fontSize: 14.5,
                      ),
                    ),
                    if (item.featureNames.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      _SectionTitle(
                        icon: Icons.star_rounded,
                        tone: AccentTone.amber,
                        title: 'المزايا',
                      ),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          for (final feature in item.featureNames)
                            _FeatureChip(label: feature),
                        ],
                      ),
                    ],
                    const SizedBox(height: 24),
                    _AgentPanel(agent: item.agent),
                  ],
                ),
              ),
            ],
          );
        },
      ),
      bottomNavigationBar: property.maybeWhen(
        data: (item) => SafeArea(
          child: Container(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 14),
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              border: Border(
                top: BorderSide(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => startConversation(
                      context,
                      ref,
                      item.id,
                      agentId: item.agent?.id,
                    ),
                    icon: const Icon(Icons.chat_bubble_rounded, size: 19),
                    label: const Text('مراسلة الوكيل'),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: FilledButton.icon(
                    onPressed: () => showViewingSheet(context, ref, item.id),
                    icon: const Icon(Icons.event_available_rounded, size: 19),
                    label: const Text('طلب معاينة'),
                  ),
                ),
              ],
            ),
          ),
        ),
        orElse: () => const SizedBox.shrink(),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({
    required this.icon,
    required this.tone,
    required this.title,
  });

  final IconData icon;
  final AccentTone tone;
  final String title;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      children: [
        TintedIcon(icon: icon, tone: tone, size: 34, iconSize: 18),
        const SizedBox(width: 10),
        Text(
          title,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    );
  }
}

class _DetailGallery extends StatefulWidget {
  const _DetailGallery({required this.images});
  final List<PropertyImage> images;

  @override
  State<_DetailGallery> createState() => _DetailGalleryState();
}

class _DetailGalleryState extends State<_DetailGallery> {
  late final PageController _pageController;
  Timer? _autoPlayTimer;
  int _currentPage = 0;
  bool _userInteracting = false;

  bool get _hasImages => widget.images.isNotEmpty;
  bool get _hasMultiple => widget.images.length > 1;

  @override
  void initState() {
    super.initState();
    _pageController = PageController();
    if (_hasMultiple) _startAutoPlay();
  }

  @override
  void dispose() {
    _stopAutoPlay();
    _pageController.dispose();
    super.dispose();
  }

  void _startAutoPlay() {
    _stopAutoPlay();
    _autoPlayTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted || !_pageController.hasClients || _userInteracting) return;
      final next = (_currentPage + 1) % widget.images.length;
      _pageController.animateToPage(
        next,
        duration: const Duration(milliseconds: 450),
        curve: Curves.easeOutCubic,
      );
    });
  }

  void _stopAutoPlay() {
    _autoPlayTimer?.cancel();
    _autoPlayTimer = null;
  }

  void _onPageChanged(int index) {
    if (mounted) setState(() => _currentPage = index);
  }

  void _goTo(int index) {
    if (!_pageController.hasClients) return;
    _pageController.animateToPage(
      index,
      duration: const Duration(milliseconds: 320),
      curve: Curves.easeOutCubic,
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!_hasImages) {
      return Container(
        color: WajhatakColors.emeraldDeep,
        child: const Center(
          child: Icon(
            Icons.villa_rounded,
            color: WajhatakColors.emeraldSoft,
            size: 64,
          ),
        ),
      );
    }
    return Stack(
      fit: StackFit.expand,
      children: [
        NotificationListener<ScrollNotification>(
          onNotification: (notification) {
            if (notification is ScrollStartNotification) {
              _userInteracting = true;
            } else if (notification is ScrollEndNotification) {
              _userInteracting = false;
            }
            return false;
          },
          child: PageView.builder(
            controller: _pageController,
            itemCount: widget.images.length,
            onPageChanged: _onPageChanged,
            itemBuilder: (_, index) {
              final url = widget.images[index].url;
              if (url.isEmpty) {
                return Container(color: WajhatakColors.emeraldDeep);
              }
              return CachedNetworkImage(
                imageUrl: url,
                fit: BoxFit.cover,
                placeholder: (_, _) => const LuxSkeleton(
                  radius: 0,
                  width: double.infinity,
                  height: double.infinity,
                ),
                errorWidget: (_, _, _) => Container(
                  color: WajhatakColors.emeraldDeep,
                  child: const Center(
                    child: Icon(
                      Icons.broken_image_rounded,
                      color: WajhatakColors.emeraldSoft,
                      size: 40,
                    ),
                  ),
                ),
              );
            },
          ),
        ),
        // تدرج سفلي
        Positioned(
          bottom: 0,
          left: 0,
          right: 0,
          height: 90,
          child: DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black.withValues(alpha: 0),
                  Colors.black.withValues(alpha: .4),
                ],
              ),
            ),
          ),
        ),
        if (_hasMultiple) ...[
          // مؤشر الصورة الحالية
          Positioned(
            bottom: 14,
            left: 0,
            right: 0,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.black.withValues(alpha: .45),
                    borderRadius: BorderRadius.circular(99),
                  ),
                  child: Text(
                    '${_currentPage + 1} / ${widget.images.length}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
              ],
            ),
          ),
          // أسهم التنقل (سماعة يسار/يمين RTL)
          Positioned(
            right: 12,
            top: 0,
            bottom: 40,
            child: Center(
              child: GlassIconButton(
                icon: Icons.chevron_right_rounded,
                onPressed: _currentPage > 0
                    ? () => _goTo(_currentPage - 1)
                    : null,
                tooltip: 'السابق',
              ),
            ),
          ),
          Positioned(
            left: 12,
            top: 0,
            bottom: 40,
            child: Center(
              child: GlassIconButton(
                icon: Icons.chevron_left_rounded,
                onPressed: _currentPage < widget.images.length - 1
                    ? () => _goTo(_currentPage + 1)
                    : null,
                tooltip: 'التالي',
              ),
            ),
          ),
          // نقاط المؤشر
          Positioned(
            bottom: 42,
            left: 0,
            right: 0,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                widget.images.length,
                (i) => AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  margin: const EdgeInsets.symmetric(horizontal: 3),
                  width: i == _currentPage ? 18 : 7,
                  height: 7,
                  decoration: BoxDecoration(
                    color: i == _currentPage
                        ? WajhatakColors.amber
                        : Colors.white.withValues(alpha: .55),
                    borderRadius: BorderRadius.circular(99),
                  ),
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}

class _FactsRow extends StatelessWidget {
  const _FactsRow({required this.property});
  final LuxProperty property;

  @override
  Widget build(BuildContext context) => Wrap(
    spacing: 9,
    runSpacing: 9,
    children: [
      if (property.area != null)
        FactChip(
          icon: Icons.square_foot_rounded,
          label: formatArea(property.area!),
          tone: AccentTone.emerald,
        ),
      if (property.bedrooms != null)
        FactChip(
          icon: Icons.bed_rounded,
          label: '${property.bedrooms} غرف',
          tone: AccentTone.sky,
        ),
      if (property.bathrooms != null)
        FactChip(
          icon: Icons.bathtub_rounded,
          label: '${property.bathrooms} حمامات',
          tone: AccentTone.teal,
        ),
      if (property.parkingSpaces != null && property.parkingSpaces! > 0)
        FactChip(
          icon: Icons.directions_car_rounded,
          label: '${property.parkingSpaces} مواقف',
          tone: AccentTone.amber,
        ),
      if (property.isFurnished)
        const FactChip(
          icon: Icons.chair_rounded,
          label: 'مفروش',
          tone: AccentTone.violet,
        ),
      if (property.isNew)
        const FactChip(
          icon: Icons.auto_awesome_rounded,
          label: 'جديد',
          tone: AccentTone.orange,
        ),
    ],
  );
}

class _FeatureChip extends StatelessWidget {
  const _FeatureChip({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 9),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHigh,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.check_circle_rounded,
            size: 15,
            color: WajhatakColors.emerald,
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: theme.textTheme.labelLarge?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _AgentPanel extends StatelessWidget {
  const _AgentPanel({this.agent});
  final PropertyAgent? agent;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: agent == null
            ? null
            : () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => AgentProfileScreen(
                    agentId: agent!.id,
                    initialAgent: agent,
                  ),
                ),
              ),
        borderRadius: BorderRadius.circular(24),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topRight,
              end: Alignment.bottomLeft,
              colors: theme.brightness == Brightness.dark
                  ? [WajhatakColors.surfaceAltDark, WajhatakColors.surfaceDark]
                  : [const Color(0xFFEAF6F1), theme.colorScheme.surface],
            ),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: theme.colorScheme.outlineVariant),
          ),
          child: Row(
            children: [
              UserAvatar(
                avatarUrl: agent?.avatarUrl,
                name: agent?.name,
                radius: 26,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'وكيل العقار',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      agent?.name ?? 'وكيل معتمد',
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    if (agent?.rating != null) ...[
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          const Icon(
                            Icons.star_rounded,
                            size: 14,
                            color: WajhatakColors.amber,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${agent!.rating!.toStringAsFixed(1)} تقييم'
                            '${agent!.reviewsCount != null ? ' (${agent!.reviewsCount})' : ''}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
              if (agent != null) ...[
                const SizedBox(width: 8),
                Icon(
                  Icons.chevron_left_rounded,
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
