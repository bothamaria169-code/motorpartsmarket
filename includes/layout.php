<?php
declare(strict_types=1);

require_once __DIR__ . '/catalog.php';

/** <head> shared by every public page. $o: canonical, image, schema (array), bodyClass, css, ogType */
function pageHead(string $title, string $desc, array $o = []): void
{
    startAppSession();
    $canonical = $o['canonical'] ?? APP_URL . '/';
    $image = $o['image'] ?? APP_URL . '/assets/img/og.png';
    $ga = get_setting('ga4_id');
    $pixel = get_setting('meta_pixel_id');
    ?>
<!doctype html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="csrf" content="<?= e(csrfToken()) ?>">
<meta name="theme-color" content="#2563eb">
<?php if (!empty($o['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
<meta property="og:type" content="<?= e($o['ogType'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= e(APP_NAME) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php if ($v = get_setting('google_site_verification')): ?><meta name="google-site-verification" content="<?= e($v) ?>"><?php endif; ?>
<link rel="icon" href="<?= e(APP_URL) ?>/assets/img/mark.svg" type="image/svg+xml">
<script>(function(){try{var t=localStorage.getItem('mpm-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}})()</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
<?php foreach ($o['css'] ?? [] as $c): ?><link rel="stylesheet" href="<?= e(asset($c)) ?>"><?php endforeach; ?>
<?php foreach ($o['schema'] ?? [] as $s): ?><script type="application/ld+json"><?= json_encode($s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script><?php endforeach; ?>
<?php if ($ga && !IS_LOCAL): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config',<?= json_encode($ga) ?>);</script>
<?php endif; ?>
<?php if ($pixel && !IS_LOCAL): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode($pixel) ?>);fbq('track','PageView');</script>
<?php endif; ?>
</head>
<body class="<?= e($o['bodyClass'] ?? '') ?>">
<div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div>
<?php
}

function pageFoot(bool $appJs = true): void
{
    $cfg = ['base' => APP_URL, 'whatsapp' => whatsappLink()];
    ?>
<script>window.MPM = Object.assign(window.MPM || {}, <?= json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>);</script>
<?php if ($appJs): ?><script src="<?= e(asset('assets/js/app.js')) ?>" defer></script><?php endif; ?>
</body>
</html>
<?php
}

function brandMark(): string
{
    return '<span class="mark" aria-hidden="true"><svg viewBox="0 0 32 32" width="22" height="22"><path d="M5 16c0-5 4-9 9-9h3v18h-3c-5 0-9-4-9-9z" fill="currentColor"/><path d="M21 9l6-2M21 16h7M21 23l6 2" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg></span>';
}

/** Product card (server-rendered twin of card() in app.js). $p = productPublic(). */
function productCard(array $p, bool $lazy = true): string
{
    ob_start(); ?>
<article class="pcard glass" data-id="<?= (int) $p['id'] ?>">
  <button class="pcard-img" data-act="view" aria-label="View photos of <?= e($p['title']) ?>">
    <img src="<?= e($p['thumb']) ?>" alt="<?= e($p['title'] . ' ' . $p['years']) ?>" width="560" height="420" <?= $lazy ? 'loading="lazy"' : 'fetchpriority="high"' ?> decoding="async">
    <span class="badge"><?= e($p['categoryLabel']) ?></span>
    <?php if ($p['videos']): ?><span class="badge badge-r"><i class="fa-solid fa-play"></i> Video</span><?php endif; ?>
    <?php if ($p['reserved']): ?><span class="badge badge-warn">Reserved</span><?php endif; ?>
  </button>
  <div class="pcard-body">
    <h3><a href="<?= e($p['url']) ?>" data-act="view"><?= e($p['title']) ?></a></h3>
    <p class="veh"><i class="fa-solid fa-car-side"></i> <?= e($p['make'] . ' ' . $p['model']) ?> <span class="yr"><?= e($p['years']) ?></span></p>
    <p class="meta"><?= e($p['side']) ?> · <?= e($p['grade']) ?></p>
    <div class="pcard-foot">
      <strong class="price<?= $p['hasPrice'] ? '' : ' ask' ?>"><?= e($p['price']) ?></strong>
      <button class="btn btn-primary btn-sm" data-act="ask">Ask / Order</button>
    </div>
  </div>
</article>
<?php
    return (string) ob_get_clean();
}

/** Header for text pages (privacy, terms, product). */
function simpleTop(): void
{
    $wa = whatsappLink('Hi, I am looking for a car light.');
    ?>
<header class="top glass top-page">
  <a class="brand" href="<?= e(APP_URL) ?>/"><?= brandMark() ?><span><?= e(APP_NAME) ?></span></a>
  <nav class="top-actions">
    <a class="btn btn-ghost btn-sm" href="<?= e(APP_URL) ?>/"><i class="fa-solid fa-comments"></i> <span>Chat to find a part</span></a>
    <?php if ($wa): ?><a class="icon-btn wa" href="<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp us"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
    <button class="icon-btn" data-theme-toggle aria-label="Switch light / dark theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
  </nav>
</header>
<?php
}

function simpleFooter(): void
{
    $mail = get_setting('contact_email', CONTACT_EMAIL);
    ?>
<footer class="foot">
  <p>© <?= date('Y') ?> <?= e(APP_NAME) ?> · <a href="<?= e(APP_URL) ?>/">Find a part</a> · <a href="<?= e(APP_URL) ?>/privacy.php">Privacy</a> · <a href="<?= e(APP_URL) ?>/terms.php">Terms</a> · <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></p>
</footer>
<?php
}
