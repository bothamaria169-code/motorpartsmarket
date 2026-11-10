<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/layout.php';

$ADMIN = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') requireCsrf();

const ADMIN_NAV = [
    'index.php'     => ['Dashboard', 'fa-gauge-high'],
    'products.php'  => ['Products', 'fa-lightbulb'],
    'enquiries.php' => ['Enquiries', 'fa-inbox'],
    'chats.php'     => ['Chats', 'fa-comments'],
    'settings.php'  => ['Settings', 'fa-gear'],
    'audit.php'     => ['Audit log', 'fa-clipboard-list'],
];

function adminHead(string $title, string $active, string $actions = ''): void
{
    global $ADMIN;
    $new = (int) getDB()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
    pageHead($title . ' — Admin', 'Admin', ['noindex' => true, 'css' => ['assets/css/admin.css'], 'bodyClass' => 'adm']);
    ?>
<div class="adm-shell">
  <aside class="adm-side glass">
    <a class="brand" href="<?= e(APP_URL) ?>/admin/"><?= brandMark() ?><span>Admin</span></a>
    <nav>
      <?php foreach (ADMIN_NAV as $file => [$label, $icon]): ?>
        <a href="<?= e(APP_URL . '/admin/' . $file) ?>" class="<?= $active === $file ? 'on' : '' ?>"><i class="fa-solid <?= $icon ?>"></i> <span><?= e($label) ?></span><?= $file === 'enquiries.php' && $new ? '<b>' . $new . '</b>' : '' ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="adm-side-foot">
      <a href="<?= e(APP_URL) ?>/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>View site</span></a>
      <a href="<?= e(APP_URL) ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span>Sign out</span></a>
      <button class="icon-btn" data-theme-toggle aria-label="Switch light / dark theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
    </div>
  </aside>
  <main class="adm-main">
    <header class="adm-head"><h1><?= e($title) ?></h1><div class="adm-actions"><?= $actions ?></div></header>
    <?php foreach (takeFlashes() as [$type, $msg]): ?><p class="alert alert-<?= $type === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($msg) ?></p><?php endforeach; ?>
<?php
}

function adminFoot(): void
{
    echo '</main></div>';
    pageFoot();
}

function pill(string $text, string $tone = ''): string
{
    return '<span class="pill ' . e($tone) . '">' . e($text) . '</span>';
}
