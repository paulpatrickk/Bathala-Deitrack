<?php
// ─── TEMPORARY: show real errors instead of blank 500 ──
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ─────────────────────────────────────────────────────

session_start();

// ─── GOOGLE OAUTH CONFIG ──────────────────────────────
require_once __DIR__ . '/google_config.php';

// ─── DB CONNECTION ────────────────────────────────────
require_once __DIR__ . '/../config/db.php';

// ─── STEP 1: GET ACCESS TOKEN ─────────────────────────
$code = $_GET['code'] ?? '';

if (!$code) {
    $_SESSION['error'] = "Google login failed. Please try again.";
    header("Location: /customer_login.php");
    exit;
}

$token_url = "https://oauth2.googleapis.com/token";
$token_data = [
    'code'          => $code,
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$token_response = curl_exec($ch);
curl_close($ch);

$token = json_decode($token_response, true);
$access_token = $token['access_token'] ?? '';

if (!$access_token) {
    $_SESSION['error'] = "Failed to get Google access token.";
    header("Location: /customer_login.php");
    exit;
}

// ─── STEP 2: GET USER INFO ────────────────────────────
$user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";
$ch = curl_init($user_info_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$user_response = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($user_response, true);

$google_id = $google_user['id'] ?? '';
$name      = $google_user['name'] ?? '';
$email     = $google_user['email'] ?? '';

if (!$email) {
    $_SESSION['error'] = "Could not get email from Google.";
    header("Location: /customer_login.php");
    exit;
}

// ─── STEP 3: CHECK IF CUSTOMER EXISTS ─────────────────
$stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Existing customer — log them in
    $customer = $result->fetch_assoc();
    session_regenerate_id(true);
    $_SESSION['customer'] = $customer;

    // Check if profile is complete
    if (empty($customer['home_address']) || empty($customer['phone'])) {
        header("Location: /customer/complete_profile.php");
    } else {
        header("Location: /customer/dashboard.php");
    }
    exit;
}
else {
    // New customer — auto register with Google info
    $password = password_hash($google_id, PASSWORD_DEFAULT); // dummy password
    $stmt = $conn->prepare("INSERT INTO customers (name, email, password, google_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $google_id);
    $stmt->execute();

    $new_id = $stmt->insert_id;

    // Fetch the new customer
    $stmt2 = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt2->bind_param("i", $new_id);
    $stmt2->execute();
    $customer = $stmt2->get_result()->fetch_assoc();

    session_regenerate_id(true);
    $_SESSION['customer'] = $customer;

    // Redirect to complete profile if address is missing
    header("Location: /customer/complete_profile.php");
    exit;
}
?>
