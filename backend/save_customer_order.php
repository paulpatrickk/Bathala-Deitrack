<?php
// save_customer_order.php — orders placed by customers from their portal
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/order_lib.php';

// Read the customer fresh from the DB (the session copy may be out of date)
$q = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$q->bind_param("i", $_SESSION['customer']['id']);
$q->execute();
$customer = $q->get_result()->fetch_assoc();
if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Account not found. Please log in again.']);
    exit;
}

if (empty($customer['home_address']) || empty($customer['municipality'])) {
    echo json_encode(['success' => false, 'message' => 'Please add your delivery address first (Profile page).']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$address = implode(', ', array_filter([
    trim($customer['home_address'] ?? ''),
    trim($customer['barangay'] ?? ''),
    trim(($customer['municipality'] ?? '') . ' ' . ($customer['zip_code'] ?? '')),
]));

[$ok, $result] = place_order($conn, $data['items'] ?? [], [
    'order_type'       => 'delivery',
    'status'           => 'pending',
    'customer_id'      => (int)$customer['id'],
    'customer_name'    => $customer['name'],
    'customer_address' => $address,
    'customer_phone'   => $customer['phone'] ?? '',
    'delivery_lat'     => $customer['latitude']  !== null ? (float)$customer['latitude']  : null,
    'delivery_lng'     => $customer['longitude'] !== null ? (float)$customer['longitude'] : null,
    'tracking_token'   => bin2hex(random_bytes(16)),
]);

if (!$ok) {
    echo json_encode(['success' => false, 'message' => $result]);
    exit;
}

echo json_encode(['success' => true, 'order_id' => $result['order_id'], 'total' => $result['total']]);
