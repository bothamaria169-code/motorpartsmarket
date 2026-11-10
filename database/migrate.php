<?php
declare(strict_types=1);

/**
 * Self-running migrations: creates missing tables on the first page load
 * after a deploy, seeds the admin and the first catalogue.
 * No MySQL 8 reserved words as names (e.g. `condition` → `grade`).
 */
const SCHEMA_VERSION = '2026-11-10.1';

function runMigrations(PDO $db): void
{
    try {
        $v = $db->query("SELECT v FROM app_settings WHERE k = 'schema_version'")->fetchColumn();
        if ($v === SCHEMA_VERSION) return;
    } catch (PDOException) {
        // First run: no settings table yet.
    }

    try {
        $tables = [
            "CREATE TABLE IF NOT EXISTS app_settings (
                k VARCHAR(80) PRIMARY KEY, v MEDIUMTEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                full_name VARCHAR(120) NULL,
                password_hash VARCHAR(255) NULL,
                role ENUM('admin','staff') NOT NULL DEFAULT 'admin',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL, ip VARCHAR(45) NOT NULL,
                success TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (email), INDEX (ip), INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(160) NOT NULL UNIQUE,
                title VARCHAR(200) NOT NULL,
                make VARCHAR(60) NOT NULL,
                model VARCHAR(80) NOT NULL,
                year_from SMALLINT UNSIGNED NULL,
                year_to SMALLINT UNSIGNED NULL,
                category VARCHAR(40) NOT NULL DEFAULT 'headlight',
                side ENUM('left','right','pair','single') NOT NULL DEFAULT 'pair',
                grade VARCHAR(40) NULL,
                defects TEXT NULL,
                description TEXT NULL,
                part_number VARCHAR(80) NULL,
                price DECIMAL(10,2) NULL,
                qty SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                stock_status ENUM('available','reserved','sold','hidden') NOT NULL DEFAULT 'available',
                stock_checked_at DATETIME NULL,
                aliases VARCHAR(255) NULL,
                views INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (make), INDEX (model), INDEX (category), INDEX (stock_status), INDEX (year_from, year_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS product_media (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                media_kind ENUM('image','video') NOT NULL DEFAULT 'image',
                path VARCHAR(255) NOT NULL,
                poster VARCHAR(255) NULL,
                width SMALLINT UNSIGNED NULL, height SMALLINT UNSIGNED NULL,
                sort_order SMALLINT NOT NULL DEFAULT 0,
                INDEX (product_id, sort_order),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS enquiries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NULL,
                customer_name VARCHAR(120) NOT NULL,
                phone VARCHAR(40) NOT NULL,
                email VARCHAR(255) NULL,
                area VARCHAR(120) NULL,
                vehicle VARCHAR(160) NULL,
                message TEXT NULL,
                source VARCHAR(30) NOT NULL DEFAULT 'chat',
                status ENUM('new','contacted','quoted','paid','dispatched','completed','cancelled') NOT NULL DEFAULT 'new',
                admin_notes TEXT NULL,
                ip VARCHAR(45) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (status), INDEX (created_at), INDEX (product_id),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS chat_sessions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                token CHAR(32) NOT NULL UNIQUE,
                channel VARCHAR(20) NOT NULL DEFAULT 'web',
                vehicle_make VARCHAR(60) NULL, vehicle_model VARCHAR(80) NULL, vehicle_year SMALLINT UNSIGNED NULL,
                category VARCHAR(40) NULL, side VARCHAR(10) NULL,
                messages INT UNSIGNED NOT NULL DEFAULT 0,
                ip VARCHAR(45) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS chat_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_id INT UNSIGNED NOT NULL,
                sender ENUM('user','bot') NOT NULL,
                body TEXT NOT NULL,
                product_ids VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (session_id),
                FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS ai_requests (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kind VARCHAR(40) NOT NULL, ok TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS audit_log (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL, detail VARCHAR(500) NULL,
                ip VARCHAR(45) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];
        foreach ($tables as $sql) $db->exec($sql);

        seedAdmin($db);
        seedProducts($db);

        $db->prepare("REPLACE INTO app_settings (k, v) VALUES ('schema_version', ?)")->execute([SCHEMA_VERSION]);
    } catch (Throwable $e) {
        error_log('MotorPartsMarket migration failed: ' . $e->getMessage());
        http_response_code(500);
        exit('<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:2rem"><h1>Setup could not finish</h1>'
            . '<p>The database update failed. For the site owner: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</p>');
    }
}

function seedAdmin(PDO $db): void
{
    $pw = (string) secret('admin_seed_password', '');
    if ($pw === '' || $pw === 'CHANGE_ME') return;
    $s = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $s->execute([ADMIN_EMAIL]);
    if ((int) $s->fetchColumn() > 0) return;
    $db->prepare("INSERT INTO users (email, full_name, password_hash, role) VALUES (?, 'Administrator', ?, 'admin')")
       ->execute([ADMIN_EMAIL, password_hash($pw, PASSWORD_DEFAULT)]);
}

/** First catalogue from database/seed-products.json — only when the table is empty. */
function seedProducts(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) return;
    $file = __DIR__ . '/seed-products.json';
    if (!is_file($file)) return;
    $items = json_decode((string) file_get_contents($file), true) ?: [];
    $p = $db->prepare('INSERT INTO products (slug, title, make, model, year_from, year_to, category, side, description, aliases, stock_status, stock_checked_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'available\', NOW())');
    $m = $db->prepare('INSERT INTO product_media (product_id, media_kind, path, poster, width, height, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $aliases = [
        'Hilux GD-6' => 'hilux, gd6, gd 6, gd-6, bakkie', 'Elantra' => 'elantra, elementry, elentra',
        'Cerato' => 'cerato, ceralo, ceratto', 'Pegas' => 'pegas, pegus, pegasus', 'Accent sedan' => 'accent',
        'Vitz' => 'vitz, viz', 'Rio' => 'rio', 'Ertiga' => 'ertiga, ertica', 'Q5' => 'q5', 'Corolla' => 'corolla, corola',
    ];
    foreach ($items as $it) {
        $p->execute([$it['slug'], $it['title'], $it['make'], $it['model'], $it['year_from'], $it['year_to'], $it['category'], $it['side'], $it['desc'], $aliases[$it['model']] ?? null]);
        $id = (int) $db->lastInsertId();
        foreach ($it['media'] as $i => $md) {
            $m->execute([$id, $md['type'], $md['path'], $md['poster'] ?? null, $md['w'] ?? null, $md['h'] ?? null, $md['type'] === 'image' ? $i : 100 + $i]);
        }
    }
}
