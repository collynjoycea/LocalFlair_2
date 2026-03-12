<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    
    if ($_POST['action'] === 'verify') {
        // I-update ang payment_status sa 'Paid'
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid' WHERE order_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $stmt->close();
    }
    exit();
}