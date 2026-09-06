# Real Estate Application — Complete Technical Documentation & Defense Guide

## Flutter + Laravel

---

# الفهرس

1. [Project Overview](#1-project-overview)
2. [System Requirements](#2-system-requirements)
3. [Features](#3-features)
4. [Users & Roles](#4-users--roles)
5. [Architecture](#5-architecture)
6. [Flutter Architecture](#6-flutter-architecture)
7. [Laravel Architecture](#7-laravel-architecture)
8. [Complete Project Structure](#8-complete-project-structure)
9. [UI Screens Documentation](#9-ui-screens-documentation)
10. [Widget Documentation](#10-widget-documentation)
11. [UI Layout Mapping](#11-ui-layout-mapping)
12. [Navigation](#12-navigation)
13. [State Management](#13-state-management)
14. [API Documentation](#14-api-documentation)
15. [Authentication](#15-authentication)
16. [Authorization](#16-authorization)
17. [Database](#17-database)
18. [Eloquent Relationships](#18-eloquent-relationships)
19. [Property Management](#19-property-management)
20. [Search & Filtering](#20-search--filtering)
21. [Favorites](#21-favorites)
22. [Image Upload](#22-image-upload)
23. [User Profile](#23-user-profile)
24. [End-to-End Workflows](#24-end-to-end-workflows)
25. [Important Code](#25-important-code)
26. [Important Classes](#26-important-classes)
27. [Important Methods](#27-important-methods)
28. [Packages & Libraries](#28-packages--libraries)
29. [Security](#29-security)
30. [Validation](#30-validation)
31. [Error Handling](#31-error-handling)
32. [Performance](#32-performance)
33. [Technical Glossary](#33-technical-glossary)
34. [Doctor Questions](#34-doctor-questions)
35. [Trick Questions](#35-trick-questions)
36. [Presentation Script](#36-presentation-script)
37. [Defense Checklist](#37-defense-checklist)

---

# 1. Project Overview

## ما هو التطبيق؟

**وجهتك (Wajhatak)** — تطبيق عقارات عربي أول (Arabic-first) يربط بين المشترين/المستأجرين والوكلاء العقاريين في اليمن والسعودية.

## المشكلة التي يحلها

السوق العقاري في اليمن والسعودية يعتمد بشكل تقليدي على الإعلانات المطبوعة وال word-of-mouth. لا يوجد منصة رقمية موحدة تجمع العقارات من وكلاء مختلفين مع إمكانية البحث والفلترة والتواصل المباشر.

## المستخدمون

| الدور | الوصف | الصلاحيات |
|-------|-------|-----------|
| **زائر (Guest)** | مستخدم لم يسجل دخوله | تصفح العقارات، البحث، عرض التفاصيل |
| **عميل (User/Client)** | مستخدم مسجل | الزائر + المفضلة، طلبات المعاينة، المحادثات، تعديل الملف الشخصي |
| **وكيل (Agent)** | صاحب عقار أو وسيط عقاري | العميل + إنشاء/تعديل/حذف العقارات، لوحة تحكم الوكيل |
| **مدير (Admin)** | مدير النظام | إدارة كاملة: المستخدمين، العقارات، الطلبات، الإعدادات، سجل النشاطات |

## العمليات الأساسية

1. تصفح العقارات (للبيع / للإيجار)
2. البحث والفلترة (by city, price, type, bedrooms, etc.)
3. عرض تفاصيل العقار مع صور وموقع على الخريطة
4. إضافة/إزالة من المفضلة
5. إنشاء محادثة مع الوكيل
6. طلب معاينة عقار
7. إنشاء/تعديل/حذف عقارات (للوكيلين)
8. إدارة المستخدمين والعقارات (للمدير)
9. إعدادات التطبيق (السمة، الإشعارات)

---

# 2. System Requirements

## Backend
- **PHP:** ^8.2
- **Laravel:** ^12.0
- **Database:** MySQL/MariaDB (الإنتاج) أو SQLite (التطوير المحلي)
- **Server:** InfinityFree (المشاركة) أو Railway (الإنتاج)
- **Queue:** Database driver
- **Cache:** Database driver
- **Session:** Database driver

## Frontend (Mobile)
- **Flutter:** SDK ^3.11.1
- **Dart:** SDK ^3.11.1
- **Android:** minSdk 24, compileSdk 36, targetSdk 36
- **iOS:** FlutterSceneDelegate
- **Font:** Cairo Variable (Arabic RTL)

---

# 3. Features

| # | الميزة | الحالة | الوصف |
|---|--------|--------|-------|
| 1 | عرض العقارات | مُنفَّذ | شبكة عقارات مع صور وأسعار ومواقع |
| 2 | البحث | مُنفَّذ | بحث نصي + فلترة متعددة المعايير |
| 3 | تفاصيل العقار | مُنفَّذ | صور + وصف + ميزات + وكيل + خريطة |
| 4 | تسجيل الدخول/التسجيل | مُنفَّذ | حساب عميل/وكيل مع email + password |
| 5 | المفضلة | مُنفَّذ | إضافة/إزالة مع تحديث فوري |
| 6 | المحادثات | مُنفَّذ | رسالة 1:1 بين عميل ووكيل + بطاقات عقارات |
| 7 | طلبات المعاينة | مُنفَّذ | طلب موعد مع تاريخ/وقت/ملاحظات |
| 8 | إدارة العقارات | مُنفَّذ | إنشاء/تعديل/حذف مع صور متعددة |
| 9 | الإشعارات | مُنفَّذ | إشعارات داخل التطبيق + إشعارات محلية |
| 10 | لوحة تحكم الوكيل | مُنفَّذ | إحصائيات + إدارة العقارات |
| 11 | الملف الشخصي | مُنفَّذ | تعديل الاسم/الهاتف/الصورة |
| 12 | خريطة العقارات | مُنفَّذ | عرض العقارات على خريطة OpenStreetMap |
| 13 | السمة (فاتح/داكن) | مُنفَّذ | تبديل مع حفظ محلي |
| 14 | لوحة تحكم المدير | مُنفَّذ | إدارة كاملة عبر Blade |
| 15 | نظام الأدوار | مُنفَّذ | Spatie Permission مع 3 أدوار و10 صلاحيات |
| 16 | العملة | مُنفَّذ | YER/SAR/USD data-driven |
| 17 | المواقع | مُنفَّذ | هرمي: Country > Region > City > Area |
| 18 | UI Preview Mode | مُنفَّذ | بيانات تجريبية للعرض بدون خادم |

---

# 4. Users & Roles

## الأدوار الحقيقية في النظام

```
UserRole enum:
  Admin = 'admin'
  Agent = 'agent'
  User = 'user'
```

## الصلاحيات (10 permissions)

```
manage_users          → Admin only
manage_agents         → Admin only
manage_properties     → Admin only
approve_properties    → Admin only
manage_viewing_requests → Admin only
manage_settings       → Admin only
view_reports          → Admin only
create_properties     → Agent + Admin
edit_own_properties   → Agent + Admin
view_incoming_requests → Agent + Admin
```

## الفرق بين الأدوار

| الزائر | العميل | الوكيل | المدير |
|--------|--------|--------|--------|
| تصفح فقط | + مفضلة | + إنشاء عقار | + إدارة شاملة |
| لا Auth | + محادثات | + تعديل عقاره | + حذف/استعادة |
| لا حفظ | + معاينات | + لوحة تحكم | + سجل النشاطات |

---

# 5. Architecture

## المعمارية العامة

```
┌─────────────────────────────────────────┐
│           Flutter Mobile App            │
│  ┌───────────────────────────────────┐  │
│  │      Presentation Layer           │  │
│  │  Screens → Widgets → Components   │  │
│  └──────────────┬────────────────────┘  │
│                 │                       │
│  ┌──────────────▼────────────────────┐  │
│  │      State Management             │  │
│  │      (Riverpod)                   │  │
│  └──────────────┬────────────────────┘  │
│                 │                       │
│  ┌──────────────▼────────────────────┐  │
│  │      Data Layer                   │  │
│  │  Repositories → ApiClient (Dio)   │  │
│  └──────────────┬────────────────────┘  │
└─────────────────┼───────────────────────┘
                  │ HTTP/JSON + Token
┌─────────────────▼───────────────────────┐
│         Laravel Backend API             │
│  ┌───────────────────────────────────┐  │
│  │  Routes → Middleware → Controller │  │
│  └──────────────┬────────────────────┘  │
│                 │                       │
│  ┌──────────────▼────────────────────┐  │
│  │  Request Validation (FormRequest) │  │
│  └──────────────┬────────────────────┘  │
│                 │                       │
│  ┌──────────────▼────────────────────┐  │
│  │  Eloquent Models → Database       │  │
│  └──────────────┬────────────────────┘  │
│                 │                       │
│  ┌──────────────▼────────────────────┐  │
│  │  API Resources (JSON Response)    │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

## أنماط المعمارية المُستخدمة

1. **Repository Pattern** — في Flutter (data/repositories/)
2. **MVC** — في Laravel (Models, Controllers, Resources)
3. **Form Request Validation** — في Laravel
4. **API Resources** — في Laravel (JSON transformation)
5. **Service Layer** — في Flutter (ApiClient + Repositories)
6. **Observer Pattern** — Riverpod Notifiers
7. **Policy Pattern** — Laravel (PropertyPolicy)

---

# 6. Flutter Architecture

## طبقات Flutter

```
lib/
├── main.dart                    ← Entry Point
├── core/                        ← Foundation Layer
│   ├── config/                  ← AppConfig (API URL, timeouts)
│   ├── constants/               ← (فارغ حالياً)
│   ├── navigation/              ← Global navigator + notification routing
│   ├── services/                ← InfinityFree solver + notifications
│   ├── theme/                   ← WajhatakColors + ThemeData + skeletons
│   └── utils/                   ← Responsive, notice, image compressor, etc.
├── data/                        ← Data Layer
│   ├── api_client.dart          ← LuxApiClient (Dio) + TokenStore
│   ├── preview_fixtures.dart    ← Dev-only mock data
│   ├── models/                  ← 13 @immutable data classes
│   └── repositories/            ← 6 domain repositories
├── state/                       ← State Management Layer
│   ├── providers.dart           ← ALL Riverpod providers (500+ lines)
│   ├── app_settings_controller.dart
│   └── appearance_controller.dart
└── ui/                          ← Presentation Layer
    ├── brand.dart               ← CustomPainter logo
    ├── widgets/                 ← 6 reusable widget files
    └── screens/                 ← 16 screens across 14 directories
```

## مسار البيانات في Flutter

```
User interacts with Screen (ConsumerWidget/ConsumerStatefulWidget)
        ↓
Widget calls Provider/Notifier method
        ↓
Provider reads Repository
        ↓
Repository calls LuxApiClient.get/post/patch/delete
        ↓
LuxApiClient sends Dio HTTP request with Bearer token
        ↓
Server returns JSON response
        ↓
Repository parses JSON using Model.fromJson()
        ↓
Provider updates state (AsyncData/AsyncError)
        ↓
Widget rebuilds with new data
```

---

# 7. Laravel Architecture

## طبقات Laravel

```
backend/
├── app/
│   ├── Console/Commands/        ← CreateLuxAdmin artisan command
│   ├── Enums/                   ← 4 enums (PropertyStatus, TransactionType, etc.)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/          ← 11 API controllers
│   │   │   ├── Admin/           ← 10 admin controllers
│   │   │   └── Auth/            ← 8 Breeze auth controllers
│   │   ├── Middleware/           ← 3 custom middleware
│   │   ├── Requests/            ← 5 form requests
│   │   └── Resources/           ← 6 API resources
│   ├── Models/                  ← 20 Eloquent models
│   ├── Notifications/           ← 3 queued notifications
│   └── Policies/                ← 1 policy (PropertyPolicy)
├── config/
│   ├── currencies.php           ← YER/SAR/USD data-driven
│   ├── permission.php           ← Spatie Permission config
│   └── sanctum.php              ← Token config (no expiration)
├── database/
│   ├── migrations/              ← 12 migrations
│   ├── seeders/                 ← 5 seeders + SQL seed
│   └── factories/               ← 1 factory (UserFactory)
└── routes/
    ├── api.php                  ← API routes (v1 prefix)
    ├── admin.php                ← Admin panel routes
    ├── auth.php                 ← Breeze auth routes
    ├── web.php                  ← Welcome + storage fallback
    └── console.php              ← Artisan commands
```

## مسار الطلب في Laravel

```
HTTP Request
    ↓
Route (api.php or admin.php)
    ↓
Middleware (inject.sanctum.token → auth:sanctum)
    ↓
Controller Method
    ↓
Form Request Validation (authorize + rules + messages)
    ↓
Policy Authorization (PropertyPolicy)
    ↓
Controller Logic (DB::transaction, Eloquent queries)
    ↓
API Resource (JSON transformation)
    ↓
JSON Response
```

---

# 8. Complete Project Structure

## Flutter File Inventory

| الملف | المسار | الأسطر | الوظيفة |
|-------|--------|--------|---------|
| main.dart | lib/main.dart | 36 | Entry point — MaterialApp + ProviderScope + RTL |
| app_config.dart | lib/core/config/app_config.dart | 29 | API URL, timeouts, preview flags |
| notification_navigation.dart | lib/core/navigation/notification_navigation.dart | 136 | Global navigator key + notification routing |
| infinityfree_challenge_solver.dart | lib/core/services/infinityfree_challenge_solver.dart | 177 | AES-128-CBC anti-bot solver |
| lux_notification_service.dart | lib/core/services/lux_notification_service.dart | 110 | Local notifications wrapper |
| app_theme.dart | lib/core/theme/app_theme.dart | 352 | WajhatakColors + buildWajhatakTheme() |
| icon_badges.dart | lib/core/theme/icon_badges.dart | 255 | GradientIconBadge + ScreenHeader + Skeletons |
| responsive.dart | lib/core/utils/responsive.dart | 49 | WindowSize enum + grid columns |
| notice.dart | lib/core/utils/notice.dart | 62 | Global snackbar utility |
| image_compressor.dart | lib/core/utils/image_compressor.dart | 67 | JPEG compression (max 1920px, quality 78) |
| format_money.dart | lib/core/utils/format_money.dart | 33 | Arabic number formatting + currency symbols |
| confirm_logout.dart | lib/core/utils/confirm_logout.dart | 57 | Logout dialog + API call |
| api_client.dart | lib/data/api_client.dart | 418 | LuxApiClient (Dio) + TokenStore + circuit breaker |
| preview_fixtures.dart | lib/data/preview_fixtures.dart | 110 | Dev mock data loader |
| models.dart | lib/data/models/models.dart | 17 | Barrel export for all models |
| user.dart | lib/data/models/user.dart | 57 | LuxUser @immutable model |
| session_data.dart | lib/data/models/session_data.dart | 15 | LuxUser + token pair |
| property.dart | lib/data/models/property.dart | 177 | LuxProperty @immutable (40+ fields) |
| property_query.dart | lib/data/models/property_query.dart | 125 | Search/filter criteria |
| property_location.dart | lib/data/models/property_location.dart | 78 | Hierarchical location model |
| property_image.dart | lib/data/models/property_image.dart | 35 | Image with cover flag |
| property_agent.dart | lib/data/models/property_agent.dart | 47 | Agent info for property |
| notification.dart | lib/data/models/notification.dart | 58 | LuxNotification model |
| message.dart | lib/data/models/message.dart | 95 | ChatMessage + ChatMessageProperty |
| conversation.dart | lib/data/models/conversation.dart | 62 | ConversationItem model |
| viewing_request.dart | lib/data/models/viewing_request.dart | 60 | ViewingRequestItem model |
| taxonomy_item.dart | lib/data/models/taxonomy_item.dart | 27 | Generic taxonomy (type/feature) |
| location_item.dart | lib/data/models/location_item.dart | 40 | Hierarchical location item |
| currency.dart | lib/data/models/currency.dart | 51 | Currency model with fallback |
| repositories.dart | lib/data/repositories/repositories.dart | 9 | Barrel export |
| auth_repository.dart | lib/data/repositories/auth_repository.dart | 138 | Login, register, logout, profile, avatar |
| property_repository.dart | lib/data/repositories/property_repository.dart | 119 | Property CRUD + favorites + images |
| conversation_repository.dart | lib/data/repositories/conversation_repository.dart | 41 | Conversations + messages |
| notification_repository.dart | lib/data/repositories/notification_repository.dart | 22 | Notifications + mark read |
| viewing_request_repository.dart | lib/data/repositories/viewing_request_repository.dart | 46 | Viewing requests CRUD |
| taxonomy_repository.dart | lib/data/repositories/taxonomy_repository.dart | 55 | Types, features, currencies, locations |
| providers.dart | lib/state/providers.dart | 500 | ALL Riverpod providers |
| app_settings_controller.dart | lib/state/app_settings_controller.dart | 128 | Notification prefs + search history |
| appearance_controller.dart | lib/state/appearance_controller.dart | 32 | Theme mode (light/dark/system) |
| brand.dart | lib/ui/brand.dart | 176 | CustomPainter animated logo |
| widgets.dart | lib/ui/widgets.dart | 9 | Barrel export |
| app_drawer.dart | lib/ui/widgets/drawer/app_drawer.dart | 506 | Side navigation drawer |
| empty_state.dart | lib/ui/widgets/feedback/empty_state.dart | 121 | Empty/error state widget |
| bottom_nav_bar.dart | lib/ui/widgets/navigation/bottom_nav_bar.dart | 247 | Floating bottom nav |
| property_card.dart | lib/ui/widgets/property/property_card.dart | 510 | Property card + carousel + favorite |
| app_header.dart | lib/ui/widgets/shared/app_header.dart | 329 | Unified app header |
| lux_skeleton.dart | lib/ui/widgets/skeleton/lux_skeleton.dart | 631 | Async wrapper + shimmer skeletons |
| account_screen.dart | lib/ui/screens/account/account_screen.dart | 262 | User account hub |
| agent_dashboard_screen.dart | lib/ui/screens/agent/agent_dashboard_screen.dart | 451 | Agent workspace |
| auth_screen.dart | lib/ui/screens/auth/auth_screen.dart | 581 | Login/Register flow |
| chat_screen.dart | lib/ui/screens/chat/chat_screen.dart | 564 | Full messaging UI |
| explore_screen.dart | lib/ui/screens/explore/explore_screen.dart | 320 | Search + filter + grid |
| home_screen.dart | lib/ui/screens/home/home_screen.dart | 383 | Landing page |
| create_listing_screen.dart | lib/ui/screens/listings/create_listing_screen.dart | 854 | Property creation form |
| edit_listing_screen.dart | lib/ui/screens/listings/edit_listing_screen.dart | 633 | Property edit form |
| messages_screen.dart | lib/ui/screens/messages/messages_screen.dart | 217 | Conversation list |
| notifications_screen.dart | lib/ui/screens/notifications/notifications_screen.dart | 175 | Notification list |
| profile_screen.dart | lib/ui/screens/profile/profile_screen.dart | 357 | Profile editor |
| property_detail_screen.dart | lib/ui/screens/property/property_detail_screen.dart | 662 | Full property view |
| property_map_screen.dart | lib/ui/screens/property/property_map_screen.dart | 98 | Map with markers |
| show_viewing_sheet.dart | lib/ui/screens/property/show_viewing_sheet.dart | 242 | Viewing request bottom sheet |
| saved_screen.dart | lib/ui/screens/saved/saved_screen.dart | 139 | Favorites grid |
| settings_screen.dart | lib/ui/screens/settings/settings_screen.dart | 235 | App settings |
| toggle_favorite.dart | lib/ui/screens/shared/toggle_favorite.dart | 121 | Favorite toggle logic |
| app_shell.dart | lib/ui/screens/shell/app_shell.dart | 241 | Main scaffold + bottom nav |
| session_gate.dart | lib/ui/screens/shell/session_gate.dart | 84 | Splash → AppShell transition |
| viewing_requests_screen.dart | lib/ui/screens/viewing_requests/viewing_requests_screen.dart | 218 | Viewing request management |

---

# 9. UI Screens Documentation

## Screen Inventory

| الشاشة | Route | أهم Widgets | API | State | Navigation | الهدف |
|--------|-------|-------------|-----|-------|------------|-------|
| SessionGate | `/` (home) | SessionGate, WajhatakBrandMark | sessionProvider.restore() | sessionProvider | → AppShell | شاشة تحميل + فحص الجلسة |
| HomeScreen | AppShell[0] | HomeScreen, PropertyCard, SearchBar | propertiesProvider, propertySearchProvider | sessionProvider | → Explore, PropertyDetail | الصفحة الرئيسية |
| ExploreScreen | AppShell[1] | ExploreScreen, PropertyCard, Chips | propertySearchProvider | exploreSearchProvider | → PropertyDetail, PropertyMap | البحث والاستكشاف |
| SavedScreen | AppShell[2] | SavedScreen, PropertyCard | favoritesProvider | favoritesProvider | → PropertyDetail | المفضلة |
| MessagesScreen | AppShell[3] | MessagesScreen | conversationsProvider | conversationsProvider | → ChatScreen | قائمة المحادثات |
| AccountScreen | AppShell[4] | AccountScreen | sessionProvider | sessionProvider | → AgentDashboard, Profile, etc. | حساب المستخدم |
| AuthScreen | (pushed) | AuthScreen, TabBar, Form | login/register via SessionController | sessionProvider | ← back to Shell | تسجيل الدخول/التسجيل |
| PropertyDetailScreen | (pushed) | PropertyDetailScreen, PageView, Map | propertyDetailProvider | favoriteOverridesProvider | → Chat, ViewingRequest | تفاصيل العقار |
| CreateListingScreen | (pushed) | CreateListingScreen, Form, Dropdowns | propertyTypes, features, locations, currencies | — | ← back | إنشاء عقار |
| EditListingScreen | (pushed) | EditListingScreen, Form | propertyDetailProvider | — | ← back | تعديل عقار |
| AgentDashboardScreen | (pushed) | AgentDashboardScreen | myListingsProvider, viewingRequestsProvider | — | → Create, Edit, Messages | لوحة تحكم الوكيل |
| ChatScreen | (pushed) | ChatScreen, MessageBubble | messagesProvider | messagesProvider | → PropertyDetail | المحادثة |
| ProfileScreen | (pushed) | ProfileScreen, AvatarPicker | sessionProvider.updateProfile | sessionProvider | ← back | تعديل الملف الشخصي |
| SettingsScreen | (pushed) | SettingsScreen, SwitchListTile | — | appSettingsProvider, themeModeProvider | → Profile | الإعدادات |
| NotificationsScreen | (pushed) | NotificationsScreen | notificationsProvider | notificationsProvider | → (根据 kind) | الإشعارات |
| ViewingRequestsScreen | (pushed) | ViewingRequestsScreen, TabBar | viewingRequestsProvider | viewingRequestsProvider | — | طلبات المعاينة |
| PropertyMapScreen | (pushed) | PropertyMapScreen, FlutterMap | — | — | → PropertyDetail | خريطة العقارات |

---

# 10. Widget Documentation

## أهم Widgets المستخدمة فعلياً

### 1. MaterialApp
- **الموجود في:** `main.dart`
- **لماذا:** لإعداد التطبيق مع RTL والترجمة والسمة
- **أهم Properties:** `locale`, `theme`, `darkTheme`, `themeMode`, `home`, `navigatorKey`
- **السؤال المحتمل:** لماذا `Directionality(textDirection: TextDirection.rtl)` حول `SessionGate`؟
- **الإجابة:** لأن التطبيق عربي أول ولا يعتمد على كشف الاتجاه من اللغة، بل يفرض RTL يدوياً.

### 2. ConsumerWidget / ConsumerStatefulWidget
- **الموجود في:** كل شاشة تقريباً (AccountScreen, HomeScreen, ExploreScreen, etc.)
- **لماذا:** للوصول إلى Riverpod providers من داخل الـ widget
- **الفروق:** `ConsumerWidget` للبيانات فقط، `ConsumerStatefulWidget` عندما يكون هناك State محلي (Form fields, Controllers, AnimationController)

### 3. ProviderScope + Provider / FutureProvider / NotifierProvider
- **الموجود في:** `main.dart` (ProviderScope) + `providers.dart` (كل الـ providers)
- **لwhy:** Riverpod هو نظام إدارة الحالة — كل الحالة centeralized في `providers.dart`
- **أنواع Providers:**
  - `Provider` → للخدمات الثابتة (ApiClient, Repositories)
  - `FutureProvider` → للبيانات غير المتزامنة (properties, favorites)
  - `AsyncNotifierProvider` → للحالة القابلة للتعديل (SessionController)
  - `NotifierProvider` → للحالة الموضعية (FavoriteOverrides, ExploreSearch)

### 4. LuxAsyncView\<T\> (مخصص)
- **الموجود في:** `lux_skeleton.dart`
- **لماذا:** هو Widget مخصص يلف أي `FutureProvider` ويعالج loading/error/success states
- **كيف يعمل:** ياخذ `AsyncValue<T>` ويعمل `when(data: ..., loading: ..., error: ...)`
- **إضافة:** `minLoading` guard لمنع الوميض السريع (闪烁)

### 5. PropertyCard
- **الموجود في:** `property_card.dart` (510 سطر)
- **المكونات:**
  - `PageView` مع `Image.network` + `CachedNetworkImage` → Carousel صور
  - `Timer.periodic` → تمرير تلقائي للصور (4 ثوانٍ)
  - `GestureDetector` → onTap للانتقال للتفاصيل
  - `IconButton` (heart) → toggle favorite
  - `Container` + `Text` → السعر والعملية
  - `VisibilityDetector` → تتبع الرؤية لتثبيت مؤقت الصور

### 6. WajhatakBottomNavBar
- **الموجود في:** `bottom_nav_bar.dart`
- **لwhy:** شريط تنقل سفلي مخصص (ليس الـ Material الافتراضي)
- **المكونات:** 5 destinations مع `AnimatedContainer` للاختيار + badge counts

### 7. AppDrawer
- **الموجود في:** `app_drawer.dart` (506 سطر)
- **المكونات:**
  - `Drawer` → Shell widget
  - `UserAccountsDrawerHeader` → صورة + اسم + دور
  - `ListTile` مع `GradientIconBadge` → عناصر التنقل
  - `SwitchListTile` → Dark mode toggle
  - `TextButton` → Logout

### 8. WajhatakHeader (PreferredSizeWidget)
- **الموجود في:** `app_header.dart`
- **لwhy:** هيدر موحد لكل الشاشات
- **المكونات:** `Row` يحتوي `IconButton` (hamburger/back) + `WajhatakBrandMark` + `Badge` (notifications)

### 9. WajhatakBrandMark (CustomPainter)
- **الموجود في:** `brand.dart`
- **لwhy:** شعار مخصص مرسوم بـ CustomPainter (قوس معماري + نقطة موقع)
- **AnimationController:** يدعم الأنيميشن لل ENTRY

### 10. GradientIconBadge
- **الموجود في:** `icon_badges.dart`
- **المكونات:** `Container` مع `BoxDecoration` (gradient) + `Icon`

### 11. CachedNetworkImage
- **الموجود في:** property_card.dart, property_detail_screen.dart, etc.
- **لwhy:** تحميل الصور مع caching + placeholder + error widget
- **المكتبة:** `cached_network_image` package

### 12. FlutterMap
- **الموجود في:** `property_map_screen.dart`
- **لwhy:** عرض العقارات على OpenStreetMap (ليس Google Maps)
- **المكتبة:** `flutter_map` + `latlong2`

### 13. PageView
- **الموجود في:** property_card.dart (carousel), property_detail_screen.dart (gallery)
- **لwhy:** عرض الصور أفقياً مع سحب + مؤشرات dots

### 14. Scaffold + BottomNavigationBar (مخصص)
- **الموجود في:** `app_shell.dart`
- **لwhy:** الهيكل الرئيسي مع drawer + bottom nav + FAB

---

# 11. UI Layout Mapping

## الشاشة الرئيسية (HomeScreen)

```
┌─────────────────────────────────┐
│ WajhatakHeader                  │
│ (hamburger + logo + bell)       │
├─────────────────────────────────┤
│ مرحباً، [اسم المستخدم]!        │
│ "ابحث عن العقار المثالي"       │
├─────────────────────────────────┤
│ ┌──────────────────────────┐    │
│ │ 🔍 ابحث عن عقار...       │    │
│ └──────────────────────────┘    │
├─────────────────────────────────┤
│ [للبيع] [للإيجار] [استكشف الكل]│
├─────────────────────────────────┤
│ عقارات مميزة ←                  │
│ ┌─────┐ ┌─────┐ ┌─────┐       │
│ │Card │ │Card │ │Card │ →      │
│ └─────┘ └─────┘ └─────┘       │
├─────────────────────────────────┤
│ عقارات جديدة ←                  │
│ ┌─────┐ ┌─────┐ ┌─────┐       │
│ │Card │ │Card │ │Card │ →      │
│ └─────┘ └─────┘ └─────┘       │
├─────────────────────────────────┤
│ [الرئيسية] [استكشف] [محفوظات]  │
│ [الرسائل] [حسابي]              │
└─────────────────────────────────┘
```

## شاشة الاستكشاف (ExploreScreen)

```
┌─────────────────────────────────┐
│ WajhatakHeader                  │
├─────────────────────────────────┤
│ ┌──────────────────────────┐    │
│ │ 🔍 بحث...    [🗺️ خريطة] │    │
│ └──────────────────────────┘    │
├─────────────────────────────────┤
│ [الكل] [للبيع] [للإيجار]       │
├─────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐      │
│ │ Property  │ │ Property  │     │
│ │ Card 1    │ │ Card 2    │     │
│ └──────────┘ └──────────┘      │
│ ┌──────────┐ ┌──────────┐      │
│ │ Property  │ │ Property  │     │
│ │ Card 3    │ │ Card 4    │     │
│ └──────────┘ └──────────┘      │
├─────────────────────────────────┤
│ [الرئيسية] [استكشف] [محفوظات]  │
│ [الرسائل] [حسابي]              │
└─────────────────────────────────┘
```

## شاشة تفاصيل العقار (PropertyDetailScreen)

```
┌─────────────────────────────────┐
│ ← WajhatakHeader               │
├─────────────────────────────────┤
│ ┌──────────────────────────┐    │
│ │      صورة العقار          │    │
│ │   (PageView + dots)      │    │
│ └──────────────────────────┘    │
├─────────────────────────────────┤
│ اسم العقار                      │
│ السعر + العملة + [للبيع]        │
├─────────────────────────────────┤
│ ┌─────┐ ┌─────┐ ┌─────┐       │
│ │🛏️ 3 │ │🚿 2 │ │🚗 1 │       │
│ │غرف  │ │حمام│ │موقف │       │
│ └─────┘ └─────┘ └─────┘       │
├─────────────────────────────────┤
│ وصف العقار                      │
├─────────────────────────────────┤
│ [مميزات ك칩ات]                  │
│ ┌──────┐ ┌──────┐ ┌──────┐    │
│ │مكيف  │ │مفلش │ │جديد  │    │
│ └──────┘ └──────┘ └──────┘    │
├─────────────────────────────────┤
│ بطاقة الوكيل                   │
│ صورة + اسم + تقييم + تقييمات   │
├─────────────────────────────────┤
│ 🗺️ موقع على الخريطة            │
├─────────────────────────────────┤
│ ┌────────────┐ ┌────────────┐  │
│ │💬 محادثة   │ │📅 طلب معاينة│ │
│ └────────────┘ └────────────┘  │
└─────────────────────────────────┘
```

---

# 12. Navigation

## Navigation System

**التقنية:** `Navigator` (Material) مع `GlobalKey<NavigatorState>` في `rootNavigatorKey`

**لا يوجد:** GoRouter, AutoRoute, أو أي routing package.

## Routes

التطبيق لا يستخدم Named Routes. كل الانتقالات تتم عبر `Navigator.push` و `Navigator.pop` مباشرة.

```
AppShell (home)
├── Bottom Nav [0] → HomeScreen
├── Bottom Nav [1] → ExploreScreen
├── Bottom Nav [2] → SavedScreen
├── Bottom Nav [3] → MessagesScreen
├── Bottom Nav [4] → AccountScreen
├── Drawer → (same screens via Navigator.pushReplacement)
│
├── AuthScreen → (pushed, then pop)
├── PropertyDetailScreen → (pushed from Home/Explore/Saved)
│   ├── ChatScreen → (pushed from detail)
│   ├── PropertyMapScreen → (pushed from detail)
│   └── showViewingSheet() → (Modal BottomSheet)
├── CreateListingScreen → (pushed from AgentDashboard or FAB)
├── EditListingScreen → (pushed from AgentDashboard)
├── AgentDashboardScreen → (pushed from Account)
├── ProfileScreen → (pushed from Settings/Account)
├── SettingsScreen → (pushed from Account/Drawer)
├── NotificationsScreen → (pushed from Header bell)
├── ViewingRequestsScreen → (pushed from Account/AgentDashboard)
└── NotificationNavigation → (from system notification tap)
```

## Notification Navigation (مخصص)

```dart
// core/navigation/notification_navigation.dart
// يستخدم rootNavigatorKey للانتقال من أي مكان في التطبيق
// عند النقر على إشعار محلي، يحدد الشاشة المناسبة بناءً على kind:
// - message_received → ChatScreen
// - viewing_request_* → ViewingRequestsScreen
// - property_* → PropertyDetailScreen
```

---

# 13. State Management

## التقنية: Riverpod 3.x

**لماذا Riverpod؟**
- Type-safe (compile-time errors)
- Testable (providers are independent)
- No BuildContext required
- Supports async state natively
- Clean dependency injection

## ملخص كل Provider

### Infrastructure Providers (Singleton)

| Provider | Type | الملف | المسؤولية |
|----------|------|-------|-----------|
| `tokenStoreProvider` | Provider\<TokenStore\> | providers.dart | إدارة التوكن في FlutterSecureStorage |
| `apiClientProvider` | Provider\<LuxApiClient\> | providers.dart | عميل HTTP مع Dio |
| `authRepositoryProvider` | Provider\<AuthRepository\> | providers.dart | عمليات المصادقة |
| `propertyRepositoryProvider` | Provider\<PropertyRepository\> | providers.dart | عمليات العقارات |
| `conversationRepositoryProvider` | Provider\<ConversationRepository\> | providers.dart | المحادثات |
| `notificationRepositoryProvider` | Provider\<NotificationRepository\> | providers.dart | الإشعارات |
| `viewingRequestRepositoryProvider` | Provider\<ViewingRequestRepository\> | providers.dart | طلبات المعاينة |
| `taxonomyRepositoryProvider` | Provider\<TaxonomyRepository\> | providers.dart | التصنيفات والمواقع |

### Session Management

| Provider | Type | المسؤولية |
|----------|------|-----------|
| `sessionProvider` | AsyncNotifierProvider\<SessionController, SessionData?\> | مصدر الحقيقة للمصادقة |

**SessionController** (AsyncNotifier):
- `build()` → يستعيد الجلسة من التخزين المحلي أو UI Preview
- `login()` → POST /auth/login → حفظ التوكن → تحديث الحالة
- `register()` → POST /auth/register → حفظ التوكن → تحديث الحالة
- `logout()` → POST /auth/logout → حذف التوكن → مسح الحالة
- `updateProfile()` → PATCH /me → تحديث المستخدم
- `uploadAvatar()` → POST /me/avatar → تحديث المستخدم

### Property Providers

| Provider | Type | المسؤولية |
|----------|------|-----------|
| `propertiesProvider` | FutureProvider\<List\<LuxProperty\>\> | قائمة العقارات الرئيسية |
| `propertySearchProvider` | FutureProvider.family\<List\<LuxProperty\>, PropertyQuery\> | نتائج البحث مع فلتر |
| `propertyDetailProvider` | FutureProvider.family\<LuxProperty, int\> | تفاصيل عقار واحد |
| `favoritesProvider` | FutureProvider\<List\<LuxProperty\>\> | العقارات المفضلة |
| `myListingsProvider` | FutureProvider\<List\<LuxProperty\>\> | عقارات الوكيل |
| `favoriteOverridesProvider` | NotifierProvider\<FavoriteOverrides, Map\<int, bool\>\> | تحديث فوري للمفضلة |

### Taxonomy Providers

| Provider | Type | المسؤولية |
|----------|------|-----------|
| `propertyTypesProvider` | FutureProvider\<List\<TaxonomyItem\>\> | أنواع العقارات |
| `featuresProvider` | FutureProvider\<List\<TaxonomyItem\>\> | المميزات |
| `currenciesProvider` | FutureProvider\<List\<Currency\>\> | العملات (مع fallback) |
| `countriesProvider` | FutureProvider\<List\<LocationItem\>\> | الدول |
| `regionsProvider` | FutureProvider.family | المناطق (حسب الدولة) |
| `citiesProvider` | FutureProvider.family | المدن (حسب المنطقة) |
| `areasProvider` | FutureProvider.family | الأحياء (حسب المدينة) |

### Chat & Notifications

| Provider | Type | المسؤولية |
|----------|------|-----------|
| `conversationsProvider` | FutureProvider\<List\<ConversationItem\>\> | قائمة المحادثات |
| `messagesProvider` | AsyncNotifierProvider.family | رسائل محادثة محددة |
| `notificationsProvider` | FutureProvider\<List\<LuxNotification\>\> | الإشعارات |
| `viewingRequestsProvider` | FutureProvider\<List\<ViewingRequestItem\>\> | طلبات المعاينة |

### UI State

| Provider | Type | المسؤولية |
|----------|------|-----------|
| `exploreSearchProvider` | NotifierProvider\<ExploreSearchNotifier, String?\> | نص البحث في الاستكشاف |
| `themeModeProvider` | NotifierProvider\<ThemeModeController, ThemeMode\> | الوضع الليلي/النهاري |
| `appSettingsProvider` | AsyncNotifierProvider | إعدادات المستخدم المحلي |

## إدارة الحالة — How it works

```
// مثال: عند تحميل العقارات
propertiesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  ref.watch(sessionProvider);  // يُعاد عند تغيير الجلسة
  return ref.read(propertyRepositoryProvider).list();
});

// في الشاشة:
final propertiesAsync = ref.watch(propertiesProvider);
propertiesAsync.when(
  data: (properties) => PropertyGrid(properties),
  loading: () => PropertyGridSkeleton(),
  error: (err, stack) => ErrorState(retry: () => ref.invalidate(propertiesProvider)),
);
```

---

# 14. API Documentation

## Base URL
```
{AppConfig.apiBaseUrl}/api/v1/
```

## API Endpoints

### Auth (Public — throttled 6/min)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| POST | `/api/v1/auth/register` | No | name, email, password, password_confirmation, account_type, phone?, locale? | 201: {data: {user, token}} | AuthController@register |
| POST | `/api/v1/auth/login` | No | email, password, device_name | 200: {data: {user, token}} | AuthController@login |

### Auth (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| POST | `/api/v1/auth/logout` | Yes | — | 204 | AuthController@logout |

### Account (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/me` | Yes | — | 200: {data: UserResource} | MeController@show |
| PATCH | `/api/v1/me` | Yes | name?, phone?, locale? | 200: {data: UserResource} | MeController@update |
| POST | `/api/v1/me/avatar` | Yes | avatar (image, jpeg/png, max 2MB) | 200: {data: UserResource} | MeController@uploadAvatar |

### Properties (Public)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/properties` | No | q?, city?, district?, property_type?, transaction_type?, min_price?, max_price?, min_area?, bedrooms?, bathrooms?, is_furnished?, sort?, page? | 200: paginated PropertyResource | PropertyController@index |
| GET | `/api/v1/properties/{id}` | No | — | 200: {data: PropertyResource} | PropertyController@show |

### Properties (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| POST | `/api/v1/properties` | Yes (agent/admin) | title, description, property_type_id, transaction_type, price, currency?, area?, bedrooms?, bathrooms?, parking_spaces?, is_furnished?, is_new?, feature_ids?, location{...}, images[] | 201: {data: PropertyResource} | PropertyController@store |
| GET | `/api/v1/agent/properties` | Yes (agent) | — | 200: paginated PropertyResource | PropertyController@mine |
| PATCH | `/api/v1/properties/{id}` | Yes (owner/admin) | (same as store, all 'sometimes') | 200: {data: PropertyResource} | PropertyController@update |
| DELETE | `/api/v1/properties/{id}` | Yes (owner/admin) | — | 204 | PropertyController@destroy |
| POST | `/api/v1/properties/{id}/images` | Yes (owner/admin) | image (jpg/png/webp, max 8MB) | 201: {data: {id, url}} | PropertyController@uploadImage |
| DELETE | `/api/v1/properties/{id}/images/{imageId}` | Yes (owner/admin) | — | 204 | PropertyController@destroyImage |
| POST | `/api/v1/properties/{id}/images/{imageId}/cover` | Yes (owner/admin) | — | 204 | PropertyController@setCover |

### Favorites (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/favorites` | Yes | — | 200: paginated PropertyResource | FavoriteController@index |
| POST | `/api/v1/favorites` | Yes | property_id | 204 | FavoriteController@store |
| DELETE | `/api/v1/favorites/{propertyId}` | Yes | — | 204 | FavoriteController@destroy |

### Agents (Public)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/agents` | No | — | 200: paginated AgentResource | AgentController@index |
| GET | `/api/v1/agents/{id}` | No | — | 200: {data: {agent, properties}} | AgentController@show |

### Conversations (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/conversations` | Yes | — | 200: paginated ConversationResource | ConversationController@index |
| POST | `/api/v1/conversations` | Yes | property_id | 201: {data: ConversationResource} | ConversationController@store |
| GET | `/api/v1/conversations/{id}/messages` | Yes | — | 200: paginated MessageResource | ConversationController@messages |
| POST | `/api/v1/conversations/{id}/messages` | Yes | body?, message_type?, property_id? | 201: {data: MessageResource} | ConversationController@sendMessage |

### Viewing Requests (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/viewing-requests` | Yes | — | 200: paginated ViewingRequestResource | ViewingRequestController@index |
| POST | `/api/v1/viewing-requests` | Yes | property_id, scheduled_date, scheduled_time, notes? | 201: {data: ViewingRequestResource} | ViewingRequestController@store |
| PATCH | `/api/v1/viewing-requests/{id}` | Yes | status (confirmed/rejected/cancelled/completed) | 200: ViewingRequestResource | ViewingRequestController@update |

### Notifications (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/notifications` | Yes | — | 200: paginated notifications | NotificationController@index |
| POST | `/api/v1/notifications/{id}/read` | Yes | — | 204 | NotificationController@read |

### Taxonomy (Public)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/property-types` | No | — | 200: {data: [{id, name_ar, name_en, slug}]} | TaxonomyController@propertyTypes |
| GET | `/api/v1/features` | No | — | 200: {data: [{id, name_ar, name_en, icon}]} | TaxonomyController@features |
| GET | `/api/v1/countries` | No | — | 200: {data: [{id, name_ar, name_en, code, currency_code}]} | TaxonomyController@countries |
| GET | `/api/v1/regions` | No | country_id? | 200: {data: [{id, country_id, name_ar, name_en}]} | TaxonomyController@regions |
| GET | `/api/v1/cities` | No | region_id? | 200: {data: [{id, region_id, name_ar, name_en}]} | TaxonomyController@cities |
| GET | `/api/v1/areas` | No | city_id? | 200: {data: [{id, city_id, name_ar, name_en}]} | TaxonomyController@areas |
| GET | `/api/v1/currencies` | No | — | 200: {data: [{code, name_ar, symbol_ar, flag, decimals, is_default}]} | TaxonomyController@currencies |

### Notification Preferences (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| GET | `/api/v1/me/notification-preferences` | Yes | — | 200: {data: {message_notifications, viewing_notifications, property_updates}} | NotificationPreferenceController@show |
| PATCH | `/api/v1/me/notification-preferences` | Yes | message_notifications?, viewing_notifications?, property_updates? | 200: {data: {...}} | NotificationPreferenceController@update |

### Devices (Authenticated)

| Method | Endpoint | Auth | Input | Response | Controller |
|--------|----------|------|-------|----------|------------|
| POST | `/api/v1/me/devices` | Yes | device_id, platform, push_token | 201: {data: {id}} | DeviceController@store |
| DELETE | `/api/v1/me/devices/{deviceId}` | Yes | — | 204 | DeviceController@destroy |

---

# 15. Authentication

## نظام المصادقة: Laravel Sanctum (Personal Access Tokens)

### Registration Flow

```
Flutter AuthScreen
→ SessionController.register()
→ AuthRepository.register()
→ POST /api/v1/auth/register
→ Laravel AuthController@register()
→ RegisterRequest validation (name, email:rfc,dns, password:min:10+mixedCase+numbers, account_type)
→ DB::transaction:
  → User::create([...]) with Hash::make(password)
  → Role::findOrCreate($roleName)
  → $user->assignRole($roleName)
  → if agent: Agent::create([...])
→ $user->createToken($deviceName)->plainTextToken
→ return {user: UserResource, token: plainTextToken}
→ Flutter: TokenStore.write(token) → FlutterSecureStorage
→ Flutter: TokenStore.saveUser(user) → FlutterSecureStorage
→ SessionController state = AsyncData(SessionData)
→ Navigate to AppShell
```

### Login Flow

```
Flutter AuthScreen
→ SessionController.login()
→ AuthRepository.login()
→ POST /api/v1/auth/login
→ Laravel AuthController@login()
→ LoginRequest validation (email, password)
→ User::where('email')->first()
→ Check: is_active? Hash::check(password)?
→ $user->createToken($deviceName)->plainTextToken
→ return {user: UserResource, token}
→ Flutter: same as registration
```

### Token Management

```
TokenStore (Flutter):
  - Token stored in FlutterSecureStorage (encrypted)
  - 30-day session duration (checked client-side)
  - Auto-clear on 401/403 response
  - User cached locally for offline access

LuxApiClient Interceptor:
  - Every request: attach Bearer token
  - Also sends X-Auth-Token header (workaround for InfinityFree/LiteSpeed)
  - Laravel InjectSanctumToken middleware: reads X-Auth-Token → sets Authorization header
```

### Logout Flow

```
Flutter confirmLogout()
→ SessionController.logout()
→ AuthRepository.logout()
→ POST /api/v1/auth/logout
→ Laravel: $request->user()->currentAccessToken()->delete()
→ TokenStore.clear() (delete token + login time + cached user)
→ ref.invalidate(favoriteOverridesProvider)
→ ref.invalidate(exploreSearchProvider)
→ state = AsyncData(null) → SessionGate shows login
```

---

# 16. Authorization

## Policy: PropertyPolicy

```php
view: published property OR admin/owner
create: agent/admin + is_active
update: admin OR owner (property.agent.user_id === user.id)
delete: same as update
```

## Middleware

| Middleware | الاستخدام | الوظيفة |
|-----------|-----------|---------|
| `auth:sanctum` | Routes that require authentication | التحقق من التوكن |
| `inject.sanctum.token` | قبل auth:sanctum | إعادة توفير التوكن من X-Auth-Token |
| `EnsureUserIsAdmin` | Admin routes | التحقق من is_active + role=admin |
| `AdminUrlGuard` | Admin routes | حماية من تعديل URL + security headers |
| `throttle:6,1` | Register/Login | Rate limiting (6 محاولات/دقيقة) |

## كيف يمنع النظام صلاحيات المستخدم

```php
// في PropertyController@store:
$agent = $request->user()->agentProfile;
abort_unless($agent?->is_active, 403, 'حساب الوكيل غير مفعّل.');

// في PropertyController@update:
$this->authorize('update', $property); // uses PropertyPolicy

// في ViewingRequestController@update:
$isAgentOwner = $user->agentProfile?->id === $viewingRequest->agent_id;
$isClientOwner = $user->id === $viewingRequest->client_id;
abort_unless($user->hasRole('admin') || $isAgentOwner || $isClientOwner, 403);
// Client can ONLY cancel, agent can confirm/reject/complete
```

---

# 17. Database

## Database Name: `wajhatak`

## Tables

| الجدول | الوظيفة | أهم الأعمدة | PK | FK |
|--------|---------|------------|-----|-----|
| users | المستخدمون | id, name, email, phone, password, avatar_path, locale, is_active | id | — |
| agents | الوكلاء | id, user_id, license_number, bio, rating, reviews_count, is_active | id | user_id → users |
| properties | العقارات | id, agent_id, property_type_id, property_location_id, title, slug, reference_code, description, transaction_type, status, price, currency, area, bedrooms, bathrooms, parking_spaces, is_furnished, is_new, is_featured, published_at | id | agent_id → agents, property_type_id → property_types, property_location_id → property_locations |
| property_types | أنواع العقارات | id, name_ar, name_en, slug, is_active | id | — |
| property_features | مميزات العقارات | id, name_ar, name_en, slug, icon, is_active | id | — |
| property_feature | جدول Pivot | property_id, property_feature_id | (property_id, property_feature_id) | property_id → properties, property_feature_id → property_features |
| property_locations | مواقع العقارات | id, country_id, region_id, city_id, area_id, city, district, neighborhood, address, latitude, longitude | id | country_id → countries, region_id → regions, city_id → cities, area_id → areas |
| property_images | صور العقارات | id, property_id, path, alt_text, sort_order, is_cover | id | property_id → properties |
| favorites | المفضلة | id, user_id, property_id | id | user_id → users, property_id → properties |
| conversations | المحادثات | id, property_id, client_id, agent_id, last_message_at | id | property_id → properties, client_id → users, agent_id → users |
| messages | الرسائل | id, conversation_id, sender_id, body, message_type, property_id, read_at | id | conversation_id → conversations, sender_id → users, property_id → properties |
| viewing_requests | طلبات المعاينة | id, property_id, client_id, agent_id, scheduled_date, scheduled_time, notes, status | id | property_id → properties, client_id → users, agent_id → agents |
| countries | الدول | id, name_ar, name_en, code, currency_code, is_active | id | — |
| regions | المناطق | id, country_id, name_ar, name_en, is_active | id | country_id → countries |
| cities | المدن | id, region_id, name_ar, name_en, is_active | id | region_id → regions |
| areas | الأحياء | id, city_id, name_ar, name_en, is_active | id | city_id → cities |
| settings | الإعدادات | id, key, value, type | id | — |
| activity_logs | سجل النشاطات | id, user_id, log_name, description, subject_type, subject_id, ip_address, user_agent, properties | id | user_id → users |
| user_notification_preferences | تفضيلات الإشعارات | id, user_id, message_notifications, viewing_notifications, property_updates | id | user_id → users |
| user_devices | الأجهزة | id, user_id, device_id, platform, push_token, last_seen_at | id | user_id → users |
| saved_searches | عمليات البحث المحفوظة | id, user_id, name, filters, notifications_enabled | id | user_id → users |
| notifications | إشعارات Laravel | id (uuid), type, notifiable_type, notifiable_id, data, read_at | id | — |
| personal_access_tokens | Sanctum tokens | id, tokenable_type, tokenable_id, name, token, abilities, ... | id | — |
| roles / permissions | Spatie Permission | id, name, guard_name | id | — |

## Indexes المهمة

```php
// في properties:
$table->index(['status', 'transaction_type', 'price']);  // for filtered listing
$table->index(['property_type_id', 'status']);            // for type filtering
$table->index('price');                                   // for price sorting
$table->index('status');                                  // for status filtering
$table->index('transaction_type');                        // for sale/rent filtering
$table->index('area');
$table->index('bedrooms');
$table->index('bathrooms');
$table->index('is_furnished');
$table->index('is_new');
$table->index('is_featured');
$table->index('published_at');

// في favorites:
$table->unique(['user_id', 'property_id']);               // idempotent favorites

// في conversations:
$table->unique(['client_id', 'agent_id']);                // one conversation per client-agent pair

// في viewing_requests:
$table->index(['agent_id', 'status', 'scheduled_date']);  // for agent's pending viewings
$table->index(['client_id', 'status']);                    // for client's viewings
```

## Soft Deletes

Properties هي الجدول الوحيد الذي يستخدم SoftDeletes:
```php
class Property extends Model {
    use SoftDeletes;
    // Enables: $property->delete() (soft), restore(), forceDelete()
    // Admin can: trash, restore, force delete, bulk operations
}
```

---

# 18. Eloquent Relationships

## العلاقات الحقيقية

### User Model

```php
agentProfile(): HasOne(Agent::class)        // User has one Agent profile
favorites(): HasMany(Favorite::class)       // User has many Favorites
clientViewingRequests(): HasMany(ViewingRequest::class, 'client_id')
sentMessages(): HasMany(Message::class, 'sender_id')
notificationPreference(): HasOne(UserNotificationPreference::class)
devices(): HasMany(UserDevice::class)
```

### Property Model

```php
agent(): BelongsTo(Agent::class)            // Property belongs to Agent
type(): BelongsTo(PropertyType::class)      // Property belongs to PropertyType
location(): BelongsTo(PropertyLocation::class)
images(): HasMany(PropertyImage::class)->orderBy('sort_order')
features(): BelongsToMany(PropertyFeature::class, 'property_feature')  // PIVOT
favorites(): HasMany(Favorite::class)
viewingRequests(): HasMany(ViewingRequest::class)
conversations(): HasMany(Conversation::class)
```

### Agent Model

```php
user(): BelongsTo(User::class)
properties(): HasMany(Property::class)
viewingRequests(): HasMany(ViewingRequest::class)
```

### Conversation Model

```php
property(): BelongsTo(Property::class)
client(): BelongsTo(User::class, 'client_id')
agent(): BelongsTo(User::class, 'agent_id')
messages(): HasMany(Message::class)
```

### Location Hierarchy

```php
Country → hasMany(Region)
Region → belongsTo(Country), hasMany(City)
City → belongsTo(Region), hasMany(Area)
Area → belongsTo(City)

PropertyLocation → belongsTo(Country, Region, City, Area)
```

## Pivot Table: property_feature

```
property_id     → properties.id (cascadeOnDelete)
property_feature_id → property_features.id (cascadeOnDelete)
PRIMARY KEY: (property_id, property_feature_id)
```

**لماذا belongsToMany مع pivot؟**
لأن العقار يمكن أن له ميزات متعددة والميزة يمكن أن تنتمي لعقارات متعددة — علاقة Many-to-Many.

---

# 19. Property Management

## دورة حياة العقار

```
1. Agent creates property → status: 'pending'
2. Admin reviews → status: 'published' or 'rejected'
3. Published property appears in listings
4. Agent can edit/delete own properties
5. Admin can: edit, delete (soft), restore, force delete
6. Properties use SoftDeletes for safe removal
```

## إنشاء عقار (Flutter → Laravel)

```
CreateListingScreen (854 lines)
→ User fills form: title, description, type, transaction, price, currency, location, features, images
→ PropertyRepository.create(data, images)
→ ImageCompressor.compress(each image)
→ FormData.fromMap (flatten nested data)
→ LuxApiClient.post('/properties', data: FormData)
→ Laravel: StorePropertyRequest validates (title required, description required, location required)
→ PropertyController@store:
  → Agent must be active
  → DB::transaction:
    → PropertyLocation::create(location data)
    → Property::create({...attributes, status: Pending, slug, reference_code})
    → features()->sync(feature_ids)
    → foreach images: storePublicly('properties/$id', 'public') + PropertyImage::create
  → return PropertyResource with 201
```

---

# 20. Search & Filtering

## Flutter Side

```dart
// PropertyQuery model (property_query.dart)
PropertyQuery(
  search: 'شقة',          // → ?q=شقة
  transactionType: 'rent', // → ?transaction_type=rent
  city: 'صنعاء',          // → ?city=صنعاء
  propertyType: 'apartment', // → ?property_type=apartment
  minPrice: 100000,       // → ?min_price=100000
  maxPrice: 500000,       // → ?max_price=500000
  bedrooms: 3,            // → ?bedrooms=3
  sort: 'price_asc',      // → ?sort=price_asc
).toParameters()  // → Map<String, dynamic>
```

## Laravel Side

```php
// PropertyController@applyFilters
$query->when($request->filled('q'), fn ($q) => $q->where(fn ($search) => $search
    ->where('title', 'like', "%$q%")
    ->orWhere('description', 'like', "%$q%")))
    ->when($request->filled('city'), fn ($q) => $q->whereHas('location', fn ($loc) => $loc->where('city', $city)))
    ->when($request->filled('property_type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('slug', $type)))
    ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $minPrice))
    ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $maxPrice))
    // ... bedrooms, bathrooms, parking, is_furnished, is_new, is_featured
```

## مسار البحث الكامل

```
User types in search bar → ExploreSearchNotifier.setSearch(term)
→ propertySearchProvider invalidated with new PropertyQuery(search: term)
→ PropertyRepository.list(query)
→ LuxApiClient.get('/properties', query: query.toParameters())
→ HTTP GET /api/v1/properties?q=شقة&city=صنعاء&...
→ Laravel: PropertyController@index
→ applyFilters() + applySort()
→ Eloquent query with whereHas, where, like
→ paginate(15)
→ PropertyResource::collection
→ JSON Response
→ Flutter: _propertyList(json) → List<LuxProperty>
→ Provider state updated → UI rebuilds
```

---

# 21. Favorites

## toggleFavorite Flow

```dart
// ui/screens/shared/toggle_favorite.dart
toggleFavorite(context, ref, property) async {
  if (!isLoggedIn) → show auth dialog
  if (removing) → show confirmation dialog

  // Optimistic UI update
  ref.read(favoriteOverridesProvider.notifier).set(property.id, shouldFavorite);

  try {
    await ref.read(propertyRepositoryProvider).setFavorite(property.id, shouldFavorite);
    notice('تمت الإضافة/الإزالة');
  } catch {
    // Revert on failure
    ref.read(favoriteOverridesProvider.notifier).remove(property.id);
    notice('حدث خطأ');
  }
}
```

## Backend

```php
// FavoriteController@store
Favorite::firstOrCreate(['user_id' => $userId, 'property_id' => $propertyId]);
// Idempotent: firstOrCreate prevents duplicates

// FavoriteController@destroy
Favorite::where('user_id', $userId)->where('property_id', $propertyId)->delete();
```

---

# 22. Image Upload

## Property Images

```
1. User selects images (image_picker: gallery/camera)
2. Flutter: ImageCompressor.compress(file)
   → Max 1920px, JPEG quality 78, max 600KB
3. FormData.fromMap with MultipartFile
4. LuxApiClient.post('/properties/$id/images', data: formData)
5. Laravel: validates (image, mimes:jpg,png,webp, max:8192)
6. $image->storePublicly('properties/$id', 'public')
7. PropertyImage::create(path, sort_order, is_cover)
8. Response: {id, url: asset('storage/'.$path)}
```

## Avatar Upload

```
1. User picks image (image_picker)
2. ImageCompressor(maxDimension: 512, quality: 84, maxBytes: 256KB)
3. POST /me/avatar (FormData)
4. Laravel validates (image, mimes:jpeg,png, max:2048)
5. store('avatars/$userId', 'public')
6. Delete old avatar if exists
7. Return UserResource with new avatar_url
```

---

# 23. User Profile

## Profile Update

```
Flutter ProfileScreen
→ Name text field + Phone text field
→ Validate: name required, phone optional
→ SessionController.updateProfile(name, phone)
→ AuthRepository.updateProfile(session, name, phone)
→ PATCH /me (name, phone, locale)
→ Laravel MeController@update
→ Validates: name max 120, phone unique
→ $user->update($data)
→ Return UserResource
→ Flutter: saveUser to TokenStore + update session state
```

---

# 24. End-to-End Workflows

## Scenario 1: فتح التطبيق

```
1. main() → WidgetsFlutterBinding.ensureInitialized()
2. runApp(ProviderScope(child: WajhatakApp()))
3. MaterialApp:
   - Theme: buildWajhatakTheme()
   - Locale: ar (RTL)
   - Home: SessionGate
4. SessionGate:
   - sessionProvider loads → AsyncLoading
   - Shows _SplashView (animated WajhatakBrandMark)
5. SessionController.build():
   - If UI Preview → load fixtures
   - Else: AuthRepository.restore()
     - TokenStore.read() → read token from SecureStorage
     - If token expired (30 days) → clear → return null
     - If cached user exists → return SessionData (offline)
     - Try GET /me → success: save user, return SessionData
     - If 401/403 → clear token → return null
     - If network error → use cached user if available
6. sessionProvider resolved:
   - If SessionData exists → show AppShell (logged in)
   - If null → show AppShell (guest mode, limited features)
```

## Scenario 2: تسجيل مستخدم جديد

```
1. User taps "حسابي" → AuthScreen (if not logged in)
2. User switches to "تسجيل" tab
3. Fills: name, email, phone (optional), password, confirm password, account type (client/agent)
4. Flutter validation: name required, email format, password min 10 chars, match
5. User taps "تسجيل"
6. SessionController.register():
   - state = AsyncLoading (shows spinner)
   - AuthRepository.register()
     - POST /api/v1/auth/register
     - Body: {name, email, password, password_confirmation, account_type, phone?, locale: 'ar'}
7. Laravel:
   - Throttle: 6 requests/minute
   - RegisterRequest validates:
     - name: required, max 120
     - email: required, rfc,dns, unique:users
     - password: required, min 10, mixedCase, numbers, confirmed
     - account_type: in:client,agent
   - AuthController@register():
     - DB::transaction
     - User::create with Hash::make(password)
     - Role::findOrCreate('user' or 'agent')
     - $user->assignRole
     - if agent: Agent::create
     - $user->createToken('mobile')->plainTextToken
   - return 201: {data: {user: UserResource, token}}
8. Flutter:
   - TokenStore.write(token)
   - TokenStore.saveUser(user)
   - sessionProvider state = AsyncData(SessionData)
   - _afterSessionChanged() → invalidate search/favorites
   - Navigate to AppShell (now logged in)
```

## Scenario 3: تصفح العقارات

```
1. HomeScreen loads
2. ref.watch(propertiesProvider) → FutureProvider fires
3. PropertyRepository.list() → GET /api/v1/properties
4. Laravel PropertyController@index:
   - Eager load: type, location.*.agent.user, images
   - where status = Published
   - applyFilters (none initially)
   - applySort (default: is_featured DESC, published_at DESC)
   - withExists (favorites for current user if logged in)
   - paginate(15)
5. PropertyResource::collection transforms each property
6. JSON response with pagination metadata
7. Flutter receives List<LuxProperty>
8. HomeScreen builds:
   - Featured section (horizontal ListView of PropertyCard)
   - New section (horizontal ListView of PropertyCard)
   - Grid section (responsive grid of PropertyCard)
```

## Scenario 4: فتح تفاصيل عقار

```
1. User taps PropertyCard → Navigator.push(PropertyDetailScreen(id))
2. ref.watch(propertyDetailProvider(id)) → FutureProvider.family fires
3. PropertyRepository.detail(id) → GET /api/v1/properties/$id
4. Laravel PropertyController@show:
   - Gate::forUser($user)->authorize('view', $property)
   - PropertyPolicy@view: published? OR admin/owner
   - Eager load: type, location.*, agent.user, images, features
   - Check is_favorited
5. PropertyResource returns full data
6. Flutter PropertyDetailScreen:
   - PageView with images (auto-play 5s)
   - Title + price + currency + transaction badge
   - Facts row (bedrooms, bathrooms, parking)
   - Description text
   - Feature chips (from featureNames)
   - Agent card (avatar, name, rating)
   - Location map (FlutterMap with markers)
   - Bottom bar: Message agent + Request viewing buttons
```

## Scenario 5: طلب معاينة

```
1. User taps "طلب معاينة" → showViewingSheet()
2. Modal BottomSheet appears with:
   - Date picker (DatePicker)
   - Time picker (TimePicker)
   - Notes text field
   - Submit button
3. User fills and taps "إرسال"
4. ViewingRequestRepository.requestViewing(propertyId, date, time, notes)
5. POST /api/v1/viewing-requests
6. Laravel ViewingRequestController@store:
   - Validates: property_id exists, scheduled_date after today, scheduled_time format H:i
   - Check: property is published
   - Check: not requesting own property
   - DB::transaction: ViewingRequest::create({...})
   - Notify agent: ViewingRequestCreated notification (queued, database)
7. Return 201 with ViewingRequestResource
8. Flutter: notice('تم إرسال طلب المعاينة')
```

---

# 25. Important Code

## LuxApiClient Interceptor Chain

```dart
// api_client.dart — InterceptorsWrapper

onRequest: (options, handler) async {
  await _throttle();  // Circuit breaker + rate limiting (350ms between requests)
  if (_challengeSolver.cookie == null) {
    await _challengeSolver.ensureCookie();  // Solve InfinityFree AES challenge
  }
  options.headers['Cookie'] = challengeCookie;
  final token = await _tokenStore.read();
  if (token != null) {
    options.headers['Authorization'] = 'Bearer $token';
    options.headers['X-Auth-Token'] = token;  // Backup for LiteSpeed
  }
  handler.next(options);
}

onResponse: (response, handler) async {
  _recordSuccess();
  if (_isChallengeHtml(response.data) && !retried) {
    // Re-solve challenge and retry once
  }
  unawaited(_tokenStore.refreshSession());
  handler.next(response);
}

onError: (error, handler) async {
  _recordFailure();
  if (statusCode == 429) → reject with clear message (no retry)
  if (statusCode == 401 || 403) → clear token
  if (challengeHtml && !retried) → solve + retry once
  // Extract validation errors from response body
  handler.reject(DioException with ApiFailure);
}
```

## InfinityFree Challenge Solver

```dart
// infinityfree_challenge_solver.dart
// Solves AES-128-CBC challenge page from InfinityFree hosting
// 1. Fetch challenge page
// 2. Parse JavaScript: extract a, b, c values from toNumbers()
// 3. Decrypt AES-128-CBC
// 4. Compute __test cookie
// 5. Include cookie in all subsequent API requests
```

## Circuit Breaker Pattern

```dart
// api_client.dart
static const _rateCooldown = Duration(milliseconds: 350);
static const _breakerThreshold = 5;
static const _breakerCooldown = Duration(seconds: 20);

Future<void> _throttle() async {
  await _waitOutBreaker();  // Wait if circuit is open
  // Minimum 350ms between requests
}

void _recordFailure() {
  _rapidFailureStreak++;
  if (_rapidFailureStreak >= 5) {
    _breakerLiftedAt = DateTime.now();  // Open circuit for 20 seconds
    _rapidFailureStreak = 0;
  }
}
```

---

# 26. Important Classes

## Flutter — Top 15 Classes

| Class | الملف | المسؤولية | أهم Methods |
|-------|-------|-----------|------------|
| `LuxApiClient` | api_client.dart | HTTP client مع Dio | get(), post(), patch(), delete() |
| `TokenStore` | api_client.dart | إدارة التوكن في SecureStorage | read(), write(), clear(), saveUser(), readUser() |
| `SessionController` | providers.dart | AsyncNotifier للمصادقة | login(), register(), logout(), updateProfile(), uploadAvatar() |
| `AuthRepository` | auth_repository.dart | عمليات المصادقة | restore(), login(), register(), logout(), updateProfile(), uploadAvatar() |
| `PropertyRepository` | property_repository.dart | عمليات العقارات | list(), detail(), create(), update(), delete(), favorites(), toggleFavorite(), uploadImage() |
| `LuxProperty` | property.dart | نموذج العقار | fromJson(), toJson(), copyWith(), coverUrl, isRent, transactionLabel |
| `PropertyQuery` | property_query.dart | معايير البحث | toParameters(), copyWith(), isEmpty |
| `LuxUser` | user.dart | نموذج المستخدم | fromJson(), toJson(), isAgent |
| `ChatMessagesNotifier` | providers.dart | حالة رسائل المحادثة | build(), refresh(), send() |
| `FavoriteOverrides` | providers.dart | تجاوزات المفضلة المؤقتة | set(), remove(), reset() |
| `InfinityFreeChallengeSolver` | infinityfree_challenge_solver.dart | حل تحدي AES | ensureCookie(), forceSolve() |
| `ImageCompressor` | image_compressor.dart | ضغط الصور | compress() |
| `LuxAsyncView<T>` | lux_skeleton.dart | Async data wrapper | when(data, loading, error) |
| `PropertyCard` | property_card.dart | بطاقة عقار | (StatefulWidget with carousel) |
| `AppDrawer` | app_drawer.dart | القائمة الجانبية | (ConsumerWidget) |

## Laravel — Top 15 Classes

| Class | الملف | المسؤولية | أهم Methods |
|-------|-------|-----------|------------|
| `PropertyController` | Api/V1/PropertyController.php | CRUD العقارات + الفلترة | index(), show(), store(), update(), destroy(), applyFilters(), applySort() |
| `AuthController` | Api/V1/AuthController.php | المصادقة | register(), login(), logout() |
| `ConversationController` | Api/V1/ConversationController.php | المحادثات | index(), store(), messages(), sendMessage() |
| `FavoriteController` | Api/V1/FavoriteController.php | المفضلة | index(), store(), destroy() |
| `ViewingRequestController` | Api/V1/ViewingRequestController.php | طلبات المعاينة | index(), store(), update() |
| `Property` | Models/Property.php | نموذج العقار | agent(), type(), location(), images(), features(), favorites() |
| `User` | Models/User.php | نموذج المستخدم | agentProfile(), favorites(), HasApiTokens, HasRoles |
| `Agent` | Models/Agent.php | نموذج الوكيل | user(), properties() |
| `PropertyPolicy` | Policies/PropertyPolicy.php | تفويض العقارات | view(), create(), update(), delete() |
| `PropertyResource` | Resources/PropertyResource.php | تحويل JSON | toArray() |
| `RegisterRequest` | Requests/Auth/RegisterRequest.php | التحقق من التسجيل | rules(), messages() |
| `StorePropertyRequest` | Requests/StorePropertyRequest.php | التحقق من إنشاء العقار | authorize(), rules(), messages() |
| `InjectSanctumToken` | Middleware/InjectSanctumToken.php | حل مشكلة الاستضافة | handle() |
| `LuxNotificationService` | core/services/ | الإشعارات المحلية | initialize(), showNotification() |
| `PropertyCard` | ui/widgets/property/ | بطاقة العقار | (ConsumerStatefulWidget) |

---

# 27. Important Methods

## Flutter Top 15 Methods

| Method | File | Class | Parameters | Return | Purpose |
|--------|------|-------|------------|--------|---------|
| `restore()` | auth_repository.dart | AuthRepository | — | SessionData? | استعادة الجلسة من التخزين المحلي أو API |
| `login()` | auth_repository.dart | AuthRepository | email, password, deviceName | SessionData | تسجيل الدخول عبر API |
| `register()` | auth_repository.dart | AuthRepository | name, email, password, accountType, phone? | SessionData | التسجيل عبر API |
| `list()` | property_repository.dart | PropertyRepository | PropertyQuery | List\<LuxProperty\> | جلب قائمة العقارات مع الفلترة |
| `detail()` | property_repository.dart | PropertyRepository | int id | LuxProperty | جلب تفاصيل عقار واحد |
| `create()` | property_repository.dart | PropertyRepository | Map data, List\<File\> images | void | إنشاء عقار جديد |
| `send()` | providers.dart | ChatMessagesNotifier | String body | void | إرسال رسالة في المحادثة |
| `toggleFavorite()` | toggle_favorite.dart | (global function) | context, ref, property | void | إضافة/إزالة المفضلة مع optimistic update |
| `compress()` | image_compressor.dart | ImageCompressor | File image | String (path) | ضغط صورة قبل الرفع |
| `ensureCookie()` | infinityfree_challenge_solver.dart | InfinityFreeChallengeSolver | — | String? | حل تحدي AES للallenge |
| `_throttle()` | api_client.dart | LuxApiClient | — | Future\<void\> | Rate limiting + circuit breaker |
| `toParameters()` | property_query.dart | PropertyQuery | — | Map\<String, dynamic\> | تحويل معايير البحث لمعاملات URL |
| `_saveSession()` | auth_repository.dart | AuthRepository | Map json | SessionData | حفظ التوكن والمستخدم محلياً |
| `updateProfile()` | providers.dart | SessionController | name, phone, locale | Future\<void\> | تحديث الملف الشخصي |
| `_readForSession()` | providers.dart | (helper) | ref, fallback, request | T | قراءة بيانات مع ت处理 401/403 |

## Laravel Top 15 Methods

| Method | File | Class | Parameters | Return | Purpose |
|--------|------|-------|------------|--------|---------|
| `register()` | AuthController.php | AuthController | RegisterRequest | JsonResponse 201 | إنشاء حساب + تعيين دور + إنشاء توكن |
| `login()` | AuthController.php | AuthController | LoginRequest | JsonResponse | التحقق + إنشاء توكن |
| `index()` | PropertyController.php | PropertyController | Request | PropertyResource collection | قائمة العقارات مع الفلترة والترتيب والصفحات |
| `store()` | PropertyController.php | PropertyController | StorePropertyRequest | JsonResponse 201 | إنشاء عقار + موقع + صور |
| `applyFilters()` | PropertyController.php | PropertyController | Builder, Request | void | تطبيق 13+ فلتر على الاستعلام |
| `applySort()` | PropertyController.php | PropertyController | Builder, string | void | تطبيق الترتيب (price_asc, price_desc, area_desc, default) |
| `store()` (Favorite) | FavoriteController.php | FavoriteController | Request | JsonResponse 204 | firstOrCreate (idempotent) |
| `store()` (Conversation) | ConversationController.php | ConversationController | Request | JsonResponse 201 | إنشاء محادثة + رسالة بطاقة عقار |
| `sendMessage()` | ConversationController.php | ConversationController | Request, Conversation | JsonResponse 201 | إرسال رسالة + إشعار + تحديث last_message_at |
| `store()` (Viewing) | ViewingRequestController.php | ViewingRequestController | Request | JsonResponse 201 | طلب معاينة + إشعار الوكيل |
| `update()` (Viewing) | ViewingRequestController.php | ViewingRequestController | Request, ViewingRequest | ViewingRequestResource | تحديث الحالة + إشعار الطرف الآخر |
| `toArray()` | PropertyResource.php | PropertyResource | Request | array | تحويل العقار لـ JSON مع relationships |
| `handle()` | InjectSanctumToken.php | InjectSanctumToken | Request, Closure | Response | إعادة توفير التوكن من X-Auth-Token |
| `record()` | ActivityLog.php | ActivityLog | logName, description, subject? | ActivityLog | تسجيل نشاط (لل Admin panel) |
| `handle()` | PropertyPolicy.php | PropertyPolicy | User?, Property | bool | التحقق من صلاحية العرض/الإنشاء/التعديل/الحذف |

---

# 28. Packages & Libraries

## Flutter Packages (pubspec.yaml)

| الحزمة | الإصدار | الوظيفة | أين تُستخدم | لماذا؟ |
|--------|---------|---------|-------------|--------|
| `flutter_riverpod` | ^3.0.3 | State Management | providers.dart, كل الشاشات | Type-safe, testable, no BuildContext |
| `dio` | ^5.8.0+1 | HTTP Client | api_client.dart | Interceptors, CancelToken, FormData support |
| `flutter_secure_storage` | ^10.0.0 | Encrypted Storage | api_client.dart (TokenStore) | Token encryption on device |
| `cached_network_image` | ^3.4.1 | Image Caching | property_card.dart, detail_screen | Memory+disk cache, placeholders |
| `image_picker` | ^1.2.1 | Camera/Gallery | create_listing, profile, edit_listing | Native image selection |
| `image` | ^4.5.4 | Image Processing | image_compressor.dart | In-memory resize + JPEG encode |
| `flutter_map` | ^8.1.1 | Map View | property_map_screen.dart | OpenStreetMap (free, no API key) |
| `latlong2` | ^0.9.1 | Coordinates | property_map_screen.dart | Lat/Lng for map markers |
| `flutter_local_notifications` | ^18.0.1 | Local Notifications | lux_notification_service.dart | In-app notification display |
| `intl` | ^0.20.2 | Internationalization | chat_screen, messages, format_money | Arabic date/number formatting |
| `encrypt` | ^5.0.3 | AES Encryption | infinityfree_challenge_solver.dart | Solve InfinityFree AES challenge |
| `shared_preferences` | ^2.5.3 | Local Prefs | app_settings, appearance | Theme mode, search history |
| `flutter_localizations` | (SDK) | RTL Support | main.dart | Arabic locale support |

## Laravel Packages (composer.json)

| الحزمة | الإصدار | الوظيفة | أين تُستخدم | لماذا؟ |
|--------|---------|---------|-------------|--------|
| `laravel/framework` | ^12.0 | Core Framework | كامل المشروع | MVC, Eloquent, Queue, Cache |
| `laravel/sanctum` | ^4.0 | API Token Auth | Auth routes, middleware | Simple token auth for mobile |
| `spatie/laravel-permission` | ^8.3 | Role/Permission | User model, admin middleware | Granular access control |
| `laravel/tinker` | ^2.10.1 | REPL | Development | Debug & test commands |

### سؤال الدكتور: لماذا Riverpod بدلاً من Provider/BLoC/GetX؟

Riverpod هو تطور لـ Provider مع ميزات إضافية:
1. **Compile-time safety** — أخطاء الـ type تُكتشف قبل التشغيل
2. **لا يحتاج BuildContext** — يمكن الوصول من أي مكان
3. **Testable** — كل provider مستقل وقابل للاختبار
4. **Destruction-safe** — لا يسبب memory leaks
5. **Supports families** — للبيانات المعلمة (如 propertyDetailProvider(id))

### سؤال الدكتور: لماذا Laravel Sanctum بدلاً من JWT/Passport؟

1. **البساطة** — Personal Access Tokens أسهل من OAuth
2. **مدمج** — جزء من Laravel بدون حزم إضافية
3. **.MOUSE** — يدعم token revocation عند logout
4. ** Stateless** — لا يحتاج session على الخادم
5. **ケータイ** — مصمم للتطبيقات المحمولة تحديداً

### سؤال الدكتور: لماذا flutter_map بدلاً من Google Maps؟

1. **مجاني** — لا يحتاج API key
2. **OpenStreetMap** — بيانات مفتوحة المصدر
3. **خصوصية** — لا يشارك بيانات المستخدم مع Google
4. **خفيف** — حجم أصغر في البناء

### سؤال الدكتور: لماذا Dio بدلاً من http/http؟

1. **Interceptors** — للتوكن والتحديات (Essential للمشروع)
2. **FormData** — لرفع الصور مباشرة
3. **CancelToken** — لإلغاء الطلبات
4. **Retry logic** — سهل التأمين مع Interceptor

### سؤال الدكتور: لماذا Spatie Permission بدلاً من يدوية؟

1. **جاهز** — جداول + cache +loquent integration
2. **Scalable** — يدعم الأدوار والصلاحيات والمجموعات
3. **Middleware** — middleware جاهز للتحقق من الأدوار
4. **Tested** — مكتبة مستخدمة بكثافة

---

# 29. Security

## المكونات الأمنية

| المكون | التنفيذ | الملف |
|--------|---------|-------|
| Password Hashing | `Hash::make()` + `password` cast = 'hashed' | User.php |
| Token Auth | Sanctum Personal Access Tokens | AuthController.php |
| Rate Limiting | `throttle:6,1` on register/login | api.php |
| Input Validation | Form Requests + Arabic error messages | Requests/ |
| Authorization | PropertyPolicy + Spatie roles | Policies/, Middleware/ |
| File Upload Security | mimes, max size validation | StorePropertyRequest, MeController |
| SQL Injection Prevention | Eloquent (parameterized queries) | All controllers |
| XSS Protection | AdminUrlGuard security headers | AdminUrlGuard.php |
| CSRF | Sanctum handles for API, Breeze for web | — |
| Open Redirect Protection | LoginRequest URL validation | Auth/AuthenticatedSessionController |
| Session Fixation Prevention | AdminUrlGuard session put | EnsureUserIsAdmin.php |
| Anti-Bot (Hosting) | InfinityFree AES Challenge Solver | infinityfree_challenge_solver.dart |
| Token Backup Header | X-Auth-Token for LiteSpeed compatibility | InjectSanctumToken.php |
| Circuit Breaker | Rate limiting + cooldown on failures | api_client.dart |
| Last Admin Protection | Can't delete/deactivate last admin | Admin/UserController |
| Sensitive Data | .env for secrets, never committed | .env.example |

## Security Issues Found

### 1. Release Signing Key
- **المشكلة:** Android release uses debug signing key
- **الموقع:** `mobile/android/app/build.gradle.kts`
- **الخطورة:** متوسطة
- **السبب:** لم يتم تكوين keystore إنتاجي
- **الحل:** إنشاء production keystore خارج المستودع

### 2. Admin Default Password
- **المشكلة:** Default admin password in .env.example
- **الموقع:** `backend/.env.example`
- **الخطورة:** منخفضة (يجب تغييره في الإنتاج)
- **السبب:** للتطوير المحلي فقط
- **الحل:** تغيير كلمة المرور في الإنتاج + استخدام ADMIN_PASSWORD env var

### 3. Cleartext Traffic (Debug)
- **المشكلة:** `usesCleartextTraffic=true` in debug build
- **الموقع:** `app/build.gradle.kts` (debug)
- **الخطورة:** منخفضة (debug فقط)
- **السبب:** للتطوير على localhost
- **الحل:** مفعل فقط في debug، release يحظر cleartext

---

# 30. Validation

## Flutter Client-Side Validation

```dart
// auth_screen.dart
if (name.isEmpty) 'الاسم مطلوب'
if (!email.contains('@')) 'بريد غير صحيح'
if (password.length < 10) 'كلمة المرور 10 أحرف على الأقل'
if (password != confirmPassword) 'كلمتا المرور غير متطابقتين'

// create_listing_screen.dart
if (title.isEmpty) 'العنوان مطلوب'
if (price == null || price <= 0) 'السعر مطلوب'
if (selectedType == null) 'نوع العقار مطلوب'
if (selectedCity.isEmpty) 'المدينة مطلوبة'
```

## Laravel Server-Side Validation

```php
// RegisterRequest.php
'name' => ['required', 'string', 'max:120'],
'email' => ['required', 'email:rfc,dns', 'unique:users,email'],
'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],

// StorePropertyRequest.php
'title' => ['required', 'string', 'max:190'],
'description' => ['required', 'string', 'max:10000'],
'property_type_id' => ['required', 'integer', 'exists:property_types,id'],
'transaction_type' => ['required', 'in:sale,rent'],
'price' => ['required', 'numeric', 'min:0'],
'currency' => ['nullable', 'string', 'size:3', Rule::in(['YER','SAR','USD'])],
'location.city' => ['required', 'string', 'max:120'],
'images' => ['nullable', 'array', 'max:12'],
'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
```

**لماذا التحقق مرتين؟**
1. **Flutter:** تجربة مستخدم فورية ( بدون انتظار الشبكة )
2. **Laravel:** أمان البيانات (التحقق النهائي قبل حفظ في قاعدة البيانات)
3. **Laravel:** حماية من الطلبات اليدوية (curl, Postman) التي تتجاوز Flutter

---

# 31. Error Handling

## HTTP Status Codes في المشروع

| Code | المعنى | كيف يعالج في Laravel | كيف يعالج في Flutter |
|------|--------|----------------------|---------------------|
| 200 | Success | Return resource/collection | Parse JSON → Model.fromJson() |
| 201 | Created | Return resource + 201 | Parse JSON → Model.fromJson() |
| 204 | No Content | Return response(status: 204) | No body expected |
| 401 | Unauthorized | Sanctum rejects token | TokenStore.clear() → redirect to auth |
| 403 | Forbidden | Policy/abort_unless | ApiFailure with message |
| 404 | Not Found | ModelNotFoundException → 404 | ApiFailure with message |
| 422 | Validation Error | FormRequest → validation errors | Extract errors from JSON → notice() |
| 429 | Too Many Requests | RateLimiter → throttle | ApiFailure('طلبات كثيرة جدًا') |
| 500 | Server Error | Laravel exception handler | 'تعذر إتمام الطلب' |

## Flutter Error Flow

```dart
// api_client.dart
try {
  final response = await _dio.get<Map<String, dynamic>>(path);
  return response.data ?? {};
} on DioException catch (error) {
  throw _toFailure(error);
  // ApiFailure(message, statusCode)
}

// In screens:
try {
  await ref.read(provider.notifier).someAction();
} on ApiFailure catch (e) {
  notice(e.message);  // Shows snackbar
} on DioException catch (e) {
  notice('تعذر الاتصال بالخادم');
}
```

## Network Error Handling

```dart
// api_client.dart - _fallbackMessage()
if (connectionTimeout || connectionError) → 'تعذر الاتصال بالخادم. تحقّق من عنوان الشبكة.'
if (receiveTimeout) → 'استغرق الخادم وقتًا أطول من المتوقع.'
default → 'تعذر إتمام الطلب. حاول مرة أخرى.'
```

## 401/403 Auto-Logout

```dart
// providers.dart — _readForSession()
Future<T> _readForSession<T>(Ref ref, T fallback, Future<T> Function() request) async {
  final session = ref.watch(sessionProvider).asData?.value;
  if (session == null) return fallback;
  try {
    return await request();
  } on ApiFailure catch (error) {
    if (error.statusCode == 401 || error.statusCode == 403) {
      unawaited(ref.read(sessionProvider.notifier).clearExpiredSession());
      return fallback;
    }
    rethrow;
  }
}
```

---

# 32. Performance

## الإجراءات المطبقة

| الإجراء | الملف | الوصف |
|---------|-------|-------|
| Image Compression | image_compressor.dart | Max 1920px, JPEG 78, max 600KB before upload |
| Eager Loading | PropertyController@index | with(['type', 'location.*', 'agent.user', 'images']) |
| Pagination | All list endpoints | paginate(15-30) |
| Database Indexes | migrations | Composite indexes on properties for common filters |
| Circuit Breaker | api_client.dart | 350ms cooldown + 20s breaker on rapid failures |
| Cached Network Image | property_card.dart | Memory + disk cache for property images |
| Optimistic UI Updates | toggle_favorite.dart | Update UI before server confirmation |
| Local User Cache | TokenStore | Store user locally for offline + fast startup |
| Min Loading Guard | lux_skeleton.dart | Prevents shimmer flash on fast loads |

## N+1 Query Prevention

```php
// PropertyController@index — Eager Loading
$query = Property::query()
    ->with(['type', 'location.country', 'location.region', 'location.cityReference', 'location.area', 'agent.user', 'images']);

// Without eager loading: N+1 = 1 query for properties + N queries for each relationship
// With eager loading: 1-3 queries total (depending on joins)
```

## التوصيات (غير المُنفَّذة)

| التوصية | السبب |
|---------|-------|
| Redis للـ cache | Database cache أبطأ من Redis |
| API Response Caching | تقليل الطلبات المتكررة |
| Image CDN | تحسين تحميل الصور |
| Search Index (Meilisearch/Elasticsearch) | بحث أسرع من LIKE queries |
| Queue Worker للإشعارات | Current: database queue needs worker |

---

# 33. Technical Glossary

| المصطلح | الإنجليزية | التعريف | الاستخدام في المشروع |
|---------|-----------|---------|---------------------|
| التوثيق | Authentication | التحقق من هوية المستخدم | Sanctum tokens + email/password |
| التفويض | Authorization | التحقق من صلاحيات المستخدم | PropertyPolicy + Spatie Roles |
| النموذج | Model | تمثيل لجدول في قاعدة البيانات | Property, User, Agent, etc. |
| الموارد | API Resources | تحويل Eloquent model لـ JSON | PropertyResource, UserResource |
| الطلبات | Form Requests | كلاسات مخصصة للتحقق | StorePropertyRequest, RegisterRequest |
| الـ Middleware | Middleware | طبقة وسيطة قبل Controller | EnsureUserIsAdmin, InjectSanctumToken |
| الـ Eloquent | Eloquent ORM | مكتبة ORM في Laravel |的所有 Models |
| الـ Provider (Flutter) | Provider | مصدر بيانات في Riverpod | propertiesProvider, sessionProvider |
| الـ Notifier | Notifier | Provider قابل للتعديل | SessionController, FavoriteOverrides |
| الـ Repository | Repository | طبقة عزل بيانات | AuthRepository, PropertyRepository |
| الـ Interceptor | Interceptor | كود يتنفذ مع كل HTTP request | Token attach, challenge solve, rate limit |
| الـ Eager Loading | Eager Loading | تحميل العلاقات دفعة واحدة | ->with(['type', 'images']) |
| الـ Soft Delete | Soft Delete | حذف ناعم (timestamp بدلاً من حذف) | properties.delete() → deleted_at |
| الـ Pivot | Pivot Table | جدول وسيط للعلاقة Many-to-Many | property_feature |
| الـ Slug | Slug | رابط نصي صديق للSEO | property slug = title + random |
| الـ Circuit Breaker | Circuit Breaker | نمط لتقليل الضغط على الخادم | 350ms cooldown + 20s breaker |
| الـ Paginate | Paginate | تقسيم النتائج لصفحات | paginate(15) |
| الـ Enum | Enum (PHP 8.1+) | نوع بيانات محصور | PropertyStatus, TransactionType |
| الـ Cast | Cast | تحويل تلقائي للبيانات | 'password' => 'hashed', 'status' => PropertyStatus::class |
| الـ Scoped | Scoped (Riverpod) | Provider مع معاملات | propertyDetailProvider(id) |
| الـ Immutable | Immutable | كائن لا يتغير | @immutable LuxProperty |
| الـ Barrel Export | Barrel Export | ملف واحد يصدّر كل ملفات المجلد | models.dart, repositories.dart |

---

# 34. Doctor Questions

## 150+ سؤال متوقع

### Project Overview (1-10)

1. **ما اسم التطبيق وموضوعه؟**
   واجهتك (Wajhatak) — تطبيق عقارات عربي أول يربط المشترين بالوكلاء في اليمن والسعودية.

2. **ما المشكلة التي يحلها؟**
   غياب منصة رقمية موحدة للسوق العقاري في اليمن.

3. **من هم المستخدمون؟**
   زوار، عملاء، وكلاء عقاريون، مدير نظام.

4. **ما التقنيات المستخدمة؟**
   Flutter (Riverpod + Dio) → Laravel 12 API (Sanctum + Spatie Permission) → MySQL.

5. **لماذا Flutter وليس React Native؟**
   أداء أفضل (Dart AOT)، دعم RTL أفضل،一套 حلول للمظهر (Material 3).

6. **لماذا Laravel وليس Django/Spring؟**
   بساطة التطوير، ecosystem غني، Eloquent ORM قوي، Sanctum للتوكنات.

7. **كيف يتواصل Flutter مع Laravel؟**
   REST API مع JSON. Dio HTTP client في Flutter، API routes في Laravel.

8. **ما الذي يجعل هذا التطبيق مختلفاً؟**
   عربي أول، نظام تحدي InfinityFree المدمج، محادثات بطاقة عقار (WhatsApp-style).

9. **ما هي韭خFeatures الرئيسية؟**
   بحث وفلترة، مفضلة، محادثات، طلبات معاينة، إدارة عقارات، إشعارات.

10. **هل التطبيق مكتمل؟**
    نعم، جميع الميزات الأساسية مُنفَّذة ومُختبرة.

### Flutter (11-30)

11. **ما Flutter؟**
    Framework من Google لبناء تطبيقات mobile/desktop/web من كود واحد بلغة Dart.

12. **ما Dart؟**
    لغة برمجة的对象-oriented من Google، compiled لـ native code.

13. **كيف يعمل FlutterRendering Pipeline؟**
    Widget → Element → RenderObject → Layer → GPU.

14. **ما الفرق بين StatelessWidget وStatefulWidget؟**
    Stateless: ثابت، لا state محلي. StatefulWidget: له state محلي يتغير.

15. **ما ConsumerWidget؟**
    Widget من Riverpod يمكنه قراءة Providers (يحتاج WidgetRef).

16. **ما ProviderScope؟**
    Widget في main.dart يوفر كل الـ providers للتطبيق.

17. **ما FutureProvider؟**
    Provider يُرجع Future — للبيانات غير المتزامنة.

18. **ما AsyncNotifierProvider؟**
    Provider قابل للتعديل ويعمل مع Future (SessionController).

19. **ما Widget tree في هذا التطبيق؟**
    MaterialApp → Directionality → SessionGate → AppShell → (Screens).

20. **ما CustomPainter؟**
    كلاس لرسم رسومات مخصصة على Canvas (المbranch logo).

21. **لماذا تستخدم cached_network_image؟**
    لتخزين الصور محلياً وتقليل تحميل الشبكة المتكرر.

22. **ما Dio؟**
    مكتبة HTTP للdart مع interceptors وcancel tokens وFormData support.

23. **لماذا تستخدم Dio بدلاً من http؟**
    Interceptors essential للتوكن والتحديات.

24. **ما FlutterSecureStorage؟**
    مكتبة لتخزين البيانات المشفرة على الجهاز (للتوكن).

25. **ما image_picker؟**
    مكتبة لاختيار صور من الكاميرا أو المعرض.

26. **ما flutter_map؟**
    مكتبة خريطة مجانية (OpenStreetMap) بدون API key.

27. **ما CachedNetworkImageProvider؟**
    Provider يعرض صورة مع placeholder وerror widget.

28. **ما MediaQuery؟**
    وسيلة للحصول على معلومات الشاشة (حجم، notch، orientation).

29. **ما SafeArea؟**
    Widget يضع المحتوى داخل منطقة آمنة (بعيد عن notch/status bar).

30. **ما Expanded وFlexible؟**
    Widget يملأ المساحة المتاحة. Expanded = Flexible(fit: FlexFit.tight).

### Dart (31-45)

31. **ما async/await؟**
    لمعالجة العمليات غير المتزامنة في Dart.

32. **ما Future<T>؟**
    كائن يمثل نتيجة عملية غير متزامنة ستحدث مستقبلاً.

33. **ما Stream<T>؟**
    تسلسل من القيم يمكن الاستماع لها مراراً (real-time).

34. **ما @immutable؟**
    annotation تدل على أن الكائن لا يتغير بعد إنشائه.

35. **ما final في Dart؟**
    متغير لا يمكن تغييره بعد التهيئة (like const但在运行时).

36. **ما copyWith()؟**
    Method تُرجع نسخة من الكائن مع تغييرات محددة.

37. **ما factory constructor؟**
    Constructor يمكنه إرجاع كائن مختلف (like fromJson).

38. **ما cascade notation (..)؟**
    سلسلة استدعاءات على نفس الكائن.

39. **ما ternary operator؟**
    شرط inline: condition ? trueValue : falseValue.

40. **ما spread operator (...)؟**
    فتح collection داخل另一位 collection.

41. **ما null safety؟**
    نظام يمنع null errors في compile-time.

42. **ما late keyword؟**
    متغير سيتم تهيئته لاحقاً (ليس الآن).

43. **ما optional chaining (?.)؟**
    الوصول لخاصية قد تكون null بأمان.

44. **ما collection if？**
    if داخل list/map literal لإضافة عنصر شرطي.

45. **ما unawaited()؟**
    تشغيل Future بدون الانتظار (fire-and-forget).

### State Management (46-60)

46. **ما هي مشكلة إدارة الحالة؟**
    كيفية تحديث UI عندما تتغير البيانات.

47. **لماذا Riverpod وليس setState؟**
    setState: محلي فقط، لا testable. Riverpod: global, testable, type-safe.

48. **ما AsyncValue؟**
    كائن يمثل حالة Future: loading, data, error.

49. **كيف يتم معالجة loading في هذا التطبيق؟**
    AsyncValue.when(data:, loading: → skeleton, error: → retry).

50. **ما LuxAsyncView؟**
    Widget مخصص يلف AsyncValue ويعالج الحالات الثلاث.

51. **ما favoriteOverridesProvider؟**
    Map مؤقتة لتحديث المفضلة فورياً قبل تأكيد الخادم.

52. **ما sessionProvider؟**
    مصدر الحقيقة الوحيد للمصادقة — AsyncNotifierProvider.

53. **كيف يتغير شكل التطبيق عند تسجيل الدخول؟**
    sessionProvider: null → SessionData → AppShell shows all tabs.

54. **ما ref.watch vs ref.read؟**
    watch: يعيد بناء Widget عند التغيير. read: قراءة لمرة واحدة.

55. **ما ref.invalidate()؟**
    إلغاء باقي cache provider لإعادة بنائه.

56. **ما Provider.family؟**
    Provider مع معامل (如 propertyDetailProvider(id)).

57. **كيف يعمل ExploreSearchNotifier؟**
    NotifierProvider<String?> يخزن نص البحث current.

58. **ما الدور المتزامن (Async) في providers.dart؟**
    `_readForSession()` يعالج 401/403 تلقائياً مع clearExpiredSession().

59. **لماذا ref.watch(sessionProvider) في propertiesProvider؟**
    لإعادة تحميل العقارات عند تغيير حالة المصادقة.

60. **كيف يعمل _afterSessionChanged()؟**
    يُبطل favoriteOverrides و exploreSearch بعد تغيير الجلسة.

### API & HTTP (61-80)

61. **ما REST API؟**
    واجهة برمجة تعتمد على HTTP methods (GET, POST, PATCH, DELETE).

62. **ما JSON؟**
    تنسيق بيانات خفيف: {key: value}.

63. **ما الفرق بين GET وPOST وPATCH وDELETE؟**
    GET: قراءة. POST: إنشاء. PATCH: تعديل جزئي. DELETE: حذف.

64. **ما Content-Type: application/json؟**
    يخبر الخادم أن البيانات بصيغة JSON.

65. **ما FormData؟**
    تنسيق لإرسال ملفات (multipart/form-data).

66. **ما Bearer Token؟**
    آلية مصادقة: Authorization: Bearer {token}.

67. **ما Rate Limiting؟**
    تقييد عدد الطلبات في فترة زمنية.

68. **ما HTTP 422؟**
    Unprocessable Entity — خطأ في التحقق من البيانات.

69. **ما HTTP 429؟**
    Too Many Requests — تجاوز Rate Limit.

70. **ما pagination؟**
    تقسيم النتائج لصفحات (page 1, 2, 3...).

71. **ما serialize/deserialize؟**
    Serialize: تحويل object → JSON. Deserialize: JSON → object.

72. **ما interceptor؟**
    كود يتنفذ قبل/بعد كل HTTP request.

73. **لماذا إرسال التوكن في X-Auth-Token أيضاً؟**
    بعض الاستضافات تحذف Authorization header — backup mechanism.

74. **ما circuit breaker pattern؟**
    نمط يتوقف عن إرسال الطلبات مؤقتاً عند فشل متكرر.

75. **ما throttling في Flutter؟**
    فرض فجوة زمنية (350ms) بين الطلبات.

76. **كيف يعالج التطبيق 401 Unauthorized؟**
    يمسح التوكن تلقائياً ويُعيد التوجيه لشاشة الدخول.

77. **ما challenge solver؟**
    كود يحل تحدي InfinityFree AES-128-CBC للسماح بالطلبات.

78. **لماذا Payload headers مهمة؟**
    تخبر الخادم بنوع البيانات المرسلة والمقبولة.

79. **ما paginated response في Laravel؟**
    response يحتوي data + meta (current_page, last_page, total).

80. **كيف يتعامل Dio مع أخطاء الشبكة؟**
    DioException → type (connectionTimeout, etc.) → ApiFailure message.

### Laravel & PHP (81-110)

81. **ما Laravel؟**
    PHP framework for web applications (MVC pattern).

82. **ما MVC؟**
    Model-View-Controller: فصل المنطق والعرض والتحكم.

83. **ما Eloquent؟**
    ORM في Laravel — كل model يمثل جدول.

84. **ما Migration؟**
    ملف PHP يصف تغييرات database schema.

85. **ما Seeder؟**
    ملف يُدخل بيانات أولية في قاعدة البيانات.

86. **ما Factory؟**
    كلاس يُنشئ بيانات وهمية للاختبار.

87. **ما Policy؟**
    كلاس يحدد صلاحيات المستخدم على مورد معين.

88. **ما FormRequest؟**
    كلاس مخصص للتحقق من صحة الطلب.

89. **ما API Resource؟**
    كلاس يحول Eloquent model لـ JSON مُنسق.

90. **ما Middleware؟**
    طبقة تتنفذ قبل/بعد Controller.

91. **ما Sanctum؟**
    نظام مصادقة API في Laravel (Personal Access Tokens).

92. **ما Spatie Permission؟**
    مكتبة لإدارة الأدوار والصلاحيات.

93. **ما Blade؟**
    محرك قوالب Laravel للعرض.

94. **ما artisan؟**
    سطر أوامر Laravel لأداء المهام.

95. **ما DB::transaction؟**
    عملية database تضمن أن كل شيء ينجح أو لا شيء ينجح.

96. **ما Carbon؟**
    مكتبة معالجة التاريخ والوقت في PHP.

97. **ما Hash::make()؟**
    تشفير كلمة المرور بـ bcrypt.

98. **ما config() function؟**
    قراءة ملفات الإعدادات في config/.

99. **ما asset() function؟**
    إنشاء رابط لملف ثابت (storage/app/public).

100. **ما abort() function؟**
    إرسال استجابة خطأ (403, 404, etc.).

101. **ما abort_unless()؟**
    abort إذا الشرط غير محقق.

102. **ما firstOrCreate()؟**
    البحث عن سجل أو إنشاؤه إذا لم يوجد.

103. **ما updateOrCreate()؟**
    تحديث سجل أو إنشاؤه إذا لم يوجد.

104. **ما SoftDeletes؟**
    حذف ناعم: يضع deleted_at بدلاً من حذف السطر.

105. **ما MorphTo/MorphMany؟**
    علاقات polymorphic (notification notifiable).

106. **ما Enum (PHP 8.1)؟**
    نوع بيانات محصور بقيم محددة.

107. **ما nullable() في migration؟**
    يسمح بقيمة NULL في العمود.

108. **ما constrained() في migration؟**
    يضيف foreign key constraint.

109. **ما cascadeOnDelete()؟**
    حذف السجلات المرتبطة عند حذف السجل الأصلي.

110. **ما Storage facade؟**
    واجهة للتعامل مع ملفات filesystem.

### Database (111-130)

111. **ما اسم قاعدة البيانات؟**
    wajhatak.

112. **كم عدد الجداول؟**
    +20 جدول (users, agents, properties, ...).

113. **ما primary key؟**
    العمود الفريد الذي يحدد كل سطر (id).

114. **ما foreign key؟**
    عمود يربط جدول بآخر (agent_id → agents.id).

115. **ما index؟**
    هيكل بيانات يسرّع الاستعلامات.

116. **ما composite index؟**
    index يتكون من أكثر من عمود (status + transaction_type + price).

117. **ما unique constraint؟**
    يمنع تكرار قيمة في عمود أو مجموعة أعمدة.

118. **ما pivot table؟**
    جدول وسيط لعلاقة Many-to-Many (property_feature).

119. **ما the difference between hasMany and belongsTo؟**
    hasMany: السجل الحالي يملك سجلات كثيرة. belongsTo: السجل الحالي ينتمي لسجل آخر.

120. **ما eager loading في Eloquent؟**
    تحميل العلاقات في نفس الاستعلام (->with()) لتجنب N+1.

121. **ما N+1 problem؟**
    استعلام واحد للبيانات + N استعلامات للعلاقات = مشكلة أداء.

122. **ما the purpose of timestamps()?**
    يضيف created_at و updated_at تلقائياً.

123. **ما the difference between delete() and forceDelete()؟**
    delete(): soft delete (يضع timestamp). forceDelete(): حذف حقيقي.

124. **ما the purpose of slug column؟**
    رابط صديق للSEO بدل ID.

125. **ما the difference between whereHas and with?**
    whereHas: فلترة بناءً على علاقة. with: تحميل العلاقة.

126. **ما paginate() doing?**
    يقسم النتائج ويُرجع metadata (current_page, last_page, etc.).

127. **ما the purpose of is_active column?**
    تنشيط/تعطيل سجل بدون حذفه.

128. **ما the difference between enum and string?**
    Enum: محصور بقيم محددة compile-time. String: أي نص.

129. **ما the purpose of reference_code for properties?**
    كود فريد للعقار يظهر للمستخدم (LUX-260904-ABC123).

130. **ما the difference between cast and accessor?**
    Cast: تحويل تلقائي للبيانات. Accessor: حساب قيمة مشتقة.

### Security (131-145)

131. **كيف تحمي كلمة المرور؟**
    Hash::make() with bcrypt (12 rounds via config).

132. **لماذا لا نحفظ كلمة المرور؟**
    لأنها مشفرة بشكل غير قابل للاختراق.

133. **ما SQL Injection؟**
    هجوم يحقن SQL عبر مدخلات المستخدم.

134. **كيف يمنعه Laravel؟**
    Eloquent يใช parameterized queries تلقائياً.

135. **ما Mass Assignment؟**
    هجوم يملأ كل الحقول دفعة واحدة.

136. **كيف يمنعه Laravel؟**
    $fillable property تحدد الحقول المسموح بها.

137. **ما Rate Limiting؟**
    تقييد عدد الطلبات (6/minute للتسجيل).

138. **ما the purpose of CORS؟**
    تحديد_origins المسموح لها بالوصول للـ API.

139. **ما X-Auth-Token header؟**
    رأس مخصص لتجاوز حذف Authorization في LiteSpeed.

140. **ما AdminUrlGuard doing؟**
    حماية لوحة التحكم: حظر user_id spoofing + security headers.

141. **ما last admin protection؟**
    منع حذف/تعطيل آخر مستخدم admin.

142. **ما the purpose of is_active on users?**
    تعطيل حساب بدون حذفه.

143. **ما property ownership check؟**
    التحقق من أن المستخدم هو صاحب العقار قبل التعديل/الحذف.

144. **ما session fixation prevention؟**
    منع اختطاف الجلسة عبر تغيير session ID.

145. **ما open redirect protection？**
    منع التحويل لمواقع خبيثة بعد تسجيل الدخول.

### Architecture (146-155)

146. **ما Repository Pattern؟**
    طبقة تجعل Flutter غير مرتبط بـ API directly.

147. **ما the benefit？**
    تستبدل API بـ Mock في الاختبارات.

148. **ما API Resources في Laravel؟**
    كلاسات تحول Eloquent model لـ JSON مُنسق.

149. **ما the difference between API Resource and直接 toJson()？**
    Resource: يدعم conditional loading, relationships, pagination. toJson: بسيط.

150. **ما Database Queue？**
    نظام لتأجيل العمليات (如 notifications) لتحسين الأداء.

151. **ما the difference between同步 and async notification？**
    Sync: ينتظر الانتهاء. Async: يعمل في الخلفية.

152. **ما the purpose of Enums in this project？**
    يضمن أن فقط القيم المحددة مقبولة (PropertyStatus, TransactionType).

153. **ما the benefit of data-driven currencies？**
    يمكن إضافة عملة جديدة من قاعدة البيانات بدون كود.

154. **ما hierarchical locations doing？**
    هيكل: Country > Region > City > Area مع cascading dropdowns.

155. **ما the WhatsApp-style property card in chat？**
    رسالة أولى في المحادثة تعرض معلومات العقار كبطاقة.

---

# 35. Trick Questions

1. **ماذا يحدث إذا فشل API بعد اختيار صورة في CreateListing؟**
   الصورة تبقى في الذاكرة (File object) — لا تُحذف. عند إعادة المحاولة، الصورة الجاهزة تُرسل مرة أخرى.

2. **لماذا نتحقق من البيانات في Flutter وLaravel؟**
   Flutter: تجربة مستخدم فورية (بدون انتظار الشبكة). Laravel: أمان (حماية من الطلبات اليدوية).

3. **ماذا يحدث إذا حذف المستخدم عقاراً لديه Favorites؟**
   `cascadeOnDelete` على `favorites.property_id` → تُحذف جميع المفضلة المرتبطة تلقائياً.

4. **ماذا يحدث عند انتهاء Token؟**
   Sanctum token لا ينتهي (`expiration: null`). ينتهي client-side بعد 30 يوم (TokenStore sessionDuration). عند الانتهاء → clear → redirect to login.

5. **ماذا يحدث عند إرسال Request بدون Authentication؟**
   Laravel `auth:sanctum` middleware → 401 Unauthorized.

6. **ماذا يحدث إذا أرسل المستخدم Price نصياً؟**
   Laravel validation: `price: required, numeric` → 422 Unprocessable Entity.

7. **لماذا استخدمت Repository؟**
   لفصل منطق البيانات عن واجهة المستخدم وجعل الاختبارات أسهل (inject mock repository).

8. **ماذا يحدث لو أزلنا Repository؟**
   الشاشات ستصبح مباشرة مرتبطة بالـ ApiClient — صعوبة تبديل API أو اختبار.

9. **لماذا نستخدم Eager Loading؟**
   لتجنب N+1 query problem — تحميل العلاقات في استعلام واحد بدلاً من N استعلام.

10. **ما مشكلة N+1؟**
    استعلام واحد يجلب 100 عقار، ثم 100 استعلام إضافي لكل علاقة (type, location, images) = 400+ استعلام.

11. **لماذا belongsToMany مع pivot للمميزات؟**
    عقدة Many-to-Many: ميزة واحدة لعدة عقارات، عقار واحد لعدة ميزات.

12. **كيف يتم حساب نتائج البحث؟**
    Flutter: PropertyQuery.toParameters() → URL params → Laravel: applyFilters() → Eloquent where/whereHas → paginate.

13. **أين يتم تنفيذ الفلترة؟**
    في Laravel (خادم). Flutter يرسل المعايير فقط.

14. **لماذا ليست الفلترة كلها في Flutter؟**
    لأن قاعدة البيانات أسرع بكثير من فلترة كل العقارات في الذاكرة.

15. **كيف تحمي Upload من الملفات الخبيثة؟**
    Laravel validates: `image`, `mimes:jpg,png,webp`, `max:8192` → rejects non-images.

16. **أين تحفظ Token؟**
    FlutterSecureStorage (مشفر على الجهاز) — وليس SharedPreferences.

17. **لماذا لا نحفظ Password؟**
    Hash::make() makes it irreversible. Storage of plain password = massive security risk.

18. **ماذا يحدث عند HTTP 422؟**
    Laravel يُرجع JSON مع أخطاء التحقق. Flutter يستخرج الرسائل ويعرضها بـ notice().

19. **من المسؤول عن التحقق من صلاحيات المستخدم؟**
    Laravel: PropertyPolicy + middleware. Flutter: لا يتحقق (يعتمد على الخادم).

20. **ماذا يحدث إذا أرسل المستخدم currency غير مدعومة؟**
    Laravel validation: `Rule::in(['YER','SAR','USD'])` → 422 error.

---

# 36. Presentation Script

## Script العرض أمام اللجنة

### 1. المقدمة (30 ثانية)

"السلام عليكم. 프로젝트 اسمه **وجهتك (Wajhatak)** — تطبيق عقارات عربي أول يربط بين المشترين والوكلاء العقاريين. مبني بـ Flutter للواجهة وLaravel للخادم."

### 2. المشكلة (30 ثانية)

"السوق العقاري في اليمن يعتمد على الإعلانات الورقية. لا توجد منصة رقمية تجمع العقارات وتبحث فيها وتتواصل مع الوكلاء. تطبيقنا يحل هذه المشكلة."

### 3. المستخدمون (20 ثانية)

"أربعة أدوار: زائر يتصفح، عميل مسجل يستخدم المفضلة والمحادثات، وكيل يُنشئ العقارات، ومدير يتحكم بالكامل."

### 4. Flutter (40 ثانية)

"واجهة Flutter بـ 16 شاشة و37 ملف. نستخدم Riverpod لإدارة الحالة وDio للاتصال بالخادم. تقسيم طبقات واضح: UI → State → Repository → API."

(افتح main.dart)
"هنا Entry Point — MaterialApp مع RTL عربي وسمة فاتحة/داكنة."

(افتح providers.dart)
"هنا كل الـ Providers — session management، عقارات، مفضلة، محادثات."

### 5. Laravel (40 ثانية)

"Backend Laravel 12 مع Sanctum للمصادقة وSpatie للصلاحيات. 11 API controller و20 model و12 migration."

(افتح api.php)
"هنا جميع الـ Routes: عقارات (public)، مصادقة (throttled)، عمليات مسجلة (authenticated)."

### 6. Architecture (30 ثانية)

"معمارية واضحة: Flutter Repository Pattern ↔ Laravel MVC. الاتصال عبر REST API مع JSON. التوكن يُخزن في FlutterSecureStorage."

### 7. Demo (5 دقائق)

**افتح التطبيق:**

1. **الرئيسية:** "شوف الشاشة الرئيسية — بحث سريع، عقارات مميزة، عقارات جديدة."

2. **البحث:** "لنبحث عن شقة في صنعاء..."

   (افتح ExploreScreen، اكتب "شقة"، اختر "صنعاء")

3. **التفاصيل:** "نفتح تفاصيل عقار..."

   (اضغط على PropertyCard)

4. **المفضلة:** "نضيف للمفضلة..."

   (اضغط على القلب)

5. **المحادثة:** "نفتح محادثة مع الوكيل..."

   (اضغط "تواصل مع الوكيل")

6. **طلب المعاينة:** "نطلب معاينة..."

   (اضغط "طلب معاينة" → اختر تاريخ ووقت)

7. **لوحة تحكم الوكيل:** "لو نسجل كوكيل..."

   (افتح AgentDashboard — إحصائيات + إدارة عقارات)

### 8. الأهم التحديات (30 ثانية)

"أهم تحدي كان التعامل مع InfinityFree — استضافة مجانية تحظر الطلبات الآلية. بنينا solver يحل تحدي AES-128-CBC في كل طلب. تحدي آخر: تأمين التوكن مع LiteSpeed عبر X-Auth-Token header."

### 9. الخاتمة (20 ثانية)

"التطبيق مبني بمعمارية نظيفة مع فصل واضح للطبقات. جميع الميزات الأساسية مُنفَّذة. شكراً لكم."

---

# 37. Defense Checklist

## Before Discussion — تأكد أنك تعرف:

### Architecture
- [ ] تقسيم الطبقات (Flutter: UI → State → Repository → API | Laravel: Routes → Middleware → Controller → Model)
- [ ] Repository Pattern في Flutter ولماذا
- [ ] MVC في Laravel
- [ ] الاتصال عبر REST API + JSON

### Flutter Structure
- [ ] main.dart entry point
- [ ] ProviderScope في main
- [ ] SessionGate → AppShell flow
- [ ] Bottom Navigation (5 tabs)
- [ ] AppDrawer

### Laravel Structure
- [ ] routes/api.php (v1 prefix)
- [ ] Middleware stack (inject.sanctum.token → auth:sanctum)
- [ ] Controller organization (Api/V1, Admin, Auth)
- [ ] Form Requests for validation
- [ ] API Resources for JSON transformation

### API
- [ ] All endpoints (auth, properties, favorites, conversations, viewing requests, notifications, taxonomy)
- [ ] Public vs Authenticated routes
- [ ] Rate limiting on register/login
- [ ] Pagination

### Authentication
- [ ] Register flow (Flutter → Laravel → Token → Storage)
- [ ] Login flow
- [ ] Logout flow (API + clear token + clear state)
- [ ] Session restoration (restore() in AuthRepository)
- [ ] Token storage (FlutterSecureStorage)
- [ ] 30-day session duration (client-side)
- [ ] 401/403 auto-logout

### Authorization
- [ ] 3 roles: admin, agent, user
- [ ] PropertyPolicy (view/create/update/delete)
- [ ] Role-based access in controllers (abort_unless)
- [ ] Spatie Permission package

### Database
- [ ] 20+ tables
- [ ] Key relationships (User-Agent, Property-Location, Property-Features pivot)
- [ ] SoftDeletes on properties
- [ ] Composite indexes for filtering
- [ ] Unique constraints (favorites, conversations)

### Main Screens
- [ ] HomeScreen (featured + new properties)
- [ ] ExploreScreen (search + filter + grid)
- [ ] PropertyDetailScreen (gallery + info + agent + map)
- [ ] AuthScreen (login/register)
- [ ] CreateListingScreen (multi-section form)
- [ ] ChatScreen (messaging with property cards)

### State Management
- [ ] Riverpod providers (50+ providers)
- [ ] SessionController (AsyncNotifier)
- [ ] FutureProvider for data
- [ ] favoriteOverrides for optimistic updates
- [ ] ExploreSearchNotifier for search state

### Search & Filtering
- [ ] PropertyQuery model → toParameters()
- [ ] Laravel applyFilters() method (13+ filters)
- [ ] Server-side filtering (not client-side)

### Favorites
- [ ] Idempotent (firstOrCreate in Laravel)
- [ ] Optimistic UI update (favoriteOverridesProvider)
- [ ] Confirmation dialog for remove

### Image Upload
- [ ] ImageCompressor (max 1920px, quality 78)
- [ ] FormData multipart upload
- [ ] Laravel validation (mimes, max size)
- [ ] Public storage disk

### Packages
- [ ] flutter_riverpod (state management)
- [ ] dio (HTTP client)
- [ ] flutter_secure_storage (encrypted storage)
- [ ] cached_network_image (image caching)
- [ ] flutter_map (maps)
- [ ] laravel/sanctum (API auth)
- [ ] spatie/laravel-permission (roles)
- [ ] image_picker (camera/gallery)

### Security
- [ ] Hash::make for passwords
- [ ] Sanctum tokens
- [ ] Form Request validation
- [ ] PropertyPolicy authorization
- [ ] Rate limiting
- [ ] InfinityFree challenge solver
- [ ] InjectSanctumToken middleware
- [ ] Circuit breaker pattern

### Known Issues
- [ ] Android release uses debug signing key
- [ ] No Redis (database queue/cache)
- [ ] No Elasticsearch (LIKE queries for search)
- [ ] No push notification delivery (only database notifications)

---

*تم إعداد هذه الوثيقة بالكامل بناءً على تحليل الكود الفعلي للمشروع — أي خطوة يمكن تتبعها في ملفات المشروع الحقيقية.*
