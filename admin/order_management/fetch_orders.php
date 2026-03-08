<?php
$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);

$status = $_GET['status'] ?? 'all';
$payment = $_GET['payment'] ?? 'all';

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$limit = 3;
$offset = ($page - 1) * $limit;



// Base Query
$query = "SELECT o.order_id, CONCAT(u.first_name, ' ', u.last_name) AS customer, 
                 o.total_amount, o.status, o.order_date, o.payment_method,
                 (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
          FROM orders o
          JOIN users u ON o.user_id = u.user_id WHERE 1=1";

// total count for pagination
$countQuery = "SELECT COUNT(*) as cnt FROM orders o JOIN users u ON o.user_id = u.user_id WHERE 1=1";

// Add Status Filter
if ($status !== 'all') {
    $query .= " AND o.status = '" . $conn->real_escape_string($status) . "'";
    $countQuery .= " AND o.status = '" . $conn->real_escape_string($status) . "'";
}

// Add Payment Filter
if ($payment !== 'all') {
    $query .= " AND o.payment_method = '" . $conn->real_escape_string($payment) . "'";
    $countQuery .= " AND o.payment_method = '" . $conn->real_escape_string($payment) . "'";
}

$query .= " ORDER BY o.order_date DESC LIMIT $limit OFFSET $offset";
// get total count
$totalOrders = $conn->query($countQuery)->fetch_assoc()['cnt'];
$totalPages  = ceil($totalOrders / $limit);

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $statusClass = str_replace(' ', '', $row['status']);
        echo "<tr>
                <td>#ORD-" . str_pad($row['order_id'], 3, "0", STR_PAD_LEFT) . "</td>
                <td>
                    <div style='font-weight: 600;'>" . htmlspecialchars($row['customer']) . "</div>
                    <small style='color:#999;'>" . htmlspecialchars($row['payment_method']) . "</small>
                </td>
                <td><span class='items-tag'>{$row['item_count']} items</span></td>
                <td class='amount-text'>₱" . number_format($row['total_amount'], 2) . "</td>
                <td>
                    <span class='status-pill status-{$statusClass}'>
                        ● {$row['status']}
                    </span>
                </td>
                <td>" . date('M d, Y', strtotime($row['order_date'])) . "</td>
                <td>
                    <button class='action-btn'><i class='fa fa-eye'></i></button>
                    <button class='action-btn'><i class='fa fa-check'></i></button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' style='text-align:center; padding: 40px; color: #999;'>No orders found.</td></tr>";
}

// pagination links
if ($totalPages > 1) {
    echo '<tr><td colspan="7" style="text-align:center;padding:10px;">';
    for ($p = 1; $p <= $totalPages; $p++) {
        if ($p == $page) {
            echo "<span style='margin:0 5px;font-weight:bold;'>$p</span>";
        } else {
            echo "<a href='#' class='page-link' data-page='$p' style='margin:0 5px;color:var(--accent);text-decoration:none;'>$p</a>";
        }
    }
    echo '</td></tr>';
}
?>