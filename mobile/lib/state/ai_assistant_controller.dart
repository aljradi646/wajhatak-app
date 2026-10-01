import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/services/location_service.dart';
import '../data/api_client.dart';
import '../data/models/ai_assistant.dart';
import '../data/models/models.dart';
import '../data/repositories/repositories.dart';
import 'providers.dart';

/// مزود المستودع والموارد.
final aiAssistantRepositoryProvider = Provider<AiAssistantRepository>(
  (ref) => AiAssistantRepository(ref.watch(apiClientProvider)),
);

final aiBootstrapProvider = FutureProvider<AiBootstrap>((ref) async {
  // إعدادات واجهة المساعد عامة (لا تحتاج تسجيلًا) — مع سقوط آمن عند فشل الشبكة.
  try {
    return await ref.watch(aiAssistantRepositoryProvider).bootstrap();
  } on ApiFailure {
    return const AiBootstrap(
      enabled: false,
      assistantName: 'مساعد وجهتك',
      welcomeMessage:
          'المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.',
      suggestions: [],
    );
  }
});

/// حالة محادثة المساعد: رسائل مرتبة قديمًا → جديدًا + مؤشر انتظار.
class AiConversationState {
  const AiConversationState({
    this.messages = const [],
    this.loading = false,
    this.error,
    this.conversationId,
  });

  final List<AiChatMessage> messages;
  final bool loading;
  final String? error;
  final int? conversationId;

  bool get isEmpty => messages.isEmpty;

  AiConversationState copyWith({
    List<AiChatMessage>? messages,
    bool? loading,
    String? error,
    int? conversationId,
    bool clearError = false,
    bool clearConversation = false,
  }) {
    return AiConversationState(
      messages: messages ?? this.messages,
      loading: loading ?? this.loading,
      error: clearError ? null : (error ?? this.error),
      conversationId: clearConversation
          ? null
          : (conversationId ?? this.conversationId),
    );
  }
}

/// متحكم المحادثة — يرسل عبر المستودع ويبقي السياق (المعايير تُدار على
/// الخادم؛ هنا نحفظ فقط هوية المحادثة والرسائل).
class AiConversationController extends Notifier<AiConversationState> {
  @override
  AiConversationState build() => const AiConversationState();

  Future<void> send(String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty || state.loading) return;

    final userMessage = AiChatMessage.local(isUser: true, content: trimmed);
    state = state.copyWith(
      messages: [...state.messages, userMessage],
      loading: true,
      clearError: true,
    );

    try {
      // Get user location silently for nearby searches
      final location = await LocationService.silentPosition();

      final reply = await ref
          .read(aiAssistantRepositoryProvider)
          .sendMessage(
            trimmed,
            conversationId: state.conversationId,
            latitude: location.latitude,
            longitude: location.longitude,
          );
      state = state.copyWith(
        messages: [...state.messages, reply],
        loading: false,
        conversationId: state.conversationId ?? _resolvedConversation(reply),
      );
    } on ApiFailure catch (error) {
      // Fallback لطيف دائمًا — التطبيق لا ينكسر بدون AI.
      final fallback = AiChatMessage.local(
        isUser: false,
        content: error.statusCode == 503
            ? 'المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.'
            : 'تعذر وصول الطلب الآن. حاول مرة أخرى بعد قليل.',
        status: 'error',
      );
      state = state.copyWith(
        messages: [...state.messages, fallback],
        loading: false,
        error: error.message,
      );
    } on Object {
      final fallback = AiChatMessage.local(
        isUser: false,
        content:
            'المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.',
        status: 'error',
      );
      state = state.copyWith(
        messages: [...state.messages, fallback],
        loading: false,
        error: 'ai_unavailable',
      );
    }
  }

  /// إعادة إرسال آخر رسالة مستخدم فاشلة.
  Future<void> resendLast() async {
    final lastUser = state.messages.lastWhere(
      (m) => m.isUser,
      orElse: () => const AiChatMessage(id: 0, role: 'user', content: ''),
    );
    if (lastUser.content.isEmpty) return;
    // إزالة رسالة الخطوة الأخيرة الفاشلة ورسالة المستخدم ثم الإرسال من جديد.
    final messages = [...state.messages];
    if (messages.isNotEmpty && !messages.last.isUser) messages.removeLast();
    messages.removeLast();
    state = state.copyWith(messages: messages);
    await send(lastUser.content);
  }

  /// مسح المحادثة (محليًا؛ وعلى الخادم للمستخدم المسجل).
  Future<void> clear() async {
    final conversationId = state.conversationId;
    final session = ref.read(sessionProvider).asData?.value;
    if (conversationId != null && session != null) {
      try {
        await ref
            .read(aiAssistantRepositoryProvider)
            .clearConversation(conversationId);
      } on Object {
        // المسح المحلي يكفي إن فشل الخادم.
      }
    }
    ref.invalidate(aiBootstrapProvider);
    await _seedWelcome();
  }

  /// تهيئة رسالة الترحيب عند فتح المساعد.
  Future<void> ensureStarted() async {
    if (state.messages.isNotEmpty) return;
    await _seedWelcome();
  }

  /// تحميل محادثة موجودة من القائمة.
  Future<void> loadConversation(int conversationId) async {
    final session = ref.read(sessionProvider).asData?.value;
    if (session == null) return;

    state = state.copyWith(loading: true, clearError: true);
    try {
      final messages = await ref
          .read(aiAssistantRepositoryProvider)
          .getConversationMessages(conversationId);
      state = AiConversationState(
        messages: messages,
        loading: false,
        conversationId: conversationId,
      );
    } on ApiFailure catch (error) {
      state = state.copyWith(loading: false, error: error.message);
    } on Object {
      state = state.copyWith(loading: false, error: 'failed_to_load');
    }
  }

  Future<void> _seedWelcome() async {
    try {
      final bootstrap = await ref.read(aiBootstrapProvider.future);
      state = AiConversationState(
        messages: [
          AiChatMessage.local(isUser: false, content: bootstrap.welcomeMessage),
        ],
        conversationId: state.conversationId,
      );
    } on Object {
      state = const AiConversationState(
        messages: [
          AiChatMessage(id: 1, role: 'assistant', content: 'كيف أخدمك اليوم؟'),
        ],
      );
    }
  }

  int? _resolvedConversation(AiChatMessage reply) => state.conversationId;
}

final aiConversationProvider =
    NotifierProvider<AiConversationController, AiConversationState>(
      AiConversationController.new,
    );

/// قائمة محادثات المستخدم المسجل.
final aiConversationsListProvider = FutureProvider<List<AiConversationItem>>((
  ref,
) async {
  final session = ref.watch(sessionProvider).asData?.value;
  if (session == null) return const [];
  try {
    return await ref.read(aiAssistantRepositoryProvider).listConversations();
  } on ApiFailure {
    return const [];
  }
});
