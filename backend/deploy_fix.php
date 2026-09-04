<?php

/**
 * يصلح مشكلة اتصال Laravel بقاعدة البيانات في الإنتاج.
 *
 * السبب الجذري الشائع: وجود "bootstrap/cache/config.php" قديم على الخادم
 * يجمّد قيم الإعدادات (بما فيها بيانات قاعدة البيانات) من نسخة سابقة من
 * ".env". عندما يكون config cache موجودًا، يتجاهل Laravel ملف ".env" تمامًا
 * ويستمر في استخدام القيم القديمة (مثل 127.0.0.1 / wajhatak) رغم تعديل ".env".
 *
 * هذا السكربت:
 *  1) يفحص القيم التي "يحلّها" Laravel فعليًا لقاعدة البيانات (دون كشف كلمات السر).
 *  2) يمسح config/route/view caches ليعود Laravel للقراءة من ".env".
 *
 * طريقة التشغيل على الخادم:
 *   cd <المجلد الجذر لـ Laravel>  &&  php deploy_fix.php
 *
 * ملاحظة أمان: بعد التأكد من عمل الاتصال، احذف هذا الملف من الخادم، ولا
 * تتركه قابلًا للوصول عبر الويب.
 */

// ---------------------------------------------------------------------------
// سر حماية الوصول عبر الويب (غيّره إلى أي قيمة عشوائية طويلة).
// الوصول عبر HTTP يتطلب:  ?token=<TOKEN>
// ---------------------------------------------------------------------------
const WEB_TOKEN = 'wj_clear_cache_7f3a9d21';

$base = __DIR__;

// تنفيذ مباشر عبر الويب: حذف ملفات الكاش القديمة فقط (لا يحتاج SSH).
// بمجرد حذف bootstrap/cache/config.php سيعيد Laravel قراءة ".env" في الطلب التالي.
function deleteCacheFiles(string $base): array {
    $dir = $base . '/bootstrap/cache';
    if (!is_dir($dir)) {
        return [];
    }
    $patterns = [
        'config.php',
        'routes-*.php',
        'packages.php',
        'services.php',
        'events.php',
        'compiled.php',
        'cache.php',
        'config-*.php',
    ];
    $removed = [];
    $blocked = [];
    foreach ($patterns as $pattern) {
        foreach (glob($dir . '/' . $pattern) ?: [] as $file) {
            if (@unlink($file)) {
                $removed[] = $file;
            } else {
                $blocked[] = $file;
            }
        }
    }
    if ($blocked !== []) {
        throw new RuntimeException(
            'تعذّر حذف ملف كاش (صلاحيات الكتابة): '
            . implode(', ', array_map(fn ($f) => basename($f), $blocked))
            . '. اجعل مجلد bootstrap/cache قابلًا للكتابة (CHMOD 755) ثم أعد المحاولة.'
        );
    }
    return $removed;
}

if (PHP_SAPI !== 'cli') {
    // وضع الويب (مؤمّن برمز): امسح الكاش القديم فورًا.
    if (($_GET['token'] ?? '') !== WEB_TOKEN) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'رمز الوصول غير صحيح.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        $removed = deleteCacheFiles($base);
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['ok' => false, 'error' => $e->getMessage()],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
        );
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        [
            'ok' => true,
            'message' => 'تم حذف كاش الإعدادات. أعد المحاولة الآن على الواجهة.',
            'removed' => $removed,
        ],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
    );
    exit;
}

// وضع سطر الأوامر الفعّال الكامل (اختبار اتصال + طباعة القيم الفعلية).
$autoload = $base . '/vendor/autoload.php';
$bootstrap = $base . '/bootstrap/app.php';

if (!file_exists($autoload) || !file_exists($bootstrap)) {
    fwrite(STDERR, 'يبدو أنك لست في المجلد الجذر لـ Laravel. نفّذ السكربت من مجلد laravel.' . PHP_EOL);
    exit(1);
}

require $autoload;
$app = require $bootstrap;

/** الآن نمسح الـ caches ليعود Laravel للقراءة من .env */
echo "== تكرار: مسح الـ caches ==" . PHP_EOL;
$removed = deleteCacheFiles($base);
foreach ($removed as $file) {
    echo "  حُذف: " . str_replace($base . '/', '', $file) . PHP_EOL;
}
if ($removed === []) {
    echo "  (لا توجد ملفات كاش قديمة — يقرأ Laravel من .env مباشرة.)" . PHP_EOL;
}
// محاولة عبر artisan إن وُجد (ليست ضرورية بعد حذف الملفات لكنها آمنة).
passthru('php ' . escapeshellarg($base . '/artisan') . ' config:clear 2>&1');

// أعد تجهيز التطبيق بعد مسح الكاش حتى نقرأ الإعدادات الصحيحة.
$app = require $bootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/** الآن نقرأ القيم التي سيستخدمها Laravel فعليًا */
$conn = config('database.default');
$host = config("database.connections.{$conn}.host");
$port = config("database.connections.{$conn}.port");
$db = config("database.connections.{$conn}.database");
$user = config("database.connections.{$conn}.username");

echo "== الإعدادات الفعلية بعد مسح الكاش ==" . PHP_EOL;
echo "Connection  : {$conn}" . PHP_EOL;
echo "Host        : {$host}:{$port}" . PHP_EOL;
echo "Database    : {$db}" . PHP_EOL;
echo "Username    : {$user}" . PHP_EOL;
echo '(كلمة المرور لا تُطبع حفاظًا على الأمان.)' . PHP_EOL;

/** اختبار الاتصال الفعلي بقاعدة البيانات */
echo PHP_EOL . "== اختبار الاتصال بقاعدة البيانات ==" . PHP_EOL;
try {
    DB::connection()->getPdo();
    $driver = DB::connection()->getDriverName();
    echo "OK: تم الاتصال بقاعدة البيانات بنجاح (driver: {$driver})." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'فشل الاتصال بقاعدة البيانات:' . PHP_EOL);
    // نعرض رسالة الخطأ دون كشف كلمة المرور (PDO لا يطبعها ضمن رسائله عادة).
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

/** فحص وجود جداول Sanctum المطلوبة (عبر PDO مباشرة دون الحاجة إلى doctrine/dbal) */
echo PHP_EOL . "== فحص جداول Sanctum ==" . PHP_EOL;
try {
    $pdo = DB::connection()->getPdo();
    $required = ['users', 'personal_access_tokens'];
    foreach ($required as $t) {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t))->fetch() !== false;
        echo $exists ? "OK : {$t}" . PHP_EOL : "MISSING: {$t} (شغّل php artisan migrate)" . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'تعذّر فحص الجداول: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo PHP_EOL . 'تم. القيم المعروضة أعلاه هي ما سيستخدمه Laravel فعليًا الآن.' . PHP_EOL;
echo 'إذا ظهر Host صحيح (sql...infinityfree.com) وOK للاتصال، جرّب الواجهة الآن.' . PHP_EOL;
echo PHP_EOL . 'الاختياري (لا تفعله قبل تأكد الاتصال): إعادة بناء كاش الإعدادات للأداء:' . PHP_EOL;
echo '  php artisan config:cache' . PHP_EOL;
echo 'احذف هذا الملف من الخادم بعد الانتهاء.' . PHP_EOL;
