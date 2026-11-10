<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (currentAdmin()) redirect('admin/');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $error = attemptLogin(postText('email'), (string) ($_POST['password'] ?? ''));
    if ($error === '') redirect('admin/');
}
pageHead('Sign in — ' . APP_NAME, 'Staff sign-in.', ['noindex' => true, 'canonical' => APP_URL . '/login.php']);
?>
<main class="auth">
  <form class="auth-card glass" method="post">
    <a class="brand" href="<?= e(APP_URL) ?>/"><?= brandMark() ?><span><?= e(APP_NAME) ?></span></a>
    <h1 style="font-size:1.5rem">Staff sign-in</h1>
    <?php if ($error): ?><p class="alert alert-err" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?= csrfField() ?>
    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required autocomplete="username" value="<?= e(postText('email')) ?>"></div>
    <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-block" type="submit">Sign in</button>
    <p class="fine" style="display:block">Locked out? Set <code>admin_reset_password</code> in config/secrets.php and load any page once.</p>
  </form>
</main>
<?php
pageFoot(false);
