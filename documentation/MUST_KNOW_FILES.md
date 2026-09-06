# MUST KNOW Files & Database Deep-Dive

## الملفات التي يجب أن تعرفها قبل المناقشة

### MUST KNOW — (افتح وافهم بنفسك)

| الملف | لماذا مهم | ما يجب أن تفهمه |
|-------|-----------|-----------------|
| `mobile/lib/main.dart` | نقطة البداية | ProviderScope, MaterialApp, RTL, theme |
| `mobile/lib/state/providers.dart` | عصبية الحالة — كل الـ providers | sessionProvider + أساليب SessionController + _readForSession() |
| `mobile/lib/data/api_client.dart` | كل منطق HTTP | Dio interceptors, TokenStore, error handling, circuit breaker |
| `mobile/lib/data/repositories/auth_repository.dart` | المصادقة | login/register/restore/logout flows |
| `mobile/lib/data/repositories/property_repository.dart` | العقارات | CRUD + فلاتر + images |
| `mobile/lib/data/models/property.dart` | نموذج العقار | fromJson, copyWith, coverUrl, isRent |
| `mobile/lib/ui/screens/shell/app_shell.dart` | الهيكل الرئيسي | bottom nav, drawer, FAB, notifications polling |
| `mobile/lib/ui/screens/shell/session_gate.dart` | Splash → AppShell | sessionProvider.when() |
| `mobile/lib/ui/screens/auth/auth_screen.dart` | تسجيل/دخول | form validation, SessionController calls |
| `mobile/lib/ui/screens/home/home_screen.dart` | الرئيسية | hero search, sections, grid |
| `mobile/lib/ui/screens/property/property_detail_screen.dart` | التفاصيل | gallery, facts, agent card, map, actions |
| `backend/routes/api.php` | كل الـ API routes | public/authenticated/throttle groups |
| `backend/app/Http/Controllers/Api/V1/AuthController.php` | المصادقة | register/login/logout, token creation |
| `backend/app/Http/Controllers/Api/V1/PropertyController.php` | العقارات | index+applyFilters, store, update, destroy |
| `backend/app/Http/Controllers/Api/V1/FavoriteController.php` | المفضلة | firstOrCreate idempotent |
| `backend/app/Http/Controllers/Api/V1/ViewingRequestController.php` | الطلبات | role-based update |
| `backend/app/Http/Controllers/Api/V1/ConversationController.php` | المحادثات | uniqueness, property card message |
| `backend/app/Http/Requests/StorePropertyRequest.php` | التحقق | كل rules + Arabic messages |
| `backend/app/Http/Requests/Auth/RegisterRequest.php` | التحقق | password rules |
| `backend/app/Http/Resources/PropertyResource.php` | JSON output | toArray conditional fields |
| `backend/app/Policies/PropertyPolicy.php` | التفويض | view/create/update/delete |
| `backend/app/Models/Property.php` | العقار | relationships, casts, SoftDeletes |
| `backend/app/Models/User.php` | المستخدم | HasApiTokens, HasRoles, cast password hashed |
| `backend/app/Http/Middleware/InjectSanctumToken.php` | الأمان | X-Auth-Token → Authorization |
| `backend/database/migrations/2026_08_24_171000_create_lux_domain_tables.php` | DB schema | كل الجداول + indexes |

### IMPORTANT

| الملف | لماذا مهم |
|-------|-----------|
| `mobile/lib/ui/widgets/property/property_card.dart` | بطاقة العقار مع carousel + favorite |
| `mobile/lib/ui/widgets/drawer/app_drawer.dart` | القائمة الجانبية |
| `mobile/lib/ui/widgets/navigation/bottom_nav_bar.dart` | شريط التنقل المخصص |
| `mobile/lib/ui/widgets/shared/app_header.dart` | الهيدر الموحد |
| `mobile/lib/ui/widgets/skeleton/lux_skeleton.dart` | async wrapper + skeletons |
| `mobile/lib/core/utils/image_compressor.dart` | ضغط الصور |
| `mobile/lib/core/services/infinityfree_challenge_solver.dart` | حل تحدي الاستضافة |
| `mobile/lib/core/services/lux_notification_service.dart` | الإشعارات المحلية |
| `mobile/lib/data/models/property_query.dart` | معايير البحث |
| `mobile/lib/data/repositories/conversation_repository.dart` | المحادثات |
| `mobile/lib/data/repositories/taxonomy_repository.dart` | التصنيفات |
| `mobile/lib/data/repositories/viewing_request_repository.dart` | الطلبات |
| `mobile/lib/ui/screens/listings/create_listing_screen.dart` | إنشاء عقار |
| `mobile/lib/ui/screens/listings/edit_listing_screen.dart` | تعديل عقار |
| `mobile/lib/ui/screens/chat/chat_screen.dart` | المحادثة |
| `mobile/lib/ui/screens/explore/explore_screen.dart` | البحث |
| `mobile/lib/ui/screens/saved/saved_screen.dart` | المفضلة |
| `mobile/lib/ui/screens/profile/profile_screen.dart` | الملف الشخصي |
| `mobile/lib/ui/screens/settings/settings_screen.dart` | الإعدادات |
| `mobile/lib/ui/screens/viewing_requests/viewing_requests_screen.dart` | الطلبات |
| `backend/app/Models/Conversation.php` | علاقات المحادثة |
| `backend/app/Models/Agent.php` | الوكيل |
| `backend/app/Http/Requests/Auth/LoginRequest.php` | rate limiting |
| `backend/app/Http/Resources/UserResource.php` | JSON المستخدم |
| `backend/app/Http/Middleware/EnsureUserIsAdmin.php` | admin security |
| `backend/app/Http/Middleware/AdminUrlGuard.php` | admin security headers |
| `backend/app/Notifications/` (3 files) | notifications |
| `backend/database/seeders/DatabaseSeeder.php` | roles/permissions setup |

### REFERENCE — (اعرف الوظيفة عامة)

| الملف | الوظيفة |
|-------|---------|
| كل العمليات داخل `backend/app/Console/Commands/` | artisan command لإنشاء admin |
| `backend/app/Enums/` (4 files) | enums |
| `backend/app/Models/ActivityLog.php` | logging |
| `backend/app/Models/Setting.php` | settings |
| `backend/app/Models/UserNotificationPreference.php` | notification prefs |
| `backend/app/Models/UserDevice.php` | devices |
| `backend/app/Models/Country/Region/City/Area.php` | location hierarchy |
| `backend/app/Models/PropertyImage.php` | image accessors |
| `backend/database/migrations/` (12 files) | all migrations |
| `backend/database/seeders/` (5 files) | seeders |
| `backend/config/currencies.php` | currencies |
| `backend/config/sanctum.php` | token config |
| `backend/config/permission.php` | spatie config |
| `mobile/lib/core/theme/app_theme.dart` | colors + theme |
| `mobile/lib/core/utils/format_money.dart` | formatting |
| `mobile/lib/core/utils/notice.dart` | snackbar |
| `mobile/lib/core/utils/confirm_logout.dart` | logout dialog |
| `mobile/lib/core/utils/responsive.dart` | responsive breakpoints |
| `mobile/lib/ui/brand.dart` | logo painter |
| `mobile/test/` | tests |

---

# Database Deep-Dive

## مخطط العلاقات

```
users ──< agents >────< properties >────< property_images
  │          │              │
  │          │              └───< property_feature >──── property_features
  │          │              │
  │          └──────────────└───> property_locations
  │                                  │
  │                                  ├──> countries <── regions <── cities <── areas
  │                                  │
  │< favorites ────< properties      │
  │
  │< conversations >──── properties (client_id / agent_id)
  │       └───< messages
  │
  │< viewing_requests >── properties (client_id / agent_id)
  │
  │< user_notification_preferences
  │< user_devices
  │< saved_searches
```

## خصائص كل جدول

### users
- **PK:** id
- **Unique:** email, phone
- **Columns:** name, email, email_verified_at, password, remember_token, phone, avatar_path, locale (default ar), is_active (default true, indexed)
- **Casts:** email_verified_at (datetime), password (hashed), is_active (boolean)

### agents
- **PK:** id
- **Unique:** user_id, license_number
- **FK:** user_id → users (cascadeDelete)
- **Casts:** rating (decimal:2), is_active (boolean)

### properties
- **PK:** id
- **Unique:** slug, reference_code
- **FK:** agent_id → agents (restrict), property_type_id → property_types (restrict), property_location_id → property_locations (restrict)
- **SoftDeletes:** deleted_at
- **Indexes:** status, transaction_type, price, area, bedrooms, bathrooms, is_furnished, is_new, is_featured, published_at, composite [status,transaction_type,price], [property_type_id,status]
- **Casts:** transaction_type (enum), status (enum), price (decimal:2), area (decimal:2), all booleans, published_at (datetime)

### property_images
- **PK:** id
- **FK:** property_id → properties (cascadeDelete)
- **Indexes:** [property_id, sort_order], is_cover
- **Cols:** path, alt_text, sort_order, is_cover

### favorites
- **PK:** id
- **Unique:** [user_id, property_id]
- **FK:** user_id, property_id (cascadeDelete)

### conversations
- **PK:** id
- **Unique:** [client_id, agent_id]
- **FK:** property_id (nullable, cascadeDelete), client_id → users, agent_id → users
- **Cols:** last_message_at

### messages
- **PK:** id
- **FK:** conversation_id, sender_id → users, property_id (nullable)
- **Cols:** body (text), message_type (default text), read_at
- **Indexes:** [conversation_id, created_at]

### viewing_requests
- **PK:** id
- **FK:** property_id, client_id → users, agent_id → agents
- **Cols:** scheduled_date, scheduled_time, notes, status (enum)
- **Indexes:** [agent_id,status,scheduled_date], [client_id,status]

### property_types / property_features
- **PK:** id
- **Unique:** slug
- **Cols:** name_ar, name_en, is_active

### property_locations
- **PK:** id
- **FK:** country_id, region_id, city_id, area_id (all nullable, nullOnDelete)
- **Cols:** city (indexed), district, neighborhood, address, latitude, longitude

### countries / regions / cities / areas
- **PK:** id
- **FK:** hierarchy (child → parent)
- **Cols:** name_ar, name_en, is_active (+ code, currency_code for countries)

### settings
- **PK:** id
- **Unique:** key
- **Cols:** value, type

### notifications (Laravel)
- **PK:** id (uuid)
- **Morph:** notifiable
- **Cols:** type, data, read_at

### activity_logs
- **PK:** id
- **FK:** user_id, subject (morphTo)
- **Cols:** log_name, description, subject_type, subject_id, ip_address, user_agent, properties

---

# بروتوكول إجابة أسئلة الدكتور السريعة

## "لماذا جداول pivot كثيرة؟"
لأن العقار يمكن أن له عدة ميزات، والميزة تظهر في عدة عقارات → العلاقة Many-to-Many → نحتاج جدول pivot (property_feature).

## "لماذا property_id nullable في conversations؟"
قرار تصميمي — المحادثة تُقام بين client وagent فقط (unique: client_id, agent_id). العقار المرجعي قد يتغير. الـ migration جمع المحادثات المكررة ودمجها.

## "كيف تحمي قاعدة البيانات من التكرار؟"
Unique constraints (favorites: user+property، conversations: client+agent، users: email/phone).

## "كيف تمنع هجوم Mass Assignment؟"
باستخدام `$fillable` — فقط الحقول المدرجة يمكن تعيينها بشكل جماعي.

## "لماذا SoftDeletes؟"
للحماية من الحذف الخاطئ — admin يمكنه استعادة عقار محذوف. الخيار مع forceDelete للحذف النهائي.

## "ما فائدة composite index؟"
يُسرّع الفلترة المشتركة. مثال: index(['status','transaction_type','price']) يسّرع كل استعلام يجمع هذه الثلالثة معاً — فلاتر الشائع: status=published + transaction_type=sale + min_price=100000.

---

*جاهز. افتح الملفات MUST KNOW واخرج للنقاش.*
