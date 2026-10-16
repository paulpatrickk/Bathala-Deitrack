<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

require_once __DIR__ . '/upload_lib.php';

$id = (int)($_POST['id'] ?? 0);

// Delete image file first
$q = $conn->prepare("SELECT image FROM products WHERE id = ?");
$q->bind_param("i", $id);
$q->execute();
$product = $q->get_result()->fetch_assoc();
delete_uploaded_image($product['image'] ?? null, __DIR__ . '/../assets/images/products');

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Product deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete product.";
}

header("Location: /dashboard/inventory.php");
exit;
?>
