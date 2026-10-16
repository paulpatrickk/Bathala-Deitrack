<?php
session_start();

// ─── AUTH CHECK ───────────────────────────────────────
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'staff'], true)) {
    header("Location: /login.php");
    exit;
}

$user = $_SESSION['user'];

// ─── DB CONNECTION ────────────────────────────────────
require_once __DIR__ . '/../config/db.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ─── FETCH PRODUCTS GROUPED BY CATEGORY ───────────────
$products_result = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY category ASC, name ASC");
$products = [];
$categories = [];

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        // Falls back to 10 for products saved before reorder_threshold existed.
        $t = $row['reorder_threshold'] ?? null;
        $row['_low_threshold'] = ($t !== null && $t !== '') ? (int)$t : 10;

        $products[] = $row;
        if (!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
    }
}

// ─── RENDER VIEW ──────────────────────────────────────
include 'pos_view.php';
?>