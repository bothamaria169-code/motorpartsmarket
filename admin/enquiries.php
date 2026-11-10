<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';

$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $status = postText('status', 20);
    if ($id && isset(ENQUIRY_STATUSES[$status])) {
        $db->prepare('UPDATE enquiries SET status = ?, admin_notes = ? WHERE id = ?')->execute([$status, postText('admin_notes', 2000) ?: null, $id]);
        audit('enquiry_update', "#{$id} → {$status}");
        flash('ok', 'Enquiry updated.');
    }
    redirect('admin/enquiries.php' . (isset($_GET['status']) ? '?status=' . rawurlencode((string) $_GET['status']) : ''));
}

$filter = isset(ENQUIRY_STATUSES[$_GET['status'] ?? '']) ? (string) $_GET['status'] : '';
$s = $db->prepare('SELECT e.*, p.title, p.slug FROM enquiries e LEFT JOIN products p ON p.id = e.product_id ' . ($filter ? 'WHERE e.status = ? ' : '') . 'ORDER BY e.id DESC LIMIT 200');
$s->execute($filter ? [$filter] : []);
$rows = $s->fetchAll();
$tone = ['new' => 'warn', 'contacted' => 'info', 'quoted' => 'info', 'paid' => 'ok', 'dispatched' => 'ok', 'completed' => 'ok', 'cancelled' => 'bad'];

$tabs = '<a class="btn btn-sm ' . ($filter === '' ? 'btn-dark' : 'btn-ghost') . '" href="enquiries.php">All</a>';
foreach (['new', 'contacted', 'quoted', 'paid'] as $k) $tabs .= '<a class="btn btn-sm ' . ($filter === $k ? 'btn-dark' : 'btn-ghost') . '" href="enquiries.php?status=' . $k . '">' . ENQUIRY_STATUSES[$k] . '</a>';
adminHead('Enquiries', 'enquiries.php', $tabs);
?>
<section class="card glass">
<?php if (!$rows): ?><p>No enquiries here yet. They arrive when a customer taps <b>Ask / Order</b> or <b>Talk to a person</b>.</p><?php else: ?>
<table class="tbl"><thead><tr><th>Ref</th><th>Customer</th><th>Wants</th><th>Status &amp; notes</th></tr></thead><tbody>
<?php foreach ($rows as $r):
    $digits = preg_replace('/\D/', '', $r['phone']);
    if (str_starts_with($digits, '0')) $digits = '27' . substr($digits, 1); ?>
<tr>
  <td data-label="Ref"><span><b>MPM-<?= str_pad((string) $r['id'], 5, '0', STR_PAD_LEFT) ?></b><small><?= e(date('j M Y H:i', strtotime($r['created_at']))) ?></small></span></td>
  <td data-label="Customer"><span><b><?= e($r['customer_name']) ?></b>
    <small><a href="tel:<?= e($r['phone']) ?>"><?= e($r['phone']) ?></a> · <a href="https://wa.me/<?= e($digits) ?>" target="_blank" rel="noopener">WhatsApp</a></small>
    <?php if ($r['area']): ?><small><?= e($r['area']) ?></small><?php endif; ?></span></td>
  <td data-label="Wants"><span><?= $r['slug'] ? '<a target="_blank" href="../part.php?s=' . e($r['slug']) . '">' . e($r['title']) . '</a>' : 'General enquiry' ?>
    <?php if ($r['vehicle']): ?><small>Vehicle: <?= e($r['vehicle']) ?></small><?php endif; ?>
    <?php if ($r['message']): ?><small>“<?= e($r['message']) ?>”</small><?php endif; ?></span></td>
  <td data-label="Status"><form method="post" class="inline-form"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <?= pill(ENQUIRY_STATUSES[$r['status']], $tone[$r['status']]) ?>
    <select name="status" aria-label="Status"><?php foreach (ENQUIRY_STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= $k === $r['status'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <input name="admin_notes" placeholder="Notes (price quoted, courier…)" aria-label="Notes" value="<?= e((string) $r['admin_notes']) ?>" size="22">
    <button class="btn btn-ghost btn-sm">Save</button></form></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</section>
<?php adminFoot();
