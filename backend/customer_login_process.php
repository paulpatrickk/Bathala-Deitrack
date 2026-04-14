<?php
session_start();

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    $_SESSION['customer'] = $customer;
    header("Location: /Bathala/customer/dashboard.php");
    exit;
} else {
    $_SESSION['error'] = "Wrong email or password. Please try again.";
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}
?>
