# Crash Course — من الصفر حتى المناقشة

تعليم تفاعلي مبني حصرياً على الكود الفعلي في المشروع. كل مرحلة تحتوي أمثلة حقيقية من الكود.

---

## المرحلة 1: فكرة التطبيق

**وجهتك** = منصة عقارات عربية. الهدف: ربط المشترين/المستأجرين بالوكلاء العقاريين.

**3 أفعال أساسية:**
1. تصفح العقارات (Search + Filter)
2. التواصل مع الوكيل (Chat + Viewing Request)
3. إدارة العقارات (Create/Edit/Delete — للوكلاء)

**من أين تأتي هذه؟**
- التطبيق Flutter يرسل طلبات HTTP إلى خادم Laravel
- الخادم يتواصل مع قاعدة بيانات MySQL
- البيانات تعود كـ JSON ليُعرضها Flutter

---

## المرحلة 2: Flutter — البنية

Flutter = إطار عمل من Google لبناء تطبيقات موبايل من كود Dart واحد.

**في هذا المشروع:**
```
mobile/lib
├── main.dart          ← نقطة البداية (runs app)
├── core/              ← الأساسيات (theme, config, utils)
├── data/              ← نماذج + واجهات API
├── state/             ← إدارة الحالة (Riverpod)
└── ui/                ← الشاشات والـwidgets
```

**اقرأ main.dart أولاً** — ستجد:
```dart
runApp(const ProviderScope(child: WajhatakApp()));
```
هذا يبدأ التطبيق مع Riverpod (نظام إدارة الحالة) ويتحول لـ WajhatakApp.

---

## المرحلة 3: Dart — أساسيات

**ما تحتاج لمعرفته عن Dart في هذا المشروع:**

| المفهوم | مثال من المشروع |
|---------|-----------------|
| async/await | `Future<SessionData?> build() async { ... }` في SessionController |
| Future<T> | `Future<List<LuxProperty>>` في providers |
| classes | `class LuxProperty { ... }` في property.dart |
| factories | `factory LuxProperty.fromJson(Map<String, dynamic> json)` |
| @immutable | `@immutable class LuxUser` |
| copyWith | `LuxProperty copyWith({bool? isFavorited})` |
| named constructors | `const PropertyQuery({this.search = '', ...})` |
| null safety | `String? firstName;` |

---

## المرحلة 4: Widgets

> **القاعدة الذهبية في Flutter:** كل شيء هو Widget. كل Widget يُرجع بناءً على بيانات أخرى Widgets.

**أهم Widgets في هذا المشروع:**

| Widget | أين يظهر |
|--------|----------|
| `MaterialApp` | main.dart — التطبيق بأكمله |
| `Scaffold` | app_shell.dart — كل شاشة |
| `AppBar` / `WajhatakHeader` | رؤوس الشاشات |
| `Container` | cards, hero sections, padding |
| `Row` / `Column` | ترتيب العناصر أفقياً/رأسياً |
| `Stack` | فوق بعض (badges فوق الصور) |
| `Expanded` / `Flexible` | ملء المساحة المتاحة |
| `ListView` | قوائم (messages, properties, notifications) |
| `GridView` | شبكة العقارات |
| `PageView` | معرض صور العقار |
| `Text` / `TextFormField` | النصوص والحقول |
| `TextField` | البحث |
| `DropdownButton` | dropdowns (العملة، النوع، الموقع) |
| `Switch` / `SwitchListTile` | المفاتيح الثنائية (مفروش، إشعارات) |
| `Image` / `CachedNetworkImage` | الصور (سيل ب الدماغ أو مخزنة) |
| `IconButton` | الأزرار (أيقونات) |
| `TextButton` | الأزرار النصية |
| `Card` | بطاقات |

---

## المرحلة 5: State Management — Riverpod

**لماذا نحتاج حالة؟** لأن البيانات تتغير أثناء استخدام التطبيق (نتائج بحث، تسجيل دخول، مفضلة).

**Riverpod في هذا المشروع:**
- `ProviderScope` — بدء Riverpod (في main.dart)
- `Provider` — خدمات ثابتة (ApiClient, Repositories)
- `FutureProvider` — بيانات غير متزامنة (properties, favorites)
- `Notifier` — حالة متغيرة (FavoriteOverrides)
- `AsyncNotifier` — حالة متغيرة مع async (SessionController)

**أين الحالة؟** كلها في `state/providers.dart` (500 سطر). هذا الملف هو "عقل" التطبيق.

**مثال عملي:** عند إنشاء حساب:
```dart
// في SessionController.register()
state = const AsyncLoading();         // 1. Loading
try {
  final session = await repo.register(...);
  state = AsyncData(session);         // 2. Success
} catch (error, stack) {
  state = AsyncError(error, stack);   // 3. Error
  rethrow;
}
```

---

## المرحلة 6: Navigation

Flutter يستخدم `Navigator` (المكدس). **لا يوجد** GoRouter أو named routes.

**أنواع التنقل:**
- `Navigator.push()` — فتح شاشة جديدة (PropertyDetail, Chat, etc.)
- `Navigator.pop()` — العودة للشاشة السابقة
- `Navigator.pushReplacement()` — تبديل الشاشة الحالية
- `rootNavigatorKey` — تنقل عالمي من أي مكان (للإشعارات)

---

## المرحلة 7: API — كيف تتحرك البيانات

**التدفق:**
```
Flutter (Dio) → HTTP Request → Laravel Route → Controller → DB → JSON → Flutter Model
```

**في api_client.dart:**
```dart
Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
  try {
    final response = await _dio.get<Map<String, dynamic>>(path, queryParameters: query);
    return response.data ?? {};
  } on DioException catch (error) {
    throw _toFailure(error);
  }
}
```

**في property_repository.dart:**
```dart
Future<List<LuxProperty>> list([PropertyQuery query = const PropertyQuery()]) async {
  final json = await _api.get('/properties', query: query.toParameters());
  return _propertyList(json);
}
```

**في providers.dart:**
```dart
final propertiesProvider = FutureProvider<List<LuxProperty>>((ref) async {
  ref.watch(sessionProvider);
  return ref.read(propertyRepositoryProvider).list();
});
```

---

## المرحلة 8: HTTP — طرقها

| Method | الاستخدام | مثال |
|--------|-----------|------|
| GET | قراءة | `/api/v1/properties` |
| POST | إنشاء | `/api/v1/properties`, `/api/v1/auth/login` |
| PATCH | تعديل جزئي | `/api/v1/properties/{id}` |
| DELETE | حذف | `/api/v1/favorites/{id}` |

---

## المرحلة 9: JSON — التحويل

**Model ⇄ JSON:**
```dart
// LuxProperty.fromJson (من JSON إلى Object)
factory LuxProperty.fromJson(Map<String, dynamic> json) => LuxProperty(
  id: json['id'] as int,
  title: json['title'] as String? ?? '',
  ...
);

// LuxProperty.fromJson (العكس)
Map<String, dynamic> toJson() => {...};
```

**Laravel (الجانب الآخر):**
```php
// PropertyResource يحول Model إلى JSON
return [
  'id' => $this->id,
  'title' => $this->title,
  ...
];
```

---

## المرحلة 10: Laravel — MVC

**MVC = Model-View-Controller** — فصل المنطق عن العرض عن التحكم.

```
Model (البيانات) → Controller (المنطق) → View/Resource (العرض)
```

**في هذا المشروع:**
- **Model:** `Property.php` يمثل جدول properties في قاعدة البيانات
- **Controller:** `PropertyController.php` يدير العقارات (index, store, update, delete)
- **Resource:** `PropertyResource.php` يحول العقار إلى JSON للعرض

---

## المرحلة 11: Controllers — المسؤوليات

**كل Controller لديه وظيفة محددة:**

| Controller | المسؤولية |
|------------|-----------|
| AuthController | تسجيل + دخول + خروج |
| PropertyController | إدارة العقارات (CRUD + فلاتر) |
| MeController | الملف الشخصي |
| ConversationController | المحادثات |
| ViewingRequestController | طلبات المعاينة |
| FavoriteController | المفضلة |
| TaxonomyController | أنواع العقارات / المميزات / المواقع / العملات |
| NotificationController | الإشعارات |

**مثال — AuthController@login:**
```php
public function login(LoginRequest $request): JsonResponse
{
    $user = User::query()->where('email', $request->string('email')->lower())->first();
    if (! $user || ! $user->is_active || ! Hash::check($request->string('password'), $user->password)) {
        return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 422);
    }
    $token = $user->createToken($request->string('device_name'))->plainTextToken;
    return response()->json(['data' => ['user' => new UserResource($user->load('roles', 'permissions')), 'token' => $token]]);
}
```

---

## المرحلة 12: Models — التمثيل للبيانات

**Eloquent Model = فئة PHP تعرض جدولاً في قاعدة البيانات.**

**مثال — Property:**
```php
class Property extends Model {
    use SoftDeletes;
    protected $fillable = ['agent_id', 'property_type_id', ...];
    protected $casts = [
        'transaction_type' => TransactionType::class,
        'status' => PropertyStatus::class,
        'price' => 'decimal:2',
    ];
    
    public function images(): HasMany { ... }
    public function agent(): BelongsTo { ... }
}
```

---

## المرحلة 13: Eloquent — الاستعلامات

| العملية | مثال في المشروع |
|---------|-----------------|
| where | `->where('status', PropertyStatus::Published)` |
| whereHas | `->whereHas('location', fn($loc) => $loc->where('city', $city))` |
| with | `->with(['type', 'location', 'images'])` |
| create | `Property::query()->create([...])` |
| update | `$property->update($data)` |
| delete | `$property->destroy()` (soft) / `forceDelete()` |
| find | `Property::findOrFail($id)` |
| first | `User::where('email', $email)->first()` |
| paginate | `->paginate(15)` |
| orderBy | `->orderByDesc('published_at')` |

---

## المرحلة 14: Database — جداول البيانات

**قاعدة البيانات اسم:** `wajhatak`

**أهم الجداول:**
| جدول | محتواه |
|------|--------|
| users | المستخدمون |
| agents | الوكلاء (يرتبط بـ users) |
| properties | العقارات |
| property_images | صور العقارات |
| favorites | المفضلة (user x property) |
| conversations | المحادثات (client x agent) |
| messages | الرسائل |
| viewing_requests | طلبات المعاينة |
| countries/regions/cities/areas | المواقع الهرمية |
| property_types/features | التصنيفات والمميزات |

---

## المرحلة 15: Authentication (المصادقة)

**التقنية:** Laravel Sanctum — Personal Access Tokens.

**الفكرة:** عند تسجيل الدخول بنجاح، يُنشئ الخادم token يعرف به المستخدم في الطلبات اللاحقة.

```
login → الخادم يتحقق من email+password → يُصدر token → يحفظ في الجوال
      → كل طلب لاحق يرسل (Authorization: Bearer token) → الخادم يتحقق من token
```

**في Flutter:**
```dart
options.headers['Authorization'] = 'Bearer $token';
```

**في Laravel:**
```php
Route::middleware('auth:sanctum')->group(...);
```

---

## المرحلة 16: Authorization (التفويض)

**Spatie Permission** — يحقق من الأدوار.

**3 أدوار:** admin, agent, user

**مثال:** الوكيل فقط يستطيع إنشاء عقار.
```php
// StorePropertyRequest authorize:
return $this->user()?->can('create', Property::class) ?? false;
// PropertyPolicy:
public function create(User $user): bool {
  return $user->hasRole(['agent', 'admin']) && $user->is_active;
}
```

---

## المرحلة 17: Properties — منطق العقارات

**أهم 4 مفاهيم:**
1. **Status:** draft → pending → published / rejected / archived
2. **Transaction:** sale (بيع) OR rent (إيجار)
3. **Location:** country → region → city → area + city/district/address
4. **Images:** متعددة مع سorts + is_cover

**كيف يُنشئ الوكيل عقار:**
```
Flutter: CreateListingScreen (fill form)
  → PropertyRepository.create(data, images)
  → POST /api/v1/properties (multipart)
  → Laravel: StorePropertyRequest validates
  → PropertyController@store:
    → Agent must be active
    → DB::transaction:
      → PropertyLocation::create(location)
      → Property::create({...status: Pending, slug, reference_code})
      → features()->sync(feature_ids)
      → foreach images: storePublicly + PropertyImage::create
```

---

## المرحلة 18: Search — البحث

**وأين تتم الفلترة؟** في الخادم (Laravel) — لأن قاعدة البيانات أسرع مليون مرة.

```
Flutter: PropertyQuery.toParameters()
  → GET /properties?q=شقة&city=صنعاء&min_price=100000&...
  → Laravel: applyFilters() + applySort()
  → paginate(15)
  → Response
```

---

## المرحلة 19: Favorites — المفضلة

**Idempotent:** نفس العملية عدة مرات = نفس النتيجة.

```php
Favorite::firstOrCreate(['user_id' => $userId, 'property_id' => $propertyId]);
```

**Optimistic UI:** تحديث الواجهة فوراً قبل إنهاء الطلب.
```dart
ref.read(favoriteOverridesProvider.notifier).set(property.id, shouldFavorite);
await repo.setFavorite(property.id, shouldFavorite);  // أخيراً يتأكد
```

---

## المرحلة 20: Upload — رفع الصور

```
Flutter ImagePicker (أو معرض)
  → ImageCompressor.compress (1920px, JPEP 78, 600KB)
  → FormData (multipart)
  → POST /properties/{id}/images
  → Laravel validates (image, mimes, max 8MB)
  → storePublicly('properties/{id}', 'public')
  → PropertyImage::create
```

---

## المرحلة 21: Notifications

**إشعارات داخل التطبيق (Database Notifications):**
```
(A gets notification) → Laravel: User::notify(new Notification) 
  → يخزن في جدول notifications
  → Flutter: notificationsProvider fetchs + shows
```

**إشعارات محلية (Local notifications):**
```
AppShell polls every 25s → fetches unread count
  → shows FlutterLocalNotificationsPlugin notification
  → tapping opens the right screen (via notification_navigation.dart)
```

---

## المرحلة 22: Security

| ميزة | كيف؟ |
|------|------|
| Password hashing | `Hash::make()` + bcrypt (12 rounds) |
| Token security | Sanctum bearer tokens in Authorization header |
| X-Auth-Token backup | For hosting that strips headers |
| Input validation | Laravel Form Requests |
| SQL injection | Eloquent parameterized queries |
| Mass assignment | `$fillable` whitelist |
| File validation | `image, mimes, max` rules |
| Rate limiting | `throttle:6,1` on login/register |
| Circuit breaker | 350ms cooldown + 20s reset |

---

## المرحلة 23: Performance

**Applied:**
- Eager loading (`->with(...)`)
- Pagination (15-30 per page)
- Database indexes (composite on properties)
- Image compression before upload
- Cached network images
- Optimistic UI (favorites)
- Local user cache (offline)

**Not applied (improvement opportunities):**
- Redis for cache/queue (currently database driver)
- Elasticsearch/Meilisearch for full-text search (currently LIKE)
- CDN for images

---

## المرحلة 24: Error Handling

| الحالة | Flutter يتغير ماذا؟ | Laravel يرسل ماذا؟ |
|--------|---------------------|---------------------|
| 200 | Success → data | Resource |
| 201 | Created → data | Resource |
| 204 | NoContent → ignore | empty |
| 401 | Clear token → redirect login | {message} |
| 403 | Show error | {message} |
| 404 | Show error | {message} |
| 422 | Parse validation errors → notice() | {message, errors{field: []}} |
| 429 | Show "طلبات كثيرة" | {message} |
| 500 | Show "تعذر إتمام الطلب" | HTML error |
| timeout | "تعذر الاتصال بالخادم" | — |
| connection error | "تعذر الاتصال" | — |

---

**تم.** الآن لديك فهم شامل من الصفر حتى المناقشة. افتح الملفات المذكورة وراجع الكود بنفسك — هذا أفضل طريقة للجاهزية.
