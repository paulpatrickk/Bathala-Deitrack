<?php
session_start();

// ─── AUTH CHECK ───────────────────────────────────────
if (!isset($_SESSION['user'])) {
    header("Location: /Bathala/LandingPage/login.php");
    exit;
}

$user = $_SESSION['user'];

// ─── DB CONNECTION ────────────────────────────────────
$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ─── FETCH PRODUCTS GROUPED BY CATEGORY ───────────────
$products_result = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY category ASC, name ASC");
$products = [];
$categories = [];

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
        if (!in_array($row['category'], $categories)) {
            $categories[] = $row['category'];
        }
    }
}

// ─── RENDER VIEW ──────────────────────────────────────
include 'pos_view.php';
?>
