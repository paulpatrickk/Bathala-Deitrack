<?php
$conn = new mysqli("localhost", "root", "", "smartpos");

$email = "admin@gmail.com";
$password = password_hash("1234", PASSWORD_DEFAULT);
$role = "admin";

$sql = "INSERT INTO users (email, password, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $password, $role);
$stmt->execute();

echo "User created!";
?>