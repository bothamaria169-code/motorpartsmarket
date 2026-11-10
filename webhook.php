<?php
declare(strict_types=1);

/**
 * WhatsApp Cloud API webhook: the same parts assistant as the website.
 * Setup: Admin → Settings → WhatsApp bot. Callback URL = this file.
 */
require_once __DIR__ . '/includes/chat.php';

// Meta's one-time handshake.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $verify = get_setting('wa_verify_token');
    if ($verify !== '' && ($_GET['hub_mode'] ?? '') === 'subscribe' && hash_equals($verify, (string) ($_GET['hub_verify_token'] ?? ''))) {
        header('Content-Type: text/plain');
        exit((string) ($_GET['hub_challenge'] ?? ''));
    }
    http_response_code(403);
    exit('Forbidden');
}

// Answer Meta straight away, then do the work.
$raw = (string) file_get_contents('php://input');
http_response_code(200);
echo 'OK';
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

$phoneId = get_setting('wa_phone_id');
$token = get_setting('wa_token');
if ($phoneId === '' || $token === '') exit;

$data = json_decode($raw, true) ?: [];
foreach ($data['entry'] ?? [] as $entry) {
    foreach ($entry['changes'] ?? [] as $change) {
        foreach ($change['value']['messages'] ?? [] as $msg) {
            $from = preg_replace('/\D/', '', (string) ($msg['from'] ?? ''));
            $text = trim((string) ($msg['text']['body'] ?? $msg['button']['text'] ?? $msg['interactive']['button_reply']['title'] ?? ''));
            if ($from === '' || $text === '') continue;

            $db = getDB();
            $sessionToken = md5('wa:' . $from);
            $s = $db->prepare('SELECT vehicle_make AS make, vehicle_model AS model, vehicle_year AS year, category, side FROM chat_sessions WHERE token = ? AND updated_at > NOW() - INTERVAL 1 DAY');
            $s->execute([$sessionToken]);
            $state = array_filter($s->fetch() ?: []);
            if (isset($state['year'])) $state['year'] = (int) $state['year'];

            $res = chatReply($text, $state);
            logChat('whatsapp', $sessionToken, $state, $text, $res);

            $body = $res['reply'];
            foreach (array_slice($res['products'], 0, 5) as $p) {
                $body .= "\n\n• {$p['title']} ({$p['years']}) — {$p['price']}\n{$p['url']}";
            }
            if (count($res['products']) > 5) $body .= "\n\nSee all: " . APP_URL . '/';
            if ($res['action']) $body .= "\n\nReply with your name and area and a person will help you.";

            $ch = curl_init("https://graph.facebook.com/v21.0/{$phoneId}/messages");
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode(['messaging_product' => 'whatsapp', 'to' => $from, 'type' => 'text', 'text' => ['preview_url' => true, 'body' => mb_substr($body, 0, 4000)]]),
            ]);
            $out = (string) curl_exec($ch);
            if ((int) curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 300) error_log('WhatsApp send failed: ' . $out);
            curl_close($ch);
        }
    }
}
