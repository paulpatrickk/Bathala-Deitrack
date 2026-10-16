<?php
// get_pending_customer_orders.php
// Returns delivery orders placed by CUSTOMERS that are still awaiting staff
// confirmation. Staff-created POS delivery orders skip 'pending' entirely
// (see save_order.php), so any 'pending' delivery order here is, by
// definition, one a customer placed themselves.
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

$result = $conn->query("
    SELECT o.*,
           GROUP_CONCAT(CONCAT(p.name, ' × ', oi.quantity) SEPARATOR ', ') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.status = 'pending' AND o.order_type = 'delivery'
    GROUP BY o.id
    ORDER BY o.created_at ASC
");

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}

echo json_encode(['success' => true, 'orders' => $rows, 'count' => count($rows)]);
?>
