<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /Bathala/LandingPage/login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

$name        = $_POST['name'];
$category    = $_POST['category'];
$price       = $_POST['price'];
$stock       = $_POST['stock'];
$description = $_POST['description'] ?? '';
$image       = '';

// Handle image upload
if (!empty($_FILES['image']['name'])) {
    $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $target   = $_SERVER['DOCUMENT_ROOT'] . '/Bathala/assets/images/products/' . $filename;

    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0777, true);
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $image = $filename;
    }
}

$stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, description, image) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdiis", $name, $category, $price, $stock, $description, $image);

if ($stmt->execute()) {
    $_SESSION['success'] = "Product '$name' added successfully!";
} else {
    $_SESSION['error'] = "Failed to add product.";
}

header("Location: /Bathala/dashboard/inventory.php");
exit;
?>
