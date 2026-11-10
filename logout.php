<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
startAppSession();
$_SESSION = [];
session_destroy();
redirect('login.php');
