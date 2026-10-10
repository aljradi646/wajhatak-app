import 'dart:async';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/format_money.dart';
import '../../../data/models/models.dart';
import '../../../state/ai_assistant_controller.dart';
import '../../../state/providers.dart';
import '../property/property_detail_screen.dart';
import '../shared/toggle_favorite.dart';
import '../../widgets/skeleton/lux_skeleton.dart';

/// نافذة المساعد الذكي — Bottom Sheet على الجوال، ولوحة عائمة متمركزة على
/// الشاشات الكبيرة. تصميم متوافق مع هوية وجهتك (زمردي + كهرماني + Cairo).
Future<void> showAiAssistant(BuildContext context) {
  final width = MediaQuery.sizeOf(context).width;
  if (width >= 1024) {
    return showDialog(
      context: context,
      builder: (_) => Dialog(
        insetPadding: const EdgeInsets.symmetric(vertical: 48, horizontal: 120),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(WajhatakRadius.panel),
          child: const SizedBox(
            width: 720,
            height: 640,
            child: AiAssistantPanel(),
          ),
        ),
      ),
    );
  }

  return Navigator.of(context).push<void>(
    MaterialPageRoute<void>(
      fullscreenDialog: true,
      builder: (_) => const AiAssistantPanel(),
    ),
  );
}

class AiAssistantPanel extends ConsumerStatefulWidget {
  const AiAssistantPanel({super.key});

  @override
  ConsumerState<AiAssistantPanel> createState() => _AiAssistantPanelState();
}

class _AiAssistantPanelState extends ConsumerState<AiAssistantPanel> {
  final _inputController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(aiConversationProvider.notifier).ensureReady();
    });
  }

  @override
  void dispose() {
    _inputController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _send() {
    final text = _inputController.text;
    _inputController.clear();
    ref.read(aiConversationProvider.notifier).send(text);
    _scrollToBottom();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 320),
          curve: Curves.easeOutCubic,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final state = ref.watch(aiConversationProvider);
    final bootstrap = ref.watch(aiBootstrapProvider).asData?.value;
    final lastFailed =
        state.messages.isNotEmpty && state.messages.last.status == 'error';
    final showTyping = state.loading &&
        (state.messages.isEmpty ||
            state.messages.last.isUser ||
            state.messages.last.status != 'streaming');
    final pristineConversation = state.conversationId == null &&
        state.messages.length == 1 &&
        !state.messages.first.isUser;

    return Scaffold(
      backgroundColor: theme.colorScheme.surface,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        backgroundColor: WajhatakColors.emeraldDeep,
        foregroundColor: Colors.white,
        systemOverlayStyle: SystemUiOverlayStyle.light,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(
            top: Radius.circular(WajhatakRadius.sheet),
          ),
        ),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                gradient: WajhatakColors.amberGradient,
                borderRadius: BorderRadius.circular(13),
              ),
              child: const Icon(
                Icons.auto_awesome_rounded,
                size: 18,
                color: Colors.white,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                bootstrap?.assistantName ?? 'مساعد وجهتك',
                style: const TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 16,
                ),
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'سجل المحادثات',
            icon: const Icon(Icons.history_rounded),
            onPressed: () => _showConversationHistory(context),
          ),
          IconButton(
            tooltip: 'محادثة جديدة',
            icon: const Icon(Icons.add_comment_rounded),
            onPressed: () => ref.read(aiConversationProvider.notifier).newConversation(),
          ),
          IconButton(
            tooltip: 'مسح المحادثة',
            icon: const Icon(Icons.delete_sweep_rounded),
            onPressed: () => ref.read(aiConversationProvider.notifier).clear(),
          ),
          IconButton(
            tooltip: 'إغلاق',
            icon: const Icon(Icons.close_rounded),
            onPressed: () => Navigator.of(context).maybePop(),
          ),
        ],
      ),
      body: SafeArea(
        top: false,
        child: Column(
          children: [
            Expanded(
              child: state.isEmpty && state.phase == AiSendPhase.bootstrapping
                  ? const _AiAssistantSkeleton()
                  : ListView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
                      itemCount: state.messages.length + (showTyping ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (showTyping && index == state.messages.length) {
                          return const _TypingIndicator();
                        }
                        final message = state.messages[index];
                        final welcome = index == 0 &&
                            state.messages.length == 1 &&
                            !message.isUser &&
                            state.conversationId == null &&
                            message.status == 'ok';
                        return _MessageBubble(
                          message: message,
                          isWelcome: welcome,
                          onPropertyTap: _openProperty,
                          onFavoriteTap: _canFavorite ? (property) => _favorite(property) : null,
                        );
                      },
                    ),
            ),
            if (pristineConversation &&
                state.phase != AiSendPhase.disabled &&
                bootstrap?.enabled == true &&
                bootstrap?.suggestions.isNotEmpty == true)
              _SuggestionsBar(
                suggestions: bootstrap!.suggestions,
                onSelected: (value) {
                  ref.read(aiConversationProvider.notifier).send(value);
                  _scrollToBottom();
                },
              ),
            if (lastFailed)
              Padding(
                padding: const EdgeInsets.only(bottom: 6),
                child: TextButton.icon(
                  onPressed: state.loading
                      ? null
                      : () => ref
                            .read(aiConversationProvider.notifier)
                            .resendLast(),
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('إعادة إرسال'),
                ),
              ),
            _InputBar(
              controller: _inputController,
              enabled: state.canSend,
              sending: state.loading,
              onSubmit: _send,
              onCancel: () =>
                  ref.read(aiConversationProvider.notifier).cancel(),
            ),
          ],
        ),
      ),
    );
  }

  bool get _canFavorite => ref.read(sessionProvider).asData?.value != null;

  void _openProperty(int propertyId) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => PropertyDetailScreen(propertyId: propertyId),
      ),
    );
  }

  Future<void> _favorite(AiPropertyResult property) async {
    // بناء LuxProperty مصغّرة من بيانات المساعد الحقيقية — تكفي لعملية
    // المفضلة على الخادم (يُعمل بـ property_id فقط).
    final session = ref.read(sessionProvider).asData?.value;
    if (session == null) return;
    final lux = LuxProperty(
      id: property.propertyId,
      title: property.title,
      price: property.price ?? 0,
      currency: property.currency ?? 'YER',
      transactionType: property.transactionType,
    );
    if (!mounted) return;
    await toggleFavorite(context, ref, lux);
  }

  void _showConversationHistory(BuildContext context) {
    final session = ref.read(sessionProvider).asData?.value;
    if (session == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يجب تسجيل الدخول لعرض سجل المحادثات')),
      );
      return;
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _ConversationHistorySheet(),
    );
  }
}

// ---------------------------------------------------------------------------
// الفقاعة — رسالة مستخدم/مساعد + بطاقات العقارات الحقيقية
// ---------------------------------------------------------------------------

class _AiAssistantSkeleton extends StatelessWidget {
  const _AiAssistantSkeleton();
  @override
  Widget build(BuildContext context) {
    final shade = Theme.of(context).colorScheme.surfaceContainerHighest;
    Widget bar(double width, double height) => Container(
      width: width, height: height,
      decoration: BoxDecoration(color: shade, borderRadius: BorderRadius.circular(10)),
    );
    return ListView(padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 24), children: [
      Row(children: [bar(44, 44), const SizedBox(width: 12), Column(crossAxisAlignment: CrossAxisAlignment.start, children: [bar(170, 16), const SizedBox(height: 9), bar(220, 11)])]),
      const SizedBox(height: 30),
      Align(alignment: AlignmentDirectional.centerEnd, child: bar(205, 54)),
      const SizedBox(height: 20),
      Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(color: Theme.of(context).colorScheme.surfaceContainerLow, borderRadius: BorderRadius.circular(22)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          bar(160, 18), const SizedBox(height: 15), bar(double.infinity, 12), const SizedBox(height: 9), bar(245, 12), const SizedBox(height: 22),
          Row(children: [Expanded(child: bar(double.infinity, 108)), const SizedBox(width: 10), Expanded(child: bar(double.infinity, 108))]),
        ]),
      ),
    ]);
  }
}

class _MessageBubble extends StatefulWidget {
  const _MessageBubble({required this.message, required this.onPropertyTap, this.onFavoriteTap, this.isWelcome = false});
  final AiChatMessage message;
  final ValueChanged<int> onPropertyTap;
  final ValueChanged<AiPropertyResult>? onFavoriteTap;
  final bool isWelcome;
  @override
  State<_MessageBubble> createState() => _MessageBubbleState();
}

class _MessageBubbleState extends State<_MessageBubble> {
  bool _showCopy = false;
  Timer? _timer;

  @override
  void dispose() { _timer?.cancel(); super.dispose(); }

  void _toggleCopy() {
    _timer?.cancel();
    setState(() => _showCopy = !_showCopy);
    if (_showCopy) {
      _timer = Timer(const Duration(milliseconds: 2400), () {
        if (mounted) setState(() => _showCopy = false);
      });
    }
  }

  Future<void> _copy() async {
    await Clipboard.setData(ClipboardData(text: widget.message.content));
    if (!mounted) return;
    _timer?.cancel();
    setState(() => _showCopy = false);
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
      content: Text('تم نسخ الرسالة'), duration: Duration(milliseconds: 850),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final user = widget.message.isUser;
    final items = widget.message.properties;
    final content = widget.message.content.trim();
    return SizedBox(width: double.infinity, child: Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (widget.isWelcome) GestureDetector(onTap: _toggleCopy, onLongPress: _copy, child: _WelcomeMessage(content: content))
        else Align(
          alignment: user ? AlignmentDirectional.centerStart : AlignmentDirectional.centerEnd,
          child: ConstrainedBox(
            constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * .88),
            child: Material(
              color: user ? theme.colorScheme.surfaceContainerHigh : theme.colorScheme.primaryContainer.withValues(alpha: .38),
              borderRadius: BorderRadiusDirectional.only(
                topStart: const Radius.circular(19), topEnd: const Radius.circular(19),
                bottomStart: Radius.circular(user ? 5 : 19), bottomEnd: Radius.circular(user ? 19 : 5),
              ),
              child: InkWell(
                onTap: _toggleCopy, onLongPress: _copy,
                borderRadius: BorderRadius.circular(19),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  child: Text(widget.message.content, style: theme.textTheme.bodyLarge?.copyWith(height: 1.72, fontSize: 15.5, fontWeight: FontWeight.w500)),
                ),
              ),
            ),
          ),
        ),
        AnimatedSize(duration: const Duration(milliseconds: 160), child:
          _showCopy && !user && content.isNotEmpty
            ? Align(alignment: AlignmentDirectional.centerEnd, child: IconButton.filledTonal(
                tooltip: 'نسخ الرد', visualDensity: VisualDensity.compact,
                iconSize: 17, onPressed: _copy, icon: const Icon(Icons.copy_rounded),
              ))
            : const SizedBox.shrink(),
        ),
        if (items.isNotEmpty) ...[
          const SizedBox(height: 8),
          LayoutBuilder(builder: (context, box) {
            final columns = box.maxWidth >= 860 ? 3 : box.maxWidth >= 520 ? 2 : 1;
            const gap = 10.0;
            final cardWidth = (box.maxWidth - gap * (columns - 1)) / columns;
            return Wrap(spacing: gap, runSpacing: gap, children: items.map((property) => SizedBox(
              width: cardWidth, height: 318,
              child: _AiPropertyMiniCard(
                property: property, onTap: () => widget.onPropertyTap(property.propertyId),
                onFavorite: widget.onFavoriteTap == null ? null : () => widget.onFavoriteTap!(property),
              ),
            )).toList(growable: false));
          }),
        ],
      ]),
    ));
  }
}

class _WelcomeMessage extends StatelessWidget {
  const _WelcomeMessage({required this.content});
  final String content;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lines = content.split('\n');
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 12, 4, 18),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Align(alignment: AlignmentDirectional.centerStart, child: Container(
          width: 46, height: 46,
          decoration: BoxDecoration(
            gradient: WajhatakColors.amberGradient, borderRadius: BorderRadius.circular(16),
            boxShadow: [BoxShadow(color: WajhatakColors.amber.withValues(alpha: .20), blurRadius: 16, offset: const Offset(0, 5))],
          ),
          child: const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 23),
        )),
        const SizedBox(height: 22),
        Text(lines.isEmpty ? content : lines.first, style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900, height: 1.45)),
        if (lines.length > 1) ...[
          const SizedBox(height: 9),
          Text(lines.skip(1).join('\n'), style: theme.textTheme.bodyLarge?.copyWith(height: 1.85, fontSize: 15.5, color: theme.colorScheme.onSurfaceVariant)),
        ],
        const SizedBox(height: 20),
        Divider(color: theme.colorScheme.outlineVariant.withValues(alpha: .8)),
      ]),
    );
  }
}

/// بطاقة عقار مصغّرة داخل المحادثة — مربوطة بعقار حقيقي عبر propertyId.
class _CopyMessageButton extends StatefulWidget {
  const _CopyMessageButton({required this.text});

  final String text;

  @override
  State<_CopyMessageButton> createState() => _CopyMessageButtonState();
}

class _CopyMessageButtonState extends State<_CopyMessageButton> {
  bool _copied = false;

  Future<void> _copy() async {
    await Clipboard.setData(ClipboardData(text: widget.text));
    if (!mounted) return;
    setState(() => _copied = true);
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('تم نسخ الرد'),
        duration: Duration(milliseconds: 900),
      ),
    );
    await Future<void>.delayed(const Duration(milliseconds: 900));
    if (mounted) setState(() => _copied = false);
  }

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: AlignmentDirectional.centerEnd,
      child: IconButton(
        tooltip: _copied ? 'تم النسخ' : 'نسخ الرد',
        visualDensity: VisualDensity.compact,
        icon: Icon(
          _copied ? Icons.check_rounded : Icons.copy_rounded,
          size: 17,
          color: _copied ? Theme.of(context).colorScheme.primary : null,
        ),
        onPressed: _copy,
      ),
    );
  }
}

class _AiPropertyMiniCard extends StatelessWidget {
  const _AiPropertyMiniCard({
    required this.property,
    required this.onTap,
    this.onFavorite,
  });

  final AiPropertyResult property;
  final VoidCallback onTap;
  final VoidCallback? onFavorite;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: SizedBox(
                  width: double.infinity,
                  height: 96,
                  child:
                      property.imageUrl != null && property.imageUrl!.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: property.imageUrl!,
                          fit: BoxFit.cover,
                          placeholder: (context, url) => const LuxSkeleton(
                            width: double.infinity,
                            height: double.infinity,
                            radius: 0,
                          ),
                          errorWidget: (context, url, error) => _imageFallback(theme),
                        )
                      : _imageFallback(theme),
                ),
              ),
              const SizedBox(height: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            property.title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ),
                        if (property.isAlternative)
                          Container(
                            margin: const EdgeInsetsDirectional.only(start: 6),
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: WajhatakColors.amber.withValues(
                                alpha: .16,
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text(
                              'قريب من طلبك',
                              style: TextStyle(
                                fontSize: 9,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            '${formatMoney(property.price ?? 0, property.currency ?? 'YER')} • ${property.transactionLabel}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.primary,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                        _copyButton(
                          context,
                          'السعر',
                          '${formatMoney(property.price ?? 0, property.currency ?? 'YER')} ${property.transactionLabel}',
                        ),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Icon(
                          Icons.location_on_rounded,
                          size: 12,
                          color: WajhatakColors.terracotta,
                        ),
                        const SizedBox(width: 2),
                        Expanded(
                          child: Text(
                            property.address?.isNotEmpty == true
                                ? property.address!
                                : property.locationLabel,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ),
                        _copyButton(
                          context,
                          'الموقع',
                          property.address?.isNotEmpty == true
                              ? property.address!
                              : property.locationLabel,
                        ),
                        if (property.bedrooms != null) ...[
                          Icon(
                            Icons.bed_rounded,
                            size: 13,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          Text(
                            ' ${property.bedrooms}',
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                        if (property.bathrooms != null) ...[
                          const SizedBox(width: 6),
                          Icon(
                            Icons.bathtub_rounded,
                            size: 13,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          Text(
                            ' ${property.bathrooms}',
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                      ],
                    ),
                    if (property.agentPhone?.isNotEmpty == true) ...[
                      Row(
                        children: [
                          Icon(
                            Icons.phone_outlined,
                            size: 13,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              property.agentPhone!,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurfaceVariant,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          IconButton(
                            tooltip: 'اتصال بالوكيل',
                            visualDensity: VisualDensity.compact,
                            padding: const EdgeInsets.all(2),
                            constraints: const BoxConstraints(
                              minWidth: 28,
                              minHeight: 28,
                            ),
                            icon: const Icon(Icons.call_rounded, size: 15),
                            onPressed: () async {
                              final uri = Uri(
                                scheme: 'tel',
                                path: property.agentPhone!,
                              );
                              await launchUrl(uri);
                            },
                          ),
                          _copyButton(
                            context,
                            'هاتف الوكيل',
                            property.agentPhone!,
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                    ],
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 2,
                      runSpacing: 2,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        if (property.referenceCode?.isNotEmpty == true) ...[
                          Icon(
                            Icons.tag_rounded,
                            size: 13,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(width: 3),
                          Flexible(
                            child: Text(
                              property.referenceCode!,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.labelSmall?.copyWith(
                                fontWeight: FontWeight.w800,
                                color: theme.colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ),
                          _copyButton(
                            context,
                            'الرمز',
                            property.referenceCode!,
                          ),
                        ],
                        if (!property.available)
                          _chip(context, 'غير متاح', WajhatakColors.terracotta)
                        else if (property.isFurnished)
                          _chip(context, 'مفروش', WajhatakColors.teal),
                        TextButton.icon(
                          onPressed: onTap,
                          icon: const Icon(Icons.visibility_rounded, size: 15),
                          label: const Text('التفاصيل'),
                          style: TextButton.styleFrom(
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            visualDensity: VisualDensity.compact,
                          ),
                        ),
                        if (onFavorite != null)
                          IconButton(
                            tooltip: 'حفظ في المفضلة',
                            onPressed: onFavorite,
                            icon: const Icon(
                              Icons.favorite_border_rounded,
                              size: 18,
                            ),
                            visualDensity: VisualDensity.compact,
                          ),
                        IconButton(
                          tooltip: 'مشاركة',
                          onPressed: () => _share(context),
                          icon: const Icon(Icons.ios_share_rounded, size: 17),
                          visualDensity: VisualDensity.compact,
                        ),
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

  Widget _copyButton(BuildContext context, String label, String value) {
    return IconButton(
      tooltip: 'نسخ $label',
      visualDensity: VisualDensity.compact,
      padding: const EdgeInsets.all(2),
      constraints: const BoxConstraints(minWidth: 28, minHeight: 28),
      icon: const Icon(Icons.copy_rounded, size: 14),
      onPressed: () async {
        await Clipboard.setData(ClipboardData(text: value));
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('تم نسخ $label'),
              duration: const Duration(milliseconds: 900),
            ),
          );
        }
      },
    );
  }

  /// مشاركة بطاقة العقار الحقيقية — نسخ سريع أو فتح واتساب بالمحتوى الجاهز.
  Future<void> _share(BuildContext context) async {
    final summary =
        '🏠 ${property.title}\n'
        '💰 ${formatMoney(property.price ?? 0, property.currency ?? 'YER')} '
        '• ${property.transactionLabel}\n'
        '📍 ${property.address?.isNotEmpty == true ? property.address : property.locationLabel}\n'
        '🛏 ${property.bedrooms ?? '-'} غرف  🛁 ${property.bathrooms ?? '-'} '
        '📐 ${property.area ?? '-'} م²'
        '${property.referenceCode?.isNotEmpty == true ? '\n🔖 ${property.referenceCode}' : ''}\n'
        '— عبر تطبيق وجهتك';

    if (!context.mounted) return;
    final action = await showModalBottomSheet<String>(
      context: context,
      builder: (sheetContext) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.copy_rounded),
              title: const Text('نسخ تفاصيل العقار'),
              onTap: () => Navigator.pop(sheetContext, 'copy'),
            ),
            ListTile(
              leading: const Icon(Icons.chat_rounded),
              title: const Text('مشاركة عبر واتساب'),
              onTap: () => Navigator.pop(sheetContext, 'whatsapp'),
            ),
          ],
        ),
      ),
    );

    if (!context.mounted) return;

    if (action == 'copy') {
      await Clipboard.setData(ClipboardData(text: summary));
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('تم نسخ تفاصيل العقار'),
            duration: Duration(seconds: 1),
          ),
        );
      }
      return;
    }

    if (action == 'whatsapp') {
      final uri = Uri.https('wa.me', '/', {'text': summary});
      final launched = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );
      if (!launched && context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تعذّر فتح واتساب على هذا الجهاز')),
        );
      }
    }
  }

  Widget _imageFallback(ThemeData theme) => Container(
    color: theme.colorScheme.surfaceContainerHigh,
    child: Icon(
      Icons.villa_rounded,
      color: theme.colorScheme.primary.withValues(alpha: .6),
    ),
  );

  Widget _chip(BuildContext context, String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: BoxDecoration(
      color: color.withValues(alpha: .15),
      borderRadius: BorderRadius.circular(99),
    ),
    child: Text(
      label,
      style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: color),
    ),
  );
}

// ---------------------------------------------------------------------------
// مؤشر الكتابة + الاقتراحات + حقل الإدخال
// ---------------------------------------------------------------------------

class _TypingIndicator extends StatefulWidget {
  const _TypingIndicator();

  @override
  State<_TypingIndicator> createState() => _TypingIndicatorState();
}

class _TypingIndicatorState extends State<_TypingIndicator>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..repeat();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Align(
      alignment: AlignmentDirectional.centerEnd,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: theme.colorScheme.primaryContainer.withValues(alpha: .45),
          borderRadius: BorderRadius.circular(18),
        ),
        child: AnimatedBuilder(
          animation: _controller,
          builder: (context, child) => Row(
            mainAxisSize: MainAxisSize.min,
            children: List.generate(3, (i) {
              final phase = (_controller.value * 3 - i).clamp(0.0, 1.0);
              final scale = 0.6 + 0.4 * (1 - (2 * phase - 1).abs());
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 2.5),
                width: 8 * scale,
                height: 8 * scale,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withValues(alpha: .7),
                  shape: BoxShape.circle,
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}

class _SuggestionsBar extends StatelessWidget {
  const _SuggestionsBar({required this.suggestions, required this.onSelected});

  final List<String> suggestions;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 42,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        itemCount: suggestions.length,
        separatorBuilder: (context, index) => const SizedBox(width: 8),
        itemBuilder: (_, index) => ActionChip(
          label: Text(
            suggestions[index],
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 12),
          ),
          onPressed: () => onSelected(suggestions[index]),
        ),
      ),
    );
  }
}

class _InputBar extends StatelessWidget {
  const _InputBar({
    required this.controller,
    required this.enabled,
    required this.sending,
    required this.onSubmit,
    required this.onCancel,
  });

  final TextEditingController controller;
  final bool enabled;
  final bool sending;
  final VoidCallback onSubmit;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SafeArea(
      top: false,
      child: Padding(
        padding: EdgeInsets.only(
          left: 14,
          right: 14,
          bottom: MediaQuery.of(context).viewInsets.bottom * 0 + 12,
        ),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: controller,
                enabled: enabled && !sending,
                textInputAction: TextInputAction.send,
                onSubmitted: (_) => onSubmit(),
                minLines: 1,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'اكتب طلبك العقاري…',
                  filled: true,
                  fillColor: theme.colorScheme.surfaceContainerHigh,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 10,
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(WajhatakRadius.input),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            AnimatedBuilder(
              animation: controller,
              builder: (context, child) =>
                  ValueListenableBuilder<TextEditingValue>(
                    valueListenable: controller,
                    builder: (context, value, child) {
                      final hasText = value.text.trim().isNotEmpty;
                      return Opacity(
                        opacity: enabled && hasText ? 1 : 0.5,
                        child: CircleAvatar(
                          radius: 23,
                          backgroundColor: WajhatakColors.emeraldDeep,
                          child: IconButton(
                            onPressed: sending
                                ? onCancel
                                : (enabled && hasText ? onSubmit : null),
                            icon: Icon(
                              sending ? Icons.stop_rounded : Icons.send_rounded,
                              color: Colors.white,
                              size: 19,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// سجل المحادثات — قائمة المحادثات السابقة
// ---------------------------------------------------------------------------

class _ConversationHistorySheet extends ConsumerWidget {
  const _ConversationHistorySheet();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final conversationsAsync = ref.watch(aiConversationsListProvider);

    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      expand: false,
      builder: (_, scrollController) => Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: const BorderRadius.vertical(
            top: Radius.circular(WajhatakRadius.sheet),
          ),
        ),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.symmetric(vertical: 12),
              decoration: BoxDecoration(
                border: Border(
                  bottom: BorderSide(color: theme.colorScheme.outlineVariant),
                ),
              ),
              child: Row(
                children: [
                  const SizedBox(width: 16),
                  Text(
                    'سجل المحادثات',
                    style: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
            ),
            Expanded(
              child: conversationsAsync.when(
                data: (conversations) {
                  if (conversations.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.chat_bubble_outline_rounded,
                            size: 64,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            'لا توجد محادثات سابقة',
                            style: theme.textTheme.bodyLarge?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                    );
                  }
                  return ListView.builder(
                    controller: scrollController,
                    itemCount: conversations.length,
                    itemBuilder: (_, index) {
                      final conv = conversations[index];
                      return ListTile(
                        leading: const Icon(Icons.chat_rounded),
                        title: Text(
                          conv.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        subtitle: Text(
                          _formatDate(conv.lastMessageAt),
                          style: theme.textTheme.bodySmall,
                        ),
                        trailing: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            IconButton(
                              tooltip: conv.isPinned
                                  ? 'إلغاء التثبيت'
                                  : 'تثبيت',
                              visualDensity: VisualDensity.compact,
                              icon: Icon(
                                conv.isPinned
                                    ? Icons.push_pin_rounded
                                    : Icons.push_pin_outlined,
                                size: 17,
                              ),
                              onPressed: () async {
                                try {
                                  await ref
                                      .read(aiAssistantRepositoryProvider)
                                      .pinConversation(conv.id, !conv.isPinned);
                                  ref.invalidate(aiConversationsListProvider);
                                } on Object catch (_) {
                                  // تعذر التثبيت دون التأثير على المحادثة الحالية.
                                }
                              },
                            ),
                            Text(
                              '${conv.messageCount} رسالة',
                              style: theme.textTheme.bodySmall,
                            ),
                          ],
                        ),
                        onTap: () {
                          Navigator.of(context).pop();
                          ref
                              .read(aiConversationProvider.notifier)
                              .loadConversation(conv.id);
                        },
                      );
                    },
                  );
                },
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, _) => Center(
                  child: Text(
                    'حدث خطأ في تحميل المحادثات',
                    style: theme.textTheme.bodyMedium,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inDays == 0) {
      if (diff.inHours == 0) {
        if (diff.inMinutes == 0) return 'الآن';
        return 'منذ ${diff.inMinutes} دقيقة';
      }
      return 'منذ ${diff.inHours} ساعة';
    } else if (diff.inDays == 1) {
      return 'أمس';
    } else if (diff.inDays < 7) {
      return 'منذ ${diff.inDays} أيام';
    }
    return '${date.day}/${date.month}/${date.year}';
  }
}
