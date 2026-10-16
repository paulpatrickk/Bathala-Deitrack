<?php
// ─── DATABASE CONNECTION ───────────────────────────────
// EVERY file in this project connects through this ONE file.
// The actual passwords live in config/secrets.php (not in the code).
// Environment variables, if your host sets them, take priority.

$__secrets = is_file(__DIR__ . '/secrets.php') ? require __DIR__ . '/secrets.php' : [];

function app_secret($key, $default = '') {
    global $__secrets;
    $env = getenv($key);
    if ($env !== false && $env !== '') return $env;
    return $__secrets[$key] ?? $default;
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli(
    app_secret('DB_HOST', 'localhost'),
    app_secret('DB_USER', 'root'),
    app_secret('DB_PASS', ''),
    app_secret('DB_NAME', 'smartpos')
);

if ($conn->connect_error) {
    // Log the real reason for you; show visitors nothing sensitive.
    error_log('DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Service temporarily unavailable. Please try again later.');
}
$conn->set_charset('utf8mb4');
