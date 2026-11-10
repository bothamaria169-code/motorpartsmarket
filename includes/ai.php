<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Groq (OpenAI-compatible). The model is never fixed: the key's live model
 * list is read (cached a day), the best one in GROQ_PREFERRED is used, and a
 * retired model is swapped automatically.
 * Key: console.groq.com → API Keys, saved in Admin → Settings → AI.
 */
const GROQ_PREFERRED = [
    'openai/gpt-oss-120b', 'qwen/qwen3.6-27b', 'moonshotai/kimi-k2-instruct-0905', 'moonshotai/kimi-k2-instruct',
    'openai/gpt-oss-20b', 'meta-llama/llama-4-maverick-17b-128e-instruct', 'meta-llama/llama-4-scout-17b-16e-instruct',
    'qwen/qwen3-32b', 'llama-3.3-70b-versatile',
];

function aiKey(): string
{
    return trim(get_setting('groq_api_key', (string) secret('groq_api_key', '')));
}

function aiEnabled(): bool
{
    return aiKey() !== '' && get_setting('ai_enabled', '1') === '1';
}

function aiMonthlyLimit(): int
{
    return max(0, (int) get_setting('ai_monthly_limit', '3000'));
}

function aiUsedThisMonth(): int
{
    $s = getDB()->prepare('SELECT COUNT(*) FROM ai_requests WHERE created_at >= ?');
    $s->execute([date('Y-m-01 00:00:00')]);
    return (int) $s->fetchColumn();
}

function logAi(string $kind, bool $ok): void
{
    getDB()->prepare('INSERT INTO ai_requests (kind, ok) VALUES (?, ?)')->execute([$kind, $ok ? 1 : 0]);
}

function groqModels(bool $refresh = false): array
{
    $cached = json_decode(get_setting('groq_models_cache'), true) ?: [];
    if (!$refresh && !empty($cached['at']) && $cached['at'] > time() - 86400 && !empty($cached['ids'])) return $cached['ids'];
    if (aiKey() === '') return [];
    $ch = curl_init('https://api.groq.com/openai/v1/models');
    curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . aiKey()], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8]);
    $res = json_decode((string) curl_exec($ch), true) ?: [];
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return $cached['ids'] ?? [];
    $ids = [];
    foreach ($res['data'] ?? [] as $m) {
        $id = (string) ($m['id'] ?? '');
        if ($id === '' || (isset($m['active']) && !$m['active'])) continue;
        if (preg_match('/whisper|tts|playai|orpheus|guard|safeguard|prompt-guard|compound|distil/i', $id)) continue;
        $ids[] = $id;
    }
    sort($ids);
    set_setting('groq_models_cache', json_encode(['at' => time(), 'ids' => $ids]));
    return $ids;
}

function groqModel(bool $refresh = false): string
{
    $chosen = get_setting('groq_model');
    $ids = groqModels($refresh);
    if (!$ids) return $chosen ?: GROQ_PREFERRED[0];
    if ($chosen !== '' && in_array($chosen, $ids, true)) return $chosen;
    foreach (GROQ_PREFERRED as $m) if (in_array($m, $ids, true)) return $m;
    return $ids[0];
}

function jsonFromText(string $text): ?array
{
    $d = json_decode($text, true);
    if (is_array($d)) return $d;
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $d = json_decode($m[0], true);
        if (is_array($d)) return $d;
    }
    return null;
}

/** @return array{ok: bool, data: array, error: string} */
function groqJson(string $system, string $user, int $maxTokens = 500): array
{
    $model = groqModel();
    $r = groqCall($model, $system, $user, $maxTokens, true);
    if (!$r['ok'] && preg_match('/does not exist|do not have access|decommission|deprecat|model_not_found|not supported/i', $r['error'])) {
        $next = groqModel(true);
        if ($next === $model) {
            $ids = groqModels();
            foreach (GROQ_PREFERRED as $m) if ($m !== $model && (!$ids || in_array($m, $ids, true))) { $next = $m; break; }
        }
        if ($next !== $model) {
            set_setting('groq_model', $next);
            error_log("MotorPartsMarket: Groq model {$model} unavailable, switched to {$next}");
            $r = groqCall($next, $system, $user, $maxTokens, true);
        }
    }
    if (!$r['ok'] && preg_match('/json|response_format/i', $r['error'])) {
        $r = groqCall(groqModel(), $system, $user, $maxTokens, false);
    }
    return $r;
}

function groqCall(string $model, string $system, string $user, int $maxTokens, bool $jsonMode): array
{
    $reasoning = (bool) preg_match('#^openai/gpt-oss|qwen3#', $model);
    $body = [
        'model' => $model, 'temperature' => 0.2,
        'max_completion_tokens' => $reasoning ? $maxTokens + 2000 : $maxTokens,
        'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $user]],
    ];
    if ($jsonMode) $body['response_format'] = ['type' => 'json_object'];
    if (str_starts_with($model, 'openai/gpt-oss')) $body['reasoning_effort'] = 'low';
    if (str_starts_with($model, 'qwen/qwen3')) $body['reasoning_format'] = 'hidden';

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . aiKey(), 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 6,
    ]);
    $raw = (string) curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err !== '') return ['ok' => false, 'data' => [], 'error' => $err];
    $res = json_decode($raw, true) ?: [];
    if ($code !== 200) return ['ok' => false, 'data' => [], 'error' => (string) ($res['error']['message'] ?? 'HTTP ' . $code)];
    $data = jsonFromText((string) ($res['choices'][0]['message']['content'] ?? ''));
    return $data !== null ? ['ok' => true, 'data' => $data, 'error' => ''] : ['ok' => false, 'data' => [], 'error' => 'The AI answer was not readable.'];
}

/** "✨ Write with AI" in Admin → Products: a short honest description. */
function aiProductDescription(array $p): array
{
    if (!aiEnabled()) return ['ok' => false, 'error' => 'Add a Groq key in Admin → Settings → AI first.'];
    $system = 'You write short, honest product descriptions for a South African car-parts shop that sells vehicle lights. '
            . 'Answer ONLY with JSON {"description":""}. 40–70 words, South African English, plain text. '
            . 'Use only the facts given. Never invent part numbers, prices, warranties, brands or condition. '
            . 'End with: "Send us your vehicle year to confirm fitment."';
    $facts = "Title: {$p['title']}\nVehicle: {$p['make']} {$p['model']} " . years($p['year_from'] ? (int) $p['year_from'] : null, $p['year_to'] ? (int) $p['year_to'] : null)
           . "\nType: " . (CATEGORIES[$p['category']] ?? $p['category']) . "\nSide: " . (SIDES[$p['side']] ?? $p['side'])
           . "\nCondition: " . (GRADES[(string) $p['grade']] ?? 'to confirm') . "\nKnown defects: " . ($p['defects'] ?: 'none listed');
    $r = groqJson($system, $facts, 400);
    $d = trim((string) ($r['data']['description'] ?? ''));
    logAi('product_text', $r['ok'] && $d !== '');
    return $d !== '' ? ['ok' => true, 'description' => $d] : ['ok' => false, 'error' => 'AI could not write it just now. ' . $r['error']];
}
