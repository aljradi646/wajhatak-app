import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/format_money.dart';
import '../../../core/utils/notice.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../property/property_detail_screen.dart';
import '../shared/toggle_favorite.dart';

/// ملف الوكيل بنمط Instagram: رأس (الصورة، الاسم، التقييم، النبذة، أزرار
/// الاتصال والمراسلة) ثم شبكة منشورات عمودية احترافية، 3 بطاقات في كل صف.
class AgentProfileScreen extends ConsumerStatefulWidget {
  const AgentProfileScreen({
    super.key,
    required this.agentId,
    this.initialAgent,
    this.propertyId,
  });

  final int agentId;
  final PropertyAgent? initialAgent;

  /// العقار الذي وصل منه العميل إلى الملف — يُستخدم لبدء المحادثة مع الوكيل.
  final int? propertyId;

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
          child: _ProfileHeader(
            data: data,
            totalCount: total,
            onMessage: () => _messageAgent(data),
            onCall: () => _callAgent(data.agent),
          ),
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
            padding: const EdgeInsets.fromLTRB(12, 4, 12, 0),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 0.64,
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

  void _messageAgent(AgentProfileData data) {
    final propertyId =
        widget.propertyId ??
        (data.properties.isEmpty ? null : data.properties.first.id);
    if (propertyId == null) {
      notice(context, 'لا توجد عقارات لبدء محادثة حولها بعد.');
      return;
    }
    startConversation(context, ref, propertyId, agentId: data.agent.id);
  }

  void _callAgent(PropertyAgent agent) {
    final phone = agent.phone;
    if (phone == null || phone.isEmpty) {
      notice(context, 'رقم الهاتف غير متوفر لهذا الوكيل.');
      return;
    }
    showModalBottomSheet<void>(
      context: context,
      useSafeArea: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      builder: (_) => _ContactSheet(name: agent.name, phone: phone),
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
  const _ProfileHeader({
    required this.data,
    required this.totalCount,
    required this.onMessage,
    required this.onCall,
  });

  final AgentProfileData data;
  final int totalCount;
  final VoidCallback onMessage;
  final VoidCallback onCall;

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
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: FilledButton.icon(
                  onPressed: onMessage,
                  icon: const Icon(Icons.chat_bubble_rounded, size: 18),
                  label: const Text('مراسلة'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onCall,
                  icon: const Icon(Icons.phone_rounded, size: 18),
                  label: const Text('اتصال'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ContactSheet extends StatelessWidget {
  const _ContactSheet({required this.name, required this.phone});

  final String name;
  final String phone;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 10, 24, 24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: theme.colorScheme.outlineVariant,
              borderRadius: BorderRadius.circular(99),
            ),
          ),
          const SizedBox(height: 20),
          CircleAvatar(
            radius: 30,
            backgroundColor: theme.colorScheme.primaryContainer,
            child: Text(
              name.isEmpty ? '؟' : name.characters.first,
              style: theme.textTheme.headlineSmall?.copyWith(
                color: theme.colorScheme.onPrimaryContainer,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          const SizedBox(height: 12),
          Text(
            name,
            style: theme.textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.phone_rounded,
                size: 18,
                color: WajhatakColors.emerald,
              ),
              const SizedBox(width: 8),
              SelectableText(
                phone,
                textDirection: TextDirection.ltr,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () async {
                await Clipboard.setData(ClipboardData(text: phone));
                if (context.mounted) {
                  Navigator.of(context).pop();
                  notice(context, 'تم نسخ رقم الوكيل.');
                }
              },
              icon: const Icon(Icons.copy_rounded, size: 19),
              label: const Text('نسخ الرقم'),
            ),
          ),
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
    final theme = Theme.of(context);
    final metaColor = theme.colorScheme.onSurfaceVariant;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: theme.colorScheme.outlineVariant),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Stack(
                fit: StackFit.expand,
                children: [
                  _TileImage(url: property.coverUrl),
                  const DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Colors.transparent, Colors.black38],
                      ),
                    ),
                  ),
                  Positioned(
                    top: 6,
                    right: 6,
                    child: _TransactionBadge(isRent: property.isRent),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    formatMoney(property.price, property.currency),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.labelMedium?.copyWith(
                      color: theme.colorScheme.primary,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    property.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodySmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  if (property.bedrooms != null || property.area != null) ...[
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        if (property.bedrooms != null) ...[
                          Icon(
                            Icons.king_bed_outlined,
                            size: 11,
                            color: metaColor,
                          ),
                          const SizedBox(width: 2),
                          Text(
                            '${property.bedrooms}',
                            style: theme.textTheme.labelSmall?.copyWith(
                              color: metaColor,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                        if (property.area != null) ...[
                          const SizedBox(width: 8),
                          Icon(
                            Icons.square_foot_rounded,
                            size: 11,
                            color: metaColor,
                          ),
                          const SizedBox(width: 2),
                          Text(
                            formatArea(property.area!),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.labelSmall?.copyWith(
                              color: metaColor,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TransactionBadge extends StatelessWidget {
  const _TransactionBadge({required this.isRent});

  final bool isRent;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2.5),
      decoration: BoxDecoration(
        color: isRent ? WajhatakColors.amber : WajhatakColors.emerald,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        isRent ? 'للإيجار' : 'للبيع',
        style: const TextStyle(
          color: Colors.white,
          fontSize: 9,
          fontWeight: FontWeight.w900,
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
      memCacheWidth: 360,
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
      child: const Icon(Icons.villa_outlined, color: Colors.white70, size: 26),
    );
  }
}
