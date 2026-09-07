import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/format_money.dart';
import '../../../core/utils/notice.dart' as util;
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';
import '../property/property_detail_screen.dart';

/// شاشة المحادثة — العنوان اسم الوكيل، مع بطاقات عقارات سياقية.
class ChatScreen extends ConsumerStatefulWidget {
  const ChatScreen({super.key, required this.conversation});
  final ConversationItem conversation;

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> {
  final _controller = TextEditingController();
  bool _sending = false;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    // تحديث فوري عند فتح المحادثة (لا يُستخدم الكاش القديم) —
    // بنفس صرامه تحديث جرس الإشعارات.
    if (ref.exists(messagesProvider(widget.conversation.id))) {
      ref.read(messagesProvider(widget.conversation.id).notifier).refresh();
    }
    // Polling خفيف للرسائل الجديدة كل 6 ثوان — تحديث خلفي بدون اهتزاز.
    _refreshTimer = Timer.periodic(const Duration(seconds: 6), (_) {
      if (mounted) {
        ref.read(messagesProvider(widget.conversation.id).notifier).refresh();
        ref.invalidate(conversationsProvider);
      }
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _sending) return; // منع الإرسال المزدوج
    setState(() => _sending = true);
    try {
      await ref
          .read(messagesProvider(widget.conversation.id).notifier)
          .send(text);
      _controller.clear();
      ref.invalidate(conversationsProvider);
    } on ApiFailure catch (error) {
      if (mounted) util.notice(context, error.message);
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final messages = ref.watch(messagesProvider(widget.conversation.id));
    final currentUser = ref.watch(sessionProvider).asData?.value?.user;
    // اسم المحادثة = اسم الوكيل (أو العميل للوكيل) وليس اسم العقار.
    final otherName = currentUser?.isAgent == true
        ? widget.conversation.clientName
        : widget.conversation.agentName;
    final displayName = otherName?.isNotEmpty == true
        ? otherName!
        : widget.conversation.title;
    final otherAvatarUrl = currentUser?.isAgent == true
        ? widget.conversation.clientAvatarUrl
        : widget.conversation.agentAvatarUrl;

    return Scaffold(
      appBar: WajhatakScreenHeader(
        title: displayName,
        subtitle: 'محادثة آمنة وموثوقة',
        actions: [
          Padding(
            padding: const EdgeInsets.only(left: 4),
            child: Center(
              child: UserAvatar(
                avatarUrl: otherAvatarUrl,
                name: displayName,
                radius: 19,
              ),
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: LuxAsyncView<List<ChatMessage>>(
              value: messages,
              errorRetry: () =>
                  ref.invalidate(messagesProvider(widget.conversation.id)),
              data: (data) => data.isEmpty
                  ? const EmptyState(
                      title: 'ابدأ المحادثة',
                      body: 'اكتب رسالتك ليصل إشعار إلى الطرف الآخر فورًا.',
                      icon: Icons.chat_bubble_outline_rounded,
                    )
                  : ListView.builder(
                      reverse: true,
                      padding: const EdgeInsets.all(16),
                      itemCount: data.length,
                      itemBuilder: (_, index) {
                        final item = data[index];
                        final mine = item.senderId == currentUser?.id;
                        if (item.isPropertyCard) {
                          return _PropertyCardMessage(
                            property: item.property!,
                            isMine: mine,
                          );
                        }
                        return _TextMessage(
                          body: item.body,
                          createdAt: item.createdAt,
                          isMine: mine,
                          readAt: item.readAt,
                        );
                      },
                    ),
            ),
          ),
          // شريط الإرسال
          DecoratedBox(
            decoration: BoxDecoration(
              color: Theme.of(context).colorScheme.surface,
              border: Border(
                top: BorderSide(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
            ),
            child: SafeArea(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 12),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _controller,
                        minLines: 1,
                        maxLines: 4,
                        textInputAction: TextInputAction.send,
                        onSubmitted: (_) => _send(),
                        decoration: InputDecoration(
                          hintText: 'اكتب رسالة…',
                          filled: true,
                          fillColor: Theme.of(
                            context,
                          ).colorScheme.surfaceContainerHigh,
                          prefixIcon: Icon(
                            Icons.chat_bubble_outline_rounded,
                            color: Theme.of(context).colorScheme.primary,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    _SendButton(onTap: _sending ? null : _send, busy: _sending),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SendButton extends StatelessWidget {
  const _SendButton({required this.onTap, required this.busy});

  final VoidCallback? onTap;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Material(
      color: onTap == null
          ? scheme.primary.withValues(alpha: .5)
          : scheme.primary,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: SizedBox(
          width: 52,
          height: 52,
          child: busy
              ? const Padding(
                  padding: EdgeInsets.all(14),
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2.4,
                  ),
                )
              : Transform.flip(
                  flipX: true,
                  child: const Icon(
                    Icons.send_rounded,
                    color: Colors.white,
                    size: 21,
                  ),
                ),
        ),
      ),
    );
  }
}

class _TextMessage extends StatelessWidget {
  const _TextMessage({
    required this.body,
    required this.createdAt,
    required this.isMine,
    this.readAt,
  });

  final String body;
  final DateTime createdAt;
  final bool isMine;
  final DateTime? readAt;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Align(
      alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: 9),
        padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
        constraints: const BoxConstraints(maxWidth: 310),
        decoration: BoxDecoration(
          gradient: isMine
              ? LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    theme.colorScheme.primary,
                    Color.lerp(
                      theme.colorScheme.primary,
                      Colors.black,
                      .12,
                    )!,
                  ],
                )
              : null,
          color: isMine
              ? null
              : theme.colorScheme.surfaceContainerHigh,
          borderRadius: BorderRadiusDirectional.only(
            topStart: const Radius.circular(18),
            topEnd: const Radius.circular(18),
            bottomStart: Radius.circular(isMine ? 18 : 5),
            bottomEnd: Radius.circular(isMine ? 5 : 18),
          ),
          boxShadow: isMine
              ? [
                  BoxShadow(
                    color: theme.colorScheme.primary.withValues(alpha: .22),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ]
              : null,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              body,
              style: TextStyle(
                color: isMine
                    ? theme.colorScheme.onPrimary
                    : theme.colorScheme.onSurface,
                height: 1.45,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 4),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  DateFormat.Hm('ar').format(createdAt),
                  style: TextStyle(
                    color: isMine
                        ? theme.colorScheme.onPrimary.withValues(alpha: .75)
                        : theme.colorScheme.onSurfaceVariant,
                    fontSize: 10,
                  ),
                ),
                if (isMine) ...[
                  const SizedBox(width: 4),
                  Icon(
                    readAt == null ? Icons.done_rounded : Icons.done_all_rounded,
                    size: 15,
                    color: readAt == null
                        ? theme.colorScheme.onPrimary.withValues(alpha: .75)
                        : const Color(0xFF9BE8DC),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// بطاقة العقار داخل المحادثة (نمط واتساب) — تفتح تفاصيل العقار الحقيقي.
class _PropertyCardMessage extends StatelessWidget {
  const _PropertyCardMessage({required this.property, required this.isMine});
  final ChatMessageProperty property;
  final bool isMine;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final locationLabel = [property.district, property.city]
        .whereType<String>()
        .where((s) => s.isNotEmpty)
        .join('، ');
    final isSale = property.transactionType != 'rent';
    return Align(
      alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
      child: GestureDetector(
        onTap: () => Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => PropertyDetailScreen(propertyId: property.id),
          ),
        ),
        child: Container(
          margin: const EdgeInsets.only(bottom: 10),
          constraints: const BoxConstraints(maxWidth: 300),
          clipBehavior: Clip.antiAlias,
          decoration: BoxDecoration(
            color: theme.colorScheme.surface,
            borderRadius: BorderRadiusDirectional.only(
              topStart: const Radius.circular(18),
              topEnd: const Radius.circular(18),
              bottomStart: Radius.circular(isMine ? 18 : 5),
              bottomEnd: Radius.circular(isMine ? 5 : 18),
            ),
            border: Border.all(color: theme.colorScheme.outlineVariant),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: .05),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                children: [
                  SizedBox(
                    height: 140,
                    width: double.infinity,
                    child: property.coverUrl != null &&
                            property.coverUrl!.isNotEmpty
                        ? CachedNetworkImage(
                            imageUrl: property.coverUrl!,
                            fit: BoxFit.cover,
                            placeholder: (context, url) => const LuxSkeleton(
                              width: double.infinity,
                              height: double.infinity,
                              radius: 0,
                            ),
                            errorWidget: (context, url, error) =>
                                const _CoverFallback(),
                          )
                        : const _CoverFallback(),
                  ),
                  Positioned(
                    top: 10,
                    right: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 9,
                        vertical: 4,
                      ),
                      decoration: BoxDecoration(
                        color: (isSale
                                ? WajhatakColors.emerald
                                : WajhatakColors.sky)
                            .withValues(alpha: .94),
                        borderRadius: BorderRadius.circular(99),
                      ),
                      child: Text(
                        isSale ? 'للبيع' : 'للإيجار',
                        style: const TextStyle(
                          fontSize: 10.5,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.all(13),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      property.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if (locationLabel.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_rounded,
                            size: 13,
                            color: WajhatakColors.terracotta,
                          ),
                          const SizedBox(width: 3),
                          Expanded(
                            child: Text(
                              locationLabel,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 8),
                    Text(
                      formatMoney(property.price, property.currency),
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                    if (property.area != null || property.bedrooms != null) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          if (property.area != null) ...[
                            const Icon(
                              Icons.square_foot_rounded,
                              size: 14,
                              color: WajhatakColors.emerald,
                            ),
                            const SizedBox(width: 3),
                            Text(
                              formatArea(property.area!),
                              style: theme.textTheme.bodySmall?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(width: 10),
                          ],
                          if (property.bedrooms != null) ...[
                            const Icon(
                              Icons.bed_rounded,
                              size: 14,
                              color: WajhatakColors.sky,
                            ),
                            const SizedBox(width: 3),
                            Text(
                              '${property.bedrooms} غرف',
                              style: theme.textTheme.bodySmall?.copyWith(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 11,
                        vertical: 7,
                      ),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.primaryContainer,
                        borderRadius: BorderRadius.circular(11),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.open_in_new_rounded,
                            size: 13,
                            color: theme.colorScheme.onPrimaryContainer,
                          ),
                          const SizedBox(width: 5),
                          Text(
                            'عرض العقار',
                            style: theme.textTheme.labelMedium?.copyWith(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                              color: theme.colorScheme.onPrimaryContainer,
                            ),
                          ),
                        ],
                      ),
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

class _CoverFallback extends StatelessWidget {
  const _CoverFallback();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      color: theme.colorScheme.surfaceContainerHighest,
      child: Center(
        child: Icon(
          Icons.villa_rounded,
          size: 40,
          color: theme.colorScheme.primary.withValues(alpha: .7),
        ),
      ),
    );
  }
}
