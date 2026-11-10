<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function currentAdmin(): ?array
{
    startAppSession();
    if (empty($_SESSION['uid'])) return null;
    static $u = false;
    if ($u === false) {
        $s = getDB()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $s->execute([$_SESSION['uid']]);
        $u = $s->fetch() ?: null;
    }
    return $u;
}

function requireAdmin(): array
{
    $u = currentAdmin();
    if (!$u) redirect('login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
    return $u;
}

/** @return string error message, or '' on success */
function attemptLogin(string $email, string $password): string
{
    $email = strtolower(trim($email));
    if (tooManyAttempts($email)) return 'Too many tries. Wait 15 minutes and try again.';
    $s = getDB()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $s->execute([$email]);
    $u = $s->fetch();
    if (!$u || !$u['password_hash'] || !password_verify($password, $u['password_hash'])) {
        recordAttempt($email, false);
        return 'That email and password don\'t match.';
    }
    recordAttempt($email, true);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
    audit('login', $email);
    return '';
}
