<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';
require_once dirname(__DIR__) . '/includes/media.php';
require_once dirname(__DIR__) . '/includes/ai.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isNew = isset($_GET['new']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'ai') { // "✨ Write with AI" (fetch → JSON)
        jsonResponse(aiProductDescription([
            'title' => postText('title'), 'make' => postText('make', 60), 'model' => postText('model', 80),
            'year_from' => (int) postText('year_from') ?: null, 'year_to' => (int) postText('year_to') ?: null,
            'category' => postText('category', 40), 'side' => postText('side', 10), 'grade' => postText('grade', 40), 'defects' => postText('defects', 1000),
        ]));
    }
    if ($action === 'delete' && $id) {
        foreach ($db->query('SELECT id FROM product_media WHERE product_id = ' . $id)->fetchAll(PDO::FETCH_COLUMN) as $mid) deleteProductMedia((int) $mid, $id);
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        audit('product_delete', "#{$id}");
        flash('ok', 'Part deleted.');
        redirect('admin/products.php');
    }
    if ($action === 'quick' && $id) { // price / status straight from the list
        $price = str_replace([' ', ','], ['', '.'], postText('price', 20));
        $status = isset(STOCK[postText('stock_status', 20)]) ? postText('stock_status', 20) : 'available';
        $db->prepare('UPDATE products SET price = ?, stock_status = ?, stock_checked_at = NOW() WHERE id = ?')->execute([is_numeric($price) && (float) $price > 0 ? $price : null, $status, $id]);
        flash('ok', 'Saved.');
        redirect('admin/products.php');
    }

    $title = postText('title', 200); $make = postText('make', 60); $model = postText('model', 80);
    if ($title === '' || $make === '' || $model === '') {
        flash('err', 'Title, make and model are required.');
        redirect('admin/products.php?' . ($id ? "id={$id}" : 'new=1'));
    }
    $yf = (int) postText('year_from') ?: null; $yt = (int) postText('year_to') ?: null;
    $price = str_replace([' ', ','], ['', '.'], postText('price', 20));
    $vals = [
        $title, $make, $model, $yf, $yt,
        isset(CATEGORIES[postText('category', 40)]) ? postText('category', 40) : 'headlight',
        isset(SIDES[postText('side', 10)]) ? postText('side', 10) : 'pair',
        isset(GRADES[postText('grade', 40)]) && postText('grade', 40) !== '' ? postText('grade', 40) : null,
        postText('defects', 2000) ?: null, postText('description', 4000) ?: null, postText('part_number', 80) ?: null,
        is_numeric($price) && (float) $price > 0 ? $price : null, max(0, (int) postText('qty')),
        isset(STOCK[postText('stock_status', 20)]) ? postText('stock_status', 20) : 'available',
        postText('aliases', 255) ?: null,
    ];
    if ($id) {
        $db->prepare('UPDATE products SET title=?, make=?, model=?, year_from=?, year_to=?, category=?, side=?, grade=?, defects=?, description=?, part_number=?, price=?, qty=?, stock_status=?, aliases=?'
            . (!empty($_POST['checked']) ? ', stock_checked_at = NOW()' : '') . ' WHERE id = ?')->execute([...$vals, $id]);
    } else {
        $db->prepare('INSERT INTO products (title, make, model, year_from, year_to, category, side, grade, defects, description, part_number, price, qty, stock_status, aliases, slug, stock_checked_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())')
           ->execute([...$vals, uniqueSlug("{$title} " . years($yf, $yt))]);
        $id = (int) $db->lastInsertId();
    }
    foreach ((array) ($_POST['remove'] ?? []) as $mid) deleteProductMedia((int) $mid, $id);

    $errors = [];
    $n = (int) $db->query('SELECT COUNT(*) FROM product_media WHERE product_id = ' . $id)->fetchColumn();
    $files = $_FILES['media'] ?? null;
    if ($files && is_array($files['name'])) {
        foreach ($files['name'] as $i => $_) {
            $err = saveProductUpload(['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]], $id, $n + $i);
            if ($err !== '') $errors[] = $err;
        }
    }
    audit('product_save', "#{$id} {$title}");
    flash($errors ? 'err' : 'ok', $errors ? 'Saved, but: ' . implode(' ', $errors) : 'Part saved.');
    redirect("admin/products.php?id={$id}");
}

// ── Edit form ─────────────────────────────────────────────
if ($id || $isNew) {
    $p = $id ? productById($id) : null;
    if ($id && !$p) { flash('err', 'That part no longer exists.'); redirect('admin/products.php'); }
    $v = fn(string $k, string $d = '') => e((string) ($p[$k] ?? $d));
    $opt = fn(array $list, string $cur) => implode('', array_map(fn($k, $l) => '<option value="' . e($k) . '"' . ((string) $k === $cur ? ' selected' : '') . '>' . e($l) . '</option>', array_keys($list), $list));
    adminHead($p ? 'Edit part' : 'Add a part', 'products.php', '<a class="btn btn-ghost btn-sm" href="products.php">← All parts</a>' . ($p ? '<a class="btn btn-ghost btn-sm" target="_blank" href="../part.php?s=' . e($p['slug']) . '">View page</a>' : ''));
    ?>
<form class="card glass" method="post" enctype="multipart/form-data" id="pform">
  <?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="fgrid">
    <div class="field wide"><label for="title">Title</label><input id="title" name="title" required maxlength="200" value="<?= $v('title') ?>" placeholder="e.g. Toyota Hilux GD-6 LED headlights"></div>
    <div class="field"><label for="make">Make</label><input id="make" name="make" required maxlength="60" value="<?= $v('make') ?>" placeholder="Toyota"></div>
    <div class="field"><label for="model">Model</label><input id="model" name="model" required maxlength="80" value="<?= $v('model') ?>" placeholder="Hilux GD-6"></div>
    <div class="field"><label for="year_from">Fits from year</label><input id="year_from" name="year_from" type="number" min="1980" max="2035" value="<?= $v('year_from') ?>"></div>
    <div class="field"><label for="year_to">Fits to year</label><input id="year_to" name="year_to" type="number" min="1980" max="2035" value="<?= $v('year_to') ?>"></div>
    <div class="field"><label for="category">Light type</label><select id="category" name="category"><?= $opt(CATEGORIES, (string) ($p['category'] ?? 'headlight')) ?></select></div>
    <div class="field"><label for="side">Side</label><select id="side" name="side"><?= $opt(SIDES, (string) ($p['side'] ?? 'pair')) ?></select></div>
    <div class="field"><label for="grade">Condition</label><select id="grade" name="grade"><?= $opt(GRADES, (string) ($p['grade'] ?? '')) ?></select></div>
    <div class="field"><label for="price">Price (R)</label><input id="price" name="price" inputmode="decimal" value="<?= $v('price') ?>" placeholder="Blank = Ask for price"></div>
    <div class="field"><label for="qty">Quantity</label><input id="qty" name="qty" type="number" min="0" value="<?= $v('qty', '1') ?>"></div>
    <div class="field"><label for="stock_status">Stock status</label><select id="stock_status" name="stock_status"><?= $opt(STOCK, (string) ($p['stock_status'] ?? 'available')) ?></select></div>
    <div class="field"><label for="part_number">Part number (optional)</label><input id="part_number" name="part_number" maxlength="80" value="<?= $v('part_number') ?>"></div>
    <div class="field wide"><label for="aliases">Other names customers type</label><input id="aliases" name="aliases" maxlength="255" value="<?= $v('aliases') ?>" placeholder="hilux, gd6, bakkie"><small>Comma-separated, including common misspellings. The chat uses these to recognise the vehicle.</small></div>
    <div class="field wide"><label for="defects">Known defects</label><textarea id="defects" name="defects" maxlength="2000" placeholder="e.g. One mounting tab repaired. Leave blank if none."><?= $v('defects') ?></textarea></div>
    <div class="field wide"><label for="description">Description</label><textarea id="description" name="description" maxlength="4000" rows="4"><?= $v('description') ?></textarea>
      <span><button type="button" class="btn btn-ghost btn-sm" id="ai-btn">✨ Write with AI</button> <small id="ai-msg"></small></span></div>
    <?php if ($p && $p['media']): ?>
    <div class="field wide"><label>Photos and videos — tick to remove</label><div class="media-list">
      <?php foreach ($p['media'] as $m): ?><label>
        <?php if ($m['media_kind'] === 'image'): ?><img src="<?= e(APP_URL . '/' . thumbOf($m['path'])) ?>" alt="" loading="lazy">
        <?php elseif ($m['poster']): ?><img src="<?= e(APP_URL . '/' . thumbOf($m['poster'])) ?>" alt="" loading="lazy">
        <?php else: ?><span class="vid"><i class="fa-solid fa-video"></i></span><?php endif; ?>
        <span><input type="checkbox" name="remove[]" value="<?= (int) $m['id'] ?>"> Remove <?= $m['media_kind'] === 'video' ? 'video' : 'photo' ?></span>
      </label><?php endforeach; ?>
    </div></div>
    <?php endif; ?>
    <div class="field wide"><label for="media">Add photos / videos</label><input id="media" name="media[]" type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"><small>Photos are shrunk and converted automatically. Videos: MP4 under 40 MB each.</small></div>
    <?php if ($p): ?><label class="check wide"><input type="checkbox" name="checked" value="1"> <span>I checked today that this is still in stock<?= $p['stock_checked_at'] ? ' (last checked ' . e(date('j M Y', strtotime($p['stock_checked_at']))) . ')' : '' ?></span></label><?php endif; ?>
  </div>
  <p style="margin-top:20px" class="inline-form"><button class="btn btn-primary" type="submit" name="action" value="save"><i class="fa-solid fa-floppy-disk"></i> Save part</button>
  <?php if ($p): ?><button class="btn btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Delete this part and its uploaded photos? This cannot be undone.')">Delete</button><?php endif; ?></p>
</form>
<script>
document.getElementById('ai-btn').addEventListener('click', async function () {
  const f = document.getElementById('pform'), msg = document.getElementById('ai-msg'), d = new FormData(f);
  d.delete('media[]'); d.set('action', 'ai');
  this.disabled = true; msg.textContent = 'Writing…';
  try {
    const j = await (await fetch(location.href, { method: 'POST', body: d, credentials: 'same-origin' })).json();
    if (j.ok) { f.description.value = j.description; msg.textContent = 'Done — check it before saving.'; } else msg.textContent = j.error;
  } catch (e) { msg.textContent = 'Could not reach the server.'; }
  this.disabled = false;
});
</script>
<?php
    adminFoot();
    exit;
}

// ── List ──────────────────────────────────────────────────
$rows = withMedia($db->query('SELECT * FROM products ORDER BY make, model, category, id')->fetchAll());
adminHead('Products', 'products.php', '<a class="btn btn-primary btn-sm" href="products.php?new=1"><i class="fa-solid fa-plus"></i> Add a part</a>');
?>
<section class="card glass">
<?php if (!$rows): ?><p>No parts yet. Click <b>Add a part</b>.</p><?php else: ?>
<table class="tbl"><thead><tr><th></th><th>Part</th><th>Years</th><th>Media</th><th>Views</th><th>Price &amp; status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $cover = coverOf($r); ?>
<tr>
  <td><?php if ($cover): ?><img src="<?= e(APP_URL . '/' . thumbOf($cover)) ?>" alt="" loading="lazy"><?php endif; ?></td>
  <td data-label="Part"><span><a href="products.php?id=<?= (int) $r['id'] ?>"><b><?= e($r['title']) ?></b></a><small><?= e($r['make'] . ' ' . $r['model']) ?> · <?= e(CATEGORIES[$r['category']] ?? $r['category']) ?> · <?= e(GRADES[(string) $r['grade']] ?? '') ?></small></span></td>
  <td data-label="Years"><?= e(years($r['year_from'] ? (int) $r['year_from'] : null, $r['year_to'] ? (int) $r['year_to'] : null)) ?></td>
  <td data-label="Media"><?= count($r['media']) ?></td>
  <td data-label="Views"><?= (int) $r['views'] ?></td>
  <td data-label="Price & status"><form method="post" class="inline-form"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="quick">
    <input name="price" inputmode="decimal" size="7" placeholder="R price" aria-label="Price for <?= e($r['title']) ?>" value="<?= e((string) $r['price']) ?>">
    <select name="stock_status" aria-label="Stock status"><?php foreach (STOCK as $k => $l): ?><option value="<?= $k ?>"<?= $k === $r['stock_status'] ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <button class="btn btn-ghost btn-sm">Save</button></form></td>
  <td><a class="btn btn-ghost btn-sm" href="products.php?id=<?= (int) $r['id'] ?>">Edit</a></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
</section>
<?php adminFoot();
