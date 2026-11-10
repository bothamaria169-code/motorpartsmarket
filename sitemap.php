<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/catalog.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
$url = fn(string $loc, string $mod = '') => '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . ($mod ? '<lastmod>' . $mod . '</lastmod>' : '') . "</url>\n";
echo $url(APP_URL . '/', date('Y-m-d'));
foreach (getDB()->query("SELECT slug, updated_at FROM products WHERE stock_status IN ('available','reserved') ORDER BY id") as $p) {
    echo $url(APP_URL . '/part.php?s=' . rawurlencode($p['slug']), date('Y-m-d', strtotime($p['updated_at'])));
}
echo $url(APP_URL . '/privacy.php') . $url(APP_URL . '/terms.php');
echo '</urlset>';
