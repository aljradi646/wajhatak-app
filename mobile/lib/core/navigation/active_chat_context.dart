/// Presentation-only context for the conversation currently visible in the UI.
/// The server remains the source of truth for message and notification read state.
class ActiveChatContext {
  ActiveChatContext._();

  static int? conversationId;
  static final Set<String> _shownNotificationIds = <String>{};

  static bool markNotificationShown(String id) {
    if (!_shownNotificationIds.add(id)) return false;
    if (_shownNotificationIds.length > 500) {
      _shownNotificationIds.remove(_shownNotificationIds.first);
    }
    return true;
  }
}
