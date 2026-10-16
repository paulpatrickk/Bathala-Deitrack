<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) die("Connection failed");

$id          = (int)($_POST['id'] ?? 0);
$name        = $_POST['name'];
$category    = trim($_POST['category']);
$price       = $_POST['price'];
$stock       = $_POST['stock'];
$description = $_POST['description'] ?? '';

$reorder_threshold = isset($_POST['reorder_threshold']) && $_POST['reorder_threshold'] !== ''
    ? max(0, (int)$_POST['reorder_threshold'])
    : 10;

// Handle image upload
if (!empty($_FILES['image']['name'])) {
    require_once __DIR__ . '/upload_lib.php';
    $imgDir = __DIR__ . '/../assets/images/products';
    [$ok, $filename] = save_uploaded_image($_FILES['image'], $imgDir, 'product');
    if (!$ok) {
        $_SESSION['error'] = $filename;
        header("Location: /dashboard/inventory.php");
        exit;
    }

    // Delete old image
    $q = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $q->bind_param("i", $id);
    $q->execute();
    $old = $q->get_result()->fetch_assoc();
    delete_uploaded_image($old['image'] ?? null, $imgDir);

    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, description=?, image=?, reorder_threshold=? WHERE id=?");
    $stmt->bind_param("ssdissii", $name, $category, $price, $stock, $description, $filename, $reorder_threshold, $id);
} else {
    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, description=?, reorder_threshold=? WHERE id=?");
    $stmt->bind_param("ssdisii", $name, $category, $price, $stock, $description, $reorder_threshold, $id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Product updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update product.";
}

header("Location: /dashboard/inventory.php");
exit;
?>