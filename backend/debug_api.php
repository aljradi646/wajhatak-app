<?php

/**
 * تشخيص اتصال قاعدة البيانات والرمز (token) في بيئة الإنتاج — بدون SSH.
 *
 * يساعد في معرفة لماذا ترفض `auth:sanctum` الرمز رغم نجاح تسجيل الدخول.
 * لا يطبع كلمات السر أبدًا.
 *
 * الوصول عبر المتصفح:
 *   https://wajhatak.infinityfree.io/debug_api.php?token=wj_debug_5a1c9f02
 *
 * اجعله CLI-only اختياريًا عبر؟ لكن نجعل الوضعين مدعومين بمفتاح سر.
 */

const DEBUG_TOKEN = 'wj_debug_5a1c9f02';

$base = __DIR__;
function out(string $k, $v): void {
    echo json_encode($k, JSON_UNESCAPED_UNICODE) . ': ' . json_encode($v, JSON_UNESCAPED_UNICODE) . "\n";
}

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    if (($_GET['token'] ?? '') !== DEBUG_TOKEN) {
        http_response_code(403);
        echo 'رمز الوصول غير صحيح.';
        exit;
    }
}

$autoload = $base . '/vendor/autoload.php';
$bootstrap = $base . '/bootstrap/app.php';
if (!file_exists($autoload) || !file_exists($bootstrap)) {
    fwrite(STDERR, 'يجب تنفيذ السكربت من جذر Laravel.' . PHP_EOL);
    exit(1);
}
require $autoload;
$app = require $bootstrap;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "== 1) الإعدادات الفعلية لقاعدة البيانات (من config، بدون كلمة السر) ==\n";
$conn = config('database.default');
$host = config("database.connections.{$conn}.host");
$port = config("database.connections.{$conn}.port");
$db = config("database.connections.{$conn}.database");
$user = config("database.connections.{$conn}.username");
$driver = config("database.connections.{$conn}.driver");
out('driver', $driver);
out('host', $host . ':' . $port);
out('database', $db);
out('username', $user);
out('MSQL_DATABASE_ENV_POINT', function_exists('getenv') ? (getenv('DB_DATABASE') ?: '-') : '-');

echo "\n== 1b) هل يصل رأس Authorization إلى PHP؟ (كشف حجب الاستضافة له) ==\n";
$gotAuth = false;
$authVal = '';
$server = $_SERVER ?? [];
foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'PHP_AUTH_USER'] as $k) {
    if (!empty($server[$k])) {
        $gotAuth = true;
        $authVal = $server[$k];
        break;
    }
}
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strcasecmp($k, 'Authorization') === 0) {
            $gotAuth = true;
            $authVal = $v;
            break;
        }
    }
}
out('authorization_received', $gotAuth);
out('authorization_scheme', $gotAuth ? (explode(' ', $authVal, 2)[0] ?? '') : '(غائب)');
out('request_method', $server['REQUEST_METHOD'] ?? '?');

echo "\n== 2) الاتصال بقاعدة البيانات ==\n";
try {
    $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
    out('ping', 'OK');
} catch (Throwable $e) {
    out('error', $e->getMessage());
    exit(1);
}

echo "\n== 3) هل تجد الاستضافة قاعدة البيانات نفسها التي تحتوي البيانات؟ ==\n";
$sqlFailed = [];
$check = function (string $sql) use ($pdo): array {
    try {
        return ['ok' => true, 'row' => $pdo->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
};
out('db_name()', $check('SELECT DATABASE() AS d'));
out('tables: users', $check('SHOW TABLES LIKE "users"')['ok'] ? 'found' : 'missing');
out('tables: personal_access_tokens', $check('SHOW TABLES LIKE "personal_access_tokens"')['ok'] ? 'found' : 'missing');

echo "\n== 4) المفاتيح (tokens) المخزنة \n";
$c = function (string $sql) use ($pdo) {
    try { return $pdo->query($sql)->fetchColumn(); } catch (Throwable $e) { return null; }
};
out('count users', $c('SELECT COUNT(*) FROM users'));
out('count personal_access_tokens', $c('SELECT COUNT(*) FROM personal_access_tokens'));
out('latest token id', $c('SELECT MAX(id) FROM personal_access_tokens'));
out('distinct tokenable_type', $c('SELECT DISTINCT tokenable_type FROM personal_access_tokens'));
out('token column length (sample)', $c('SELECT CHAR_LENGTH(token) FROM personal_access_tokens LIMIT 1'));

echo "\n== 5) محاولة إيجاد رمز صالح عبر Sanctum مباشرة ==\n";
// نقرأ آخر token اعتماده من؟ نعرض فقط ما إذا كان موجودًا وطول hash.
$row = null;
try { $row = $pdo->query('SELECT id, token FROM personal_access_tokens ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC); } catch (Throwable $e) { $row = null; }
if ($row) {
    out('sample id', $row['id']);
    out('sample hash length', strlen($row['token']));
    out('sample hash pattern_is_sha256', (bool) preg_match('/^[0-9a-f]{64}$/', $row['token']));
} else {
    out('note', 'لا يوجد أي رمز في الجدول — هذا هو سبب رفض auth:sanctum للرمز: '
        . 'الجدول فارغ (قد تكون البيانات في قاعدة مختلفة عمّا يُقرأ الآن).');
}

// POST توضيحي: يمكنك إرسال رمز حقيقي هنا للمعالجة الآمنة
if (isset($_POST['plain_token'])) {
    $plain = trim((string) $_POST['plain_token']);
    if ($plain !== '') {
        $parts = explode('|', $plain, 2);
        if (count($parts) === 2) {
            [$id, $hash] = $parts;
            $found = $c('SELECT COUNT(*) FROM personal_access_tokens WHERE id = ' . (int) $id);
            // Sanctum يستخدم hash('sha256', $hash)
            $wanted = hash('sha256', $hash);
            $match = $c("SELECT COUNT(*) FROM personal_access_tokens WHERE id = " . (int) $id . " AND token = '" . addslashes($wanted) . "'");
            out('token_id_exists', (int) $found > 0);
            out('token_hash_matches_sanctum', (int) $match > 0);
        }
    }
}

echo "\n=== انتهى. لا تترك هذا الملف على الخادم بعد التشخيص. ===\n";