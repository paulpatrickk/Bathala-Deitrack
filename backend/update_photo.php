<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}

$customer = $_SESSION['customer'];
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/upload_lib.php';

if (!empty($_FILES['photo']['name'])) {
    $imgDir = __DIR__ . '/../assets/images/customers';
    [$ok, $result] = save_uploaded_image($_FILES['photo'], $imgDir, 'customer_' . (int)$customer['id'], 5 * 1024 * 1024);

    if ($ok) {
        $stmt = $conn->prepare("UPDATE customers SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $result, $customer['id']);
        $stmt->execute();

        // Remove the previous photo (only if it was an uploaded file, not a Google URL)
        delete_uploaded_image($customer['photo'] ?? null, $imgDir);

        // Refresh session
        $refresh = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $refresh->bind_param("i", $customer['id']);
        $refresh->execute();
        $_SESSION['customer'] = $refresh->get_result()->fetch_assoc();

        $_SESSION['success'] = "Profile photo updated!";
    } else {
        $_SESSION['error'] = $result;
    }
}

header("Location: /customer/profile.php");
exit;
