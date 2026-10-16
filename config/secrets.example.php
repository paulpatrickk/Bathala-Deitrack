<?php
// ─── SECRETS TEMPLATE ────────────────────────────────────────────────
// Copy this file to config/secrets.php and fill in your own values.
// secrets.php is ignored by git — never commit real passwords.

return [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'smartpos',

    // Google Cloud Console → APIs & Services → Credentials → OAuth client
    'GOOGLE_CLIENT_ID'     => 'your-client-id.apps.googleusercontent.com',
    'GOOGLE_CLIENT_SECRET' => 'your-client-secret',
];
