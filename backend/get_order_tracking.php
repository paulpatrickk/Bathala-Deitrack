<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$order_id = (int)($_GET['id'] ?? 0);
$token    = trim($_GET['token'] ?? '');

if (!$order_id || $token === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid link']);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        o.id, o.status, o.customer_name, o.customer_address, o.customer_landmark,
        o.total, o.delivery_lat, o.delivery_lng, o.created_at, o.delivered_at,
        o.rider_id,
        r.name  AS rider_name,
        r.phone AS rider_phone,
        rl.lat  AS rider_lat,
        rl.lng  AS rider_lng,
        rl.updated_at AS rider_loc_updated
    FROM orders o
    LEFT JOIN users r          ON r.id = o.rider_id
    LEFT JOIN rider_locations rl ON rl.rider_id = o.rider_id
    WHERE o.id = ? AND o.tracking_token = ?
    LIMIT 1
");
$stmt->bind_param("is", $order_id, $token);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found or link invalid']);
    exit;
}

$items = $conn->query("
    SELECT p.name, oi.quantity
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = " . (int)$order_id . "
")->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success'           => true,
    'order_id'          => $order['id'],
    'status'            => $order['status'],
    'customer_name'     => $order['customer_name'],
    'customer_address'  => $order['customer_address'],
    'customer_landmark' => $order['customer_landmark'],
    'total'             => $order['total'],
    'created_at'        => $order['created_at'],
    'delivered_at'      => $order['delivered_at'],
    'delivery_lat'      => $order['delivery_lat'],
    'delivery_lng'      => $order['delivery_lng'],
    'has_rider'         => !empty($order['rider_id']),
    'rider_name'        => $order['rider_name'],
    'rider_phone'       => $order['rider_phone'],
    'rider_lat'         => $order['rider_lat'],
    'rider_lng'         => $order['rider_lng'],
    'rider_loc_updated' => $order['rider_loc_updated'],
    'items'             => $items,
]);
?>
