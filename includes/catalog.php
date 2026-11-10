<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Products the public can see (in stock or reserved), with optional filters.
 * Filters: make, model, year, category, side, q (free text).
 */
function findProducts(array $f = [], int $limit = 60): array
{
    $where = ["p.stock_status IN ('available','reserved')"];
    $args = [];
    if (!empty($f['make']))     { $where[] = 'p.make = ?'; $args[] = $f['make']; }
    if (!empty($f['model']))    { $where[] = 'p.model = ?'; $args[] = $f['model']; }
    if (!empty($f['category'])) { $where[] = 'p.category = ?'; $args[] = $f['category']; }
    if (!empty($f['side']) && in_array($f['side'], ['left', 'right'], true)) {
        $where[] = "p.side IN (?, 'pair', 'single')"; $args[] = $f['side'];
    }
    if (!empty($f['year'])) {
        $where[] = '(p.year_from IS NULL OR p.year_from <= ?) AND (p.year_to IS NULL OR p.year_to >= ?)';
        $args[] = (int) $f['year']; $args[] = (int) $f['year'];
    }
    if (!empty($f['q'])) {
        foreach (array_slice(preg_split('/\s+/', trim((string) $f['q'])) ?: [], 0, 5) as $w) {
            $where[] = "CONCAT_WS(' ', p.title, p.make, p.model, p.aliases, p.part_number, p.category) LIKE ?";
            $args[] = '%' . $w . '%';
        }
    }
    $sql = 'SELECT p.* FROM products p WHERE ' . implode(' AND ', $where)
         . " ORDER BY p.make, p.model, FIELD(p.category, 'headlight', 'taillight'), p.id LIMIT " . max(1, min(200, $limit));
    $s = getDB()->prepare($sql);
    $s->execute($args);
    return withMedia($s->fetchAll());
}

function productBySlug(string $slug): ?array
{
    $s = getDB()->prepare("SELECT * FROM products WHERE slug = ? AND stock_status <> 'hidden'");
    $s->execute([$slug]);
    $p = $s->fetch();
    return $p ? withMedia([$p])[0] : null;
}

function productById(int $id): ?array
{
    $s = getDB()->prepare('SELECT * FROM products WHERE id = ?');
    $s->execute([$id]);
    $p = $s->fetch();
    return $p ? withMedia([$p])[0] : null;
}

function withMedia(array $rows): array
{
    if (!$rows) return [];
    $ids = array_column($rows, 'id');
    $s = getDB()->prepare('SELECT * FROM product_media WHERE product_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY sort_order, id');
    $s->execute($ids);
    $by = [];
    foreach ($s->fetchAll() as $m) $by[$m['product_id']][] = $m;
    foreach ($rows as &$r) $r['media'] = $by[$r['id']] ?? [];
    return $rows;
}

/** Small version of an image if one exists (…-sm.webp), else the image itself. */
function thumbOf(string $path): string
{
    $sm = preg_replace('/\.(webp|jpe?g|png)$/i', '-sm.webp', $path);
    return is_file(BASE_DIR . '/' . $sm) ? $sm : $path;
}

/** The first picture of a product: a photo, else a video poster. */
function coverOf(array $p): ?string
{
    foreach ($p['media'] as $m) if ($m['media_kind'] === 'image') return $m['path'];
    foreach ($p['media'] as $m) if ($m['poster']) return $m['poster'];
    return null;
}

/** Safe public shape of a product for JSON (no supplier data). */
function productPublic(array $p): array
{
    $cover = coverOf($p);
    return [
        'id'       => (int) $p['id'],
        'slug'     => $p['slug'],
        'url'      => APP_URL . '/part.php?s=' . rawurlencode($p['slug']),
        'title'    => $p['title'],
        'make'     => $p['make'],
        'model'    => $p['model'],
        'years'    => years($p['year_from'] ? (int) $p['year_from'] : null, $p['year_to'] ? (int) $p['year_to'] : null),
        'category' => $p['category'],
        'categoryLabel' => CATEGORIES[$p['category']] ?? ucfirst($p['category']),
        'side'     => SIDES[$p['side']] ?? $p['side'],
        'grade'    => GRADES[(string) $p['grade']] ?? 'Condition to confirm',
        'defects'  => (string) $p['defects'],
        'description' => (string) $p['description'],
        'partNumber'  => (string) $p['part_number'],
        'price'    => money($p['price']),
        'hasPrice' => $p['price'] !== null && (float) $p['price'] > 0,
        'stock'    => STOCK[$p['stock_status']] ?? $p['stock_status'],
        'reserved' => $p['stock_status'] === 'reserved',
        'checked'  => $p['stock_checked_at'] ? date('j M Y', strtotime($p['stock_checked_at'])) : '',
        'thumb'    => $cover ? APP_URL . '/' . thumbOf($cover) : APP_URL . '/assets/img/placeholder.svg',
        'images'   => array_values(array_map(fn($m) => ['src' => APP_URL . '/' . $m['path'], 'thumb' => APP_URL . '/' . thumbOf($m['path'])],
                        array_filter($p['media'], fn($m) => $m['media_kind'] === 'image'))),
        'videos'   => array_values(array_map(fn($m) => ['src' => APP_URL . '/' . $m['path'], 'poster' => $m['poster'] ? APP_URL . '/' . thumbOf($m['poster']) : ''],
                        array_filter($p['media'], fn($m) => $m['media_kind'] === 'video'))),
    ];
}

/**
 * Every vehicle we stock: make → models with year ranges, light types and counts.
 * @return array<string, array<int, array{model:string, years:string, from:?int, to:?int, count:int, categories:array}>>
 */
function vehiclesInStock(): array
{
    $rows = getDB()->query("SELECT make, model, MIN(year_from) AS yf, MAX(year_to) AS yt, COUNT(*) AS n, GROUP_CONCAT(DISTINCT category ORDER BY category) AS cats
                            FROM products WHERE stock_status IN ('available','reserved')
                            GROUP BY make, model ORDER BY make, model")->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['make']][] = [
            'model' => $r['model'],
            'from'  => $r['yf'] ? (int) $r['yf'] : null,
            'to'    => $r['yt'] ? (int) $r['yt'] : null,
            'years' => years($r['yf'] ? (int) $r['yf'] : null, $r['yt'] ? (int) $r['yt'] : null),
            'count' => (int) $r['n'],
            'categories' => array_map(fn($c) => CATEGORIES[$c] ?? $c, explode(',', (string) $r['cats'])),
        ];
    }
    return $out;
}
