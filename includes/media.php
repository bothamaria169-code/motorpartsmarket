<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Saves one uploaded photo or video for a product.
 * Photos become WebP (1400px + a 560px "-sm" thumbnail); videos are kept as they are.
 * @return string '' on success, or a message for the admin
 */
function saveProductUpload(array $file, int $productId, int $sort): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    $name = (string) ($file['name'] ?? 'file');
    if ($file['error'] !== UPLOAD_ERR_OK) return "{$name}: upload failed (too big for the server? Videos must be under 40 MB).";
    if ($file['size'] > MAX_UPLOAD) return "{$name}: larger than 40 MB.";
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) return 'The uploads/products folder could not be created.';

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $base = 'p' . $productId . '-' . bin2hex(random_bytes(5));
    $db = getDB();

    if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if (!$src) return "{$name}: not a readable photo.";
        // Phone photos carry their rotation in EXIF.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $o = (int) (@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
            $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($deg) $src = imagerotate($src, $deg, 0);
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $dims = [0, 0];
        foreach (['' => 1400, '-sm' => 560] as $suffix => $max) {
            $scale = min(1, $max / max($w, $h));
            $img = $scale < 1 ? imagescale($src, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC) : $src;
            imagepalettetotruecolor($img);
            imagewebp($img, UPLOAD_DIR . $base . $suffix . '.webp', $suffix === '' ? 80 : 76);
            if ($suffix === '') $dims = [imagesx($img), imagesy($img)];
        }
        $db->prepare("INSERT INTO product_media (product_id, media_kind, path, width, height, sort_order) VALUES (?, 'image', ?, ?, ?, ?)")
           ->execute([$productId, 'uploads/products/' . $base . '.webp', $dims[0], $dims[1], $sort]);
        return '';
    }

    $videoExt = ['video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm'][$mime] ?? null;
    if ($videoExt) {
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $base . '.' . $videoExt)) return "{$name}: could not be saved.";
        $db->prepare("INSERT INTO product_media (product_id, media_kind, path, sort_order) VALUES (?, 'video', ?, ?)")
           ->execute([$productId, 'uploads/products/' . $base . '.' . $videoExt, 100 + $sort]);
        return '';
    }
    return "{$name}: only JPG, PNG, WebP photos and MP4, MOV, WebM videos are accepted.";
}

/** Removes a media row; deletes the file only if it lives in uploads/products. */
function deleteProductMedia(int $mediaId, int $productId): void
{
    $db = getDB();
    $s = $db->prepare('SELECT * FROM product_media WHERE id = ? AND product_id = ?');
    $s->execute([$mediaId, $productId]);
    if (!$m = $s->fetch()) return;
    foreach (array_filter([$m['path'], preg_replace('/\.webp$/', '-sm.webp', $m['path']), $m['poster']]) as $path) {
        if (str_starts_with($path, 'uploads/products/') && !str_contains($path, '..') && is_file(BASE_DIR . '/' . $path)) @unlink(BASE_DIR . '/' . $path);
    }
    $db->prepare('DELETE FROM product_media WHERE id = ?')->execute([$mediaId]);
}

function uniqueSlug(string $text, int $ignoreId = 0): string
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-') ?: 'part';
    $slug = substr($slug, 0, 140);
    $s = getDB()->prepare('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?');
    for ($i = 1, $try = $slug; ; $try = $slug . '-' . ++$i) {
        $s->execute([$try, $ignoreId]);
        if (!(int) $s->fetchColumn()) return $try;
    }
}
