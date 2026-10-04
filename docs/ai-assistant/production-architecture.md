# Wajhatak AI assistant — production architecture

## Decision path

1. Guardrails inspect the current message.
2. AiIntentRouter classifies the current turn before any property tool is exposed.
3. Search context is reused only for explicit conversational refinement/correction.
4. Non-property intents cannot inherit retrieved properties or call property tools.
5. Property discovery can use ai_search_index only to find candidates; every returned record is rehydrated from live properties data before reaching the API.
6. Specific property lookups read the live property record and can report its current non-published status without making it discoverable.
7. AiResponseContract removes property payloads from non-property response types.
8. Flutter renders cards from the message response_type and its own properties list; no global property result state is used.

## Source of truth

- Property price, status, availability, location, features, and media: live application database.
- Platform usage: versioned platform knowledge.
- User preferences: structured AI memory with confidence, source and optional expiry.
- Behavior examples and regression cases: evaluation dataset.

## Security

Tools are allowlisted by intent and their arguments are bounded. Passwords, tokens, database credentials, system prompts, and arbitrary SQL are never exposed to the model. Property descriptions and other database text are untrusted data.

## Observability

AI request logs store intent, response type, bounded tool metadata, latency, result count, fallback/error metadata, and knowledge version without storing secrets.
