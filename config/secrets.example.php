<?php
// Motor Parts Market secrets — copy to config/secrets.php and fill in.
// config/secrets.php is in .gitignore: NEVER commit it. Upload it once to the
// server with cPanel File Manager; git deploys leave it alone.
return [
    // Live database (cPanel → MySQL® Databases)
    'db_host' => 'localhost',
    'db_name' => 'CPANELUSER_mpm',
    'db_user' => 'CPANELUSER_mpm',
    'db_pass' => 'CHANGE_ME',

    // Local XAMPP database (used automatically on localhost; created for you)
    'local_db_name' => 'motorpartsmarket',
    'local_db_user' => 'root',
    'local_db_pass' => '',

    // Public address of the live site, no trailing slash
    'app_url' => 'https://motorpartsmarket.co.za',

    // Admin account: created on first page load if it doesn't exist yet.
    'admin_email'         => 'admin@swiftfusion.co.za',
    'admin_seed_password' => 'CHANGE_ME',

    // Locked out of admin? Set a new password here and load any page once.
    'admin_reset_password' => '',
    'admin_reset_emails'   => [],

    // Where customer enquiries are emailed
    'contact_email' => 'info@motorpartsmarket.co.za',

    // Mailbox that sends emails (cPanel → Email Accounts). Blank pass = PHP mail().
    'smtp_host' => 'mail.motorpartsmarket.co.za',
    'smtp_port' => 465,
    'smtp_user' => 'info@motorpartsmarket.co.za',
    'smtp_pass' => '',

    // Groq AI (console.groq.com → API Keys). Can also be set in Admin → Settings.
    'groq_api_key' => '',

    // Long random strings
    'app_secret' => 'make-a-long-random-string',
    'cron_key'   => 'make-a-random-string',
];
