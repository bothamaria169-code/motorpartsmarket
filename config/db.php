<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (IS_LOCAL && (int) ($e->errorInfo[1] ?? 0) === 1049) {
                // Local XAMPP: create the database on first run.
                $root = new PDO(sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $root->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                return getDB();
            }
            databaseDown($e);
        }
        require_once dirname(__DIR__) . '/database/migrate.php';
        runMigrations($pdo);
        adminResetFromSecrets($pdo);
    }
    return $pdo;
}

/**
 * Locked out of admin? Add 'admin_reset_password' => 'NewPassword123' to
 * config/secrets.php and load any page once. Each new value is applied once.
 */
function adminResetFromSecrets(PDO $db): void
{
    $pw = (string) secret('admin_reset_password', '');
    if ($pw === '' || $pw === 'CHANGE_ME') return;
    $mark = hash('sha256', $pw . '|' . implode(',', (array) secret('admin_reset_emails', [])));
    if ($db->query("SELECT v FROM app_settings WHERE k = 'admin_reset_applied'")->fetchColumn() === $mark) return;

    $hash = password_hash($pw, PASSWORD_DEFAULT);
    foreach (array_unique(array_merge([ADMIN_EMAIL], array_map('strtolower', (array) secret('admin_reset_emails', [])))) as $email) {
        $u = $db->prepare('SELECT id FROM users WHERE email = ?');
        $u->execute([$email]);
        if ($id = $u->fetchColumn()) {
            $db->prepare("UPDATE users SET password_hash = ?, role = 'admin', is_active = 1 WHERE id = ?")->execute([$hash, $id]);
        } else {
            $db->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, 'Administrator', ?, 'admin')")->execute([$email, $hash]);
        }
        $db->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);
    }
    $db->prepare("REPLACE INTO app_settings (k, v) VALUES ('admin_reset_applied', ?)")->execute([$mark]);
}

/** A clear page instead of a blank one when MySQL can't be reached. */
function databaseDown(PDOException $e): never
{
    error_log('MotorPartsMarket DB connection failed: ' . $e->getMessage());
    $code = (int) ($e->errorInfo[1] ?? 0);
    $hint = match (true) {
        !is_file(__DIR__ . '/secrets.php') => 'config/secrets.php is missing. Upload it to the config folder (copy config/secrets.example.php and fill it in).',
        $code === 1045 => 'MySQL refused the username or password (error 1045). Check db_user and db_pass in config/secrets.php. In cPanel the user must be added to the database with All Privileges.',
        $code === 1044 => 'The MySQL user has no access to this database (error 1044). cPanel → MySQL Databases → Add User To Database → All Privileges.',
        $code === 1049 => 'The database name in config/secrets.php does not exist (error 1049). Check db_name.',
        default        => 'MySQL could not be reached (error ' . ($code ?: 'unknown') . ').',
    };
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Database connection failed: {$hint}\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Temporarily unavailable</title>'
       . '<body style="font-family:system-ui,sans-serif;background:#f4f6fb;color:#0e1726;display:grid;place-items:center;min-height:100vh;margin:0;padding:1rem">'
       . '<div style="max-width:520px;background:#fff;border:1px solid #e4e8f1;border-radius:16px;padding:28px">'
       . '<h1 style="font-size:20px;margin:0 0 10px">We\'ll be right back</h1><p>Motor Parts Market can\'t reach its database right now. Please try again in a few minutes.</p>'
       . '<p style="font-size:13px;color:#4a5768;background:#f8f9fc;border-radius:10px;padding:12px;margin-top:18px"><strong>For the site owner:</strong> '
       . htmlspecialchars($hint, ENT_QUOTES) . '</p></div></body>';
    exit;
}
