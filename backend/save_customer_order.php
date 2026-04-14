<?php
// save_customer_order.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$customer = $_SESSION['customer'];
$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$order_type   = 'delivery';
$total        = $data['total'];
$items        = $data['items'];
$status       = 'pending';
$name         = $customer['name'];
$phone        = $customer['phone'] ?? '';
$address      = ($customer['home_address'] ?? '') . ', ' .
                ($customer['barangay'] ?? '') . ', ' .
                ($customer['municipality'] ?? '') . ' ' .
                ($customer['zip_code'] ?? '');

// Insert order
$stmt = $conn->prepare("INSERT INTO orders (order_type, total, status, customer_name, customer_address, customer_phone) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sdssss", $order_type, $total, $status, $name, $address, $phone);

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

    $s = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $s->bind_param("iiid", $order_id, $product_id, $qty, $price);
    $s->execute();

    $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $product_id AND stock >= $qty");
}

echo json_encode(['success' => true, 'order_id' => $order_id]);
?>
