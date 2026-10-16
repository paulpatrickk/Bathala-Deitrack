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

$rider_id = $_SESSION['user']['id'];
$order_id = (int)($_POST['order_id'] ?? 0);
$notes    = trim($_POST['notes'] ?? '');
$lat      = $_POST['lat'] ?? null;
$lng      = $_POST['lng'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit;
}

// Require photo
if (empty($_FILES['photo']['name'])) {
    echo json_encode(['success' => false, 'message' => 'Proof photo is required']);
    exit;
}

require_once __DIR__ . '/upload_lib.php';
[$ok, $result] = save_uploaded_image($_FILES['photo'], __DIR__ . '/../assets/images/deliveries', 'delivery_' . $order_id, 20 * 1024 * 1024);
if (!$ok) {
    echo json_encode(['success' => false, 'message' => $result]);
    exit;
}
$photo_filename = $result;

// Mark delivered
$stmt = $conn->prepare("
    UPDATE orders SET
        status         = 'delivered',
        delivery_photo = ?,
        delivery_notes = ?,
        delivery_lat   = ?,
        delivery_lng   = ?,
        delivered_at   = NOW()
    WHERE id = ? AND rider_id = ? AND status = 'in_transit'
");
$stmt->bind_param("sssdii", $photo_filename, $notes, $lat, $lng, $order_id, $rider_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    // Return customer contact so rider can notify them
    $q = $conn->prepare("SELECT customer_name, customer_phone, customer_address, total FROM orders WHERE id = ?");
    $q->bind_param("i", $order_id);
    $q->execute();
    $info = $q->get_result()->fetch_assoc();
    echo json_encode([
        'success'          => true,
        'order_id'         => $order_id,
        'customer_name'    => $info['customer_name']    ?? '',
        'customer_phone'   => $info['customer_phone']   ?? '',
        'customer_address' => $info['customer_address'] ?? '',
        'total'            => $info['total']            ?? 0,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not confirm. Order may not be in transit or is not yours.']);
}
?>