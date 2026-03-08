<?php
session_start();
include '../db.php';

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        header("Location: orders.php?msg=cancelled");
    } else {
        header("Location: orders.php?msg=error");
    }
    $stmt->close();
    $conn->close();
    exit();
}