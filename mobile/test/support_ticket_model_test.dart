import 'package:flutter_test/flutter_test.dart';
import 'package:wajhatak/data/models/support_ticket.dart';

void main() {
  test('parses ticket identity, status and conversation messages from API JSON', () {
    final ticket = SupportTicket.fromJson({
      'id': 42,
      'reference': 'WJ-261011-A1B2C3',
      'subject': 'مشكلة في الحساب',
      'category': 'account',
      'status': 'waiting_user',
      'priority': 'normal',
      'created_at': '2026-10-11T10:00:00Z',
      'updated_at': '2026-10-11T11:00:00Z',
      'messages': [
        {
          'id': 1,
          'sender_role': 'user',
          'body': 'لا أستطيع تسجيل الدخول.',
          'created_at': '2026-10-11T10:00:00Z',
          'user': {'name': 'مستخدم تجريبي'},
        },
        {
          'id': 2,
          'sender_role': 'admin',
          'body': 'تمت مراجعة البلاغ.',
          'email_status': 'sent',
          'created_at': '2026-10-11T11:00:00Z',
          'user': {'name': 'الدعم'},
        },
      ],
    });

    expect(ticket.id, 42);
    expect(ticket.reference, 'WJ-261011-A1B2C3');
    expect(ticket.statusLabel, 'بانتظارك');
    expect(ticket.messages, hasLength(2));
    expect(ticket.messages.last.senderRole, 'admin');
    expect(ticket.messages.last.emailStatus, 'sent');
  });

  test('closed tickets cannot be replied to in the UI model', () {
    final ticket = SupportTicket.fromJson({
      'id': 7,
      'reference': 'WJ-261011-Z9Y8X7',
      'subject': 'طلب مساعدة',
      'category': 'other',
      'status': 'closed',
      'created_at': '2026-10-11T10:00:00Z',
      'updated_at': '2026-10-11T11:00:00Z',
      'messages': <dynamic>[],
    });

    expect(ticket.isClosed, isTrue);
    expect(ticket.messages, isEmpty);
  });
}
