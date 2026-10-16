<?php
session_start();
header('Content-Type: application/json');

$allowed_roles = ['admin', 'staff', 'warehouse'];
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles, true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/upload_lib.php';

$product_id = intval($_POST['product_id'] ?? 0);
if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$imgDir = __DIR__ . '/../assets/images/products';
[$ok, $result] = save_uploaded_image($_FILES['image'] ?? [], $imgDir, 'product_' . $product_id);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => $result]);
    exit;
}
$filename = $result;

// Delete old image if exists
$q = $conn->prepare("SELECT image FROM products WHERE id = ?");
$q->bind_param("i", $product_id);
$q->execute();
$old = $q->get_result()->fetch_assoc();
delete_uploaded_image($old['image'] ?? null, $imgDir);

$u = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
$u->bind_param("si", $filename, $product_id);
$u->execute();

echo json_encode(['success' => true, 'filename' => $filename]);
