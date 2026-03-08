<?php
session_start();

// 1. SECURITY CHECK
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'User';

// 2. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// 3. FETCH TRACKING STATISTICS
$countAll = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$countInTransit = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('Shipped', 'Out for Delivery')")->fetch_assoc()['total'];
$countPending = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Order Placed'")->fetch_assoc()['total'];
$countDelivered = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Delivered'")->fetch_assoc()['total'];

// 4. FETCH ORDERS WITH DESTINATION DATA
$query = "
    SELECT o.order_id, CONCAT(u.first_name, ' ', u.last_name) AS customer, 
           o.status, o.order_date, a.city_municipality as destination
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN addresses a ON u.user_id = a.user_id
    ORDER BY o.order_date DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-light: #fdfaf7;
            --accent: #e07a5f;
            --text-dark: #3d3d3d;
            --border: #f0e6dd;
            --success: #059669;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-light);
            margin: 0;
            color: var(--text-dark);
        }

        .main {
            flex: 1;
            margin-left: 260px; /* Space for Sidebar */
            padding: 100px 40px 80px;
        }

        /* Header */
        .page-title { margin-bottom: 25px; }
        .page-title h2 { margin: 0; font-size: 24px; color: #4a2c1d; }
        .page-title p { margin: 5px 0 0; color: #888; font-size: 14px; }

        /* Status Tabs */
        .order-status-tabs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .status-tab {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: center;
            transition: 0.3s;
        }
        .status-tab.active {
            border-color: var(--accent);
            background: #fffcfb;
            position: relative;
        }
        .status-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40%;
            height: 3px;
            background: var(--accent);
            border-radius: 10px 10px 0 0;
        }
        .status-tab h4 { margin: 0; font-size: 11px; text-transform: uppercase; color: #999; letter-spacing: 0.5px; }
        .status-tab .count { display: block; margin-top: 8px; font-size: 24px; font-weight: 700; color: #4a2c1d; }

        /* Table Styling */
        .order-container {
            background: #fff;
            border-radius: 15px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #faf8f6;
            padding: 15px;
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        td { padding: 16px 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }

        /* Tracking Elements */
        .track-bar {
            width: 100px;
            height: 6px;
            background: #eee;
            border-radius: 10px;
            display: inline-block;
            margin-right: 8px;
            overflow: hidden;
        }
        .track-fill { height: 100%; background: var(--accent); border-radius: 10px; }
        .fill-pending { width: 25%; }
        .fill-processed { width: 50%; }
        .fill-shipped { width: 75%; }
        .fill-delivered { width: 100%; background: var(--success); }

        .status-pill {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-OrderPlaced { background: #fff4e5; color: #d48307; }
        .status-Shipped { background: #eef2ff; color: #4338ca; }
        .status-Delivered { background: #ecfdf5; color: #059669; }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #eee;
            background: #fff;
            cursor: pointer;
            color: #777;
            transition: 0.2s;
        }
        .action-btn:hover { background: var(--accent); color: #fff; }

        /* Update Section Styling */
        .update-form-container {
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border);
            margin-top: 20px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1.5fr 1fr auto;
            gap: 15px;
            align-items: flex-end;
        }
        label { display: block; font-size: 11px; font-weight: 700; color: #999; margin-bottom: 5px; text-transform: uppercase; }
        select, input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }
        .btn-save {
            background: var(--success);
            color: #fff;
            border: none;
            padding: 11px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="page-title">
        <h2>Live Order Tracking</h2>
        <p>Manage and update logistical milestones for customer orders.</p>
    </div>

    <section class="order-status-tabs">
        <div class="status-tab active">
            <h4>All Shipments</h4>
            <span class="count"><?= $countAll ?></span>
        </div>
        <div class="status-tab">
            <h4>For Packing</h4>
            <span class="count"><?= $countPending ?></span>
        </div>
        <div class="status-tab">
            <h4>In Transit</h4>
            <span class="count"><?= $countInTransit ?></span>
        </div>
        <div class="status-tab">
            <h4>Success</h4>
            <span class="count"><?= $countDelivered ?></span>
        </div>
    </section>

    <div class="order-container">
        <table>
            <thead>
                <tr>
                    <th>Tracking ID</th>
                    <th>Customer</th>
                    <th>Destination</th>
                    <th>Delivery Progress</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
                            $fillClass = 'fill-pending';
                            if($row['status'] == 'Processed') $fillClass = 'fill-processed';
                            if($row['status'] == 'Shipped' || $row['status'] == 'Out for Delivery') $fillClass = 'fill-shipped';
                            if($row['status'] == 'Delivered') $fillClass = 'fill-delivered';
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--accent);">
                                #LF-TRK-<?= str_pad($row['order_id'], 4, "0", STR_PAD_LEFT) ?>
                            </td>
                            <td><div style="font-weight: 600;"><?= htmlspecialchars($row['customer']) ?></div></td>
                            <td>
                                <i class="fa fa-location-dot" style="color:var(--accent)"></i> 
                                <?= htmlspecialchars($row['destination'] ?? 'Manila') ?>
                            </td>
                            <td>
                                <div class="track-bar">
                                    <div class="track-fill <?= $fillClass ?>"></div>
                                </div>
                                <small style="color:#888; font-size:11px"><?= $row['status'] ?></small>
                            </td>
                            <td>
                                <span class="status-pill status-<?= str_replace(' ', '', $row['status']) ?>">
                                    ● <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['order_date'])) ?></td>
                            <td>
                                <button class="action-btn" title="View Details"><i class="fa fa-eye"></i></button>
                                <button class="action-btn" title="Update Timeline" 
                                        onclick="showUpdateSection('<?= $row['order_id'] ?>', '<?= $row['status'] ?>')">
                                    <i class="fa fa-truck-ramp-box"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 40px;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="update-timeline-section" style="display: none; margin-top: 50px; padding-top: 30px; border-top: 2px dashed var(--border);">
        <div class="page-title">
            <h3>Update Logistics Milestone</h3>
            <p>Setting status for <span id="display-order-id" style="color: var(--accent); font-weight: bold;"></span></p>
        </div>

        <div class="update-form-container">
            <form action="update_order_status.php" method="POST" class="form-row">
                <input type="hidden" name="order_id" id="input-order-id">
                
                <div class="input-field">
                    <label>New Status</label>
                    <select name="status" id="status-select">
                        <option value="Order Placed">Order Placed</option>
                        <option value="Processed">Processed</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="input-field">
                    <label>Milestone Note</label>
                    <input type="text" name="note" placeholder="E.g. Arrived at Sortation Center">
                </div>

                <div class="input-field">
                    <label>Current Location</label>
                    <input type="text" name="location" placeholder="City / Branch Name">
                </div>

                <button type="submit" class="btn-save">
                    <i class="fa fa-save"></i> Save Update
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showUpdateSection(orderId, currentStatus) {
    const section = document.getElementById('update-timeline-section');
    section.style.display = 'block';

    // Populate data
    document.getElementById('display-order-id').innerText = '#LF-TRK-' + orderId.toString().padStart(4, '0');
    document.getElementById('input-order-id').value = orderId;
    document.getElementById('status-select').value = currentStatus;

    // Smooth scroll to the form
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

</body>
</html>