import 'package:flutter_test/flutter_test.dart';

import 'package:wajhatak/state/ai_assistant_controller.dart';

void main() {
  test('send is disabled until bootstrap completes', () {
    expect(
      const AiConversationState(phase: AiSendPhase.bootstrapping).canSend,
      isFalse,
    );
    expect(
      const AiConversationState(phase: AiSendPhase.ready).canSend,
      isTrue,
    );
    expect(
      const AiConversationState(phase: AiSendPhase.streaming).canSend,
      isFalse,
    );
    expect(
      const AiConversationState(phase: AiSendPhase.failed).canSend,
      isTrue,
    );
  });
}
