<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';

$db = getDB();
$sessions = $db->query('SELECT * FROM chat_sessions ORDER BY updated_at DESC LIMIT 60')->fetchAll();
$msgs = [];
if ($sessions) {
    $ids = implode(',', array_map('intval', array_column($sessions, 'id')));
    foreach ($db->query("SELECT session_id, sender, body FROM chat_messages WHERE session_id IN ({$ids}) ORDER BY id") as $m) $msgs[$m['session_id']][] = $m;
}
adminHead('Chats', 'chats.php');
?>
<section class="card glass">
  <h2>What customers asked the assistant (latest 60)</h2>
  <?php if (!$sessions): ?><p>No chats yet.</p><?php endif; ?>
  <?php foreach ($sessions as $s): $veh = trim("{$s['vehicle_make']} {$s['vehicle_model']} {$s['vehicle_year']}"); ?>
  <details class="chat">
    <summary><b><?= e(date('j M H:i', strtotime($s['updated_at']))) ?></b> <?= pill($s['channel'], 'info') ?>
      <?= $veh !== '' ? pill($veh, 'ok') : pill('No vehicle found', 'warn') ?>
      <?= $s['category'] ? pill(CATEGORIES[$s['category']] ?? $s['category']) : '' ?>
      <small><?= (int) $s['messages'] ?> messages</small></summary>
    <div class="chat-log"><?php foreach ($msgs[$s['id']] ?? [] as $m): ?><p class="<?= $m['sender'] === 'user' ? 'u' : 'b' ?>"><?= e($m['body']) ?></p><?php endforeach; ?></div>
  </details>
  <?php endforeach; ?>
</section>
<?php adminFoot();
