# Flutter Widgets Dictionary & Code Walkthrough

قاموس تفصيلي للـ Widgets المستخدمة فعلياً، مع شرح الكود الأساسي في المشروع، وإجابات نموذجية لأسئلة الدكتور، وأهم سيناريوهات الـ Code Flow المتبعة عبر الملفات.

---

# القسم الأول: بسم الله Widgets المهمة

## 1. MaterialApp (في main.dart)

**ما هو؟** Widget الجذر لتطبيق Flutter — يضبط السمة، اللغة، الـ theme mode، ونقطة البداية.

**لماذا استخدم؟** في المشروع لإعداد:
- `theme: buildWajhatakTheme()` — سمة مخصصة (WajhatakColors)
- `darkTheme: buildWajhatakTheme(Brightness.dark)` — سمة داكنة
- `themeMode: ref.watch(themeModeProvider)` — من Riverpod
- `locale: const Locale('ar')` — العربية إلزامية
- `navigatorKey: rootNavigatorKey` — تنقل عالمي

**أهم Properties:**
- `locale` — لغة التطبيق
- `theme` / `darkTheme` / `themeMode` — السمات
- `home` — الشاشة الأولى (SessionGate)
- `supportedLocales` + `localizationsDelegates` — دعم اللغات

**الفرق مع البديل:** يمكن استخدام `Material` و `Scaffold` مباشرةً لكن بدون إعدادات عالمية.

**سؤال الدكتور:** لماذا `Directionality(textDirection: TextDirection.rtl)` حول SessionGate؟
**الإجابة:** لأن التطبيق عربي أول ويعتمد على اتجاه RTL قسري بغض النظر عن إعدادات الجهاز، حتى مع locale = 'ar' فلن يكون هناك ارتباك مع locale = 'en'.

---

## 2. SessionGate (في shell/session_gate.dart)

**ما هو؟** Widget يعرض شاشة الـ Splash أثناء تحميل الجلسة ثم ينتقل لـ AppShell.

**كيف يعمل:**
```dart
class SessionGate extends ConsumerWidget {
  Widget build(context, ref) {
    final sessionAsync = ref.watch(sessionProvider);
    return sessionAsync.when(
      data: (_) => const AppShell(),       // جاهز → AppShell
      loading: () => const _SplashView(),  // تحميل → شاشة البداية
      error: (_, __) => const _SplashView(),// خطأ → يُعرض AppShell بدون جلسة
    );
  }
}
```

**ما الذي يحدث عند فتح التطبيق:**
1. `sessionProvider` يجري `build()`
2. `SessionController.build()`:
   - UI Preview؟ → تحميل fixtures
   - لا → `AuthRepository.restore()`:
     - `TokenStore.read()` → يقرأ التوكن من FlutterSecureStorage
     - إذا التوكن منتهي (30 يوم) → clear → null
     - جرّب GET /me → نجاح: saveUser + return SessionData
     - 401/403 → clear → null
     - خطأ شبكة → استخدم الـ cached user إن وُجد
3. SessionGate يعرض AppShell (سواء كان مسجلاً أو Guest)

---

## 3. AppShell (في shell/app_shell.dart)

**ما هو؟** هيكل التطبيق الرئيسي.

**بما يحتوي:**
- bottom nav (WajhatakBottomNavBar)
- Drawer (AppDrawer)
- Notification service إعداد
- Polling للإشعارات كل 25 ثانية
- FLoating action button للوكلاء (CreateListing)

**كيف يعمل الـ State:**
```dart
int _currentIndex = 0;
// * body = IndexedStack / switch on _currentIndex
// home=0, explore=1, saved=2, messages=3, account=4
```

**سؤال الدكتور:** كيف يبدّل المستخدم بين الشاشات؟
**الإجابة:** عبر `WajhatakBottomNavBar` — وهو StatelessWidget يأخذ onDestinationSelected callback. عند التحديد، `AppShell` يغيّر `_currentIndex` ، فيتم عرض الشاشة المقابلة.

---

## 4. WajhatakHeader (في widgets/shared/app_header.dart)

**ما هو؟** هيدر موحد للتطبيق.

**المكونات:**
```
Row
├── IconButton (hamburger: openDrawer)  [RTL: أولاً]
├── WajhatakBrandMark (اللوجو + اسم "وجّهتك")
├── Spacer
└── Badge (غلاف مع إشعارات unread count)
```

**Properties المخصصة:**
- `showBackButton` — يعرض رجوع بدل الحمبرغر
- `title` — عنوان مخصص
- `child` — محتوى إضافي

**أين يُستخدم:** HomeScreen, ExploreScreen, SavedScreen, MessagesScreen, AccountScreen + كل الشاشات باستخدام WajhatakHeader المخصص.

---

## 5. WajhatakBottomNavBar (في widgets/navigation/bottom_nav_bar.dart)

**ما هو؟** شريط تنقل سفلي مخصص — Result بدلاً من BottomNavigationBar الافتراضي.

**المكونات:**
```
Row (mainAxisAlignment: spaceEvenly)
├── _NavItem: الرئيسية
├── _NavItem: استكشف
├── _NavItem: محفوظات
├── _NavItem: الرسائل
└── _NavItem: حسابي (avatar image)
```

**أهم ما يميزه:**
- `AnimatedContainer` — انتقال سلس عند الاختيار
- Badge counts (للمحادثات غير المقروءة)
- Profile avatar destination (يعرض صورة المستخدم)

**لماذا مخصص بدلاً من BottomNavigationBar؟**
1. تصميم فريد مع badges + avatars
2. Responsive width (يتكيف مع حجم الشاشة)
3. Animated selection effects

---

## 6. PropertyCard (في widgets/property/property_card.dart)

**ما هو؟** بطاقة عقار قابلة لإعادة الاستخدام.

**المكونات الداخلية (نظرة هرمية):**
```
PropertyCard (ConsumerStatefulWidget)
├── Card/Container
│   ├── Stack
│   │   ├── PageView (carousel)
│   │   │   ├── CachedNetworkImage (كل صورة)
│   │   │   └── Timer periodic (auto-scroll كل 4 ثوانٍ)
│   │   ├── Positioned (transaction pill: للبيع/لإيجار)
│   │   ├── Positioned (new badge: جديد)
│   │   ├── Positioned (favorite heart IconButton)
│   │   └── Positioned (carousel dots)
│   └── Column
│       ├── Text: السعر (formatMoney)
│       ├── Text: اسم العقار
│       ├── Row: [location icon + city] [•] [type]
│       └── Row: [♥ عدد المفضلة] [حالة]
```

**ما يجعله خاص:** **Auto-scrolling image carousel** — يستخدم `Timer.periodic` لتمرير الصور تلقائياً.

**الـ State المحلي:**
```dart
// PropertyCardState:
PageController _pageController;
Timer? _carouselTimer;
int _currentPage = 0;
bool _isVisible = true;  // visibility detection
```

**API:** via `favoriteOverridesProvider` + `toggleFavorite()`

**ما يستمع له:** `ref.watch(favoriteOverridesProvider)` — لتحديث حالة القلب فوراً

---

## 7. LuxAsyncView\<T\> (في widgets/skeleton/lux_skeleton.dart)

**ما هو؟** Universal async data wrapper. يأخذ AsyncValue وتنفيذ التحميل/الخطأ/النجاح.

**المكونات:**
```dart
class LuxAsyncView<T> extends StatefulWidget {
  final AsyncValue<T> value;
  final Widget Function(T) builder;      // for data
  final Widget? loading;                  // for loading
  final Widget Function(Object, StackTrace)? errorBuilder; // for error
  final Duration minLoadingTime;          // prevent shimmer flash
}
```

**كيف يعمل:**
```dart
value.when(
  data: (data) => builder(data),
  loading: () => loading ?? Skeleton(),
  error: (err, stack) => ErrorState(retry: () => ref.invalidate(provider)),
);
```

**لماذا `minLoadingTime`؟** لمنع الوميض (flash) عند فتح شاشة فيها بيانات سريعة — يضمن عرض skeleton لوقت أدنى ثم انتقال سلس للبيانات.

---

## 8. GradientIconBadge (في core/theme/icon_badges.dart)

**ما هو؟** دائرة/بلاست بأيقونة ملونة مع تدرج (gradient) خلفية.

**المكونات:**
```dart
Container(
  decoration: BoxDecoration(
    gradient: LinearGradient(colors: widget.gradient),
    shape: BoxShape.circle,
  ),
  child: Icon(widget.icon, color: widget.iconColor),
)
```

**أين يستخدم:** في AppDrawer (أيقونات القائمة)، HomeScreen (Category icons)، Statistics tiles.

---

## 9. WajhatakBrandMark (في ui/brand.dart)

**ما هو؟** شعار مخصص مرسوم بـ CustomPainter.

**كيف يرسم:**
```dart
class _ArchMarkerPainter extends CustomPainter {
  void paint(Canvas canvas, Size size) {
    // يرسم قوساً معمارياً (arch) لتمثيل "وجهتك" (وجه)
    // + نقطة location في أعلى القوس
  }
}
```

**ما أجمل:** **AnimationController** — يدعم animation لدخول الشعار (scale + opacity).

**أين يستخدم:** Splash screen (SessionGate), AuthScreen, HomeScreen hero, WajhatakHeader.

---

## 10. EmptyState (في widgets/feedback/empty_state.dart)

**ما هو؟** Widget للحالات الفارغة أو الأخطاء.

**المكونات:**
```
EmptyState
├── GradientIconBadge (أيقونة كبيرة)
├── Text: title
├── Text: body
└── (اختياري) TextButton: action
```

**أين يستخدم:** SavedScreen (قائمة فارغة), ChatScreen (لا رسائل), NotificationsScreen (لا إشعارات), Properties (لا نتائج).

**خطأ خاص:** `ErrorState` — مع زر "إعادة محاولة" (retry).

---

## 11. LuxNotificationService (في core/services/lux_notification_service.dart)

**ما هو؟** Wrapper على FlutterLocalNotificationsPlugin.

**يقدم:**
- `initialize()` — إعداد Android channel
- `showLocalNotification(...)` — عرض إشعار
- `listenForNotifications(callback)` — معالجة الضغط على الإشعار
- Riverpod provider: `localNotificationServiceProvider`

**كيف يتفاعل مع AppShell:**
```dart
// AppShell initState:
notificationService.initialize()
  .then((_) => notificationService.listenForNotifications((payload) {
    // navigate to matching screen based on kind
    openNotificationSource(context, payload);
  }));
```

---

# القسم الثاني: Widgets سؤال-جواب شائعة

## سؤال: ما الفرق بين `StatelessWidget` و `ConsumerWidget`؟

| StatelessWidget | ConsumerWidget |
|-----------------|----------------|
| لا يقرأ Providers | يقرأ Providers عبر `ref` |
| `StatelessWidget` base | `ConsumerWidget extends StatelessWidget` |
| build(context) only | build(context, ref) |

## سؤال: ما الفرق بين `StatefulWidget` و `ConsumerStatefulWidget`؟

| StatefulWidget | ConsumerStatefulWidget |
|----------------|------------------------|
| State فقط | State + reads Providers |
| Uses `setState()` | Uses `ref.read` + `ref.watch` |
| Less common | الأكثر استخداماً هنا (forms, controllers) |

## سؤال: لماذا يوجد `ConsumerStatefulWidget` وليس `ConsumerWidget` في CreateListing؟

لأن النموذج يحتاج **State محلي** — controllers للحقول، قوائم الصور، الـ selected dropdowns. لكن مع ذلك يجب قراءة Providers (taxonomy, currencies). `ConsumerStatefulWidget` يجمع الاثنين.

## سؤال: ما الفرق بين `ref.watch` و `ref.read`؟
- `ref.watch(provider)` — يعيد بناء الـ Widget عند تغيّر الـ provider. **مهم**: لا تستخدم داخل callbacks.
- `ref.read(provider)` — يقرأ قيمة لمرة واحدة. **مهم**: داخل callbacks (onPressed).

## سؤال: لماذا تستخدم `IndexedStack` بدلاً من `Switch` في AppShell؟

لأن IndexedStack يحافظ على حالة كل Screen حية في الذاكرة (لا يُعاد بناؤه) — بديل أسرع وأسهل للانتقالات.

---

# القسم الثالث: Code Walkthrough — الملفات الأهم

## 1. api_client.dart (418 سطر) — العمود الفقري

**هذا هو أهم ملف في Flutter.** يحصر كل منطق HTTP.

**ما بداخله:**
1. `ApiFailure` — Exception مخصص (message + statusCode)
2. `TokenStore` — إدارة التوكن في SecureStorage
3. `LuxApiClient` — كل منطق الـ API

**الميزات الأهم:**
- **Throttling:** كل طلب ينتظر 350ms على الأقل
- **Circuit breaker:** بعد 5 فشل سريع، ينتظر 20 ثانية
- **Challenge solver:** يحل InfinityFree AES
- **Auto token attach:** يضيف Bearer token لكل طلب
- **X-Auth-Token backup:** للاستضافات التي تحذف Authorization
- **Auto-logout on 401/403**
- **Error extraction:** يستخرج رسائل التحقق من JSON

## 2. providers.dart (500 سطر) — عصبية الحالة

**أهم 3 providers:**
- `sessionProvider` — مصدر الحقيقة للمصادقة
- `propertiesProvider` — قائمة العقارات
- `favoriteOverridesProvider` — تحديث فوري للمفضلة

**الميزة الأهم:** `_readForSession()`:
```dart
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

**ما يعمله:** كل طلبات الـ Auth-gated تمر من هنا:
1. إذا لا توجد جلسة → يُرجع fallback (قائمة فارغة)
2. ينفذ الطلب
3. إذا 401/403 → يمسح الجلسة ويعيد التوجيه

## 3. property_repository.dart (119 سطر)

**العمليات:**
- `list(query)` → GET /properties
- `detail(id)` → GET /properties/{id}
- `favorites()` → GET /favorites
- `setFavorite(id, bool)` → POST/DELETE /favorites
- `create(data, images)` → POST /properties (multipart)
- `update(id, data)` → PATCH /properties/{id}
- `delete(id)` → DELETE /properties/{id}
- `mine()` → GET /agent/properties

**خاصة مهمة:** `_flattenToForm` — يحول Map nested إلى أسماء حقول (nested[child]).

```dart
static void _flattenToForm(Map<String, dynamic> out, String prefix, dynamic value) {
  if (value is Map<String, dynamic>) {
    for (final entry in value.entries) {
      final key = prefix.isEmpty ? entry.key : '$prefix[${entry.key}]';
      _flattenToForm(out, key, entry.value);
    }
  } else if (value is bool) {
    out[prefix] = value ? 1 : 0;   // يحول bool إلى 1/0
  } else if (value != null) {
    out[prefix] = value;
  }
}
```

## 4. session_controller (في providers.dart)

**أهم طريقة — `login()`:**
```dart
Future<void> login({required String email, required String password, required String deviceName}) async {
  state = const AsyncLoading();
  try {
    final session = await (() async {
      if (AppConfig.isUiPreview) { ... }
      return ref.read(authRepositoryProvider)
        .login(email: email, password: password, deviceName: deviceName);
    })();
    state = AsyncData(session);
    _afterSessionChanged();
  } catch (error, stackTrace) {
    state = AsyncError(error, stackTrace);
    rethrow;
  }
}
```

## 5. infinityfree_challenge_solver.dart (177 سطر) — الفريد

**ماذا يفعل؟** يحل تحدي InfinityFree AES-128-CBC anti-bot:

1. `ensureCookie()` — يرسل GET للصفحة الرئيسية
2. يقرأ السكربت: يستخرج `a`, `b`, `c` من `toNumbers("...")`
3. يفك تشفير AES-128-CBC
4. يحسب قيمة `__test` cookie
5. `forceSolve()` — يعيد الحل بالقوة عندما يرى التحدي

**لماذا هذا منطقي؟** InfinityFree تستضيف مواقع مجانية وتوظف حماية من الـ bots. تطبيقنا authentic لهذا الاستضافة، لذا يحل التحدي ليسمح بالطلبات.

---

# القسم الرابع: Code Flow — عمليات أساسية

## Flow 1: تسجيل الدخول (الكامل)

```
[AuthScreen]
  → TextFormField validation (client-side)
  → onPressed "تسجيل الدخول"
    → _formKey.currentState!.validate()
    → ref.read(sessionProvider.notifier).login(
        email: _emailController.text,
        password: _passwordController.text,
        deviceName: 'mobile')
      → SessionController.login()
        → state = AsyncLoading
        → ref.read(authRepositoryProvider).login(...)
          → TokenStore.read() (no-op for login)
          → _api.post('/auth/login', data: {...})
            → LuxApiClient.post()
              → _dio.post(...)
                → Interceptor: _throttle()
                → Interceptor: challenge cookie
                → Interceptor: Bearer token (if any)
                → HTTP POST /api/v1/auth/login
                  → Laravel Route (throttle:6,1)
                  → InjectSanctumToken (no token)
                  → AuthController@login()
                    → LoginRequest validates
                    → User::where('email', $email)->first()
                    → Hash::check(password, user->password)
                    → $user->createToken('mobile')->plainTextToken
                    → response {data: {user, token}}
                ← HTTP 200
              ← _dio returns JSON
            ← TokenStore.write(token)
            ← TokenStore.saveUser(user)
            ← return SessionData(user, token)
        → state = AsyncData(session)
        → _afterSessionChanged() → invalidate search + favorites
      → Pop AuthScreen → Back to AppShell (now logged in)
```

## Flow 2: البحث عن عقار

```
[ExploreScreen]
  → changeText (TextField)
    → ExploreSearchNotifier.setSearch(term)
    → propertySearchProvider invalidated with PropertyQuery(search: term)
    → PropertyRepository.list(query)
      → _api.get('/properties', query: query.toParameters())
        → GET /api/v1/properties?q=term&...
        → Laravel: PropertyController@index
          → with(['type', 'location.*', 'agent.user', 'images'])
          → applyFilters() — q → where title like %term% or description like %term%
          → applySort()
          → withExists is_favorited
          → paginate(15)
        → PropertyResource::collection
      → _propertyList(json) → List<LuxProperty>
    → Provider resolved → UI shows grid
```

## Flow 3: طلب معاينة

```
[PropertyDetailScreen] → [📅 طلب معاينة]
  → showViewingSheet(context, property) (BottomSheet)
  → User picks date/time/notes
  → onPressed "إرسال"
    → ViewingRequestRepository.requestViewing(propertyId, date, time, notes)
      → _api.post('/viewing-requests', data: {...})
        → POST /api/v1/viewing-requests
        → Laravel: ViewingRequestController@store
          → validate: property_id exists, date after today, time H:i
          → check: property published
          → check: not own property
          → DB::transaction → ViewingRequest::create({...status: pending})
          → $property->agent->user->notify(new ViewingRequestCreated())
        → return 201 with ViewingRequestResource
    → notice('تم إرسال طلب المعاينة')
    → Pop sheet
```

## Flow 4: إزالة المفضلة

```
[PropertyCard] → tap heart (if removing)
  → toggleFavorite(context, ref, property)
    → Is logged in? else → show auth dialog
    → show confirm dialog "إزالة من المفضلة؟"
    → if confirmed:
      → ref.read(favoriteOverridesProvider.notifier).set(property.id, false)
      → ref.read(propertyRepositoryProvider).setFavorite(property.id, false)
        → DELETE /api/v1/favorites/{propertyId}
        → Laravel: FavoriteController@destroy
          → Favorite::where(...)->delete()
        → 204
      → notice('تمت الإزالة')
    → else (error):
      → ref.read(favoriteOverridesProvider.notifier).remove(property.id)
      → notice('حدث خطأ في إزالة المفضلة')
```

---

# القسم الخامس: أهم ما يحتاج التركيز

## "لماذا يحدث هذا؟" — إجابات سريعة

**لماذا خرجت من الجلسة رغم أن الموقع الذي أتصفح منه يعمل؟**
لأن التوكن انتهى (30 يوم) فعله، أو الخادم رد 401 → clear → SessionGate يعيد التوجيه.

**لماذا تضع صوري وقت تحميل أقل؟**
لأن CachedNetworkImage يخزنها، و ImageCompressor يضغطها قبل الرفع.

**لماذا لا أرى 특정 عقار في البحث؟**
لأنه غير published (ربما pending أو rejected) — البحثيعرض فقط status='published'.

**لماذا لا أستطيع تعديل عقار ليس ملكي؟**
لأن PropertyPolicy.update يتحقق من `property.agent.user_id === user.id`.

**لماذا لا أستطيع إضافة مفضلة لغير مسجل؟**
لأن UI يمنع ذلك مع AuthRequiredScreen، والخادم auth:sanctum يعيد 401.

**لماذا لا يعمل الرفع على Hosting؟**
لأن الاستضافة تحذف Authorization header → InjectSanctumToken middleware يحلها عبر X-Auth-Token.

---

*انتهى — هذه هي الملفات التي ستجعلك جاهزاً لأي سؤال عن الكود.*
