<?php
declare(strict_types=1);

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/security.php';

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('mpm_sess');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure' => !IS_LOCAL && (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postText(string $key, int $len = 255): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $len);
}

/** URL of a file inside the site, with a cache-busting version for assets. */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_DIR . '/' . $path;
    return APP_URL . '/' . $path . (is_file($file) && str_starts_with($path, 'assets/') && preg_match('/\.(css|js)$/', $path) ? '?v=' . filemtime($file) : '');
}

const CATEGORIES = [
    'headlight' => 'Headlight',
    'taillight' => 'Taillight',
    'fog_light' => 'Fog light',
    'indicator' => 'Indicator / side marker',
    'third_brake' => 'Third brake light',
    'drl' => 'Daytime running light',
];

const SIDES = ['pair' => 'Left + right pair', 'left' => 'Left (passenger side)', 'right' => 'Right (driver side)', 'single' => 'Single unit — ask which side'];

const GRADES = ['' => 'Condition to confirm', 'new' => 'New', 'new_aftermarket' => 'New (aftermarket)', 'used_a' => 'Used — grade A (like new)', 'used_b' => 'Used — grade B (minor marks)', 'used_c' => 'Used — grade C (see defects)', 'refurbished' => 'Refurbished'];

const STOCK = ['available' => 'In stock', 'reserved' => 'Reserved', 'sold' => 'Sold', 'hidden' => 'Hidden'];

const ENQUIRY_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted', 'paid' => 'Paid', 'dispatched' => 'Dispatched', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

const PROVINCES = ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape'];

function money(?string $price): string
{
    return $price === null || (float) $price <= 0 ? 'Ask for price' : 'R ' . number_format((float) $price, 2, '.', ' ');
}

function years(?int $from, ?int $to): string
{
    if (!$from && !$to) return 'All years';
    if ($from && $to && $from !== $to) return $from . '–' . $to;
    if ($from && !$to) return $from . '+';
    return (string) ($from ?: $to);
}

/** WhatsApp click-to-chat link (empty when no number is set). */
function whatsappLink(string $text = ''): string
{
    $n = preg_replace('/\D/', '', get_setting('whatsapp_number'));
    if ($n === '') return '';
    if (str_starts_with($n, '0')) $n = '27' . substr($n, 1);
    return 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function audit(string $action, string $detail = ''): void
{
    try {
        getDB()->prepare('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (?, ?, ?, ?)')
            ->execute([$_SESSION['uid'] ?? null, $action, mb_substr($detail, 0, 500), clientIp()]);
    } catch (Throwable) {
    }
}

/**
 * Sends one plain-text email. SMTP comes from Admin → Settings → Email,
 * falling back to secrets.php. Never throws: many hosts switch PHP's mail()
 * off, so a failure comes back as a message instead of a 500 page.
 *
 * @return string '' when sent, otherwise the reason it failed
 */
function sendMail(string $to, string $subject, string $body, ?string $replyTo = null): string
{
    require_once __DIR__ . '/lib/PHPMailer/Exception.php';
    require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

    $host = get_setting('smtp_host', SMTP_HOST);
    $port = (int) get_setting('smtp_port', (string) SMTP_PORT) ?: 465;
    $user = get_setting('smtp_user', SMTP_USER);
    $pass = get_setting('smtp_pass', SMTP_PASS);

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        if ($host !== '' && $user !== '' && $pass !== '') {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SMTPSecure = $port === 465 ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Timeout = 20;
        } elseif (function_exists('mail')) {
            $mail->isMail();
        } else {
            return 'This server does not allow PHP mail(). Fill in the mailbox details under Admin → Settings → Email.';
        }
        $mail->setFrom($user !== '' ? $user : get_setting('contact_email', CONTACT_EMAIL), APP_NAME);
        $mail->addAddress($to);
        if ($replyTo) $mail->addReplyTo($replyTo);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return '';
    } catch (Throwable $e) {
        error_log('MotorPartsMarket mail to ' . $to . ' failed: ' . $e->getMessage());
        return $e->getMessage();
    }
}
