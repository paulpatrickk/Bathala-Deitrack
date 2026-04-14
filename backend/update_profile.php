<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}

$customer = $_SESSION['customer'];
$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$name         = $_POST['name'];
$phone        = $_POST['phone'];
$home_address = $_POST['home_address'];
$barangay     = $_POST['barangay'];
$municipality = $_POST['municipality'];
$zip_code     = $_POST['zip_code'];
$new_password = $_POST['new_password'];
$confirm_pass = $_POST['confirm_password'];


if (!empty($new_password)) {
    if ($new_password !== $confirm_pass) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: /Bathala/customer/profile.php");
        exit;
    }
    $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, home_address=?, barangay=?, municipality=?, zip_code=?, password=? WHERE id=?");
    $stmt->bind_param("sssssssi", $name, $phone, $home_address, $barangay, $municipality, $zip_code, $new_password, $customer['id']);
} else {
    $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, home_address=?, barangay=?, municipality=?, zip_code=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $phone, $home_address, $barangay, $municipality, $zip_code, $customer['id']);
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

header("Location: /Bathala/customer/profile.php");
exit;
?>
