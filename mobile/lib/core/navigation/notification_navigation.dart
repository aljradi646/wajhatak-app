import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../data/models/models.dart';
import '../../state/providers.dart';
import '../../ui/screens/chat/chat_screen.dart';
import '../../ui/screens/messages/messages_screen.dart';
import '../../ui/screens/notifications/notifications_screen.dart';
import '../../ui/screens/property/property_detail_screen.dart';
import '../../ui/screens/viewing_requests/viewing_requests_screen.dart';

/// المُلاحق الرئيسي للتطبيق — ضروري للتنقل من إشعار النظام وخارجه.
final rootNavigatorKey = GlobalKey<NavigatorState>();

/// دفع شاشة من جذر التطبيق (يصلح حتى قبل اكتمال الجلسة).
void pushFromRoot(Widget page) {
  rootNavigatorKey.currentState
      ?.push(MaterialPageRoute(builder: (_) => page));
}

/// النقر على إشعار → فتح المصدر الفعلي (المحادثة / الطلبات / العقار).
Future<void> openNotificationSource(
  BuildContext context,
  ProviderContainer container,
  LuxNotification item,
) => _openNotificationSource(Navigator.of(context), container, item);

Future<void> _openNotificationSource(
  NavigatorState navigator,
  ProviderContainer container,
  LuxNotification item,
) async {
  if (!item.isRead) {
    try {
      await container
          .read(notificationRepositoryProvider)
          .markNotificationRead(item.id);
      container.invalidate(notificationsProvider);
    } on Object {
      // القراءة تأكيدية؛ الفشل لا يمنع التنقل.
    }
  }

  if (item.isMessage) {
    final conversationId = item.conversationId;
    if (conversationId != null) {
      final conversation = await _findConversation(container, conversationId);
      if (conversation != null) {
        await navigator.push(
          MaterialPageRoute(
            builder: (_) => ChatScreen(conversation: conversation),
          ),
        );
        container.invalidate(conversationsProvider);
        return;
      }
    }
    // المحادثة غير متاحة — نكفي بفتح صفحة الرسائل.
    await navigator.push(
      MaterialPageRoute(
        builder: (_) => Scaffold(
          appBar: AppBar(title: const Text('الرسائل')),
          body: const MessagesScreen(),
        ),
      ),
    );
    return;
  }

  if (item.isViewingRequest) {
    await navigator.push(
      MaterialPageRoute(builder: (_) => const ViewingRequestsScreen()),
    );
    container.invalidate(viewingRequestsProvider);
    return;
  }

  if (item.propertyId != null) {
    await navigator.push(
      MaterialPageRoute(
        builder: (_) => PropertyDetailScreen(propertyId: item.propertyId!),
      ),
    );
    return;
  }

  await navigator.push(
    MaterialPageRoute(builder: (_) => const NotificationsScreen()),
  );
}

/// فتح المصدر من إشعار نظام حسب معرّف الإشعار فقط.
Future<void> openNotificationSourceById(
  ProviderContainer container,
  String notificationId, {
  BuildContext? context,
}) async {
  final navigator = context != null
      ? Navigator.of(context)
      : rootNavigatorKey.currentState;
  LuxNotification? item;
  try {
    final items = await container.refresh(notificationsProvider.future);
    for (final notification in items) {
      if (notification.id == notificationId) {
        item = notification;
        break;
      }
    }
  } on Object {
    // فشل التحقق لا يمنع الوصول لصفحة الإشعارات.
  }
  if (item == null) {
    pushFromRoot(const NotificationsScreen());
    return;
  }
  if (navigator == null) return;
  await _openNotificationSource(navigator, container, item);
}

Future<ConversationItem?> _findConversation(
  ProviderContainer container,
  int conversationId,
) async {
  try {
    // تحديث فوري — لا نعتمد على الكاش القديم حتى نجد المحادثة وفتحها
    // بمحتواها الحقيقي (المعالج أسفله يُحدّث الرسائل أيضًا عند فتحها).
    final conversations = await container.refresh(conversationsProvider.future);
    for (final conversation in conversations) {
      if (conversation.id == conversationId) return conversation;
    }
  } on Object {
    // قائمة المحادثات غير متاحة — نعود للمصدر العام.
  }
  return null;
}