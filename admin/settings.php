<?php
declare(strict_types=1);

require __DIR__ . '/_shell.php';
require_once dirname(__DIR__) . '/includes/ai.php';

const PLAIN = ['whatsapp_number', 'contact_email', 'groq_model', 'ai_monthly_limit', 'ga4_id', 'meta_pixel_id', 'google_site_verification', 'wa_phone_id', 'wa_verify_token'];
const SECRET_KEYS = ['groq_api_key', 'wa_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'save') {
        foreach (PLAIN as $k) set_setting($k, postText($k, 500));
        foreach (SECRET_KEYS as $k) save_secret_setting($k, (string) ($_POST[$k] ?? ''));
        set_setting('ai_enabled', empty($_POST['ai_enabled']) ? '0' : '1');
        set_setting('groq_models_cache', '');
        audit('settings_save');
        flash('ok', 'Settings saved.');
    } elseif ($action === 'test_ai') {
        if (aiKey() === '') flash('err', 'Save a Groq API key first.');
        else {
            $r = groqJson('Answer ONLY with JSON {"ok":true,"word":"lights"}.', 'Reply now.', 60);
            flash($r['ok'] ? 'ok' : 'err', $r['ok'] ? 'Groq works. Model in use: ' . groqModel() : 'Groq said: ' . $r['error']);
        }
    } elseif ($action === 'test_email') {
        $to = get_setting('contact_email', CONTACT_EMAIL);
        $sent = sendMail($to, 'Test email from ' . APP_NAME, "This is a test.\nEnquiries from the website will arrive at this address.");
        flash($sent ? 'ok' : 'err', $sent ? "Test email handed to the server for {$to}. Check the inbox (and spam) in a minute." : "The server refused to send email to {$to}. Enquiries are still saved under Enquiries.");
    } elseif ($action === 'password') {
        $pw = (string) ($_POST['new_password'] ?? '');
        if (strlen($pw) < 10) flash('err', 'Use at least 10 characters.');
        elseif (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $ADMIN['password_hash'])) flash('err', 'Your current password is not right.');
        else {
            getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $ADMIN['id']]);
            audit('password_change');
            flash('ok', 'Password changed.');
        }
    }
    redirect('admin/settings.php');
}

$g = fn(string $k, string $d = '') => e(get_setting($k, $d));
$models = aiKey() !== '' ? groqModels() : [];
adminHead('Settings', 'settings.php');
?>
<form method="post" class="card glass">
  <?= csrfField() ?>
  <h2>Contact</h2>
  <div class="fgrid">
    <div class="field"><label for="whatsapp_number">WhatsApp number</label><input id="whatsapp_number" name="whatsapp_number" inputmode="tel" value="<?= $g('whatsapp_number') ?>" placeholder="e.g. 082 123 4567"><small>Shows the green WhatsApp buttons. Leave blank to hide them.</small></div>
    <div class="field"><label for="contact_email">Enquiries go to this email</label><input id="contact_email" name="contact_email" type="email" value="<?= $g('contact_email', CONTACT_EMAIL) ?>"></div>
  </div>

  <h2 style="margin-top:28px">AI (Groq)</h2>
  <div class="fgrid">
    <div class="field"><label for="groq_api_key">Groq API key</label><input id="groq_api_key" name="groq_api_key" autocomplete="off" value="<?= e(masked(aiKey())) ?>" placeholder="gsk_…"><small>From console.groq.com → API Keys.</small></div>
    <div class="field"><label for="groq_model">Model</label><select id="groq_model" name="groq_model"><option value="">Automatic (recommended)</option><?php foreach ($models as $m): ?><option<?= $m === get_setting('groq_model') ? ' selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="ai_monthly_limit">Monthly AI call limit</label><input id="ai_monthly_limit" name="ai_monthly_limit" type="number" min="0" value="<?= $g('ai_monthly_limit', '3000') ?>"><small>Used this month: <?= aiUsedThisMonth() ?></small></div>
    <label class="check"><input type="checkbox" name="ai_enabled" value="1"<?= get_setting('ai_enabled', '1') === '1' ? ' checked' : '' ?>> <span>Let AI help read messy chat messages and write descriptions. The chat still works without it.</span></label>
  </div>

  <h2 style="margin-top:28px">WhatsApp bot (Meta Cloud API)</h2>
  <div class="fgrid">
    <div class="field"><label for="wa_phone_id">Phone number ID</label><input id="wa_phone_id" name="wa_phone_id" value="<?= $g('wa_phone_id') ?>"></div>
    <div class="field"><label for="wa_token">Access token</label><input id="wa_token" name="wa_token" autocomplete="off" value="<?= e(masked(get_setting('wa_token'))) ?>"></div>
    <div class="field"><label for="wa_verify_token">Verify token (you make this up)</label><input id="wa_verify_token" name="wa_verify_token" value="<?= $g('wa_verify_token') ?>"><small>Callback URL: <?= e(APP_URL) ?>/webhook.php</small></div>
  </div>

  <h2 style="margin-top:28px">Google &amp; Meta tracking</h2>
  <div class="fgrid">
    <div class="field"><label for="ga4_id">Google Analytics 4 ID</label><input id="ga4_id" name="ga4_id" value="<?= $g('ga4_id') ?>" placeholder="G-XXXXXXXXXX"></div>
    <div class="field"><label for="google_site_verification">Search Console verification code</label><input id="google_site_verification" name="google_site_verification" value="<?= $g('google_site_verification') ?>"></div>
    <div class="field"><label for="meta_pixel_id">Meta Pixel ID</label><input id="meta_pixel_id" name="meta_pixel_id" value="<?= $g('meta_pixel_id') ?>"></div>
  </div>
  <p style="margin-top:20px" class="inline-form">
    <button class="btn btn-primary" name="action" value="save"><i class="fa-solid fa-floppy-disk"></i> Save settings</button>
    <button class="btn btn-ghost" name="action" value="test_ai">Test AI</button>
    <button class="btn btn-ghost" name="action" value="test_email">Send test email</button>
  </p>
</form>

<form method="post" class="card glass">
  <?= csrfField() ?><input type="hidden" name="action" value="password">
  <h2>Change my password</h2>
  <div class="fgrid">
    <div class="field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="field"><label for="new_password">New password (10+ characters)</label><input id="new_password" name="new_password" type="password" required minlength="10" autocomplete="new-password"></div>
  </div>
  <p style="margin-top:16px"><button class="btn btn-dark">Change password</button></p>
</form>
<?php adminFoot();
