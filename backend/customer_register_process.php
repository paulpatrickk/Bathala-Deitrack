<?php
session_start();

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$name         = $_POST['name'];
$email        = $_POST['email'];
$phone        = $_POST['phone'];
$home_address = $_POST['home_address'];
$barangay     = $_POST['barangay'];
$municipality = $_POST['municipality'];
$zip_code     = $_POST['zip_code'];
$password     = $_POST['password'];


// Check if email already exists
$check = $conn->prepare("SELECT id FROM customers WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "Email already registered. Please sign in.";
    header("Location: /Bathala/LandingPage/customer_login.php?tab=register");
    exit;
}

// Insert new customer
$stmt = $conn->prepare("INSERT INTO customers (name, email, password, phone, home_address, barangay, municipality, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssss", $name, $email, $password, $phone, $home_address, $barangay, $municipality, $zip_code);

if ($stmt->execute()) {
    $_SESSION['success'] = "Account created! You can now sign in.";
    header("Location: /Bathala/LandingPage/customer_login.php?tab=login");
    exit;
} else {
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: /Bathala/LandingPage/customer_login.php?tab=register");
    exit;
}
?>
