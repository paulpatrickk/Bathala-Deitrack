<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /login.php");
    exit;
}

$user = $_SESSION['user'];
require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$id       = (int)$_POST['id'];
$quantity = (int)$_POST['quantity'];
$notes    = $_POST['notes'] ?? '';
$by       = $user['name'] ?? $user['email'];

if ($quantity <= 0) {
    $_SESSION['error'] = "Quantity must be greater than 0.";
    header("Location: /dashboard/inventory.php");
    exit;
}

// Get current stock
$result = $conn->query("SELECT stock, name FROM products WHERE id = $id");
$product = $result->fetch_assoc();
$prev_stock = $product['stock'];
$new_stock  = $prev_stock + $quantity;

// Update stock
$conn->query("UPDATE products SET stock = $new_stock WHERE id = $id");

// Log to stock history
$stmt = $conn->prepare("INSERT INTO stock_history (product_id, action, quantity, previous_stock, new_stock, notes, created_by) VALUES (?, 'restock', ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiss", $id, $quantity, $prev_stock, $new_stock, $notes, $by);
$stmt->execute();

$_SESSION['success'] = "Restocked '{$product['name']}' by +$quantity units. New stock: $new_stock";
header("Location: /dashboard/inventory.php");
exit;
?>
