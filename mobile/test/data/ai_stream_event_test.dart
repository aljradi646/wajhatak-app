import 'package:flutter_test/flutter_test.dart';

import 'package:wajhatak/data/models/ai_assistant.dart';

void main() {
  test('SSE event parser keeps event data and delta', () {
    final event = AiStreamEvent.fromJson({
      'event': 'delta',
      'id': 7,
      'delta': 'نص',
      'data': {'session_token': 'guest-token'},
    });

    expect(event.event, 'delta');
    expect(event.id, 7);
    expect(event.delta, 'نص');
    expect(event.data?['session_token'], 'guest-token');
  });
}
