<?php

/**
 * فحص نهائي: هل يصل رأس Authorization لـ PHP؟ وهل يتطابق الرمز مع Sanctum على
 * مستوى PHP (بدون المرور عبر الإنترنت/البروكسي)؟
 *
 * افتح:
 *   https://wajhatak.infinityfree.io/diag2.php?token=wj_diag2_7b9d31c4
 *   (اختياري): أضف  try_token=ID|PLAIN  لاختبار رمز محدد
 */

const DIAG_TOKEN = 'wj_diag2_7b9d31c4';
$base = __DIR__;

header('Content-Type: text/plain; charset=utf-8');
if (($_GET['token'] ?? '') !== DIAG_TOKEN) {
    http_response_code(403);
    echo 'token خاطئ';
    exit;
}

$server = $_SERVER ?? [];
function jout($k, $v): void { echo json_encode($k, JSON_UNESCAPED_UNICODE).': '.json_encode($v, JSON_UNESCAPED_UNICODE)."\n"; }

echo "== A) هل يصل رأس Authorization إلى PHP؟ ==\n";
$gotAuth = false;
$authVal = '';
foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'PHP_AUTH_USER'] as $k) {
    if (!empty($server[$k])) { $gotAuth = true; $authVal = $server[$k]; break; }
}
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strcasecmp($k, 'Authorization') === 0) { $gotAuth = true; $authVal = $v; break; }
    }
    if (!empty($server['HTTP_X_AUTH_TOKEN'])) {
        echo json_encode('كشفنا X-Auth-Token كذلك'); echo ': '.json_encode(substr($server['HTTP_X_AUTH_TOKEN'],0,12).'...')."\n";
    }
}
jout('authorization_received', $gotAuth);
jout('authorization_scheme', $gotAuth ? (explode(' ', $authVal, 2)[0] ?? '') : '(غائب)');
jout('request_method', $server['REQUEST_METHOD'] ?? '?');
// لاختبار وصول الرأس مباشرة: أضف try_header=anything
if (isset($_GET['try_header'])) {
    $hdr = function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '(غائب via getallheaders)') : '(لا getallheaders)';
    jout('echo_try_header', $server['HTTP_AUTHORIZATION'] ?? $hdr);
}

echo "\n== B) هل يُقبل الرمز في الإطار نفسه عند إنشائه في نفس العملية؟ ==\n";
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$model = \Laravel\Sanctum\PersonalAccessToken::class;

try {
    $clientToken = $_GET['try_token'] ?? '';
    if ($clientToken !== '') {
        jout('testing_given_token', substr($clientToken, 0, 6).'…');
    }

    $u = \App\Models\User::where('email', 'client.demo@lux.local')->first();
    if (!$u) { jout('demo_user', 'غير موجود'); exit; }
    jout('demo_user_found', $u->id);

    $u->tokens()->where('name', 'diag')->delete();
    $token = $u->createToken('diag');
    $plain = rtrim($token->plainTextToken);
    [$id, $secret] = explode('|', $plain, 2);
    jout('fresh_token_default_accepts', (new $model)->findToken($plain) ? true : false);

    $hash = hash('sha256', $secret);
    $stored = $model::where('id', (int) $id)->where('token', $hash)->exists();
    jout('token_hash_lookup_matches', $stored);

    // تعيين الوقت الأخير للاستخدام كمؤشر على أن الرمز يعمل
    $row = $model::find((int) $id);
    $row->last_used_at = now();
    $row->save();
    jout('last_used_at_saved', true);

    // هل توكن تجاوز صلاحية؟ (الافتراضي NULL = لا انتهاء)
    jout('expires_at_value', $model::find((int) $id)->expires_at ?? null);
    jout('grand', 'انتهى الفحص بنجاح.');
} catch (\Throwable $e) {
    jout('b_fatal', $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
}