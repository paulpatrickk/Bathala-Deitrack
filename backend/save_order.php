<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$order_type     = $data['order_type'];
$total          = $data['total'];
$items          = $data['items'];
$customer_name  = $data['customer_name'] ?? '';
$customer_phone = $data['customer_phone'] ?? '';
$customer_address = $data['customer_address'] ?? '';
$status = $order_type === 'walkin' ? 'paid' : 'pending';

// Insert order
$stmt = $conn->prepare("INSERT INTO orders (order_type, total, status, customer_name, customer_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sdssss", $order_type, $total, $status, $customer_name, $customer_address, $customer_phone);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save order']);
    exit;
}

$order_id = $stmt->insert_id;

// Insert order items + deduct stock
foreach ($items as $item) {
    $product_id = $item['id'];
    $qty        = $item['qty'];
    $price      = $item['price'];

    // Insert order item
    $s = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $s->bind_param("iiid", $order_id, $product_id, $qty, $price);
    $s->execute();

    // Deduct stock
    $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $product_id");
}

echo json_encode(['success' => true, 'order_id' => $order_id]);
?>