# التشغيل والنشر — LUX Living

## متطلبات الخادم

يتطلب الخادم PHP 8.3+ وComposer وMySQL 8+ وامتدادات PHP القياسية لـ Laravel وتخزين ملفات قابلًا للكتابة. يجب تشغيل Laravel خلف Nginx أو Apache مع PHP-FPM وHTTPS في الإنتاج. تستخدم لوحة الإدارة المسار `/admin`، وتُنشأ أول هوية مدير صراحةً بواسطة أمر `lux:create-admin`؛ لا يحتوي المشروع حساب مدير أو كلمة مرور افتراضية.

## قاعدة البيانات وLaravel

```bash
cd backend
cp .env.example .env
# اضبط DB_CONNECTION=mysql وDB_HOST وDB_DATABASE وDB_USERNAME وDB_PASSWORD
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
php artisan lux:create-admin
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

يتضمن `DatabaseSeeder` الأدوار والصلاحيات فقط؛ لا يُنشئ عقارات أو مستخدمين تجريبيين. لبيئة التطوير أو الاختبار الآلي فقط، يمكن تشغيل بيانات API مكتملة ومنفصلة عبر الأمر التالي. لا يستدعيها `DatabaseSeeder`، ويرفض الباذر العمل خارج بيئتي `local` و`testing`:

```bash
# عندما تكون APP_ENV=local أو testing
php artisan db:seed --class=DemoDataSeeder

# لتهيئة قاعدة التطوير المحلية التي تعمل بإعداد بيئة مختلف؛ موافقة صريحة لعملية CLI واحدة فقط
LUX_ALLOW_DEMO_SEED=true php artisan db:seed --class=DemoDataSeeder
```

ينشئ الأمر بيانات MySQL دائمة لمراجعة API والعميل يدويًا: عميلًا ووكيلًا نشطًا وأربعة عقارات وصور SVG محلية وطلب معاينة ومحادثة. بيانات الدخول المحلية فقط هي `client.demo@lux.local` / `LuxDemo2026` و`agent.demo@lux.local` / `LuxDemo2026`، ولا يجوز إطلاقًا إعادة استخدامها أو نشرها في الإنتاج. في الإنتاج يجب تشغيل queue worker فقط عند تفعيل مزود queue حقيقي، وضبط `APP_ENV=production` و`APP_DEBUG=false` ومفتاح `APP_KEY` فريد وHTTPS وتخزين إنتاجي.

## عنوان API عبر الشبكة والمحاكي

| سياق العميل | مثال `LUX_API_BASE_URL` |
|---|---|
| جهاز Android حقيقي على نفس الشبكة | `http://192.168.1.20:8000/api/v1` أثناء التطوير فقط |
| محاكي Android الرسمي | `http://10.0.2.2:8000/api/v1` |
| Windows/سطح المكتب أو متصفح على نفس الجهاز | `http://127.0.0.1:8000/api/v1` |
| إنتاج | `https://api.example.com/api/v1` |

لا يستعمل التطبيق `localhost` ثابتًا. مرر العنوان وقت التشغيل أو البناء:

```bash
cd mobile
flutter run --dart-define=LUX_API_BASE_URL=http://10.0.2.2:8000/api/v1
# أو عند بناء التوزيع
LUX_API_BASE_URL=https://api.example.com/api/v1 bash ../scripts/build_android_universal.sh
```

تسمح إعدادات Android بالاتصال غير المشفر في build debug فقط. يجب استخدام HTTPS في release. اضبط CORS في Laravel على نطاقات العملاء المعتمدة فقط، ولا تستخدم wildcard مع بيانات اعتماد.

## بناء Android

تعمل أدوات البناء في بيئة محدودة الذاكرة عبر `scripts/build_android_universal.sh` (اسم تاريخي للسكربت). ينفذ السكربت AOT لـ `android-arm64`، ثم ينقّي مكتبات المعماريات الأخرى من حزمة الإصدار ويعيد توقيع APK الناتج. الملف الافتراضي هو `mobile/dist/LUX-Living-0.2.0-armv8.apk` ويحتوي `arm64-v8a` فقط. راجع `FLUTTER_3_41_ANDROID_BUILD_NOTE.md` للسبب الفني.

للتوقيع الإنتاجي، أنشئ keystore خاصًا بالمؤسسة خارج المستودع، ثم استبدل `signingConfigs.getByName("debug")` في build type `release` بإعداد يعتمد على `key.properties` محفوظ في مدير أسرار. لا ترفع keystore أو `key.properties` إلى التحكم بالإصدار.

## الصيانة والتحقق

```bash
# Laravel
cd backend && php artisan test && php artisan route:list --path=api/v1

# Flutter
cd mobile && flutter analyze && flutter test

# التحقق من APK
$ANDROID_SDK_ROOT/build-tools/35.0.0/apksigner verify --verbose dist/LUX-Living-0.1.0-universal.apk
```
