# Flutter Refresh and Screen-State Policy

## Rules

- A skeleton is reserved for the first load, or an explicit retry when no usable data exists.
- Background refreshes keep the last successful value visible (stale-while-revalidate) and show a thin progress indicator where applicable.
- BackgroundRefreshController<T> performs in-place refreshes for conversations, notifications, unread count, viewing requests, and the agent's listings. Failed background refreshes do not erase usable data.
- Authenticated data providers depend on sessionUserIdProvider, not the entire session object. Refreshing a profile or avatar therefore does not reload every data screen. Login/logout and account changes still update the dependency.
- App-wide periodic invalidation is intentionally removed. Push and WebSocket events trigger targeted refreshes. While a conversation is open and WebSocket is unavailable, only that conversation uses a six-second fallback poll; the conversation list is refreshed in place.
- Explicit mutations (create/edit/delete property, read notification, send message, update viewing request) refresh only affected providers. They do not reset unrelated screens.
- AppShell uses IndexedStack for tab state. Scrollable widgets should retain stable identity and keys when changing this policy.

## Realtime configuration

The mobile WebSocket client is opt-in via WAJHATAK_WS_URL; the Laravel API issues a short-lived, single-use signed ticket. The realtime service and Laravel publisher must share REALTIME_SHARED_SECRET. Until the realtime service is deployed and these variables are configured, push notifications and the active-chat polling fallback remain the available delivery paths.

## Verification

mobile/test/ui/widgets/skeleton/lux_skeleton_refresh_test.dart verifies that both LuxAsyncView and SliverAsyncView keep the last successful content visible while the supplied AsyncValue enters loading again.
