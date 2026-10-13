# Wajhatak Realtime Service

This is a separate authenticated WebSocket fan-out service. Laravel remains the source of truth for messages, notifications, unread counts, and moderation decisions; WebSocket packets are invalidation hints, not durable data.

## Railway deployment

1. Create a separate Railway service from this repository.
2. Set its Dockerfile path to deploy/realtime/Dockerfile and enable public networking.
3. Set REALTIME_SHARED_SECRET to a random secret of at least 32 characters. Configure the same value in the Laravel app service.
4. Optionally set REALTIME_ALLOWED_ORIGINS to a comma-separated allowlist for browser clients. Native clients may not send an Origin header.
5. Configure Laravel:
   - REALTIME_PUBLISH_URL=https://<realtime-service-domain>/publish
   - REALTIME_SHARED_SECRET=<same secret>
6. Build the Flutter app with:
   - --dart-define=WAJHATAK_WS_URL=wss://<realtime-service-domain>/ws
7. Ensure the Laravel queue worker is running so PublishRealtimeEvent jobs are delivered. Monitor failed jobs and the realtime service logs.

GET /health is the health check. POST /publish requires an HMAC-SHA256 signature over <timestamp>.<raw-body> and rejects timestamps more than 30 seconds from server time. /ws requires a Laravel-issued signed ticket with a short expiry and a single-use nonce.

## Scaling limitation

This implementation keeps active sockets and consumed ticket nonces in process memory. Run one service replica until a shared replay-protection store and cross-replica pub/sub are implemented. Do not horizontally scale this service as-is, because a publisher request may reach a replica that does not own the recipient's WebSocket connection.

## Operational security

- Use a different secret per environment and rotate it if exposed.
- Never commit the secret or place it in a Flutter source file.
- Require HTTPS/WSS in production.
- Verify authenticated ticket issuance and message delivery in staging before enabling the Flutter build define.
