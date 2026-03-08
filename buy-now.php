<?php
session_start();
include __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please register/login first to continue.'); window.location='register.php';</script>";
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit();
}
if ($qty <= 0) {
    $qty = 1;
}

$user_id = (int)$_SESSION['user_id'];

$check_query = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $new_qty = ((int)$row['quantity']) + $qty;

    $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $upd_stmt = $conn->prepare($update_query);
    $upd_stmt->bind_param("ii", $new_qty, $row['cart_id']);
    $upd_stmt->execute();
} else {
    $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $ins_stmt = $conn->prepare($insert_query);
    $ins_stmt->bind_param("iii", $user_id, $id, $qty);
    $ins_stmt->execute();
}

header("Location: place-order.php");
exit();
