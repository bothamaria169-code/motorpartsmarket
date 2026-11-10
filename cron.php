<?php
declare(strict_types=1);

/**
 * Housekeeping, once a day from cPanel → Cron Jobs:
 *   /usr/local/bin/php /home/CPANELUSER/public_html/cron.php >/dev/null 2>&1
 * or by URL: cron.php?key=<cron_key from config/secrets.php>
 */
require_once __DIR__ . '/includes/functions.php';

if (PHP_SAPI !== 'cli') {
    $key = (string) secret('cron_key', '');
    if ($key === '' || !hash_equals($key, (string) ($_GET['key'] ?? ''))) {
        http_response_code(403);
        exit('Forbidden');
    }
    header('Content-Type: text/plain');
}

$db = getDB();
$done = [
    'old sign-in attempts' => $db->exec('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 30 DAY'),
    'chats older than 12 months' => $db->exec('DELETE FROM chat_sessions WHERE updated_at < NOW() - INTERVAL 12 MONTH'),
    'AI log older than 6 months' => $db->exec('DELETE FROM ai_requests WHERE created_at < NOW() - INTERVAL 6 MONTH'),
];
set_setting('cron_last_run', date('Y-m-d H:i:s'));
foreach ($done as $what => $n) echo "Removed {$n} {$what}\n";
echo "Done " . date('Y-m-d H:i') . "\n";
