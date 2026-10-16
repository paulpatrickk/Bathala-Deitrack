<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}

$customer = $_SESSION['customer'];
require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$name         = $_POST['name'];
$phone        = $_POST['phone'];
$home_address = $_POST['home_address'];
$region       = $_POST['region'] ?? '';
$province     = $_POST['province'] ?? '';
$barangay     = $_POST['barangay'];
$municipality = $_POST['municipality'];
$zip_code     = $_POST['zip_code'];
$latitude     = ($_POST['latitude'] !== '') ? (float)$_POST['latitude'] : null;
$longitude    = ($_POST['longitude'] !== '') ? (float)$_POST['longitude'] : null;
$new_password = $_POST['new_password'];
$confirm_pass = $_POST['confirm_password'];

// Region/Province/Municipality/Barangay come from fixed PSGC dropdown lists
// (see assets/data/*.json) -- the customer can only pick a listed value,
// never type an arbitrary one, so no free-text validation is needed here.

if (!empty($new_password)) {
    if ($new_password !== $confirm_pass) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: /customer/profile.php");
        exit;
    }
    $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, home_address=?, region=?, province=?, barangay=?, municipality=?, zip_code=?, latitude=?, longitude=?, password=? WHERE id=?");
    $stmt->bind_param("ssssssssddsi", $name, $phone, $home_address, $region, $province, $barangay, $municipality, $zip_code, $latitude, $longitude, $new_password, $customer['id']);
} else {
    $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, home_address=?, region=?, province=?, barangay=?, municipality=?, zip_code=?, latitude=?, longitude=? WHERE id=?");
    $stmt->bind_param("ssssssssddi", $name, $phone, $home_address, $region, $province, $barangay, $municipality, $zip_code, $latitude, $longitude, $customer['id']);
}

if ($stmt->execute()) {
    
    $refresh = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $refresh->bind_param("i", $customer['id']);
    $refresh->execute();
    $_SESSION['customer'] = $refresh->get_result()->fetch_assoc();

    $_SESSION['success'] = "Profile updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update profile.";
}

header("Location: /customer/profile.php");
exit;
?>
