<?php
$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);

$status = $_GET['status'] ?? 'all';

$query = "SELECT o.order_id, CONCAT(u.first_name, ' ', u.last_name) AS customer, 
                 o.total_amount, o.payment_method, o.payment_status, o.order_date
          FROM orders o
          JOIN users u ON o.user_id = u.user_id";

if ($status !== 'all') {
    $query .= " WHERE o.payment_status = '" . $conn->real_escape_string($status) . "'";
}

$query .= " ORDER BY CASE WHEN o.payment_status = 'Pending Verification' THEN 1 ELSE 2 END, o.order_date DESC";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $statusClass = str_replace(' ', '', $row['payment_status']);
        echo "<tr>
                <td>#ORD-" . str_pad($row['order_id'], 3, "0", STR_PAD_LEFT) . "</td>
                <td>
                    <div style='font-weight: 600;'>" . htmlspecialchars($row['customer']) . "</div>
                    <small style='color:#999;'>Customer Account</small>
                </td>
                <td><span class='method-tag'><i class='fa fa-credit-card'></i> {$row['payment_method']}</span></td>
                <td class='amount-text'>₱" . number_format($row['total_amount'], 2) . "</td>
                <td>
                    <span class='status-pill status-{$statusClass}'>
                        ● {$row['payment_status']}
                    </span>
                </td>
                <td>" . date('M d, Y', strtotime($row['order_date'])) . "</td>
                <td>
                    <button class='action-btn view-receipt-btn' data-id='{$row['order_id']}' title='View Receipt'>
                        <i class='fa fa-file-invoice'></i>
                    </button>";
        
        // Ipakita lang ang Verify button kung hindi pa 'Paid' ang status
        if ($row['payment_status'] !== 'Paid') {
            echo "<button class='action-btn verify-payment-btn' data-id='{$row['order_id']}' title='Verify Payment'>
                    <i class='fa fa-check-circle'></i>
                  </button>";
        }

        echo "  </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' style='text-align:center; padding: 40px; color: #999;'>Walang nakitang payment records.</td></tr>";
}


