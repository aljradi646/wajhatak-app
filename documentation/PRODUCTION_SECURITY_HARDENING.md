# Production security hardening — 2026-10-12

## Included in this branch

- The public web routes GET /register and POST /register are removed. The dashboard login page no longer offers self-registration. Mobile client/agent registration remains at POST /api/v1/auth/register and is intentionally separate from admin access.
- AdminUserSeeder no longer falls back to the known admin123 password. Bootstrap requires explicit ADMIN_EMAIL and ADMIN_PASSWORD server secrets. The password must be at least 12 characters and contain uppercase/lowercase letters, a number, and a symbol. If these values are absent or weak, the seeder logs a warning and skips creating/updating the bootstrap account.
- Failed web and API sign-ins record a SHA-256 hash of the submitted email, IP address, user agent, and attempt time; raw submitted email is not stored in failed_login_attempts.
- Admin login retains the existing login rate limit. Security response headers are set by the Laravel middleware.
- Optional TOTP can be enabled for an existing active administrator from the server CLI: php artisan admin:2fa-enable admin@example.com. The command prints the secret and provisioning URI. Add the account to an authenticator app and store the secret securely. The admin panel requires a valid six-digit TOTP after password authentication; verification expires after eight hours, and the challenge endpoint is rate-limited.
- Property approval/rejection records reviewer, timestamp, decision, and reason. Rejection requires a reason. A database notification is persisted synchronously; optional email is queued when the user's property-update preference is enabled.
- Opening a conversation marks its incoming messages and matching database notifications read. The app header uses a server-side unread count endpoint.
- Theme initialization reads browser preferences before CSS is loaded, and the sidebar scrolls the active route item into view.

## Deployment procedure

1. Set strong ADMIN_EMAIL and ADMIN_PASSWORD secrets in the production environment before bootstrapping a new database. Never commit those values.
2. Back up the database and run php artisan migrate --force.
3. For an existing admin account, enable TOTP only after the admin can access the server CLI. Store the secret in a password manager.
4. Configure FIREBASE_PROJECT_ID, FIREBASE_CLIENT_EMAIL, and FIREBASE_PRIVATE_KEY from a Firebase service account with Firebase Cloud Messaging permissions. Keep these as Railway secrets; never commit them. Ensure the existing queue worker is running for queued moderation email and push delivery.
5. Run php artisan test and flutter test, then verify admin login, /register returning 404, TOTP challenge, property moderation, and notification read counts against a staging database before deployment.

## Explicit remaining work

A queued server-side FCM HTTP v1 sender is implemented, but it is not operational until the Firebase service-account secrets are configured and delivery is verified in staging. This branch does not claim a production WebSocket transport: the current application still has polling-based refresh. True real-time delivery requires adding/configuring a compatible broadcast server and authenticated channel authorization; this must be verified in staging before disabling polling. Refresh-token rotation and TOTP recovery codes also require a dedicated implementation and test cycle.
