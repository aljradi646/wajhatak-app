import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/services/location_service.dart';
import '../data/api_client.dart';
import '../data/models/ai_assistant.dart';
import '../data/repositories/repositories.dart';
import 'providers.dart';

String buildAiWelcomeMessage(String? fullName, {DateTime? now}) {
  final full = (fullName ?? '').trim();
  final firstName = full.isEmpty ? '' : full.split(RegExp(r'\s+')).first;
  final suffix = firstName.isEmpty ? '' : '، $firstName';
  final hour = (now ?? DateTime.now()).hour;
  if (hour >= 5 && hour < 12) {
    return 'صباح الخير$suffix 👋\n'
        'أنا مساعد وجهتك الذكي. أخبرني عمّا تبحث عنه، وسأساعدك في العثور على العقار المناسب.';
  }
  if (hour >= 12 && hour < 17) {
    return 'أهلًا$suffix، أتمنى لك يومًا طيبًا 🌿\n'
        'ما نوع العقار الذي تبحث عنه اليوم؟ يمكنني مساعدتك في تضييق الخيارات.';
  }
  return 'مساء الخير$suffix 🌙\n'
      'أنا هنا لمساعدتك في العثور على عقار يناسب احتياجك وميزانيتك.';
}

final aiAssistantRepositoryProvider = Provider<AiAssistantRepository>(
  (ref) => AiAssistantRepository(ref.watch(apiClientProvider)),
);

final aiBootstrapProvider = FutureProvider<AiBootstrap>((ref) async {
  return ref.watch(aiAssistantRepositoryProvider).bootstrap();
});

enum AiSendPhase {
  bootstrapping,
  ready,
  sending,
  streaming,
  completed,
  failed,
  cancelled,
  disabled,
}

class AiConversationState {
  const AiConversationState({
    this.messages = const [],
    this.loading = false,
    this.error,
    this.conversationId,
    this.phase = AiSendPhase.bootstrapping,
  });

  final List<AiChatMessage> messages;
  final bool loading;
  final String? error;
  final int? conversationId;
  final AiSendPhase phase;

  bool get isEmpty => messages.isEmpty;
  bool get canSend =>
      phase == AiSendPhase.bootstrapping ||
      phase == AiSendPhase.ready ||
      phase == AiSendPhase.completed ||
      phase == AiSendPhase.failed ||
      phase == AiSendPhase.cancelled;

  AiConversationState copyWith({
    List<AiChatMessage>? messages,
    bool? loading,
    String? error,
    int? conversationId,
    AiSendPhase? phase,
    bool clearError = false,
    bool clearConversation = false,
  }) => AiConversationState(
    messages: messages ?? this.messages,
    loading: loading ?? this.loading,
    error: clearError ? null : (error ?? this.error),
    conversationId: clearConversation
        ? null
        : (conversationId ?? this.conversationId),
    phase: phase ?? this.phase,
  );
}

class AiConversationController extends Notifier<AiConversationState> {
  @override
  AiConversationState build() => const AiConversationState();

  CancelToken? _cancelToken;
  int _generation = 0;

  Future<void> ensureReady() async {
    if (state.phase != AiSendPhase.bootstrapping) return;
    final generation = _generation;
    if (state.messages.isEmpty) {
      final user = ref.read(sessionProvider).asData?.value?.user;
      state = state.copyWith(
        messages: [AiChatMessage.local(isUser: false, content: buildAiWelcomeMessage(user?.name))],
        loading: false,
        clearError: true,
      );
    }
    try {
      final bootstrap = await ref.read(aiBootstrapProvider.future);
      if (generation != _generation || state.phase != AiSendPhase.bootstrapping) return;
      state = state.copyWith(
        loading: false,
        phase: bootstrap.enabled ? AiSendPhase.ready : AiSendPhase.disabled,
        clearError: true,
      );
    } on ApiFailure catch (error) {
      if (generation != _generation || state.phase != AiSendPhase.bootstrapping) return;
      state = state.copyWith(loading: false, phase: AiSendPhase.failed, error: error.message);
    } on Object {
      if (generation != _generation || state.phase != AiSendPhase.bootstrapping) return;
      state = state.copyWith(loading: false, phase: AiSendPhase.failed, error: 'bootstrap_failed');
    }
  }

  Future<void> send(String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty || !state.canSend) return;

    final generation = ++_generation;
    state = state.copyWith(
      messages: [
        ...state.messages,
        AiChatMessage.local(isUser: true, content: trimmed),
      ],
      loading: true,
      phase: AiSendPhase.sending,
      clearError: true,
    );

    try {
      double? latitude;
      double? longitude;
      if (RegExp(
        r'(قريب(?:ة)?|بالقرب|أقرب|الاقرب|الأقرب|حول(?:ي|ك)|بجانبي|بجواري|موقعي|near(?:by)?|closest|nearest)',
        caseSensitive: false,
      ).hasMatch(trimmed)) {
        try {
          final location = await LocationService.requestAndLocate();
          latitude = location.latitude;
          longitude = location.longitude;
        } catch (_) {
          // لا تمنع فشل خدمة الموقع إرسال رسالة المستخدم؛ سيطلب الخادم الموقع عند الحاجة.
        }
      }

      final cancel = CancelToken();
      _cancelToken = cancel;

      await for (final event in ref
          .read(aiAssistantRepositoryProvider)
          .streamMessage(
            trimmed,
            conversationId: state.conversationId,
            latitude: latitude,
            longitude: longitude,
            cancelToken: cancel,
          )) {
        if (generation != _generation || cancel.isCancelled) return;

        if (event.event == 'delta') {
          final delta = event.delta ?? '';
          if (delta.isEmpty) continue;
          final messages = [...state.messages];
          if (messages.isNotEmpty &&
              !messages.last.isUser &&
              messages.last.status == 'streaming') {
            messages[messages.length - 1] = messages.last.copyWith(
              content: messages.last.content + delta,
            );
          } else {
            messages.add(
              AiChatMessage.local(
                isUser: false,
                content: delta,
                status: 'streaming',
              ),
            );
          }
          state = state.copyWith(
            messages: messages,
            loading: true,
            phase: AiSendPhase.streaming,
          );
        } else if (event.event == 'done') {
          final result = event.data ?? const <String, dynamic>{};
          final finalMessage = AiChatMessage(
            id: (result['message_id'] as num?)?.toInt() ??
                DateTime.now().microsecondsSinceEpoch,
            role: 'assistant',
            content: result['reply']?.toString() ?? '',
            properties: (result['properties'] as List<dynamic>? ?? const [])
                .whereType<Map<String, dynamic>>()
                .map(AiPropertyResult.fromJson)
                .toList(growable: false),
            status: result['status']?.toString() ?? 'ok',
            responseType: result['response_type']?.toString() ?? 'text',
            actions: (result['actions'] as List<dynamic>? ?? const [])
                .whereType<Map<String, dynamic>>()
                .toList(growable: false),
            createdAt: DateTime.now(),
          );

          final messages = [...state.messages];
          if (messages.isNotEmpty &&
              !messages.last.isUser &&
              messages.last.status == 'streaming') {
            messages[messages.length - 1] = finalMessage;
          } else {
            messages.add(finalMessage);
          }

          state = state.copyWith(
            messages: messages,
            loading: false,
            phase: AiSendPhase.completed,
            conversationId: state.conversationId ??
                (result['conversation_id'] as num?)?.toInt(),
          );
        } else if (event.event == 'error') {
          throw ApiFailure(
            event.message ?? 'تعذر إكمال بث المساعد.',
            statusCode: event.statusCode,
          );
        }
      }

      if (generation == _generation &&
          state.phase != AiSendPhase.completed &&
          state.phase != AiSendPhase.cancelled) {
        _fail('انقطع بث المساعد قبل اكتمال الرد.', 'stream_ended_early');
      }
    } on ApiFailure catch (error) {
      if (generation == _generation && !(_cancelToken?.isCancelled ?? false)) {
        _fail(
          error.statusCode == 503
              ? 'المساعد غير متاح حاليًا.'
              : 'تعذر وصول الرد. حاول مرة أخرى.',
          error.message,
        );
      }
    } on Object {
      if (generation == _generation && !(_cancelToken?.isCancelled ?? false)) {
        _fail('تعذر إكمال الطلب. حاول مرة أخرى بعد قليل.');
      }
    } finally {
      if (generation == _generation) _cancelToken = null;
    }
  }

  void _fail(String message, [String? error]) {
    final messages = [...state.messages];
    if (messages.isNotEmpty &&
        !messages.last.isUser &&
        messages.last.status == 'streaming') {
      messages.removeLast();
    }
    messages.add(
      AiChatMessage.local(isUser: false, content: message, status: 'error'),
    );
    state = state.copyWith(
      messages: messages,
      loading: false,
      phase: AiSendPhase.failed,
      error: error ?? message,
    );
  }

  void cancel() {
    _generation++;
    _cancelToken?.cancel('user_cancelled');
    _cancelToken = null;
    state = state.copyWith(loading: false, phase: AiSendPhase.cancelled);
  }

  Future<void> resendLast() async {
    AiChatMessage? lastUser;
    for (final message in state.messages.reversed) {
      if (message.isUser) {
        lastUser = message;
        break;
      }
    }
    if (lastUser == null || lastUser.content.trim().isEmpty || !state.canSend) {
      return;
    }

    final messages = [...state.messages];
    if (messages.isNotEmpty && !messages.last.isUser) messages.removeLast();
    if (messages.isNotEmpty && messages.last.isUser) messages.removeLast();
    state = state.copyWith(
      messages: messages,
      phase: AiSendPhase.completed,
      loading: false,
    );
    await send(lastUser.content);
  }

  Future<void> newConversation() async {
    cancel();
    final user = ref.read(sessionProvider).asData?.value?.user;
    state = AiConversationState(
      messages: [AiChatMessage.local(isUser: false, content: buildAiWelcomeMessage(user?.name))],
      phase: AiSendPhase.bootstrapping,
    );
    // Do not wait for a network round-trip; the first streamed message creates
    // the saved conversation and returns its authoritative server ID.
    unawaited(ensureReady());
  }

  Future<void> clear() async {
    final id = state.conversationId;
    final signedIn = ref.read(sessionProvider).asData?.value != null;
    cancel();
    ref.invalidate(aiBootstrapProvider);
    final user = ref.read(sessionProvider).asData?.value?.user;
    state = AiConversationState(
      messages: [AiChatMessage.local(isUser: false, content: buildAiWelcomeMessage(user?.name))],
      phase: AiSendPhase.bootstrapping,
    );
    if (id != null && signedIn) unawaited(_clearConversationSilently(id));
    await ensureReady();
  }

  Future<void> _clearConversationSilently(int id) async {
    try {
      await ref.read(aiAssistantRepositoryProvider).clearConversation(id);
    } on Object {
      // Local reset remains available if remote cleanup fails.
    }
  }

  Future<void> loadConversation(int conversationId) async {
    final session = ref.read(sessionProvider).asData?.value;
    if (session == null) return;
    state = state.copyWith(loading: true, phase: AiSendPhase.sending, clearError: true);
    try {
      final messages = await ref
          .read(aiAssistantRepositoryProvider)
          .getConversationMessages(conversationId);
      state = AiConversationState(
        messages: messages,
        loading: false,
        conversationId: conversationId,
        phase: AiSendPhase.completed,
      );
    } on ApiFailure catch (error) {
      state = state.copyWith(
        loading: false,
        phase: AiSendPhase.failed,
        error: error.message,
      );
    } on Object {
      state = state.copyWith(
        loading: false,
        phase: AiSendPhase.failed,
        error: 'failed_to_load',
      );
    }
  }
}

final aiConversationProvider =
    NotifierProvider<AiConversationController, AiConversationState>(
  AiConversationController.new,
);

final aiConversationsListProvider =
    FutureProvider<List<AiConversationItem>>((ref) async {
  final session = ref.watch(sessionProvider).asData?.value;
  if (session == null) return const [];
  try {
    return await ref.read(aiAssistantRepositoryProvider).listConversations();
  } on ApiFailure {
    return const [];
  }
});
