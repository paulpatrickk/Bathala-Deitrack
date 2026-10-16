<?php
// ─── GOOGLE OAUTH CONFIG (single source of truth) ─────────────────
// Included by google_login.php and google_callback.php so the redirect URI
// can never drift between them — mismatched values are the #1 cause of
// "Error 400: redirect_uri_mismatch".

// The ID and secret themselves live in config/secrets.php.
$__google = is_file(__DIR__ . '/../config/secrets.php') ? require __DIR__ . '/../config/secrets.php' : [];
define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID')     ?: ($__google['GOOGLE_CLIENT_ID']     ?? ''));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: ($__google['GOOGLE_CLIENT_SECRET'] ?? ''));

// The redirect URI is built from the request itself, so the SAME code works
// on localhost (http://localhost/Bathala/backend/...) and on the live host
// (https://deitrack.byethost7.com/backend/...) with no editing.

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

$scheme = $isHttps ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Directory of the currently running script, relative to the web root:
// "/backend" when hosted at the root, "/Bathala/backend" under XAMPP.
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/backend/x.php')), '/');

define('GOOGLE_REDIRECT_URI', $scheme . '://' . $host . $dir . '/google_callback.php');

// ──────────────────────────────────────────────────────────────────
// Register BOTH of these in Google Cloud Console → Clients → your OAuth
// client → Authorized redirect URIs (exact match, no trailing slash):
//
//   https://deitrack.byethost7.com/backend/google_callback.php
//   http://localhost/Bathala/backend/google_callback.php
//
// And under "Authorized JavaScript origins":
//
//   https://deitrack.byethost7.com
//   http://localhost
// ──────────────────────────────────────────────────────────────────
?>
