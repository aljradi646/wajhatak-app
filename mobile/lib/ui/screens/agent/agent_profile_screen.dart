import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/format_money.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../property/property_detail_screen.dart';

/// ملف الوكيل بنمط Instagram: رأس (الصورة، الاسم، التقييم، النبذة) ثم شبكة
/// من منشوراته ب 4 بطاقات في كل صف — النقر على أي بطاقة يفتح تفاصيل العقار.
class AgentProfileScreen extends ConsumerStatefulWidget {
  const AgentProfileScreen({
    super.key,
    required this.agentId,
    this.initialAgent,
  });

  final int agentId;
  final PropertyAgent? initialAgent;

  @override
  ConsumerState<AgentProfileScreen> createState() => _AgentProfileScreenState();
}

class _AgentProfileScreenState extends ConsumerState<AgentProfileScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.hasClients &&
        _scrollController.position.extentAfter < 400) {
      ref.read(agentProfileProvider(widget.agentId).notifier).loadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(agentProfileProvider(widget.agentId));
    return Scaffold(
      appBar: WajhatakScreenHeader(
        title: widget.initialAgent?.name ?? 'ملف الوكيل',
        subtitle: widget.initialAgent == null ? null : 'جميع منشورات الوكيل',
      ),
      body: LuxAsyncView<AgentProfileData>(
        value: profile,
        errorRetry: () => ref.invalidate(agentProfileProvider(widget.agentId)),
        data: _buildBody,
      ),
    );
  }

  Widget _buildBody(AgentProfileData data) {
    final theme = Theme.of(context);
    final total = data.agent.propertiesCount ?? data.properties.length;
    return CustomScrollView(
      controller: _scrollController,
      slivers: [
        SliverToBoxAdapter(
          child: _ProfileHeader(data: data, totalCount: total),
        ),
        if (data.properties.isEmpty)
          SliverFillRemaining(
            hasScrollBody: false,
            child: EmptyState(
              title: 'لا توجد منشورات بعد',
              body: 'عقارات هذا الوكيل ستظهر هنا فور نشرها.',
            ),
          )
        else ...[
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(8, 4, 8, 0),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 4,
                mainAxisSpacing: 3,
                crossAxisSpacing: 3,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, index) => _AgentGridTile(
                  property: data.properties[index],
                  onTap: () => _openProperty(data.properties[index].id),
                ),
                childCount: data.properties.length,
              ),
            ),
          ),
          if (data.hasMore)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Center(
                  child: SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: theme.colorScheme.primary,
                    ),
                  ),
                ),
              ),
            ),
        ],
      ],
    );
  }

  void _openProperty(int id) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => PropertyDetailScreen(propertyId: id),
      ),
    );
  }
}

class _ProfileHeader extends StatelessWidget {
  const _ProfileHeader({required this.data, required this.totalCount});

  final AgentProfileData data;
  final int totalCount;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final agent = data.agent;
    return Container(
      margin: const EdgeInsets.fromLTRB(8, 8, 8, 12),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: theme.brightness == Brightness.dark
              ? [WajhatakColors.surfaceAltDark, theme.colorScheme.surface]
              : [const Color(0xFFEAF6F1), theme.colorScheme.surface],
        ),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              UserAvatar(
                avatarUrl: agent.avatarUrl,
                name: agent.name,
                radius: 40,
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      agent.name,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 6),
                    if (agent.rating != null)
                      Row(
                        children: [
                          const Icon(
                            Icons.star_rounded,
                            size: 18,
                            color: WajhatakColors.amber,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            agent.rating!.toStringAsFixed(1),
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          if (agent.reviewsCount != null) ...[
                            const SizedBox(width: 4),
                            Text(
                              '(${agent.reviewsCount})',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ],
                      ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(
                          Icons.verified_rounded,
                          size: 15,
                          color: WajhatakColors.emerald,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'وكيل معتمد',
                          style: theme.textTheme.labelMedium?.copyWith(
                            color: WajhatakColors.emerald,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _StatItem(value: '$totalCount', label: 'منشور'),
              _StatItem(value: '${data.properties.length}', label: 'معروض'),
              if (agent.phone != null) ...[
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    agent.phone!,
                    textDirection: TextDirection.ltr,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ],
          ),
          if ((agent.bio ?? '').isNotEmpty) ...[
            const SizedBox(height: 14),
            const Divider(height: 1),
            const SizedBox(height: 12),
            Text(
              agent.bio!,
              style: theme.textTheme.bodyMedium?.copyWith(
                height: 1.6,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  const _StatItem({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      children: [
        Text(
          value,
          style: theme.textTheme.titleMedium?.copyWith(
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(width: 5),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }
}

class _AgentGridTile extends StatelessWidget {
  const _AgentGridTile({required this.property, required this.onTap});

  final LuxProperty property;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(8),
        child: Stack(
          fit: StackFit.expand,
          children: [
            _TileImage(url: property.coverUrl),
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Colors.black54],
                ),
              ),
            ),
            Positioned(
              left: 4,
              right: 4,
              bottom: 4,
              child: Text(
                formatMoney(property.price, property.currency),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textDirection: TextDirection.rtl,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 9,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TileImage extends StatelessWidget {
  const _TileImage({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) return const _TileFallback();
    return CachedNetworkImage(
      imageUrl: url,
      fit: BoxFit.cover,
      memCacheWidth: 400,
      placeholder: (_, _) => const _TileFallback(),
      errorWidget: (_, _, _) => const _TileFallback(),
    );
  }
}

class _TileFallback extends StatelessWidget {
  const _TileFallback();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [WajhatakColors.emerald, WajhatakColors.emeraldDeep],
        ),
      ),
      child: const Icon(Icons.villa_outlined, color: Colors.white70, size: 30),
    );
  }
}
