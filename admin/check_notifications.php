<?php
include "../db.php";

header('Content-Type: application/json');

// Base sa schema mo, 'Order Placed' ang initial status ng bagong order.
// I-join natin sa 'users' table para makuha ang pangalan ng bumili.
$sql = "SELECT o.order_id, u.first_name, u.last_name, o.total_amount 
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        WHERE o.status = 'Order Placed' 
        ORDER BY o.order_date DESC 
        LIMIT 5";

$result = mysqli_query($conn, $sql);

$notifications = [];

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            'id'    => $row['order_id'],
            'title' => 'New Order from ' . $row['first_name'] . ' ' . $row['last_name'],
            'desc'  => 'Total Amount: ₱' . number_format($row['total_amount'], 2)
        ];
    }
}

// I-output ang JSON para sa JavaScript polling mo
echo json_encode([
    'count' => count($notifications), 
    'list' => $notifications
]);
?>