<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($data['order_id'] ?? 0);
$status   = $data['status'] ?? '';
$rider_id = $_SESSION['user']['id'];

if ($status !== 'in_transit' || !$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Self-assign rider if not yet assigned, then set in_transit
$stmt = $conn->prepare("
    UPDATE orders
    SET status = 'in_transit', rider_id = ?
    WHERE id = ?
    AND (rider_id = ? OR rider_id IS NULL)
    AND status IN ('pending', 'assigned')
");
$stmt->bind_param("iii", $rider_id, $order_id, $rider_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order already taken or not found']);
}
?>