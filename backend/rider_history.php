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

$result = $conn->query("
    SELECT o.id, o.customer_name, o.total, o.delivered_at, o.created_at, o.delivery_photo,
           GROUP_CONCAT(CONCAT(p.name, ' ×', oi.quantity) SEPARATOR ', ') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.rider_id = $rider_id AND o.status = 'delivered'
    GROUP BY o.id
    ORDER BY o.delivered_at DESC
    LIMIT 50
");

$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;
echo json_encode($rows);
?>
