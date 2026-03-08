<?php
session_start();
include __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$u_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) AS total_items FROM cart WHERE user_id = ?");
$stmt->bind_param("i", $u_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

echo json_encode(['count' => (int)($row['total_items'] ?? 0)]);

