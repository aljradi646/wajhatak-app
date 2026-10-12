# Support, public pages, and agent-role operations

## Scope implemented on this branch

- The public information site is served independently from the admin area at `/pages` and `/pages/{slug}`. Public API consumers use `/api/v1/pages` and `/api/v1/pages/{slug}`; the Flutter account tab loads that catalog from the API.
- The existing database-backed legal pages cover privacy, terms, about, usage guide, contact, usage policy, cancellation/refund, cookies, and FAQ. The new migration inserts missing entries only; it does not overwrite pages already customized by an administrator.
- Support tickets are stored in `support_tickets` and `support_ticket_messages`. Reply notifications are persisted through Laravel notifications; push delivery is best-effort and does not block email delivery. The email status of an admin reply is stored on the message.
- Evidence attachments are stored in `support_ticket_attachments` and never exposed through a public storage URL. Downloads require authentication and are scoped to the ticket owner or the admin area. Uploads are limited to five files, 10 MiB per file, and JPG/PNG/WEBP/PDF/TXT.
- The admin ticket list filters by status, category, date range, and search. Statuses include open/new, in progress, waiting for user, resolved, closed, and rejected.
- The public agent API now requires both an active agent profile and the `agent` role. User role updates retain the user's account and related data. A role-change event is persisted as a notification and sent through FCM when configured; the app refreshes the current user and roles without forcing a sign-out. A five-second session poll remains as a fallback.

## Deployment

1. Deploy the code and run `php artisan migrate --force` against the intended production database.
2. Keep `SUPPORT_TICKET_DISK=local` only when the deployed storage path is persistent and backed up. For ephemeral container filesystems, configure a private S3-compatible Laravel disk and set `SUPPORT_TICKET_DISK` to that disk name. Do not point this setting to a public disk.
3. Confirm the existing mail transport and sender identity in the production environment. Admin replies show a failed status if the provider rejects delivery; credentials and sender-domain verification cannot be inferred from code.
4. Confirm Firebase Cloud Messaging credentials and test both an in-app notification and a device push. A durable database notification remains available if push delivery fails.
5. Test attachment download as the ticket owner, another user, and an admin. Only the owner and authorized admin should succeed.
6. Review privacy, terms, cancellation/refund, cookies, and usage wording with qualified counsel for the jurisdictions in which the platform operates. The pages are real, editable platform content, but this change does not constitute legal approval.

## Verification

The pull request runs the backend feature tests and Flutter formatting/analyzer/tests. Production SMTP/FCM credentials, persistent/object storage, and the production database migration must still be verified in the target deployment; CI cannot prove those external services are configured.
