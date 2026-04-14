<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /Bathala/LandingPage/login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$id          = $_POST['id'];
$name        = $_POST['name'];
$category    = $_POST['category'];
$price       = $_POST['price'];
$stock       = $_POST['stock'];
$description = $_POST['description'] ?? '';

// Handle image upload
if (!empty($_FILES['image']['name'])) {
    $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $target   = $_SERVER['DOCUMENT_ROOT'] . '/Bathala/assets/images/products/' . $filename;

    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Delete old image
        $old = $conn->query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
        if (!empty($old['image'])) {
            $old_path = $_SERVER['DOCUMENT_ROOT'] . '/Bathala/assets/images/products/' . $old['image'];
            if (file_exists($old_path)) unlink($old_path);
        }

        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, description=?, image=? WHERE id=?");
        $stmt->bind_param("ssdissi", $name, $category, $price, $stock, $description, $filename, $id);
    }
} else {
    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=?, description=? WHERE id=?");
    $stmt->bind_param("ssdisi", $name, $category, $price, $stock, $description, $id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "Product updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update product.";
}

header("Location: /Bathala/dashboard/inventory.php");
exit;
?>
