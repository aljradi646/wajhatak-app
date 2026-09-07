# وجهتك — Wajhatak

<div dir="rtl">

**وجهتك إلى العقار المناسب.**

منصة عقارية عربية متكاملة جاهزة للإنتاج: تطبيق Flutter (عربي RTL أولًا) + واجهة API بخادم Laravel 12 + لوحة تحكم إدارية عربية + قاعدة بيانات علائقية كاملة.

| المكوّن | التقنية | المكان |
|---------|---------|--------|
| تطبيق الجوال/الويب | Flutter 3.47 · Dart 3.13 · Riverpod 3 · Dio 5 | `mobile/` |
| واجهة API | Laravel 12 · Sanctum · Spatie Permission | `backend/` |
| لوحة التحكم | Blade + Tailwind (عربية RTL كاملة) | `backend/resources/views/admin` |
| قاعدة البيانات | MySQL/MariaDB (إنتاج) · SQLite (تطوير/اختبار) | `backend/database/migrations` |

---


## 1) الهوية البصرية

- **الاسم العربي:** وجهتك (الاسم الأساسي في كل الواجهات العربية)
- **الاسم الإنجليزي الثانوي:** WAJHATAK (يظهر فوق الاسم العربي في الشعار)
- **الشعار النصي (Tagline):** وجهتك إلى العقار المناسب.
- **اللغة الأساسية:** العربية (RTL كامل) — الخط: **Cairo** (خط متغير 200–1000 مضمّن في التطبيق)
- **الأيقونة:** قوس معماري (حرف و) بنقطة موقع كهرمانية على تدرج زمردي — `mobile/assets/images/wajhatak_icon.png` وجميع كثافات Android/iOS/Web مولّدة ومربوطة فعليًا.

## 2) المعمارية

```
Flutter UI
    ↓ (Riverpod providers — مصدر حقيقة واحد لكل مورد)
Repositories (منفصلة: Auth / Property / Conversation / ViewingRequest / Notification / Taxonomy)
    ↓ (LuxApiClient — Dio + Interceptors + توكن آمن + أخطاء عربية موحدة)
Laravel API v1 (Sanctum tokens + Policies + FormRequests + Resources)
    ↓ (Eloquent + فهرسة + قيود فريدة على مستوى القاعدة)
MySQL / SQLite
```

### أبرز القرارات المعمارية
- **تفرد المحادثات:** قيد فريد على `(client_id, agent_id)` — أي عقار لنفس الوكيل يعيد نفس المحادثة، وبطاقة العقار تُرسل كرسالة `message_type=property`.
- **المفضلة:** قيد فريد `(user_id, property_id)` + `firstOrCreate` (إضافة idempotent) + عزل كامل بين المستخدمين، وحالة القلب الحمراء تتزامن فورًا عبر `favoriteOverrides` عند تأكيد الخادم.
- **العملات (data-driven):** `GET /api/v1/currencies` من `config/currencies.php` — YER افتراضية + SAR + USD بأسماء ورموز وأعلام عربية، والقوائم المنسدلة في Flutter تجلبها من الخادم.
- **المواقع (تسلسل هرمي):** دولة ← محافظة ← مدينة ← حي، بقوائم منسدلة متتالية تجلب الأبناء عند اختيار الأب (`/countries` ثم `/regions?country_id=` ثم `/cities?region_id=` ثم `/areas?city_id=`). بيانات اليمن (22 محافظة) والسعودية مضبوطة في `LocationSeeder`.
- **تسجيل الخروج:** API logout → حذف التوكن → إبطال كل مزودات الجلسة → واجهة الدخول فورًا (بدون إعادة تشغيل). التوكن يُرفض بـ401 بعدها (مُختبر E2E).
- **الإشعارات:** إشعارات قاعدة بيانات مع `MessageReceived` (ShouldQueue) + Polling محسوب في التطبيق كل 25 ثانية + إشعارات محلية شرطية حسب تفضيلات المستخدم.

## 3) تشغيل الواجهة الخلفية (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# قاعدة البيانات: MySQL في الإنتاج
#   DB_CONNECTION=mysql  DB_DATABASE=wajhatak  ...
# أو SQLite للتجربة السريعة:
#   DB_CONNECTION=sqlite ثم: touch database/database.sqlite

php artisan migrate --seed      # ينشئ الأدوار + المواقع + بيانات تجريبية
php artisan db:seed --class=DemoDataSeeder --force   # عقارات تجريبية (اختياري)
php artisan storage:link        # لعرض صور العقارات والأواتار
php artisan serve               # http://localhost:8000
```

### قائمة الإنتاج
```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
npm install && npm run build
# تشغيل عامل الطابور (للإشعارات المؤجلة):
php artisan queue:work --tries=3
```
- ضع `APP_ENV=production` و`APP_DEBUG=false` ولا تضع أي مفاتيح سرية في المستودع.
- حدّث `SANCTUM_STATEFUL_DOMAINS` عند نشر نطاق فعلي.

## 4) تشغيل تطبيق Flutter

```bash
cd mobile
flutter pub get

# عنوان API — يحُدد عبر dart-define (المحاكي الافتراضي 10.0.2.2):
flutter run \
  --dart-define=WAJHATAK_API_BASE_URL=http://10.0.2.2:8000/api/v1

# جهاز حقيقي على نفس الشبكة:
flutter run \
  --dart-define=WAJHATAK_API_BASE_URL=http://<IP-الخادم>:8000/api/v1
```

### بناء الإنتاج
```bash
flutter build apk --release \
  --dart-define=WAJHATAK_API_BASE_URL=https://api.your-domain.com/api/v1
flutter build appbundle --release \
  --dart-define=WAJHATAK_API_BASE_URL=https://api.your-domain.com/api/v1
```

- `applicationId`: `com.wajhatak.app` · `minSdk 24` · تسمية التطبيق: **وجهتك**
- وضع معاينة التصميم (debug فقط): `--dart-define=WAJHATAK_UI_PREVIEW=true` مع `WAJHATAK_UI_PREVIEW_ROLE=client|agent`.

## 5) لوحة التحكم

- العنوان: `/admin` (تسجيل دخول ويب منفصل عن تطبيق الجوال — المستخدم العادي لا يملك صلاحيات الإدارة)
- عربية RTL كاملة `<html lang="ar" dir="rtl">` بخط Cairo، متجاوبة (جوال/تابلت/سطح مكتب)
- **مبدّل مظهر** (نظام/فاتح/داكن) محفوظ بجانب ملف المستخدم
- شريط جانق قابل للطي (أيقونة فقط عند الطي، ويزر الطي يختفي ويعود عبر الشعار)
- أقسام كاملة CRUD: المستخدمون، الوكلاء، العقارات (مع سلّة محذوفات/استعادة/حذف نهائي/إجراءات جماعية + الصور)، طلبات المعاينة، أنواع العقارات، المزايا، المواقع (دولة/محافظة/مدينة/منطقة)، الإعدادات، **سجل نشاط النظام**
- لوحة القيادة: إحصائيات حقيقية من القاعدة (مستخدمون، وكلاء، عقارات بالحالة، محادثات، رسائل آخر 7 أيام، مفضلات، أحدث العقارات، آخر النشاطات)

## 6) الاختبارات

```bash
# الخادم (47 اختبارًا، 156 تأكيدًا)
cd backend && php artisan test

# تطبيق Flutter (27 اختبارًا)
cd mobile && flutter analyze && flutter test

# اختبار تكامل E2E حقيقي (45 تأكيدًا): يشغّل خادم Laravel فعليًا
# ويمر بدورة كاملة: تسجيل وكلاء/عملاء → إنشاء عقار YER → نشر →
# مفضلة (إضافة/تكرار/إزالة/عزل) → محادثة فريدة + بطاقة عقار →
# رسائل + وصول إشعار → طلب معاينة → تسجيل خروج يبطل التوكن
bash scripts/e2e_test.sh
```

## 7) استكشاف الأخطاء

| المشكلة | الحل |
|---------|------|
| الصور لا تظهر | نفّذ `php artisan storage:link` وتأكد من `APP_URL` |
| 401 متكرر في التطبيق | تأكد أن `WAJHATAK_API_BASE_URL` يصل للخادم (المحاكي: `10.0.2.2` وليس `localhost`) |
| الإشعارات لا تصل في الإنتاج | شغّل `php artisan queue:work` (الإشعار مؤجّل عبر الطابور) |
| فشل تسجيل بريد إلكتروني | التحقق يستخدم `email:rfc,dns` — استخدم بريدًا بنطاق حقيقي |
| قوائم المواقع فارغة | `php artisan db:seed --class=LocationSeeder --force` |

## 8) خريطة أهم الملفات

```
mobile/lib/
├── core/ (config, theme: نظام الألوان/الأيقونات الملونة/التجاوب, utils)
├── data/ (api_client, models/ 15 ملفًا, repositories/ 6 ملفات)
├── state/ (providers: Riverpod — الجلسة/العقارات/المفضلة/المحادثات/العملات)
└── ui/ (brand, widgets/, screens/ 16 شاشة معاد تصميمها)

backend/
├── app/Http/Controllers/Api/V1 (12 متحكمًا)
├── app/Http/Controllers/Admin (10 متحكمات لوحة التحكم)
├── app/Models (20 نموذجًا)
├── config/currencies.php (كتالوج العملات)
├── database/migrations (13 هجرة آمنة قابلة للعكس)
├── database/seeders (DatabaseSeeder + LocationSeeder + DemoDataSeeder)
└── resources/views/admin (لوحة تحكم عربية RTL)
```

</div>
