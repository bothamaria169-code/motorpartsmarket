<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';

$rows = getDB()->query('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
adminHead('Audit log', 'audit.php');
?>
<section class="card glass">
<?php if (!$rows): ?><p>Nothing logged yet.</p><?php else: ?>
<table class="tbl"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
  <td data-label="When"><?= e(date('j M Y H:i', strtotime($r['created_at']))) ?></td>
  <td data-label="Who"><?= e($r['email'] ?? '—') ?></td>
  <td data-label="Action"><?= e($r['action']) ?></td>
  <td data-label="Detail"><?= e((string) $r['detail']) ?></td>
  <td data-label="IP"><?= e((string) $r['ip']) ?></td>
</tr><?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</section>
<?php adminFoot();
