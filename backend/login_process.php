<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Get user by email only
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {

        session_regenerate_id(true);
        unset($user['password']);
        $_SESSION['user'] = $user;

        if ($user['role'] == 'admin') {
            header("Location: ../dashboard/admin.php");
        } elseif ($user['role'] == 'staff') {
            header("Location: ../dashboard/pos.php");
        } elseif ($user['role'] == 'warehouse') {
            header("Location: ../dashboard/inventory.php");
        } elseif ($user['role'] == 'rider') {
            header("Location: ../dashboard/delivery.php");
        }
        exit;

    } else {
        $_SESSION['error'] = "Wrong password.";
    }

} else {
    $_SESSION['error'] = "User not found.";
}

header("Location: ../login.php");
exit;
?>