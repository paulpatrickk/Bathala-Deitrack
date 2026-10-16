<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$name        = $_POST['name'];
$category    = trim($_POST['category']);
$price       = $_POST['price'];
$stock       = $_POST['stock'];
$description = $_POST['description'] ?? '';
$image       = '';

// Reorder threshold: default to 10 if left blank or invalid, same as the
// fallback used everywhere this used to be hardcoded.
$reorder_threshold = isset($_POST['reorder_threshold']) && $_POST['reorder_threshold'] !== ''
    ? max(0, (int)$_POST['reorder_threshold'])
    : 10;

// Handle image upload
if (!empty($_FILES['image']['name'])) {
    require_once __DIR__ . '/upload_lib.php';
    [$ok, $result] = save_uploaded_image($_FILES['image'], __DIR__ . '/../assets/images/products', 'product');
    if (!$ok) {
        $_SESSION['error'] = $result;
        header("Location: /dashboard/inventory.php");
        exit;
    }
    $image = $result;
}

$stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, description, image, reorder_threshold) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdissi", $name, $category, $price, $stock, $description, $image, $reorder_threshold);

if ($stmt->execute()) {
    $_SESSION['success'] = "Product '$name' added successfully!";
} else {
    $_SESSION['error'] = "Failed to add product.";
}

header("Location: /dashboard/inventory.php");
exit;
?>