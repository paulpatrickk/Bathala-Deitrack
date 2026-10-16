<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) { echo json_encode(['success' => false]); exit; }

$data     = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($data['order_id'] ?? 0);
$action   = $data['action'] ?? ''; // 'accept' or 'reject'
$rider_id = $_SESSION['user']['id'];

if (!$order_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($action === 'accept') {
    // Assign rider and set status to assigned
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'assigned', rider_id = ?
        WHERE id = ? AND rider_id IS NULL AND status = 'confirmed'
    ");
    $stmt->bind_param("ii", $rider_id, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'action' => 'accepted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order already taken']);
    }
} else {
    // Reject: just ignore (don't assign), return success so UI can hide card
    echo json_encode(['success' => true, 'action' => 'rejected']);
}
