# Project Inventory — Wajhatak

الجرد الكامل لجميع مكونات المشروع مع الوظائف والتبعيات. مبنى على فحص الملفات الفعلي.

## Flutter Inventory

### Screens

| الاسم | المسار | الأسطر | الوظيفة | يعتمد على |
|-------|--------|--------|---------|-----------|
| SessionGate | ui/screens/shell/session_gate.dart | 84 | Splash + gate | sessionProvider, AppShell, WajhatakBrandMark |
| AppShell | ui/screens/shell/app_shell.dart | 241 | الهيكل الرئيسي | WajhatakBottomNavBar, AppDrawer, LuxNotificationService |
| HomeScreen | ui/screens/home/home_screen.dart | 383 | الصفحة الرئيسية | WajhatakHeader, PropertyCard, propertiesProvider |
| ExploreScreen | ui/screens/explore/explore_screen.dart | 320 | البحث/الفلاتر | propertySearchProvider, ExploreSearchNotifier, PropertyCard |
| SavedScreen | ui/screens/saved/saved_screen.dart | 139 | المفضلة | favoritesProvider, PropertyCard |
| MessagesScreen | ui/screens/messages/messages_screen.dart | 217 | قائمة المحادثات | conversationsProvider |
| AccountScreen | ui/screens/account/account_screen.dart | 262 | مركز المستخدم | sessionProvider |
| AuthScreen | ui/screens/auth/auth_screen.dart | 581 | تسجيل/دخول | SessionController, WajhatakBrandMark |
| PropertyDetailScreen | ui/screens/property/property_detail_screen.dart | 662 | تفاصيل العقار | propertyDetailProvider, FlutterMap |
| CreateListingScreen | ui/screens/listings/create_listing_screen.dart | 854 | إنشاء عقار | taxonomyProviders, PropertyRepository, image_picker |
| EditListingScreen | ui/screens/listings/edit_listing_screen.dart | 633 | تعديل عقار | propertyDetailProvider, PropertyRepository |
| AgentDashboardScreen | ui/screens/agent/agent_dashboard_screen.dart | 451 | لوحة الوكيل | myListingsProvider, viewingRequestsProvider |
| ChatScreen | ui/screens/chat/chat_screen.dart | 564 | المحادثة | messagesProvider, ChatMessagesNotifier |
| ProfileScreen | ui/screens/profile/profile_screen.dart | 357 | ملف شخصي | SessionController, image_picker |
| SettingsScreen | ui/screens/settings/settings_screen.dart | 235 | الإعدادات | themeModeProvider, appSettingsProvider |
| NotificationsScreen | ui/screens/notifications/notifications_screen.dart | 175 | الإشعارات | notificationsProvider, notification_navigation |
| ViewingRequestsScreen | ui/screens/viewing_requests/viewing_requests_screen.dart | 218 | طلبات المعاينة | viewingRequestsProvider |

### Widgets

| الاسم | المسار | الأسطر | الوظيفة | يعتمد على |
|-------|--------|--------|---------|-----------|
| PropertyCard | ui/widgets/property/property_card.dart | 510 | بطاقة عقار مع carousel | favoriteOverridesProvider, CachedNetworkImage, formatMoney |
| AppDrawer | ui/widgets/drawer/app_drawer.dart | 506 | القائمة الجانبية | confirm_logout, appearance_controller |
| WajhatakBottomNavBar | ui/widgets/navigation/bottom_nav_bar.dart | 247 | شريط تنقل سفلي | — |
| WajhatakHeader | ui/widgets/shared/app_header.dart | 329 | هيدر موحد | WajhatakBrandMark |
| LuxAsyncView | ui/widgets/skeleton/lux_skeleton.dart | 631 | Async wrapper + skeleton | EmptyState |
| EmptyState/ErrorState | ui/widgets/feedback/empty_state.dart | 121 | فارغ/خطأ | GradientIconBadge |
| WajhatakBrandMark | ui/brand.dart | 176 | الشعار المتحرك (CustomPainter) | — |
| GradientIconBadge | core/theme/icon_badges.dart | 255 | أيقونات متدرجة | WajhatakColors |

### Models

| الاسم | المسار | الأسطر | الوظيفة |
|-------|--------|--------|---------|
| LuxUser | data/models/user.dart | 57 | المستخدم (roles, isAgent) |
| SessionData | data/models/session_data.dart | 15 | المستخدم + token |
| LuxProperty | data/models/property.dart | 177 | العقار (40+ fields) |
| PropertyQuery | data/models/property_query.dart | 125 | معايير البحث |
| PropertyLocation | data/models/property_location.dart | 78 | الموقع |
| PropertyImage | data/models/property_image.dart | 35 | الصورة |
| PropertyAgent | data/models/property_agent.dart | 47 | الوكيل |
| LuxNotification | data/models/notification.dart | 58 | الإشعار |
| ChatMessage | data/models/message.dart | 95 | الرسالة |
| ConversationItem | data/models/conversation.dart | 62 | المحادثة |
| ViewingRequestItem | data/models/viewing_request.dart | 60 | طلب المعاينة |
| TaxonomyItem | data/models/taxonomy_item.dart | 27 | النوع/الميزة |
| LocationItem | data/models/location_item.dart | 40 | الموقع الهرمي |
| Currency | data/models/currency.dart | 51 | العملة |

### Repositories

| الاسم | المسار | الأسطر | الأساليب |
|-------|--------|--------|----------|
| AuthRepository | data/repositories/auth_repository.dart | 138 | restore, login, register, logout, updateProfile, uploadAvatar |
| PropertyRepository | data/repositories/property_repository.dart | 119 | list, detail, create, update, delete, favorites, setFavorite, mine |
| ConversationRepository | data/repositories/conversation_repository.dart | 41 | conversations, startConversation, messages, sendMessage |
| NotificationRepository | data/repositories/notification_repository.dart | 22 | notifications, markNotificationRead |
| ViewingRequestRepository | data/repositories/viewing_request_repository.dart | 46 | viewingRequests, requestViewing, updateStatus |
| TaxonomyRepository | data/repositories/taxonomy_repository.dart | 55 | propertyTypes, features, currencies, countries, regions, cities, areas |

### State

| الاسم | المسار | الوظيفة |
|-------|--------|---------|
| providers.dart | state/providers.dart | كل الـ Riverpod providers |
| SessionController | state/providers.dart | إدارة الجلسة |
| ChatMessagesNotifier | state/providers.dart | رسائل المحادثة |
| FavoriteOverrides | state/providers.dart | تحديث فوري للمفضلة |
| ExploreSearchNotifier | state/providers.dart | نص البحث |
| ThemeModeController | state/appearance_controller.dart | السمة |
| AppSettingsController | state/app_settings_controller.dart | إعدادات المستخدم |

### Core Services/Utils

| الاسم | المسار | الوظيفة |
|-------|--------|---------|
| AppConfig | core/config/app_config.dart | إعدادات التطبيق |
| LuxApiClient | data/api_client.dart | عميل HTTP + Dio + interceptors |
| TokenStore | data/api_client.dart | إدارة التوكن في SecureStorage |
| InfinityFreeChallengeSolver | core/services/infinityfree_challenge_solver.dart | حل تحدي AES |
| LuxNotificationService | core/services/lux_notification_service.dart | الإشعارات المحلية |
| ImageCompressor | core/utils/image_compressor.dart | ضغط الصور |
| formatMoney | core/utils/format_money.dart | تنسيق العملات |
| notice | core/utils/notice.dart | Snackbar |
| confirmLogout | core/utils/confirm_logout.dart | تأكيد الخروج |
| Responsive | core/utils/responsive.dart | تكيّف الشاشة |
| WajhatakColors/buildWajhatakTheme | core/theme/app_theme.dart | السمة |

---

## Laravel Inventory

### Controllers (API V1)

| الاسم | المسار | الأساليب | يعتمد على |
|-------|--------|----------|-----------|
| AuthController | Http/Controllers/Api/V1/AuthController.php | register, login, logout | RegisterRequest, LoginRequest, UserResource, User, Agent |
| PropertyController | Http/Controllers/Api/V1/PropertyController.php | index, show, mine, store, uploadImage, destroyImage, setCover, update, destroy | StorePropertyRequest, UpdatePropertyRequest, PropertyResource, Property, PropertyLocation |
| MeController | Http/Controllers/Api/V1/MeController.php | show, update, uploadAvatar | UserResource |
| AgentController | Http/Controllers/Api/V1/AgentController.php | index, show | AgentResource, PropertyResource |
| FavoriteController | Http/Controllers/Api/V1/FavoriteController.php | index, store, destroy | PropertyResource, Favorite, Property |
| ConversationController | Http/Controllers/Api/V1/ConversationController.php | index, store, messages, sendMessage | ConversationResource, MessageResource, Conversation, Message |
| ViewingRequestController | Http/Controllers/Api/V1/ViewingRequestController.php | index, store, update | ViewingRequestResource, ViewingRequest, Property |
| TaxonomyController | Http/Controllers/Api/V1/TaxonomyController.php | propertyTypes, features, countries, regions, cities, areas, currencies | PropertyType, PropertyFeature, Country, Region, City, Area |
| NotificationController | Http/Controllers/Api/V1/NotificationController.php | index, read | — |
| NotificationPreferenceController | Http/Controllers/Api/V1/NotificationPreferenceController.php | show, update | UserNotificationPreference |
| DeviceController | Http/Controllers/Api/V1/DeviceController.php | store, destroy | UserDevice |

### Models

| الاسم | Table | العلاقات | يميزه |
|-------|-------|-----------|-------|
| User | users | agentProfile(h1), favorites(hm), clientViewingRequests(hm), sentMessages(hm), notificationPreference(h1), devices(hm) | HasApiTokens, HasRoles, Notifiable |
| Property | properties | agent(bt), type(bt), location(bt), images(hm), features(btm), favorites(hm), viewingRequests(hm), conversations(hm) | SoftDeletes |
| Agent | agents | user(bt), properties(hm), viewingRequests(hm) | — |
| PropertyImage | property_images | property(bt) | accessor image_url, is_primary |
| PropertyType | property_types | properties(hm) | — |
| PropertyFeature | property_features | properties(btm) | — |
| PropertyLocation | property_locations | country(bt), region(bt), cityReference(bt), area(bt), properties(hm) | — |
| Conversation | conversations | property(bt), client(bt), agent(bt), messages(hm) | لو بالclient_id/agent_id |
| Message | messages | conversation(bt), sender(bt), property(bt) | message_type, property_id |
| ViewingRequest | viewing_requests | property(bt), client(bt), agent(bt) | status enum |
| Favorite | favorites | user(bt), property(bt) | — |
| Country/Region/City/Area | countries/regions/... | hierarchy | — |
| Setting | settings | — | get/put/seedDefaults |
| ActivityLog | activity_logs | user(bt), subject(morphTo) | record() |
| UserNotificationPreference | user_notification_preferences | user(bt) | booleans |
| UserDevice | user_devices | user(bt) | push_token |

### Middleware

| الاسم | الوظيفة |
|-------|---------|
| EnsureUserIsAdmin | admin role + is_active + session fixation |
| AdminUrlGuard | URL manipulation + security headers |
| InjectSanctumToken | X-Auth-Token → Authorization |

### Form Requests

| الاسم | التحقق |
|-------|--------|
| RegisterRequest | name, email rfc/dns unique, password min 10 mixedCase numbers, account_type |
| LoginRequest | email, password + rate limit 5 |
| StorePropertyRequest | title, description, type, transaction, price, currency, location, images |
| UpdatePropertyRequest | إلا الكل 'sometimes' |
| ProfileUpdateRequest | name, email unique ignore self |

### API Resources

| الاسم | المخرجات |
|-------|----------|
| UserResource | id, name, email, phone, avatar_url, locale, roles, capabilities |
| PropertyResource | full + type, location, agent, images, features, is_favorited |
| AgentResource | id, name, email, phone, avatar, bio, rating, reviews_count, properties_count |
| ConversationResource | id, property, client, agent, unread_count, last_message_at, last_message |
| MessageResource | id, body, sender_id, message_type, property_id, property, read_at, created_at |
| ViewingRequestResource | id, property, client, agent, date, time, notes, status, created_at |

### Notifications

| الاسم | Event | kind |
|-------|-------|------|
| MessageReceived | رسالة جديدة | message_received |
| ViewingRequestCreated | طلب معاينة جديد | viewing_request_created |
| ViewingRequestUpdated | تحديث طلب | viewing_request_updated |

### Policies

| الاسم | Rules |
|-------|-------|
| PropertyPolicy | view (published/admin/owner), create (agent/admin), update (admin/owner), delete (admin/owner) |

---

## Database Tables

| الجدول | PK | FK | مميز |
|--------|-----|-----|------|
| users | id | — | phone unique, avatar_path, locale, is_active |
| agents | id | user_id → users | license_number unique |
| properties | id | agent_id→agents, property_type_id→types, property_location_id→locations | softDeletes, slug/ref unique |
| property_types | id | — | — |
| property_features | id | — | — |
| property_feature | (property_id, feature_id) | both | pivot |
| property_locations | id | country/region/city/area | flat+ref |
| property_images | id | property_id | sort_order, is_cover |
| favorites | id | user_id, property_id | unique(user,property) |
| conversations | id | property_id, client_id, agent_id | unique(client,agent) |
| messages | id | conversation_id, sender_id, property_id | message_type |
| viewing_requests | id | property_id, client_id, agent_id | status enum |
| countries/regions/cities/areas | id | hierarchy | — |
| settings | id | — | key unique |
| activity_logs | id | user_id, (morph) | — |
| notifications | uuid | notifiable morph | — |
| user_notification_preferences | id | user_id | booleans |
| user_devices | id | user_id | push_token |

---

## Packages

### Flutter (pubspec.yaml)

| الحزمة | الإصدار |
|--------|---------|
| flutter_riverpod | ^3.0.3 |
| dio | ^5.8.0+1 |
| flutter_secure_storage | ^10.0.0 |
| cached_network_image | ^3.4.1 |
| image_picker | ^1.2.1 |
| image | ^4.5.4 |
| flutter_map | ^8.1.1 |
| latlong2 | ^0.9.1 |
| flutter_local_notifications | ^18.0.1 |
| intl | ^0.20.2 |
| encrypt | ^5.0.3 |
| shared_preferences | ^2.5.3 |

### Laravel (composer.json)

| الحزمة | الإصدار |
|--------|---------|
| php | ^8.2 |
| laravel/framework | ^12.0 |
| laravel/sanctum | ^4.0 |
| spatie/laravel-permission | ^8.3 |
| laravel/tinker | ^2.10.1 |
