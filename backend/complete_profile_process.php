<?php
session_start();

if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}

$customer = $_SESSION['customer'];
require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$name         = trim($_POST['name']);
$phone        = trim($_POST['phone']);
$home_address = trim($_POST['home_address']);
$region       = trim($_POST['region'] ?? '');
$province     = trim($_POST['province'] ?? '');
$barangay     = trim($_POST['barangay']);
$municipality = trim($_POST['municipality']);
$zip_code     = trim($_POST['zip_code']);
$latitude     = ($_POST['latitude'] !== '') ? (float)$_POST['latitude'] : null;
$longitude    = ($_POST['longitude'] !== '') ? (float)$_POST['longitude'] : null;
$id           = $customer['id'];

// Validate
if (!$name || !$phone || !$home_address || !$region || !$province || !$barangay || !$municipality || !$zip_code || $latitude === null || $longitude === null) {
    $_SESSION['error'] = "Please fill in all required fields, including your map pin.";
    header("Location: /customer/complete_profile.php");
    exit;
}

// Update customer profile
$stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, home_address=?, region=?, province=?, barangay=?, municipality=?, zip_code=?, latitude=?, longitude=? WHERE id=?");
$stmt->bind_param("ssssssssddi", $name, $phone, $home_address, $region, $province, $barangay, $municipality, $zip_code, $latitude, $longitude, $id);

if ($stmt->execute()) {
    // Refresh session with updated data
    $refresh = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $refresh->bind_param("i", $id);
    $refresh->execute();
    $_SESSION['customer'] = $refresh->get_result()->fetch_assoc();

    // Redirect to dashboard
    header("Location: /customer/dashboard.php");
    exit;
} else {
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: /customer/complete_profile.php");
    exit;
}
?>