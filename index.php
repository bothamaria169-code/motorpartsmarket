<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$vehicles = vehiclesInStock();
$products = array_map('productPublic', findProducts([], 200));
$byId = [];
foreach ($products as $p) $byId[$p['id']] = $p;
$count = count($products);
$wa = whatsappLink('Hi, I am looking for a car light.');

$modelNames = [];
foreach ($vehicles as $mk => $list) foreach ($list as $v) $modelNames[] = "{$mk} {$v['model']}";

$schema = [[
    '@context' => 'https://schema.org', '@type' => 'AutoPartsStore', 'name' => APP_NAME, 'url' => APP_URL . '/',
    'description' => 'Headlights and taillights for popular vehicles in South Africa. Chat to find the right light for your car.',
    'areaServed' => 'ZA', 'email' => get_setting('contact_email', CONTACT_EMAIL),
], [
    '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Vehicle lights in stock',
    'itemListElement' => array_map(fn($p, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $p['url'], 'name' => $p['title'] . ' ' . $p['years']], $products, array_keys($products)),
]];

pageHead(
    APP_NAME . ' — Headlights & taillights for your car | South Africa',
    'Chat your way to the right car light. Headlights and taillights for ' . implode(', ', array_slice($modelNames, 0, 6)) . ' and more — real photos, delivered across South Africa.',
    ['schema' => $schema, 'bodyClass' => 'app']
);
?>
<div class="shell" id="shell">
  <aside class="side glass" id="side" aria-label="Vehicles in stock">
    <div class="side-head">
      <a class="brand" href="<?= e(APP_URL) ?>/"><?= brandMark() ?><span><?= e(APP_NAME) ?></span></a>
      <button class="icon-btn side-close" data-side-close aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <button class="btn btn-primary btn-block" data-new-chat><i class="fa-solid fa-plus"></i> New chat</button>

    <div class="side-scroll">
      <h2>Vehicles in stock</h2>
      <?php foreach ($vehicles as $make => $list): $n = array_sum(array_column($list, 'count')); ?>
        <div class="make">
          <div class="make-name"><?= e($make) ?> <span><?= $n ?> part<?= $n === 1 ? '' : 's' ?></span></div>
          <?php foreach ($list as $v): ?>
            <button class="veh-btn" data-say="<?= e("{$make} {$v['model']}") ?>">
              <span><b><?= e($v['model']) ?></b><br><small><?= e(implode(' · ', array_map(fn($c) => $c . 's', $v['categories']))) ?></small></span>
              <span class="yr"><?= e($v['years']) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

      <h2>Light type</h2>
      <button class="veh-btn" data-say="Show me headlights"><i class="fa-solid fa-lightbulb"></i> <b>Headlights</b></button>
      <button class="veh-btn" data-say="Show me taillights"><i class="fa-solid fa-circle-dot"></i> <b>Taillights</b></button>
      <button class="veh-btn" data-say="Show all parts"><i class="fa-solid fa-table-cells"></i> <b>Everything in stock</b> <span class="yr"><?= $count ?></span></button>
    </div>

    <div class="side-foot">
      <?php if ($wa): ?><a class="btn btn-wa btn-block" href="<?= e($wa) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp us</a><?php endif; ?>
      <button class="btn btn-ghost btn-block" data-enquire><i class="fa-solid fa-headset"></i> Talk to a person</button>
      <p class="links"><a href="privacy.php">Privacy</a> · <a href="terms.php">Terms</a> · © <?= date('Y') ?></p>
    </div>
  </aside>
  <button class="scrim" data-side-close aria-label="Close menu"></button>

  <main class="main">
    <header class="top glass">
      <button class="icon-btn menu-btn" data-side-open aria-label="Open vehicle list" aria-controls="side"><i class="fa-solid fa-bars"></i></button>
      <a class="brand" href="<?= e(APP_URL) ?>/"><?= brandMark() ?><span><?= e(APP_NAME) ?></span></a>
      <p class="top-title"><b>Parts assistant</b> · <?= $count ?> lights in stock for <?= count($modelNames) ?> vehicles</p>
      <div class="top-actions">
        <?php if ($wa): ?><a class="icon-btn wa" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp us"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
        <button class="icon-btn" data-theme-toggle aria-label="Switch light / dark theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
      </div>
    </header>

    <div class="thread" id="thread">
      <div class="wrap">
        <section class="hero" id="hero">
          <h1>Find the right <em>car light</em>.<br>Just ask.</h1>
          <p>Tell us your vehicle, the year and which light you need. We'll show you real photos and videos of what's in stock.</p>
          <div class="chips">
            <button class="chip" data-say="Toyota Hilux tail lights"><i class="fa-solid fa-bolt"></i> Hilux tail lights</button>
            <button class="chip" data-say="Headlight for a 2014 Kia Rio"><i class="fa-solid fa-bolt"></i> Headlight for a 2014 Kia Rio</button>
            <button class="chip" data-say="Toyota Vitz"><i class="fa-solid fa-bolt"></i> Toyota Vitz</button>
            <button class="chip" data-say="Do you deliver?"><i class="fa-solid fa-truck-fast"></i> Do you deliver?</button>
          </div>
        </section>

        <div class="msgs" id="msgs" aria-live="polite"></div>

        <section id="stock" aria-labelledby="stock-h">
          <div class="sec-head"><h2 id="stock-h">Everything in stock</h2><span><?= $count ?> light<?= $count === 1 ? '' : 's' ?> · tap any part for photos and video</span></div>
          <?php if (!$products): ?><p class="note">No parts are listed yet. Add your first one in Admin → Products.</p><?php endif; ?>
          <div class="grid">
            <?php foreach ($products as $i => $p) echo productCard($p, $i > 3); ?>
          </div>
        </section>
      </div>
    </div>

    <div class="composer-wrap">
      <div class="composer-in">
        <div class="quick" id="quick"></div>
        <form class="composer glass" id="composer" autocomplete="off">
          <label class="sr" for="say">Message</label>
          <textarea id="say" rows="1" maxlength="500" placeholder="e.g. Hilux 2018 tail light, driver side" enterkeyhint="send"></textarea>
          <button class="send" type="submit" aria-label="Send message"><i class="fa-solid fa-arrow-up"></i></button>
        </form>
        <p class="fine">Not sure it fits? Ask us before you order — we check every part against your vehicle.</p>
      </div>
    </div>
  </main>
</div>

<dialog class="sheet" id="sheet" aria-label="Part details"><div class="sheet-in"><button class="icon-btn sheet-x" data-sheet-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button><div id="sheet-body"></div></div></dialog>

<script>window.MPM = { products: <?= json_encode((object) $byId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> };</script>
<?php pageFoot();
