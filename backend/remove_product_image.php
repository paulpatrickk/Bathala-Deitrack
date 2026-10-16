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

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['product_id'] ?? 0);
if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }

$q = $conn->prepare("SELECT image FROM products WHERE id = ?");
$q->bind_param("i", $id);
$q->execute();
$row = $q->get_result()->fetch_assoc();
delete_uploaded_image($row['image'] ?? null, __DIR__ . '/../assets/images/products');

$u = $conn->prepare("UPDATE products SET image = NULL WHERE id = ?");
$u->bind_param("i", $id);
$u->execute();
echo json_encode(['success' => true]);
