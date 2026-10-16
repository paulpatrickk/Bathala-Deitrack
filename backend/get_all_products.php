<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($conn->connect_error) {
    echo json_encode(array('error' => $conn->connect_error));
    exit;
}

$res = $conn->query("SELECT id, name, category, image FROM products ORDER BY category, name");

if (!$res) {
    echo json_encode(array('error' => $conn->error));
    exit;
}

$out = array();
while ($row = $res->fetch_assoc()) {
    $out[] = array(
        'id'       => $row['id'],
        'name'     => $row['name'],
        'category' => $row['category'],
        'image'    => $row['image']
    );
}

echo json_encode($out);