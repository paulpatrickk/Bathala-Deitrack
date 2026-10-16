<?php
// save_order.php — orders placed by staff at the POS
session_start();
header('Content-Type: application/json');

$allowed_roles = ['admin', 'staff'];
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/order_lib.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$order_type = $data['order_type'] ?? '';
if (!in_array($order_type, ['walkin', 'delivery'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order type']);
    exit;
}

$delivery_lat = (isset($data['delivery_lat']) && $data['delivery_lat'] !== '' && $data['delivery_lat'] !== null)
                    ? floatval($data['delivery_lat']) : null;
$delivery_lng = (isset($data['delivery_lng']) && $data['delivery_lng'] !== '' && $data['delivery_lng'] !== null)
                    ? floatval($data['delivery_lng']) : null;

// Staff placed this order directly at POS, so it doesn't need the
// customer-order review step — it goes straight to 'confirmed' and is
// immediately visible to riders. (Only orders placed by customers
// themselves via save_customer_order.php start at 'pending'.)
$status = $order_type === 'walkin' ? 'paid' : 'confirmed';

// Delivery orders get a public tracking token so a share-link can be sent to the customer
$tracking_token = $order_type === 'delivery' ? bin2hex(random_bytes(16)) : null;

[$ok, $result] = place_order($conn, $data['items'] ?? [], [
    'order_type'        => $order_type,
    'status'            => $status,
    'customer_name'     => mb_substr(trim((string)($data['customer_name'] ?? '')), 0, 255),
    'customer_address'  => trim((string)($data['customer_address'] ?? '')),
    'customer_phone'    => mb_substr(trim((string)($data['customer_phone'] ?? '')), 0, 20),
    'customer_landmark' => mb_substr(trim((string)($data['customer_landmark'] ?? '')), 0, 255),
    'delivery_lat'      => $delivery_lat,
    'delivery_lng'      => $delivery_lng,
    'tracking_token'    => $tracking_token,
]);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => $result]);
    exit;
}

echo json_encode([
    'success'        => true,
    'order_id'       => $result['order_id'],
    'total'          => $result['total'],
    'tracking_token' => $tracking_token
]);
