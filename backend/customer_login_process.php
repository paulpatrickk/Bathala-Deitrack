<?php
session_start();

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $customer = $result->fetch_assoc();
    $stored_password = $customer['password'];
    $valid = false;

    if (password_verify($password, $stored_password)) {
        $valid = true;
    } elseif (strpos($stored_password, '$2y$') !== 0 && hash_equals($stored_password, $password)) {
        // Legacy account created before passwords were hashed.
        // It checks out, so upgrade it to a proper hash right now.
        $valid = true;
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $upd->bind_param("si", $new_hash, $customer['id']);
        $upd->execute();
        $customer['password'] = $new_hash;
    }

    if ($valid) {
        session_regenerate_id(true);
        $_SESSION['customer'] = $customer;
        // No address yet (signed up but skipped step 2) -> finish profile first
        if (empty($customer['home_address']) || empty($customer['phone'])) {
            header("Location: /customer/complete_profile.php");
        } else {
            header("Location: /customer/dashboard.php");
        }
        exit;
    }
}

$_SESSION['error'] = "Wrong email or password. Please try again.";
header("Location: /customer_login.php");
exit;
?>
