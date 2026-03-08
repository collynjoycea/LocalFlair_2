<?php
session_start();
include __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Please login.";
    exit;
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;

if ($cart_id <= 0 || $qty < 1) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
$stmt->bind_param("iii", $qty, $cart_id, $user_id);

if (!$stmt->execute()) {
    http_response_code(500);
    echo "Failed to update cart.";
    exit;
}

echo "success";

