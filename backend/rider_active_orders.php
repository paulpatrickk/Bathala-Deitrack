<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'rider') {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) { echo json_encode([]); exit; }

$rider_id = $_SESSION['user']['id'];

// Show: orders assigned to this rider OR unassigned pending delivery orders
$result = $conn->query("
    SELECT o.*,
           GROUP_CONCAT(CONCAT(p.name, ' × ', oi.quantity) SEPARATOR ', ') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE (
        (o.rider_id = $rider_id AND o.status IN ('assigned', 'in_transit'))
        OR
        (o.rider_id IS NULL AND o.status = 'confirmed' AND o.order_type = 'delivery')
    )
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
echo json_encode($rows);
?>