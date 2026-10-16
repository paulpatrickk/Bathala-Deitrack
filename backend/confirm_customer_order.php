<?php
// confirm_customer_order.php
// Staff/Admin reviews a customer-placed order:
//   action=confirm -> status becomes 'confirmed', now visible to riders
//   action=decline  -> status becomes 'cancelled', stock is restored
session_start();
header('Content-Type: application/json');

$allowed_roles = ['admin', 'staff', 'warehouse'];
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles)) {
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
$action   = $data['action'] ?? '';
$staff_id = $_SESSION['user']['id'] ?? null;

if (!$order_id || !in_array($action, ['confirm', 'decline'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($action === 'confirm') {
    $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'confirmed', confirmed_by = ?, confirmed_at = NOW()
        WHERE id = ? AND status = 'pending' AND order_type = 'delivery'
    ");
    $stmt->bind_param("ii", $staff_id, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'action' => 'confirmed', 'order_id' => $order_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order already handled or not found']);
    }
} else {
    // Decline: cancel the order and give the stock back
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'cancelled', confirmed_by = ?, confirmed_at = NOW()
        WHERE id = ? AND status = 'pending' AND order_type = 'delivery'
    ");
    $stmt->bind_param("ii", $staff_id, $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = " . (int)$order_id);
        if ($items) {
            while ($item = $items->fetch_assoc()) {
                $conn->query("UPDATE products SET stock = stock + " . (int)$item['quantity'] . " WHERE id = " . (int)$item['product_id']);
            }
        }
        echo json_encode(['success' => true, 'action' => 'declined', 'order_id' => $order_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order already handled or not found']);
    }
}
?>
