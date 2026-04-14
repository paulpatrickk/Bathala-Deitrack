<?php
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /Bathala/LandingPage/customer_login.php");
    exit;
}

$customer = $_SESSION['customer'];
$conn = new mysqli("localhost", "root", "", "smartpos");

if (!empty($_FILES['photo']['name'])) {
    $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = 'customer_' . $customer['id'] . '_' . time() . '.' . $ext;
    $target   = $_SERVER['DOCUMENT_ROOT'] . '/Bathala/assets/images/customers/' . $filename;

    // Create directory if not exists
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
        $stmt = $conn->prepare("UPDATE customers SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $customer['id']);
        $stmt->execute();

        // Refresh session
        $refresh = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $refresh->bind_param("i", $customer['id']);
        $refresh->execute();
        $_SESSION['customer'] = $refresh->get_result()->fetch_assoc();

        $_SESSION['success'] = "Profile photo updated!";
    } else {
        $_SESSION['error'] = "Failed to upload photo.";
    }
}

header("Location: /Bathala/customer/profile.php");
exit;
?>
