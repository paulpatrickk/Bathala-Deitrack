<?php
// Step 1 of sign-up: name, email, phone, password.
// The customer is logged in right away and sent to complete_profile.php
// to add their delivery address + map pin (same as Google sign-ups).
session_start();

require_once __DIR__ . '/../config/db.php';

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

function back_with_error($msg) {
    $_SESSION['error'] = $msg;
    header("Location: /customer_login.php?tab=register");
    exit;
}

if ($name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with_error("Please fill in your name, a valid email and your phone number.");
}
if (strlen($password) < 8) {
    back_with_error("Password must be at least 8 characters.");
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    back_with_error("Email already registered. Please sign in.");
}

// Insert new customer
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO customers (name, email, password, phone) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $hash, $phone);

if (!$stmt->execute()) {
    back_with_error("Something went wrong. Please try again.");
}

// Log them in and continue to the address step
$new_id = $stmt->insert_id;
$q = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$q->bind_param("i", $new_id);
$q->execute();

session_regenerate_id(true);
$_SESSION['customer'] = $q->get_result()->fetch_assoc();
$_SESSION['success']  = "Account created! Now add your delivery address.";
header("Location: /customer/complete_profile.php");
exit;
