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
$rider_id = $_SESSION['user']['id'];
$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$phone    = trim($data['phone'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Name and email required']);
    exit;
}

if ($password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, password=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $hashed, $rider_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $phone, $rider_id);
}

if ($stmt->execute()) {
    $_SESSION['user']['name']  = $name;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['phone'] = $phone;
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
