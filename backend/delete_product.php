<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /Bathala/LandingPage/login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$id = $_POST['id'];

// Delete image file first
$product = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
if (!empty($product['image'])) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/Bathala/assets/images/products/' . $product['image'];
    if (file_exists($path)) unlink($path);
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Product deleted successfully!";
} else {
    $_SESSION['error'] = "Failed to delete product.";
}

header("Location: /Bathala/dashboard/inventory.php");
exit;
?>
