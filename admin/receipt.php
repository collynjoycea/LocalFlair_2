<?php
session_start();
include '../db.php';

if (!isset($_GET['order_id'])) { die("Order ID required."); }
$order_id = intval($_GET['order_id']);

// Kunin ang Order at User Info
$sql = "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Kunin ang Items sa loob ng Order (Base sa iyong order_items table)
$item_sql = "SELECT oi.*, p.product_name 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.product_id 
             WHERE oi.order_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt #ORD-<?= $order_id ?></title>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; padding: 20px; background: #f1f5f9; }
        .receipt-card { 
            max-width: 450px; margin: auto; background: white; 
            padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .text-center { text-align: center; }
        hr { border: 0; border-top: 1px dashed #e2e8f0; margin: 20px 0; }
        table { width: 100%; font-size: 14px; border-collapse: collapse; }
        th { text-align: left; color: #64748b; font-size: 11px; text-transform: uppercase; }
        td { padding: 10px 0; color: #1e293b; }
        .total-section { font-weight: 800; font-size: 20px; color: #f05a28; margin-top: 15px; }
        .btn-print { 
            background: #1e293b; color: white; border: none; padding: 10px 20px; 
            border-radius: 10px; cursor: pointer; text-decoration: none; font-size: 13px;
        }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .receipt-card { box-shadow: none; border: none; } }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" class="btn-print">Print Receipt</button>
        <a href="orders.php" class="btn-print" style="background:#64748b;">Back to Orders</a>
    </div>

    <div class="receipt-card">
        <div class="text-center">
            <h2 style="margin:0; color:#1e293b;">LocalFlair</h2>
            <p style="margin:5px 0; color:#64748b; font-size:13px;">Official Transaction Receipt</p>
        </div>
        
        <hr>
        
        <div style="display:flex; justify-content:space-between; font-size:13px;">
            <span><strong>Order ID:</strong> #ORD-<?= str_pad($order_id, 4, '0', STR_PAD_LEFT) ?></span>
            <span><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
        </div>
        <p style="font-size:13px; margin: 10px 0;"><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        
        <hr>
        
        <table>
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td style="text-align:center;"><?= $item['quantity'] ?></td>
                    <td style="text-align:right;">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <hr>
        
        <div class="total-section" style="display:flex; justify-content: space-between;">
            <span>Total Amount</span>
            <span>₱<?= number_format($order['total_amount'], 2) ?></span>
        </div>
        
        <p class="text-center" style="font-size:11px; color:#94a3b8; margin-top: 30px;">
            This is a system-generated receipt.<br>Thank you for supporting local makers!
        </p>
    </div>

</body>
</html>