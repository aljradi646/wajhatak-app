import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/notice.dart';
import '../../../data/api_client.dart';
import '../../../data/models/models.dart';
import '../../../state/providers.dart';
import '../auth/auth_screen.dart';
import '../chat/chat_screen.dart';

/// تبديل المفضلة — يرسل العملية إلى API، وعند نجاح الخادم يُحدّث كل
/// الواجهات المرتبطة فورًا (Heart أحمر في كل مكان لنفس العقار).
/// يمنع الطلبات المزدوجة أثناء الانتظار.
Future<void> toggleFavorite(
  BuildContext context,
  WidgetRef ref,
  LuxProperty property,
) async {
  if (ref.read(sessionProvider).asData?.value == null) {
    Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const AuthScreen()));
    return;
  }
  final overrides = ref.read(favoriteOverridesProvider);
  final currentFavState = overrides[property.id] ?? property.isFavorited;
  if (currentFavState) {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        icon: const Icon(
          Icons.heart_broken_rounded,
          color: WajhatakColors.terracotta,
          size: 40,
        ),
        title: const Text('إزالة من المفضلة'),
        content: const Text('هل أنت متأكد من إزالة هذا العقار من قائمة المفضلة؟'),
        actions: [
          Row(
            children: [
              Expanded(
                child: TextButton(
                  onPressed: () => Navigator.pop(ctx, false),
                  child: const Text('إلغاء'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  style: FilledButton.styleFrom(
                    backgroundColor: Theme.of(ctx).colorScheme.error,
                  ),
                  onPressed: () => Navigator.pop(ctx, true),
                  child: const Text('إزالة'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
    if (confirmed != true || !context.mounted) return;
  }
  final newFavoriteState = !currentFavState;
  try {
    // 1) العملية على الخادم أولًا
    await ref
        .read(propertyRepositoryProvider)
        .setFavorite(property.id, newFavoriteState);
    // 2) عند النجاح: تحديث الحالة المشتركة فورًا
    ref
        .read(favoriteOverridesProvider.notifier)
        .set(property.id, newFavoriteState);
    if (!context.mounted) return;
    // 3) إعادة جلب الموارد المتأثرة
    ref.invalidate(favoritesProvider);
    ref.invalidate(propertiesProvider);
    ref.invalidate(propertySearchProvider);
    ref.invalidate(propertyDetailProvider(property.id));
    notice(
      context,
      !newFavoriteState ? 'تمت الإزالة من المفضلة' : 'أُضيف إلى المفضلة',
    );
  } on ApiFailure catch (error) {
    // فشل: لا تغيير في الحالة المحلية
    if (context.mounted) notice(context, error.message);
  }
}

/// بدء/استئناف محادثة مع الوكيل من صفحة العقار.
/// الخادم يضمن تفرد المحادثة (عميل + وكيل) ويعيد نفس المحادثة لأي عقار لنفس الوكيل.
Future<void> startConversation(
  BuildContext context,
  WidgetRef ref,
  int propertyId, {
  int? agentId,
}) async {
  if (ref.read(sessionProvider).asData?.value == null) {
    await Navigator.of(
      context,
    ).push(MaterialPageRoute(builder: (_) => const AuthScreen()));
    if (!context.mounted) return;
    if (ref.read(sessionProvider).asData?.value == null) return;
  }
  try {
    final conversation = await ref
        .read(conversationRepositoryProvider)
        .startConversation(propertyId);
    ref.invalidate(conversationsProvider);
    if (context.mounted) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ChatScreen(conversation: conversation),
        ),
      );
    }
  } on ApiFailure catch (error) {
    if (context.mounted) notice(context, error.message);
  }
}
