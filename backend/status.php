<?php

/**
 * فحص + إصلاح شامل لحالة «المناداة» على الخادم (لا يُفرَّغ منه):
 *  1) هل ملف الوسيط InjectSanctumToken موجود على الخادم؟
 *  2) هل يوجد كاش routes قديم يمنع تنفيذ التعديل؟ (نحذفه بصلاحيات)
 *  3) هل الوسيط مسجّل في bootstrap/app.php؟
 *  4) هل مسار /me الفعلي يستخدم الوسيط؟
 *
 * افتح:
 *   https://wajhatak.infinityfree.io/status.php?token=wj_status_3d8b7e11
 */

const STATUS_TOKEN = 'wj_status_3d8b7e11';

header('Content-Type: text/plain; charset=utf-8');
if (($_GET['token'] ?? '') !== STATUS_TOKEN) {
    http_response_code(403);
    echo 'token خاطئ';
    exit;
}

function jout($k, $v): void { echo json_encode($k, JSON_UNESCAPED_UNICODE).': '.json_encode($v, JSON_UNESCAPED_UNICODE)."\n"; }

$base = __DIR__;

// 0) أين نحن بالضبط على الخادم؟ مهم للتحقق من أن الملفات وُضعت في المسار الصحيح.
echo "== 0) موقع التنفيذ ==\n";
jout('realpath_dir', realpath($base));
jout('project_root', file_exists($base.'/artisan') ? 'يوجد artisan هنا (جذر Laravel)' : 'لا يوجد artisan (ربما نُفّذ من مسار خاطئ!)');
jout('public_exists', is_dir($base.'/public'));
jout('server_host', $_SERVER['HTTP_HOST'] ?? '?');
jout('request_uri', $_SERVER['REQUEST_URI'] ?? '?');

// 1) ملف الوسيط
$mw = $base . '/app/Http/Middleware/InjectSanctumToken.php';
jout('middleware_file_exists', file_exists($mw));
jout('middleware_file_size', file_exists($mw) ? filesize($mw) : 0);
if (file_exists($mw)) {
    // تحقق سريع من وجود الكلاس والدالة (وليس مجرد وجود الملف)
    $src = file_get_contents($mw);
    jout('has_class_decl', strpos($src, 'class InjectSanctumToken') !== false);
    jout('has_handle_method', strpos($src, 'function handle(') !== false);
    jout('has_x_auth_token', stripos($src, 'X-Auth-Token') !== false);
}

// 2) كاش routes قديم (هو السبب الأرجح لمنع التعديل)
echo "\n== كاش routes/config ==\n";
$cacheDir = $base . '/bootstrap/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.php') ?: [];
    jout('cache_files_count', count($files));
    foreach ($files as $f) {
        $deleted = @unlink($f);
        jout('deleted_'.basename($f), ($deleted === false ? 'FAILED (permissions)' : 'OK'));
    }
}

// 3) هل الوسيط مسجّل؟
echo "\n== تسجيل الوسيط ==\n";
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $aliases = $app['router'] !== null ? [] : [];
} catch (\Throwable $e) {}
// نقرأ bootstrap/app.php لنرى إن كان الـ alias مكتوبًا
$appSrc = file_get_contents($base.'/bootstrap/app.php');
jout('has_alias_inject', strpos($appSrc, "'inject.sanctum.token'") !== false);
jout('has_use_Inject', strpos($appSrc, 'InjectSanctumToken') !== false);
jout('has_global_prepend', strpos($appSrc, "prepend(\\App\\Http\\Middleware\\InjectSanctumToken::class)") !== false);

// 4) اختبار حقيقي: هل تعمل السلسلة كاملة (وسط + auth:sanctum) على مستوى PHP؟
echo "\n== 4) اختبار auth عبر رأس X-Auth-Token (داخل العملية) ==\n";
try {
    $u = \App\Models\User::where('email', 'client.demo@lux.local')->first();
    if (!$u) { jout('demo_user', 'غير موجود'); exit; }
    $u->tokens()->where('name', 'auth_test')->delete();
    $token = $u->createToken('auth_test');
    $plain = rtrim($token->plainTextToken);

    // طلب وهمي لـ /me مع رأس X-Auth-Token فقط
    $req = Illuminate\Http\Request::create('/api/v1/me', 'GET');
    $req->headers->set('X-Auth-Token', $plain);
    $req->headers->set('Accept', 'application/json');

    // هذه الربطة ستحقن الرمز فعليًا قبل auth
    $injectedAuth = null;
    $next = function ($r) use (&$injectedAuth) {
        $injectedAuth = $r->headers->get('Authorization');
        return new Illuminate\Http\JsonResponse([]);
    };
    if (!class_exists(\App\Http\Middleware\InjectSanctumToken::class)) {
        jout('middleware_class_load', 'FAILED (الكلاس غير قابل للتحميل)');
    } else {
        jout('middleware_class_load', 'OK');
        (new \App\Http\Middleware\InjectSanctumToken())->handle($req, $next);
        jout('header_after_injection', $injectedAuth);

        // الآن نتحقق هل يقبل auth:sanctum الرمز بعد الحقن؟
        $try = Illuminate\Http\Request::create('/api/v1/me', 'GET');
        $try->headers->set('Authorization', $injectedAuth);
        $try->headers->set('Accept', 'application/json');
        try {
            \Laravel\Sanctum\Sanctum::setRequest($try);
        } catch (\Throwable $e) {}
        $guard = \Illuminate\Support\Facades\Auth::guard('sanctum');
        $user = $guard->user();
        jout('authenticated_user_id', $user ? $user->id : null);
    }
    $u->tokens()->where('name', 'auth_test')->delete();
} catch (\Throwable $e) {
    jout('fatal', $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
}

echo "\n== نهاية الفحص ==\n";