# وثيقة ما يهم — Wajhatak (كل شيء في مستند واحد)

كل المعلومات المهمة: البنية، **أدوات الواجهات كلها**، **طريقة الربط** بين Flutter و Laravel، قاعدة البيانات، المصادقة، والجداول الهامة. مبنية على فحص الكود الفعلي.

---

## 1. فكرة التطبيق

منصة عقارات عربية (أندرويد/iOS): المستخدم **يبحث** عن عقار، يشاهده على **الخريطة**، يحفظه **مفضلة**، **يراسل الوكيل**، ويحجز **طلب معاينة**. الوكيل يُنشئ/يعدّل/يحذف عقاراته ويرد على الطلبات. المشرف يدير كل شيء.

| الطرف | التقنية |
|-------|---------|
| الجوال | Flutter 3.x + Dart |
| الخادم | Laravel 12 + PHP 8.2 |
| قاعدة البيانات | MySQL (اسمها `wajhatak`) |
| الحزم المميزة | Riverpod 3 (حالة)، Dio (HTTP)، Sanctum (توكن)، Spatie Permission (صلاحيات) |

---

## 2. طريقة الربط — كيف تعمل كل الورقة (الأهم)

> "الربط" = Flutter يشاور Laravel عن طريق **REST API** بصيغة JSON.

```
[ شاشة Flutter ]  →  Provider (Riverpod)  →  Repository  →  ApiClient (Dio)
                                                            │
                                        HTTP (GET/POST/PATCH/DELETE)
                                                            ▼
                                              routes/api.php (Laravel)
                                                            ▼
                                          Middleware (auth:sanctum, throttle)
                                                            ▼
                                              Controller (Manage يجيب)
                                                            ▼
                                        Model (Eloquent) → قاعدة البيانات
                                                            ▼
                                   2. نموذج Eloquent  →  Resource (يحوله JSON)
                                                            ▲
              ─────────────── 3. JSON response يعود ────────┘
              │
   4. ApiClient يستلم JSON  →  5. Model (fromJson)  →  6. Provider يحدّث  →  7. الواجهة تتغير
```

**بالخطوات الدقيقة:**
1. المستخدم يكبس زر في `PropertyCard` المفضلة
2. `toggleFavorite()` يقرأ `favoriteOverridesProvider` (تحديث فوري — Optimistic UI)
3. `PropertyRepository.setFavorite(id, false)` يستدعي `_api.delete('/favorites/{id}')`
4. `LuxApiClient` (في `api_client.dart`) يجهّز الطلب: يضيف `Authorization: Bearer token`، ينتظر 350ms، يحل تحدي الاستضافة إن ظهر
5. Laravel: `routes/api.php` → `FavoriteController@destroy` → `Favorite::where(...)->delete()` → يرد **204**
6. JSON يتحول في Flutter → الواجهة الأخيرة

**كل التوكنز موجودة هنا:**
- العميل (الموبايل): هو الذي يبدأ الطلب **دائماً**
- الخادم: لا يرسل إلا رداً (JSON)
- المعرف: يوجد `api/v1/` في بداية كل مسار

**الربط المباشر لكل ميزة:**
| من الشاشة | عبر Repository | إلى Endpoint | عبر Controller |
|-----------|----------------|--------------|----------------|
| Login/Register | auth_repository | `POST /auth/login`, `POST /auth/register` | AuthController |
| سبب البيت في الرئيسية | property_repository | `GET /properties` | PropertyController@index |
| تفاصيل عقار | property_repository | `GET /properties/{id}` | PropertyController@show |
| إنشاء عقار | property_repository | `POST /properties` | PropertyController@store |
| المفضلة | property_repository | `GET /favorites`, `POST|DELETE /favorites/{id}` | FavoriteController |
| المحادثات | conversation_repository | `GET /conversations`, `GET /conversations/{id}/messages`, `POST ...` | ConversationController |
| طلب معاينة | viewing_request_repository | `POST /viewing-requests` | ViewingRequestController |
| الإشعارات | notification_repository | `GET /notifications`, `POST /notifications/{id}/read` | NotificationController |
| التصنيفات/المواقع/العملات | taxonomy_repository | `GET /taxonomies/*` | TaxonomyController |

---

## 3. الشاشات (16) — ما هو موجود في كل واجهة

### 3.1 الرئيسية (HomeScreen)
```
WajhatakHeader (شعار + اسم + زر إشعارات + Badge count)
├── بحث سريع (TextField + أيقونة بحث) — يفتح Explore
├── قسم "نرحب بك" (waving_hand icon + اسم المستخدم)
├── Categories (أيقونات بزر: villa للشقق، key للإيجار، sell للبيع، explore للاستكشاف)
└── GridView من PropertyCard (يرتبط بـ propertiesProvider)
```

### 3.2 البحث/الاستكشاف (ExploreScreen)
```
WajhatakHeader
├── TextField بحث (يعيش مع explore: search_rounded → arrow_back عند النص)
├── شرائح فلاتر سريعة (نوع العملية: بيع/إيجار)
├── زر فلاتر متقدمة → BottomSheet (السعر، المساحة، الغرف، المفروش، المواقع)
└── شبكة نتائج (PropertyCard) — لو فاضية EmptyState
```

### 3.3 تفاصيل العقار (PropertyDetailScreen)
```
Scaffold + AppBar مخصص
├── Stack صور (PageView carousel + أسهم left/right + dots)
├── زر المفضلة (قلب) يعيش مع override
├── شارة العملية (للبيع/لإيجار) ارتفاع على الصور
├── السعر الكبير (formatMoney) + العملة
├── العنوان + الـ type chip
├── أرقام: مربع القدم + غرف النوم + حمامات + مواقف + مفروش (أيقونات)
├── الوصف
├── الميزات (chips مثل: مكيف، موقف سيارة)
├── خريطة (FlutterMap + Marker) — زر "فتح الخريطة" → PropertyMapScreen
├── بطاقة الوكيل (الصورة + الاسم + تقييم + عدد العقارات)
└── أزرار: 💬 راسل الوكيل + 📅 اطلب معاينة (تفتح show_viewing_sheet)
```

### 3.4 المحفوظات/المفضلة (SavedScreen)
```
WajhatakHeader
├── Grid/Lv من العقارات المفضلة (favoritesProvider + override)
└── حالة فارغة: EmptyState (قلب + نص + زر استكشاف)
```

### 3.5 الرسائل (MessagesScreen)
```
WajhatakHeader
├── Tab: "المراسلات" + "طلبات المعاينة"
├── محادثات: avatar + اسم + آخر رسالة + الوقت + unread badge
└── EmptyState عند الفارغ
```

### 3.6 المحادثة (ChatScreen)
```
WajhatakHeader + عنوان
├── قائمة رسائل: فقاعات (mine left / theirs right)
├── ✓ = وصلت (done), ✓✓ = قرئت (done_all)
├── شريط إدخال + زر إرسال (send_rounded)
└── بطاقة العقار داخل المحادثة (صورة + سعر + زر "افتح العقار") — message_type = property
```

### 3.7 حسابي (AccountScreen) — بعد تسجيل الدخول
```
صورة المستخدم + زر تعديل (edit)
├── لوحة: طلبات المعاينة | الإشعارات | عقاراتي | إدارة الحساب | الإعدادات
├── (لو Agent: بطاقة Dashboard + زر إضافة عقار)
└── زر تسجيل الخروج
```

### 3.8 تسجيل الدخول/إنشاء حساب (AuthScreen)
```
الشعار المتحرك (WajhatakBrandMark) أعلى
├── Tab: تسجيل دخول / إنشاء حساب
├── Idan الحقول: الاسم | البريد | رقم الموبايل | كلمة المرور (رؤية + قفل)
├── أزرار: تسجيل الدخول (login) / إنشاء حساب (person_add)
├── عند إنشاء الملف: Switcher "أنت وكيل؟" (real_estate_agent icon)
└── Validation client-side تحت كل حقل
```

### 3.9 لوحة الوكيل (AgentDashboardScreen)
```
AppBar → قائمة (more_horiz) → تعديل الملف
├── بطاقة إحصائيات: العقارات المنشورة / المفضلة / المشاهدات
├── عقاراتي (list + FAB لإضافة عقار add_home_outlined)
└── طلبات المعاينة الواردة (calendar_month_outlined) بحالة كل طلب
```

### 3.10 إنشاء عقار (CreateListingScreen)
```
Form كبير (854 سطر) مقسوم أقسام بأيقونات:
├── [📝] النوع (category) + العملية (بيع/إيجار: sell/key) + الوصف
├── [₿] السعر (attach_money) + العملة (currency_exchange) + المساحة/غرف/حمامات/مواقف/المفروش (chair) + ميزات (auto_awesome)
├── [📍] الدولة (public) ← المحافظة ← المدينة ← المنطقة + الحي (streetview) + العنوان التفصيلي
├── [х] المميزات star
├── [🖼] الصور (photo_library + add_photo_alternate + close لإزالة)
└── زر "إرسال" (send) مع حالة Loading
```

### 3.11 تعديل عقار (EditListingScreen)
نفس حقول الإنشاء مهيّأة مسبقاً + إدارة الصور الموجودة (star_border تمييز الغلاف + close حذف).

### 3.12 الملف الشخصي (ProfileScreen)
صورة + تعديل الاسم/البريد/الموبايل + اختيار صورة من الكاميرا/المعرض + حفظ.

### 3.13 الإعدادات (SettingsScreen)
```
├── وضع المظهر: Auto / فاتح (light_mode) / داكن (dark_mode) (راديو)
├── إشعارات: المحادثات / طلبات المعاينة / العقارات الجديدة (Switches)
├── إدارة الحساب (manage_accounts)
└── الخصوصية + تسجيل الخروج
```

### 3.14 طلبات المعاينة (ViewingRequestsScreen)
قائمة طلبات مع التاريخ/الوقت/الملاحظات + حالة (معلق/مقبول/مرفوض) + أزرار قبول/رفض (للكل من نفس الطلب).

### 3.15 الإشعارات (NotificationsScreen)
قائمة الإشعارات (ايقونة حسب النوع + نص + وقت) + تحديد "مقروء" ينقل للشاشة المرتبطة (عقار/محادثة).

### 3.16 الخريطة (PropertyMapScreen)
FlutterMap كامل الشاشة + Marker على موقع العقار + زر رجوع.

---

## 4. أدوات مشتركة (Widgets) تُستخدم في كل الواجهات

| الأداة | الملف | مهمة |
|--------|-------|------|
| `WajhatakHeader` | app_header.dart | رأس موحد: hamburger/رجوع + شعار + Badge إشعارات |
| `WajhatakBottomNavBar` | bottom_nav_bar.dart | 5 تبويبات أسفل (الرئيسية، استكشف، محفوظات، رسائل، حسابي) مع Badge |
| `AppDrawer` | app_drawer.dart | قائمة جانبية: روابط + إعدادات الظهور + تسجيل الخروج |
| `PropertyCard` | property_card.dart | البطاقة الأهم: Carousel صور يلف تلقائياً + سعر + مفضلة + شارة |
| `LuxAsyncView` | lux_skeleton.dart | يلف كل تحميل: نجاح/تحميل(Skeleton)/خطأ(إعادة محاولة) |
| `EmptyState` | empty_state.dart | شاشة فارغة أو خطأ مع زر إعادة |
| `WajhatakBrandMark` | brand.dart | اللوجو (CustomPainter) المتحرك عند الدخول |
| `GradientIconBadge` | icon_badges.dart | دائرة متدرجة بأيقونة (qsidebar، تبويبات، إحصائيات) |
| `showLoading`/`notice` | notice.dart | SnackBar مخصص (نجاح ✓ / خطأ !) |

**الأيقونات الأساسية المستخدمة فعلياً (Material Icons):**
`search`, `favorite/favorite_border`, `heart_broken` (إزالة)، `send`, `done/done_all` (الرسائل)، `location_on`, `map`, `square_foot`, `bed`, `bathtub`, `directions_car`, `chair`, `auto_awesome`, `villa`, `key`, `sell`, `explore`, `calendar_month`, `event_available`, `schedule`, `chat_bubble`, `notifications`, `settings`, `manage_accounts`, `logout`, `person/person_outline`, `lock_outline`, `alternate_email`, `phone_outlined`, `public`, `location_city`, `signpost`, `streetview`, `title`, `description`, `category`, `payments`, `attach_money`, `currency_exchange`, `photo_library`, `add_photo_alternate`, `image_outlined`, `close`, `star/star_border`, `more_horiz`, `add`, `add_home_outlined`, `chevron_left/right`, `arrow_back`, `arrow_forward_ios`, `light_mode`, `dark_mode`, `brightness_auto`, `waving_hand`, `privacy_tip`, `login`, `person_add_alt`, `sticky_note_2`, `info`.

---

## 5. الأكثر تحكم في كل شيء — الملفات الحاسمة + مرجع الربط

### Flutter (المفاتيح)
| ملف | لماذا هو المفتاح |
|-----|------------------|
| `mobile/lib/state/providers.dart` (500 سطر) | *عقل النظام*: كل الـ Providers، إدارة الجلسة، Login/Register + `_readForSession()` |
| `mobile/lib/data/api_client.dart` (418 سطر) | كل HTTP: interceptors (Token, throttle 350ms, circuit breaker, challenge) + TokenStore |
| `mobile/lib/data/repositories/*.dart` | الطبقة الوسطى بين UI و API |
| `mobile/lib/data/models/*.dart` | تحويل JSON → كائن |
| `mobile/lib/ui/screens/shell/app_shell.dart` | الهيكل: bottom nav + drawer + FAB + polling إشعارات كل 25 ثانية |
| `mobile/lib/core/services/infinityfree_challenge_solver.dart` | يحل تحدي InfinityFree AES-128-CBC (تجاوز anti-bot) |
| `mobile/lib/core/utils/image_compressor.dart` | يضغط الصور قبل الرفع (حد 1920px، جودة 78) |
| `mobile/lib/core/utils/format_money.dart` | تحويل المبالغ مع رمز العملة العربية |

### Laravel (المفاتيح)
| ملف | لماذا هو المفتاح |
|-----|------------------|
| `backend/routes/api.php` | كل المسارات + المجموعات (public / auth / admin) + throttle |
| `backend/app/Http/Controllers/Api/V1/PropertyController.php` | منطق العقارات: applyFilters + store (transaction) |
| `backend/app/Http/Controllers/Api/V1/AuthController.php` | إنشاء/تفعيل token |
| `backend/app/Http/Requests/*.php` | كل التحقق من الإدخال (rules + رسائل عربية) |
| `backend/app/Http/Resources/PropertyResource.php` | صيغة إخراج JSON |
| `backend/app/Policies/PropertyPolicy.php` | من يستطيع ماذا (إنشاء/تعديل/حذف) |
| `backend/app/Models/Property.php` | العلاقات + SoftDeletes + casts |
| `backend/app/Http/Middleware/InjectSanctumToken.php` | X-Auth-Token → Authorization (في الاستضافات التي تحذف الرأس) |
| `backend/database/migrations/2026_08_24_171000_create_lux_domain_tables.php` | سكيم قاعدة البيانات كاملة |

---

## 6. قاعدة البيانات (جداول الـ 12 Migration)

| الجدول | المحتوى | علاقته |
|--------|---------|--------|
| `users` | الاسم/البريد/الهاتف/الصورة/نوع الحساب | منبع كل شيء |
| `agents` | بيانات الوكيل (عن الـ user) | 1:1 مع users، 1:N مع properties |
| `properties` | العقار (سعر/نوع/موقع/غرف/أثاث) — SoftDeletes | N:1 type, 1:N images |
| `property_images` | صور العقار (sort, is_cover) | 1:N إلى property |
| `property_locations` | الموقع: بلد/منطقة/مدينة/حي + إحداثيات | 1:1 مع property |
| `favorites` | user_id + property_id (فريد مجتمعين) | Pivot بين user & property |
| `conversations` | property + client_id + agent_id | القيد: (client, agent) فريد |
| `messages` | المحادثة + sender + property_id (بطاقة العقار) | 1:N في conversation |
| `viewing_requests` | الطلب: تاريخ/وقت/ملاحظات/حالة enum | 1:N مع property |
| `property_types` / `property_features` | التصنيفات والمميزات | pivot `property_feature` |
| `countries/regions/cities/areas` | الهرم الجغرافي | child → parent |
| `notifications` (Laravel) | إشعارات DB | morph `notifiable` |
| `activity_logs`, `settings`, `user_devices`, `user_notification_preferences` | تسجيل + إعدادات + أجهزة | مساندة |

**فهارس تسريع:** مركب `[status, transaction_type, price]` على `properties` — كل فلاتر البحث تستفيد منه. وفهارس `[user_id, property_id]`، `[client_id, agent_id]`.

---

## 7. المصادقة والصلاحيات (كيف يعرف النظام من أنت؟)

**المصادقة (تحقق):**
1. تسجيل الدخول → `AuthController@login` يتحقق من البريد+كلمة المرور (`Hash::check`) ومن `is_active`
2. يُصدر **Sanctum token** (`$user->createToken('mobile')`)
3. الجوال يحفظه في **FlutterSecureStorage** (ملف مشفّر — ليس SharedPreferences)
4. كل طلب يحمل `Authorization: Bearer <token>` → Middleware `auth:sanctum` يفتح به

**الصلاحيات (تفويض — من يسمح له):**
| الدور | يملك | مثال فعلي |
|-------|------|-----------|
| `admin` | الكل | يعدّل/يحذف أي عقار |
| `agent` | إدارة عقاراته | `PropertyPolicy.create`: `hasRole(['agent','admin']) && is_active` |
| `user` | المفضلة/الرسائل/الطلبات | لا يستطيع إنشاء عقار → 403 |

**الإنهاء (Logout):** يمسح الجوال التوكن + يستدعي `POST /auth/logout` → الخادم يحذف التوكن من جدول personal_access_tokens (لا يمكن استخدامه بعدها).

**متى تنتهي الجلسة؟** التوكن صالح 30 يوم؛ وعند أي **401/403** يقوم `_readForSession()` بإلغاء التخزين تلقائياً والرجوع لشاشة الدخول (مع إبقاء بيانات آخر إشعار).

---

## 8. أهم العمليات الكاملة (سؤال "اشرح كيف يعمل؟")

### 8.1 تسجيل الدخول
AuthScreen → validation → `SessionController.login()` → `state=AsyncLoading` → `AuthRepository.login()` → `POST /auth/login` → AuthController يتحقق → يرجّع `{user, token}` → `TokenStore.saveUser + write(token)` → `state=AsyncData` → **الواجهة أصبحت منطقية (AppShell)**.

### 8.2 إنشاء عقار (الوكيل)
CreateListing → `PropertyRepository.create(data, images)` → `_flattenToForm` (تحويل Map المتداخل إلى `nested[key]`) → `POST /properties` (multipart/FormData) → `StorePropertyRequest` يتحقق → `PropertyController@store`:
1. تحقق الوكيل نشط
2. `DB::transaction`:
   - إنشاء `PropertyLocation`
   - إنشاء `Property` بحالة **pending** + slug + reference_code
   - `features()->sync()`
   - رفع كل صورة (`storePublicly`) + إنشاء `PropertyImage`
3. إشعار Admin + إرجاع 201

### 8.3 البحث والفلترة — *أين تتم؟*
**في الخادم** (وليس الجوال):
`PropertyQuery.toParameters()` → `GET /properties?q=&city=&min_price=...` → `applyFilters()`:
- `q` → `title/description LIKE %term%`
- `city` → `whereHas(location)`
- `price` → بين min/max → `orderBy`
- يربط `with(['type','location','agent.user','images'])` (منع N+1)
- يعرض **published فقط** → paginate(15)

### 8.4 المفضلة (Optimistic UI)
إضافة فورية بدون انتظار الشبكة:
```dart
ref.read(favoriteOverridesProvider.notifier).set(id, true); // يغيّر الـ UI فوراً
await repo.setFavorite(id, true);                            // ثم يثبت في الخادم
```
لو فشل الطلب → `remove(id)` فوراً + رسالة خطأ.

### 8.5 المحادثة ببطاقة عقار
Start from property "راسل الوكيل" → `POST /conversations` (مع property_id) → في رسالة يرسل `message_type='property'` + `property_id` → ChatScreen يعرض بطاقة العقار داخل الفقاعة + زر "افتح العقار".

### 8.6 طلب معاينة
BottomSheet (date picker + time picker + ملاحظات) → `POST /viewing-requests` → الخادم: يتحقق (العقار منشور، ليس عقاري) → ينشئ الطلب بحالة pending → **يُرسل إشعار DB** إلى الوكيل (`ViewingRequestCreated`).

### 8.7 لوحة الوكيل
AgentDashboard → `GET /agent/properties` (عقاراتي) + `GET /viewing-requests` (الواردة) → إحصائيات من نفس البيانات.

---

## 9. الأمان الذي يهم حديثك

| التهديد | الحماية المطبقة |
|---------|-----------------|
| سرقة التوكن | SecureStorage + انتهاء 30 يوم + إلغاء عند 401 |
| تسريب كلمة المرور | `Hash::make` (bcrypt 12 round) — لا تخزن نصاً |
| حقن SQL | كل الاستعلامات Eloquent (parameterized) |
| Mass Assignment | `$fillable` (قائمة بيضاء) |
| رفع ملفات خبيثة | rules: image + mimes + max 8MB |
| إغراق بالطلبات | `throttle:6,1` على تسجيل الدخول + تثبيت 350ms + Circuit Breaker (5 فشل → مهلة 20 ثانية) |
| وصول غير مصرح | Middlewares: `auth:sanctum`, `EnsureUserIsAdmin`, `AdminUrlGuard` (رؤوس أمان + منع URL twiddling) |
| الحذف الخاطئ | SoftDeletes |
| ميزة فريدة | `InfinityFreeChallengeSolver` يحل تحدي AES-128-CBC فتتجول طلباتنا عبر الاستضافة الحرة |

**نقاط ضعف تقرّ بها (بثقة):**
- لا إشعارات push خارج التطبيق حاليًا (داخل التطبيق تعمل)
- لا استعادة كلمة مرور بعد
- البحث يعتمد LIKE (ليس بحثاً عربياً ذكياً)
- لا واجهة وب لإدارة المحتوى (أدوات artisan + API)

---

## 10. أسئلة الدكتور الأكثر شيوعاً — إجابات قصيرة

| السؤال | الإجابة |
|--------|---------|
| لماذا Riverpod وليس Provider/BLoC؟ | اختبار أبسط، لا BuildContext لازم للقراءة، تجاوز rebuild اللا ضروري، ويدعم State التحليلي (AsyncValue) بأريحية. |
| لماذا Sanctum وليس Passport/JWT؟ | أبسط للـ mobile، مدمج في Laravel 12، tokens قابلة للمسح فورياً، بدون OAuth التعقيد للموبايل. |
| لماذا flutter_map وليس Google Maps؟ | مجاني بدون مفتاح API، خفيف، والاحتياج هنا بسيط (Marker واحد) — قابل للتبديل لاحقاً. |
| لماذا Dio وليس http؟ | Interceptors (رأس token، throttle، تحديات)، Timeout مركزي، إدارة أخطاء موحدة. |
| لماذا Spatie وليس منطق مخصص؟ | تيجان حل **ناضج** للأدوار، يدعم cache، وتكامل سهل مع Policies. |
| أين تجري الفلترة؟ بواقع؟ | في **الخادم** — لقاعدة البيانات فهارس ويمكن paginate؛ لا ننزل كل العقارات للجوال. |
| كيف تمنع تكرار البيانات؟ | Unique constraints: email/phone للمستخدم، (user,property) للمفضلة، (client,agent) للمحادثة. |
| كيف تعالج بطء الشبكة؟ | Skeleton + optimistic UI + CachedNetworkImage + ضغط الصور قبل الرفع. |
| ماذا لو انطفأ الإنترنت؟ | الجلسة المخزنة تُستخدم آخر مرة (`saveUser`) مع رسالة معاودة؛ البيانات القادمة تُحدَّث عند الاتصال. |
| كيف تنتهي الجلسة؟ | token صالح 30 يوم، وعند أي 401/403 `_readForSession` يمسح ويعيد للدخول. |
| لماذا SoftDeletes؟ | الحماية من حذف خاطئ — admin يستعيد عقاراً محذوفاً؛ و forceDelete متاح للمسح النهائي. |
| كيف يصلني إشعار؟ | داخل التطبيق: polling كل 25 ثانية + shade DB notifications → local notification (flutter_local_notifications). |
| كيف تعرف أن الطلب من وكيل؟ | Policy `create` يتحقق من `hasRole('agent')` (الدور في JWT/DB) + `is_active` — وإذا لا → 403. |
| ما هو الـ N+1 وكيف منعته؟ | تحميل العلاقات مسبقاً بـ `with(['images','agent.user',...])` بدل استعلامات مكررة. |
| لماذا تحفظ التوكن في SecureStorage؟ | لأنه API للـ platform (iOS Keychain / Android Keystore) — لا تقرؤه أي تطبيق آخر. |

---

## 11. هيكل ملخص سريع (أقل من دقيقتين)

- **الموبايل**: 16 شاشة + 9 widgets مشتركة + 7 repositories + Models + ApiClient (مع جاهزية IndefinityFree solver و circuit breaker).
- **الخادم**: 11 Controller + 20 Model + 17 Migration/Seeder + 5 Form Requests + 6 Resources + 3 Middlewares + Policies + Notifications.
- **الربط**: جملة واحدة — "Flutter يرسل JSON ويستقبل JSON عبر REST؛ كل قرار (فلترة/تحقق/سماح) في Laravel، وكل قرار واجهة (حالة/روح) في Riverpod."
- **عقل النظام**: ملفان — `providers.dart` (Flutter) و `PropertyController.php` (Laravel). لو حفظتهما، أنت جاهز.

---

*انتهت الوثيقة الواحدة. افتح الآن `mobile/lib/state/providers.dart` و `backend/app/Http/Controllers/Api/V1/PropertyController.php` — كل ما فيها واقعي وقابل للعرض.*