<?php
// ─── GOOGLE OAUTH CONFIG ──────────────────────────────
require_once __DIR__ . '/google_config.php';

$params = [
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'access_type'   => 'online',
];

$url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
header("Location: $url");
exit;
?>
