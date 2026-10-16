<?php
// customer/track.php?id=123
// The "Track" button on My Orders lands here. We check the order belongs
// to the logged-in customer, then send them to the live tracking page
// (track_order.php) with that order's private tracking link.
session_start();
if (!isset($_SESSION['customer'])) {
    header("Location: /customer_login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$order_id    = (int)($_GET['id'] ?? 0);
$customer_id = (int)$_SESSION['customer']['id'];

$q = $conn->prepare("SELECT id, tracking_token FROM orders WHERE id = ? AND customer_id = ? AND order_type = 'delivery'");
$q->bind_param("ii", $order_id, $customer_id);
$q->execute();
$order = $q->get_result()->fetch_assoc();

if (!$order) {
    header("Location: /customer/orders.php");
    exit;
}

// Older orders were saved without a tracking link — create one now.
$token = $order['tracking_token'];
if (!$token) {
    $token = bin2hex(random_bytes(16));
    $u = $conn->prepare("UPDATE orders SET tracking_token = ? WHERE id = ? AND tracking_token IS NULL");
    $u->bind_param("si", $token, $order_id);
    $u->execute();
}

header("Location: /customer/track_order.php?id=" . $order_id . "&token=" . urlencode($token));
exit;
