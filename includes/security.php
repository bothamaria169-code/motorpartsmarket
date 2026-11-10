<?php
declare(strict_types=1);

function csrfToken(): string
{
    startAppSession();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrfToken() . '">';
}

function csrfValid(): bool
{
    startAppSession();
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return $sent !== '' && hash_equals((string) ($_SESSION['csrf'] ?? ''), $sent);
}

function requireCsrf(): void
{
    if (!csrfValid()) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

function clientIp(): string
{
    return substr((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function tooManyAttempts(string $email): bool
{
    $s = getDB()->prepare('SELECT SUM(email = ?) AS by_email, COUNT(*) AS by_ip FROM login_attempts
                           WHERE success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE) AND (email = ? OR ip = ?)');
    $s->execute([$email, $email, clientIp()]);
    $r = $s->fetch() ?: [];
    return (int) ($r['by_email'] ?? 0) >= 6 || (int) ($r['by_ip'] ?? 0) >= 25;
}

function recordAttempt(string $email, bool $success): void
{
    getDB()->prepare('INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, ?)')->execute([substr($email, 0, 255), clientIp(), $success ? 1 : 0]);
}

/** Simple per-session rate limit for public endpoints (chat, enquiries). */
function rateLimit(string $bucket, int $max, int $seconds): bool
{
    startAppSession();
    $now = time();
    $hits = array_filter($_SESSION['rl'][$bucket] ?? [], fn($t) => $t > $now - $seconds);
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    $_SESSION['rl'][$bucket] = array_values($hits);
    return true;
}

function flash(string $type, string $message): void
{
    startAppSession();
    $_SESSION['flash'][] = [$type, $message];
}

function takeFlashes(): array
{
    startAppSession();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): never
{
    header('Location: ' . (str_starts_with($to, 'http') ? $to : APP_URL . '/' . ltrim($to, '/')));
    exit;
}
