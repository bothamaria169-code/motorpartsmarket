<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';
require_once dirname(__DIR__) . '/includes/ai.php';

$db = getDB();
$one = fn(string $sql) => (int) $db->query($sql)->fetchColumn();
$inStock   = $one("SELECT COUNT(*) FROM products WHERE stock_status = 'available'");
$noPrice   = $one("SELECT COUNT(*) FROM products WHERE stock_status IN ('available','reserved') AND (price IS NULL OR price <= 0)");
$newEnq    = $one("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
$enq30     = $one('SELECT COUNT(*) FROM enquiries WHERE created_at > NOW() - INTERVAL 30 DAY');
$chats30   = $one('SELECT COUNT(*) FROM chat_sessions WHERE created_at > NOW() - INTERVAL 30 DAY');

$days = [];
for ($i = 29; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-{$i} day"))] = 0;
foreach ($db->query('SELECT DATE(created_at) d, COUNT(*) n FROM chat_sessions WHERE created_at > CURDATE() - INTERVAL 30 DAY GROUP BY d') as $r) $days[$r['d']] = (int) $r['n'];
$max = max(1, ...array_values($days));

// What people asked for that we could not match (demand you are missing).
$missed = $db->query("SELECT m.body, m.created_at FROM chat_messages m JOIN chat_messages r ON r.id = m.id + 1 AND r.session_id = m.session_id
                      WHERE m.sender = 'user' AND r.sender = 'bot' AND (r.body LIKE 'We don''t%' OR r.body LIKE 'I couldn''t%')
                      ORDER BY m.id DESC LIMIT 8")->fetchAll();
$recent = $db->query('SELECT e.*, p.title FROM enquiries e LEFT JOIN products p ON p.id = e.product_id ORDER BY e.id DESC LIMIT 6')->fetchAll();

adminHead('Dashboard', 'index.php', '<a class="btn btn-primary btn-sm" href="products.php?new=1"><i class="fa-solid fa-plus"></i> Add a part</a>');
?>
<div class="kpis">
  <div class="kpi glass"><span>In stock</span><strong><?= $inStock ?></strong><small><?= $noPrice ?> without a price</small></div>
  <div class="kpi glass"><span>New enquiries</span><strong><?= $newEnq ?></strong><small><?= $enq30 ?> in the last 30 days</small></div>
  <div class="kpi glass"><span>Chats (30 days)</span><strong><?= $chats30 ?></strong><small>people who used the assistant</small></div>
  <div class="kpi glass"><span>AI</span><strong><?= aiEnabled() ? 'On' : 'Off' ?></strong><small><?= aiEnabled() ? aiUsedThisMonth() . ' of ' . aiMonthlyLimit() . ' calls this month' : 'Add a Groq key in Settings' ?></small></div>
</div>

<?php if ($noPrice): ?><p class="alert alert-err"><?= $noPrice ?> part<?= $noPrice === 1 ? ' has' : 's have' ?> no price yet and show "Ask for price". <a href="products.php">Add prices</a>.</p><?php endif; ?>
<?php if (whatsappLink() === ''): ?><p class="alert alert-err">No WhatsApp number is set, so the WhatsApp buttons are hidden. <a href="settings.php">Add it in Settings</a>.</p><?php endif; ?>

<section class="card glass">
  <h2>Chats per day — last 30 days</h2>
  <div class="bars" role="img" aria-label="Chats per day for the last 30 days"><?php foreach ($days as $d => $n): ?><i style="height:<?= round($n / $max * 100) ?>%" title="<?= e(date('j M', strtotime($d))) ?>: <?= $n ?>"></i><?php endforeach; ?></div>
</section>

<section class="card glass">
  <h2>Latest enquiries</h2>
  <?php if (!$recent): ?><p>No enquiries yet.</p><?php else: ?>
  <table class="tbl"><thead><tr><th>When</th><th>Customer</th><th>Part</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($recent as $r): ?><tr>
      <td data-label="When"><?= e(date('j M H:i', strtotime($r['created_at']))) ?></td>
      <td data-label="Customer"><?= e($r['customer_name']) ?> · <?= e($r['phone']) ?></td>
      <td data-label="Part"><?= e($r['title'] ?? 'General enquiry') ?></td>
      <td data-label="Status"><?= pill(ENQUIRY_STATUSES[$r['status']], $r['status'] === 'new' ? 'warn' : 'info') ?></td>
    </tr><?php endforeach; ?>
  </tbody></table>
  <p style="margin-top:12px"><a href="enquiries.php">All enquiries →</a></p>
  <?php endif; ?>
</section>

<section class="card glass">
  <h2>Asked for, but not in stock</h2>
  <?php if (!$missed): ?><p>Nothing yet. When customers ask for parts you don't list, they show here — a shopping list for your suppliers.</p><?php else: ?>
  <table class="tbl"><tbody><?php foreach ($missed as $m): ?><tr><td><?= e($m['body']) ?></td><td data-label="When"><?= e(date('j M H:i', strtotime($m['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table>
  <?php endif; ?>
</section>
<?php adminFoot();
