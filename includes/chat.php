<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/ai.php';

/**
 * The shopping chat. Rules read the vehicle, year, light type and side from
 * what the customer types; Groq (when switched on) helps with messy messages
 * ("my bakkie's back light on the driver side is cracked"). Products, prices
 * and stock ALWAYS come from the database — the AI never writes them.
 *
 * State: make, model, year, category, side. Shared by the website chat and
 * the WhatsApp/Messenger webhook.
 *
 * @return array{reply:string, state:array, products:array, quick:array, action:string}
 */
function chatReply(string $text, array $state): array
{
    $text = trim(mb_substr($text, 0, 500));
    $state = array_intersect_key($state, array_flip(['make', 'model', 'year', 'category', 'side']));
    $l = mb_strtolower($text);
    $vehicles = vehiclesInStock();

    // ── Intents ─────────────────────────────────────────────
    if (preg_match('/^(start over|new chat|reset|restart|clear)\b/', $l)) {
        return chatWelcome($vehicles, 'Fresh start. Which vehicle is the light for?');
    }
    if (preg_match('/\b(person|human|agent|someone|call me|phone me|speak to|talk to|consultant|help me order|order|buy|checkout|whatsapp)\b/', $l)
        && !preg_match('/\b(head|tail|light|lamp)\b/', $l)) {
        return [
            'reply' => "No problem — a real person will help you. Leave your name and number and we'll confirm the part, price and delivery with you." . (whatsappLink() ? ' Or tap WhatsApp to chat to us now.' : ''),
            'state' => $state, 'products' => [], 'quick' => ['Show all parts', 'Start over'], 'action' => 'enquire',
        ];
    }
    if (preg_match('/\b(show (me )?(all|everything)|all parts|everything|catalogue|catalog|what do you (have|sell)|stock list|browse)\b/', $l)) {
        $all = findProducts([], 200);
        return [
            'reply' => 'Here is everything in stock right now — ' . count($all) . ' light' . (count($all) === 1 ? '' : 's') . '. Tell me your vehicle and I\'ll narrow it down.',
            'state' => [], 'products' => array_map('productPublic', $all), 'quick' => array_slice(array_keys($vehicles), 0, 8), 'action' => '',
        ];
    }
    if (preg_match('/\b(deliver|delivery|courier|shipping|ship)\b/', $l)) {
        return ['reply' => 'We deliver across South Africa by courier. The delivery price depends on your area and the size of the box, so we confirm it with you before you pay. Leave your details and we\'ll send a quote.',
                'state' => $state, 'products' => [], 'quick' => ['Talk to a person', 'Show all parts'], 'action' => 'enquire'];
    }
    if (preg_match('/\b(return|refund|warranty|guarantee)\b/', $l)) {
        return ['reply' => 'Every part is checked and photographed before it ships. If a part arrives damaged or doesn\'t fit the vehicle you gave us, tell us within 7 days and we\'ll sort it out. See our terms for the details.',
                'state' => $state, 'products' => [], 'quick' => ['Talk to a person', 'Show all parts'], 'action' => ''];
    }

    if (preg_match('/\b(how much|price|prices|cost|costs|hoeveel)\b/', $l) && !array_filter(parseVehicleText($l, $vehicles))) {
        $r = $state ? chatSearch($state, $vehicles) : chatWelcome($vehicles, '');
        $r['reply'] = 'The price is on each card. Where it says "Ask for price", tap Ask / Order and we\'ll send you the price and delivery cost — no obligation.' . ($state ? '' : ' Which vehicle is it for?');
        return $r;
    }

    // ── Read the message ────────────────────────────────────
    $found = parseVehicleText($l, $vehicles);
    if (aiEnabled() && aiUsedThisMonth() < aiMonthlyLimit() && needsAi($text, $found)) {
        $found = array_merge(aiParse($text, $state, $vehicles), array_filter($found));
    }

    if (!empty($found['make']) && ($state['make'] ?? '') !== $found['make']) {
        unset($state['model']); // new make: forget the old model
    }
    foreach (['make', 'model', 'year', 'category', 'side'] as $k) {
        if (!empty($found[$k])) $state[$k] = $found[$k];
    }
    if (!empty($state['model']) && empty($state['make'])) {
        foreach ($vehicles as $mk => $list) foreach ($list as $v) if ($v['model'] === $state['model']) $state['make'] = $mk;
    }

    $nothingNew = !array_filter($found);
    if ($nothingNew && !$state) {
        if (preg_match('/^(hi|hello|hey|howzit|sawubona|molo|good (morning|afternoon|evening))\b/', $l)) {
            return chatWelcome($vehicles, 'Hi! 👋 I\'ll help you find the right light. Which vehicle is it for?');
        }
        return chatWelcome($vehicles, "I couldn't spot a vehicle in that. Tell me the make, model and year — for example \"Hilux 2018 tail light\" — or pick one below.");
    }

    return chatSearch($state, $vehicles);
}

function chatWelcome(array $vehicles, string $msg): array
{
    $quick = [];
    foreach ($vehicles as $mk => $list) foreach ($list as $v) $quick[] = "{$mk} {$v['model']}";
    return ['reply' => $msg, 'state' => [], 'products' => [], 'quick' => array_merge(array_slice($quick, 0, 10), ['Show all parts']), 'action' => ''];
}

/** Search with the current state and phrase an honest answer. */
function chatSearch(array $state, array $vehicles): array
{
    $vehicleName = trim(($state['make'] ?? '') . ' ' . ($state['model'] ?? ''));
    $cat = isset($state['category']) ? strtolower(CATEGORIES[$state['category']] ?? $state['category']) . 's' : 'lights';
    $yearTxt = !empty($state['year']) ? " ({$state['year']})" : '';

    // Make known, model not: list that make's models with years.
    if (!empty($state['make']) && empty($state['model'])) {
        $list = $vehicles[$state['make']] ?? [];
        if (!$list) return chatNoMatch($state, $vehicles, "We don't stock {$state['make']} lights at the moment.");
        $products = findProducts(['make' => $state['make'], 'category' => $state['category'] ?? null]);
        $names = array_map(fn($v) => "{$v['model']} ({$v['years']})", $list);
        return [
            'reply' => "For {$state['make']} we have lights for: " . implode(', ', $names) . '. Which model is yours?',
            'state' => $state, 'products' => array_map('productPublic', $products),
            'quick' => array_map(fn($v) => "{$state['make']} {$v['model']}", $list), 'action' => '',
        ];
    }

    $filters = $state;
    $products = findProducts($filters);

    // Year outside the range we stock: show the model anyway and say so.
    $yearNote = '';
    if (!$products && !empty($state['year'])) {
        $noYear = $filters; unset($noYear['year']);
        $products = findProducts($noYear);
        if ($products) {
            $ranges = array_unique(array_map(fn($p) => years($p['year_from'] ? (int) $p['year_from'] : null, $p['year_to'] ? (int) $p['year_to'] : null), $products));
            $yearNote = "We list these for " . implode(' / ', $ranges) . ", not {$state['year']} — shapes change between years, so please check the photos or ask us before ordering. ";
        }
    }
    // Light type we don't have for this vehicle: show what we do have.
    $catNote = '';
    if (!$products && !empty($state['category']) && $vehicleName !== '') {
        $noCat = $filters; unset($noCat['category'], $noCat['year']);
        $products = findProducts($noCat);
        if ($products) $catNote = "We don't have {$cat} for the {$vehicleName} in stock right now, but we do have these. ";
    }
    if (!$products) return chatNoMatch($state, $vehicles);

    $n = count($products);
    $what = $vehicleName !== '' ? "{$vehicleName}{$yearTxt}" : ucfirst($cat);
    $reply = $yearNote . $catNote . ($catNote === '' ? "Found {$n} match" . ($n === 1 ? '' : 'es') . " for {$what}" . ($vehicleName !== '' && isset($state['category']) ? " {$cat}" : '') . '.' : '');
    if (!empty($state['side']) && in_array($state['side'], ['left', 'right'], true)) {
        $reply .= ' Sets marked "pair" include the ' . $state['side'] . ' side — ask us if you only need one side.';
    }
    $reply .= ' Tap a part to see every photo and video, or ask us about it.';

    // Next useful questions.
    $quick = [];
    if (empty($state['category'])) {
        $cats = array_unique(array_column($products, 'category'));
        if (count($cats) > 1) foreach ($cats as $c) $quick[] = (CATEGORIES[$c] ?? ucfirst($c)) . 's';
    } elseif ($vehicleName !== '') {
        foreach ($vehicles[$state['make']] ?? [] as $v) {
            if ($v['model'] === ($state['model'] ?? '')) foreach ($v['categories'] as $c) if (strtolower($c) !== strtolower(CATEGORIES[$state['category']] ?? '')) $quick[] = $c . 's';
        }
    }
    if (empty($state['year']) && $vehicleName !== '') $quick[] = 'What year?';
    if (empty($state['side'])) array_push($quick, 'Left side', 'Right side');
    array_push($quick, 'Talk to a person', 'Start over');

    return ['reply' => trim($reply), 'state' => $state, 'products' => array_map('productPublic', $products), 'quick' => array_values(array_unique($quick)), 'action' => ''];
}

function chatNoMatch(array $state, array $vehicles, string $lead = ''): array
{
    $want = trim(($state['make'] ?? '') . ' ' . ($state['model'] ?? '') . ' ' . ($state['year'] ?? '') . ' ' . (isset($state['category']) ? CATEGORIES[$state['category']] ?? '' : ''));
    $close = !empty($state['category']) ? findProducts(['category' => $state['category']], 12) : [];
    return [
        'reply' => ($lead ?: "We don't have " . ($want !== '' ? "a {$want}" : 'that') . ' in stock right now.')
                 . " We won't guess — leave your number and we'll ask our suppliers and come back to you."
                 . ($close ? ' Meanwhile, here are the ' . strtolower(CATEGORIES[$state['category']]) . 's we do have.' : ''),
        'state' => $state, 'products' => array_map('productPublic', $close),
        'quick' => ['Request this part', 'Show all parts', 'Start over'], 'action' => 'request',
    ];
}

/** Rule-based reading of make / model / year / light type / side. */
function parseVehicleText(string $l, array $vehicles): array
{
    $out = ['make' => null, 'model' => null, 'year' => null, 'category' => null, 'side' => null];
    $l = ' ' . preg_replace('/[^a-z0-9\- ]+/', ' ', $l) . ' ';

    $aliases = [];
    foreach (getDB()->query("SELECT DISTINCT make, model, aliases FROM products WHERE stock_status IN ('available','reserved')") as $r) {
        $names = array_filter(array_map('trim', explode(',', strtolower((string) $r['aliases']))));
        $names[] = strtolower($r['model']);
        foreach ($names as $n) $aliases[$n] = [$r['make'], $r['model']];
    }
    uksort($aliases, fn($a, $b) => strlen($b) <=> strlen($a)); // longest first
    foreach ($aliases as $alias => [$mk, $md]) {
        if ($alias !== '' && preg_match('/(?<![a-z0-9])' . preg_quote($alias, '/') . '(?![a-z0-9])/', $l)) { $out['make'] = $mk; $out['model'] = $md; break; }
    }
    $makeAliases = ['vw' => 'Volkswagen', 'volkswagen' => 'Volkswagen', 'merc' => 'Mercedes-Benz', 'mercedes' => 'Mercedes-Benz', 'benz' => 'Mercedes-Benz', 'chev' => 'Chevrolet', 'beemer' => 'BMW'];
    if (!$out['make']) {
        foreach (array_keys($vehicles) as $mk) if (str_contains($l, ' ' . strtolower($mk) . ' ')) $out['make'] = $mk;
        foreach ($makeAliases + array_combine(array_map('strtolower', ['Toyota', 'Kia', 'Hyundai', 'Suzuki', 'Audi', 'Ford', 'Nissan', 'Honda', 'Mazda', 'BMW', 'Renault', 'Isuzu', 'Mahindra', 'Haval', 'Chery', 'Datsun', 'Opel', 'Chevrolet', 'Peugeot', 'Mitsubishi']), ['Toyota', 'Kia', 'Hyundai', 'Suzuki', 'Audi', 'Ford', 'Nissan', 'Honda', 'Mazda', 'BMW', 'Renault', 'Isuzu', 'Mahindra', 'Haval', 'Chery', 'Datsun', 'Opel', 'Chevrolet', 'Peugeot', 'Mitsubishi']) as $a => $mk) {
            if (!$out['make'] && str_contains($l, " {$a} ")) $out['make'] = $mk;
        }
    }
    if (preg_match('/\b(19[89]\d|20[0-3]\d)\b/', $l, $m)) $out['year'] = (int) $m[1];
    elseif (preg_match("/\b(?:'|model |year )(\d{2})\b/", $l, $m)) $out['year'] = 2000 + (int) $m[1];

    if (preg_match('/\b(head ?lights?|head ?lamps?|front lights?|front lamps?|koplig|headlite|hid|xenon)\b/', $l)) $out['category'] = 'headlight';
    elseif (preg_match('/\b(tail ?lights?|tail ?lamps?|rear lights?|back lights?|brake lights?|stop ?lights?|agterlig|taillite)\b/', $l)) $out['category'] = 'taillight';
    elseif (preg_match('/\bfog\b/', $l)) $out['category'] = 'fog_light';
    elseif (preg_match('/\b(indicator|signal|side marker)\b/', $l)) $out['category'] = 'indicator';
    elseif (preg_match('/\b(head|front)\b/', $l)) $out['category'] = 'headlight';
    elseif (preg_match('/\b(tail|rear|back)\b/', $l)) $out['category'] = 'taillight';

    // SA cars are right-hand drive: driver = right, passenger = left.
    if (preg_match('/\b(left|lh|l\/h|passenger|nearside)\b/', $l)) $out['side'] = 'left';
    elseif (preg_match('/\b(right|rh|r\/h|driver|drivers|offside)\b/', $l)) $out['side'] = 'right';
    elseif (preg_match('/\b(both|pair|set|left and right)\b/', $l)) $out['side'] = 'pair';
    return $out;
}

/** AI only when the rules found nothing useful in a longer message. */
function needsAi(string $text, array $found): bool
{
    return str_word_count($text) >= 4 && empty($found['model']) && empty($found['make']);
}

function aiParse(string $text, array $state, array $vehicles): array
{
    $list = [];
    foreach ($vehicles as $mk => $vs) foreach ($vs as $v) $list[] = "{$mk} | {$v['model']} | {$v['years']}";
    $system = "You read customer messages for a South African car-light shop and pull out search fields. Answer ONLY with JSON:\n"
            . '{"make":null,"model":null,"year":null,"category":null,"side":null}' . "\n"
            . "make/model: copy EXACTLY from this stock list, or null if the vehicle isn't in it (put the make alone if only the make matches):\n"
            . implode("\n", $list) . "\n"
            . 'category: "headlight", "taillight", "fog_light", "indicator" or null. side: "left", "right", "pair" or null (South Africa is right-hand drive: driver side = right). year: 4-digit number or null. Never guess.';
    $r = groqJson($system, "Already known: " . json_encode($state) . "\nCustomer: " . $text, 200);
    logAi('chat_parse', $r['ok']);
    if (!$r['ok']) return [];
    $d = $r['data'];
    $out = [];
    foreach ($vehicles as $mk => $vs) {
        if (strcasecmp((string) ($d['make'] ?? ''), $mk) === 0) {
            $out['make'] = $mk;
            foreach ($vs as $v) if (strcasecmp((string) ($d['model'] ?? ''), $v['model']) === 0) $out['model'] = $v['model'];
        }
    }
    if (isset($d['year']) && (int) $d['year'] >= 1980 && (int) $d['year'] <= (int) date('Y') + 1) $out['year'] = (int) $d['year'];
    if (isset(CATEGORIES[(string) ($d['category'] ?? '')])) $out['category'] = (string) $d['category'];
    if (in_array($d['side'] ?? null, ['left', 'right', 'pair'], true)) $out['side'] = $d['side'];
    return $out;
}

/** Saves the exchange so Admin → Chats shows what people ask for. */
function logChat(string $channel, string $token, array $state, string $userText, array $res): void
{
    try {
        $db = getDB();
        $s = $db->prepare('SELECT id FROM chat_sessions WHERE token = ?');
        $s->execute([$token]);
        $sid = (int) $s->fetchColumn();
        if (!$sid) {
            $db->prepare('INSERT INTO chat_sessions (token, channel, ip) VALUES (?, ?, ?)')->execute([$token, $channel, clientIp()]);
            $sid = (int) $db->lastInsertId();
        }
        $st = $res['state'];
        $db->prepare('UPDATE chat_sessions SET vehicle_make = ?, vehicle_model = ?, vehicle_year = ?, category = ?, side = ?, messages = messages + 2 WHERE id = ?')
           ->execute([$st['make'] ?? null, $st['model'] ?? null, $st['year'] ?? null, $st['category'] ?? null, $st['side'] ?? null, $sid]);
        $ins = $db->prepare('INSERT INTO chat_messages (session_id, sender, body, product_ids) VALUES (?, ?, ?, ?)');
        $ins->execute([$sid, 'user', $userText, null]);
        $ins->execute([$sid, 'bot', $res['reply'], implode(',', array_slice(array_column($res['products'], 'id'), 0, 40))]);
    } catch (Throwable $e) {
        error_log('chat log failed: ' . $e->getMessage());
    }
}
