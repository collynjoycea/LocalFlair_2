<?php
session_start();
// Ginamit ang ../ para lumabas sa admin folder at mahanap ang db.php sa root
include '../db.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        header("Location: orders.php?msg=updated");
    } else {
        header("Location: orders.php?msg=error");
    }
    $stmt->close();
    $conn->close();
    exit();
}