/// Presentation-only context for the conversation currently visible in the UI.
/// The server remains the source of truth for message and notification read state.
class ActiveChatContext {
  ActiveChatContext._();

  static int? conversationId;
}
