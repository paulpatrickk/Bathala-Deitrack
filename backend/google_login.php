<?php
// ─── GOOGLE OAUTH CONFIG ──────────────────────────────
define('GOOGLE_CLIENT_ID',    '543500889849-a18pimv0kq4h5dlu3h07scnllsnmp5km.apps.googleusercontent.com');
define('GOOGLE_REDIRECT_URI', 'http://localhost/Bathala/backend/google_callback.php');

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
