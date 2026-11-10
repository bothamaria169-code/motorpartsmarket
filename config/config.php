<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Motor Parts Market needs PHP 8.1 or newer. In cPanel open "Select PHP Version" (or "MultiPHP Manager") and choose PHP 8.2.');
}

// ── Secrets ──────────────────────────────────────────────────────
// Passwords and keys live in config/secrets.php (not in git).
// Copy config/secrets.example.php to start one.
$secretsFile = __DIR__ . '/secrets.php';
$GLOBALS['MPM_SECRETS'] = is_file($secretsFile) ? (require $secretsFile) : [];

function secret(string $key, mixed $default = ''): mixed
{
    return $GLOBALS['MPM_SECRETS'][$key] ?? $default;
}

// ── App ──────────────────────────────────────────────────────────
define('APP_NAME', 'Motor Parts Market');
define('APP_TAGLINE', 'Chat your way to the right car light');
define('BASE_DIR', dirname(__DIR__));

$host = $_SERVER['HTTP_HOST'] ?? '';
define('IS_LOCAL', $host === ''
    ? PHP_OS_FAMILY === 'Windows'
    : (bool) preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$|\.(localhost|test)(:\d+)?$/', $host));

$appUrl = (string) getenv('APP_URL');
if ($appUrl === '' && $host !== '') {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $basePath = '/motorpartsmarket';
    if ($documentRoot && realpath(BASE_DIR) !== false) {
        $relativePath = str_replace('\\', '/', substr(realpath(BASE_DIR), strlen($documentRoot)));
        $basePath = '/' . trim($relativePath, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $appUrl = $scheme . '://' . $host . ($basePath === '/' ? '' : $basePath);
}
if ($appUrl === '') {
    $appUrl = IS_LOCAL ? 'http://localhost/motorpartsmarket' : (string) secret('app_url', 'https://motorpartsmarket.co.za');
}
define('APP_URL', rtrim($appUrl, '/'));

define('ADMIN_EMAIL', strtolower((string) secret('admin_email', 'admin@swiftfusion.co.za')));
define('APP_SECRET', (string) secret('app_secret', 'change-me'));
define('CONTACT_EMAIL', (string) secret('contact_email', 'info@motorpartsmarket.co.za'));

// ── Database ─────────────────────────────────────────────────────
define('DB_HOST', (string) secret('db_host', 'localhost'));
define('DB_NAME', (string) secret(IS_LOCAL ? 'local_db_name' : 'db_name', 'motorpartsmarket'));
define('DB_USER', (string) secret(IS_LOCAL ? 'local_db_user' : 'db_user', 'root'));
define('DB_PASS', (string) secret(IS_LOCAL ? 'local_db_pass' : 'db_pass', ''));

// ── Uploads ──────────────────────────────────────────────────────
define('UPLOAD_DIR', BASE_DIR . '/uploads/products/');
define('MAX_UPLOAD', 40 * 1024 * 1024); // 40 MB (videos)

// ── Email / SMTP ─────────────────────────────────────────────────
define('SMTP_HOST', (string) secret('smtp_host', ''));
define('SMTP_PORT', (int) secret('smtp_port', 465));
define('SMTP_USER', (string) secret('smtp_user', ''));
define('SMTP_PASS', (string) secret('smtp_pass', ''));

date_default_timezone_set('Africa/Johannesburg');
if (!IS_LOCAL) {
    ini_set('display_errors', '0');
}
