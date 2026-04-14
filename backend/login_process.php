<?php
session_start();

$conn = new mysqli("localhost", "root", "", "smartpos");

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $_SESSION['user'] = $user; // Store entire user data in session for later use

    if ($user['role'] == 'admin') {
        header("Location: ../dashboard/admin.php");
        exit;
    } elseif ($user['role'] == 'staff') {
        header("Location: ../dashboard/pos.php");
        exit;
    } elseif ($user['role'] == 'warehouse') {
        header("Location: ../dashboard/inventory.php");
        exit;
    } elseif ($user['role'] == 'rider') {
        header("Location: ../dashboard/delivery.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Wrong email or password. Please try again.";
    header("Location: ../LandingPage/login.php");
    exit;
}
?>