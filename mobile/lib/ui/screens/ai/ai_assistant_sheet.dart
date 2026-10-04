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
      ref.read(aiConversationProvider.notifier).ensureStarted();
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

    return Scaffold(
      backgroundColor: theme.colorScheme.surface,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        backgroundColor: WajhatakColors.emeraldDeep,
        foregroundColor: Colors.white,
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
            onPressed: state.loading
                ? null
                : () => ref.read(aiConversationProvider.notifier).newConversation(),
          ),
          IconButton(
            tooltip: 'مسح المحادثة',
            icon: const Icon(Icons.refresh_rounded),
            onPressed: state.loading
                ? null
                : () => ref.read(aiConversationProvider.notifier).clear(),
          ),
          IconButton(
            tooltip: 'إغلاق',
            icon: const Icon(Icons.close_rounded),
            onPressed: () => Navigator.of(context).maybePop(),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 14),
              itemCount: state.messages.length + (state.loading ? 1 : 0),
              itemBuilder: (context, index) {
                if (state.loading && index == state.messages.length) {
                  return const _TypingIndicator();
                }
                final message = state.messages[index];
                return _MessageBubble(
                  message: message,
                  onPropertyTap: (propertyId) => _openProperty(propertyId),
                  onFavoriteTap: _canFavorite
                      ? (property) => _favorite(property)
                      : null,
                );
              },
            ),
          ),
          if (state.isEmpty && bootstrap?.suggestions.isNotEmpty == true)
            _SuggestionsBar(
              suggestions: bootstrap!.suggestions,
              onSelected: (value) {
                ref.read(aiConversationProvider.notifier).send(value);
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
            onCancel: () => ref.read(aiConversationProvider.notifier).cancel(),
          ),
        ],
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

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    required this.message,
    required this.onPropertyTap,
    this.onFavoriteTap,
  });

  final AiChatMessage message;
  final ValueChanged<int> onPropertyTap;
  final ValueChanged<AiPropertyResult>? onFavoriteTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isUser = message.isUser;

    return Align(
      alignment: isUser ? Alignment.centerLeft : Alignment.centerRight,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.sizeOf(context).width * 0.82,
        ),
        child: Column(
          crossAxisAlignment: isUser
              ? CrossAxisAlignment.start
              : CrossAxisAlignment.end,
          children: [
            GestureDetector(
              onLongPress: () {
                Clipboard.setData(ClipboardData(text: message.content));
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('تم نسخ النص'),
                    duration: Duration(seconds: 1),
                  ),
                );
              },
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  color: isUser
                      ? theme.colorScheme.surfaceContainerHigh
                      : theme.colorScheme.primaryContainer.withValues(
                          alpha: .45,
                        ),
                  borderRadius: BorderRadius.only(
                    topLeft: const Radius.circular(18),
                    topRight: const Radius.circular(18),
                    bottomLeft: Radius.circular(isUser ? 6 : 18),
                    bottomRight: Radius.circular(isUser ? 18 : 6),
                  ),
                ),
                child: SelectableText(
                  message.content,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    height: 1.55,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
            if (message.properties.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: SizedBox(
                  height: 224,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: message.properties.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 8),
                    itemBuilder: (_, index) {
                      final property = message.properties[index];
                      return SizedBox(
                        width: 280,
                        child: _AiPropertyMiniCard(
                          property: property,
                          onTap: () => onPropertyTap(property.propertyId),
                          onFavorite: onFavoriteTap != null
                              ? () => onFavoriteTap!(property)
                              : null,
                        ),
                      );
                    },
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// بطاقة عقار مصغّرة داخل المحادثة — مربوطة بعقار حقيقي عبر propertyId.
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
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: SizedBox(
                  width: 74,
                  height: 74,
                  child:
                      property.imageUrl != null && property.imageUrl!.isNotEmpty
                      ? Image.network(
                          property.imageUrl!,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) => _imageFallback(theme),
                        )
                      : _imageFallback(theme),
                ),
              ),
              const SizedBox(width: 10),
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
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: WajhatakColors.amber.withValues(alpha: .16),
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
                              final uri = Uri(scheme: 'tel', path: property.agentPhone!);
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
                    Row(
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
                          _copyButton(context, 'الرمز', property.referenceCode!),
                        ],
                        if (!property.available)
                          _chip(context, 'غير متاح', WajhatakColors.terracotta)
                        else if (property.isFurnished)
                          _chip(context, 'مفروش', WajhatakColors.teal),
                        const Spacer(),
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
      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
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
      alignment: Alignment.centerRight,
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
    required this.onSubmit,
  });

  final TextEditingController controller;
  final bool enabled;
  final VoidCallback onSubmit;

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
                enabled: enabled,
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
              builder: (context, child) => ValueListenableBuilder<TextEditingValue>(
                valueListenable: controller,
                builder: (context, value, child) {
                  final hasText = value.text.trim().isNotEmpty;
                  return Opacity(
                    opacity: enabled && hasText ? 1 : 0.5,
                    child: CircleAvatar(
                      radius: 23,
                      backgroundColor: WajhatakColors.emeraldDeep,
                      child: IconButton(
                        onPressed: enabled && hasText ? onSubmit : null,
                        icon: const Icon(
                          Icons.send_rounded,
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
                              tooltip: conv.isPinned ? 'إلغاء التثبيت' : 'تثبيت',
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
                            Text('${conv.messageCount} رسالة', style: theme.textTheme.bodySmall),
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
                error: (_, __) => Center(
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
