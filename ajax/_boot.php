<?php
declare(strict_types=1);

// Every public AJAX endpoint: POST + valid CSRF token (from the page's <meta name="csrf">).
require_once dirname(__DIR__) . '/includes/chat.php';

startAppSession();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
    jsonResponse(['ok' => false, 'error' => 'Your session expired — refresh the page and try again.'], 419);
}
