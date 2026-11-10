<?php
declare(strict_types=1);

require __DIR__ . '/_boot.php';

if (!rateLimit('chat', 30, 60)) jsonResponse(['ok' => false, 'error' => 'You\'re going very fast — give it a few seconds and try again.'], 429);

$text = postText('message', 500);
if ($text === '') jsonResponse(['ok' => false, 'error' => 'Type a message first.'], 422);
$state = json_decode((string) ($_POST['state'] ?? '{}'), true);
$state = is_array($state) ? array_map(fn($v) => is_scalar($v) ? mb_substr((string) $v, 0, 80) : null, $state) : [];
if (isset($state['year'])) $state['year'] = (int) $state['year'];

$res = chatReply($text, array_filter($state));

if (empty($_SESSION['chat_token'])) $_SESSION['chat_token'] = bin2hex(random_bytes(16));
logChat('web', $_SESSION['chat_token'], $state, $text, $res);

jsonResponse(['ok' => true] + $res);
