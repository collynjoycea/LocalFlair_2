<?php
session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'User';

$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- START OF AJAX HANDLERS ---

// 1. Fetch Order Details for Modal
if (isset($_GET['action']) && $_GET['action'] == 'get_details') {
    $id = intval($_GET['id']);
    
    // Kunin ang Order at User Info
    $orderRes = $conn->query("SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                             FROM orders o JOIN users u ON o.user_id = u.user_id 
                             WHERE o.order_id = $id");
    $order = $orderRes->fetch_assoc();

    // Kunin ang mga Items (Joined with products table for images/names)
    $itemsRes = $conn->query("SELECT oi.*, p.product_name, p.product_image 
                             FROM order_items oi 
                             JOIN products p ON oi.product_id = p.product_id 
                             WHERE oi.order_id = $id");
    $items = [];
    while($item = $itemsRes->fetch_assoc()) {
        $items[] = $item;
    }

    header('Content-Type: application/json');
    echo json_encode(['order' => $order, 'items' => $items]);
    exit;
}

// 2. Handle Status Update from Modal
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = intval($_POST['order_id']);
    $current = $_POST['current_status'];
    
    $next = $current;
    if ($current === "Order Placed") $next = "Processed";
    elseif ($current === "Processed") $next = "Shipped";
    elseif ($current === "Shipped") $next = "Delivered";

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $next, $order_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'next_status' => $next]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
// --- END OF AJAX HANDLERS ---

// Counts for tabs
$countAll = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$countPending = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Order Placed'")->fetch_assoc()['total'];
$countProcessing = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Processed'")->fetch_assoc()['total'];
$countShipped = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Shipped'")->fetch_assoc()['total'];
$countDelivered = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Delivered'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Management | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg-light: #fdfaf7; --accent: #e07a5f; --text-dark: #3d3d3d; --border: #f0e6dd; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-light); margin: 0; color: var(--text-dark); overflow-x: hidden; }
        .main { margin-left: 260px; padding: 100px 40px 80px; }
        .page-title h2 { margin: 0; font-size: 24px; color: #4a2c1d; }
        
        /* Tabs Styling */
        .order-status-tabs { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px; }
        .status-tab { background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--border); transition: 0.3s; text-align: center; cursor: pointer; position: relative; }
        .status-tab.active { border-color: var(--accent); background: #fffcfb; }
        .status-tab.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 40%; height: 3px; background: var(--accent); border-radius: 10px 10px 0 0; }
        .status-tab h4 { margin: 0; font-size: 11px; text-transform: uppercase; color: #999; }
        .status-tab .count { display: block; margin-top: 8px; font-size: 22px; font-weight: 700; }

        /* Filter & Table */
        .filter-bar { background: #fff; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: flex-end; border: 1px solid var(--border); }
        .order-container { background: #fff; border-radius: 15px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #faf8f6; padding: 15px; font-size: 12px; text-transform: uppercase; color: #888; border-bottom: 1px solid var(--border); text-align: left; }
        td { padding: 16px 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; }

        .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-OrderPlaced { background: #fff4e5; color: #d48307; }
        .status-Processed { background: #e6f2ff; color: #007bff; }
        .status-Shipped { background: #f3e8ff; color: #7e22ce; }
        .status-Delivered { background: #ecfdf5; color: #059669; }
        
        .action-btn { width: 32px; height: 32px; border-radius: 8px; border: 1px solid #eee; background: #fff; cursor: pointer; color: #777; }
        .action-btn:hover { background: var(--accent); color: #fff; }

        /* MODAL / DRAWER STYLING */
        .backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); display: none; z-index: 999; }
        .modal-drawer { position: fixed; top: 0; right: -500px; width: 450px; height: 100%; background: #fff; box-shadow: -5px 0 15px rgba(0,0,0,0.1); transition: 0.4s ease; z-index: 1000; padding: 30px; box-sizing: border-box; overflow-y: auto; }
        .modal-drawer.active { right: 0; }
        
        .m-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .m-tabs { display: flex; gap: 20px; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .m-tab { padding-bottom: 10px; font-weight: 600; color: #999; cursor: pointer; border-bottom: 2px solid transparent; }
        .m-tab.active { color: var(--accent); border-bottom-color: var(--accent); }
        
        .item-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .item-img { width: 50px; height: 50px; border-radius: 8px; object-fit: cover; background: #f9f9f9; }
        .summary-box { background: #fdfaf7; padding: 20px; border-radius: 12px; margin-top: 20px; }
        .update-btn-big { width: 100%; background: var(--accent); color: white; border: none; padding: 15px; border-radius: 10px; font-weight: 700; cursor: pointer; margin-top: 20px; transition: 0.3s; }
        .update-btn-big:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="backdrop" id="backdrop" onclick="closeDetails()"></div>
<div class="modal-drawer" id="orderDrawer">
    <div class="m-header">
        <div>
            <small id="m-order-id" style="color:var(--accent); font-weight:700;"></small>
            <h2 id="m-customer-name" style="margin:5px 0 0 0;"></h2>
            <p id="m-customer-contact" style="font-size:13px; color:#666; margin:5px 0 0 0;"></p>
        </div>
        <button onclick="closeDetails()" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
    </div>

    <div class="m-tabs">
        <div class="m-tab active">Details</div>
        <div class="m-tab">Timeline</div>
        <div class="m-tab">Payment</div>
    </div>

    <div class="m-content">
        <p style="text-transform:uppercase; font-size:11px; font-weight:700; color:#999; margin-bottom:15px;">Order Info</p>
        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span>Status</span>
            <span id="m-status-pill" class="status-pill"></span>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <span>Order Date</span>
            <span id="m-date" style="font-weight:600;"></span>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <span>Address</span>
            <span id="m-address" style="text-align:right; font-size:13px; max-width:200px; font-weight:600;"></span>
        </div>

        <p style="text-transform:uppercase; font-size:11px; font-weight:700; color:#999; margin-bottom:15px;">Items Ordered</p>
        <div id="m-items-list"></div>

        <div class="summary-box">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>Subtotal</span><span id="m-subtotal" style="font-weight:600;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>Delivery Fee</span><span style="font-weight:600;">₱50.00</span>
            </div>
            <hr style="border:none; border-top:1px solid var(--border); margin:15px 0;">
            <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:800;">
                <span>Total</span><span id="m-total" style="color:var(--accent);"></span>
            </div>
        </div>

        <button class="update-btn-big" id="updateStatusBtn" onclick="updateOrderStatus()">
            <i class="fa fa-sync-alt"></i> Update Status
        </button>
    </div>
</div>

<div class="main">
    <div class="page-title">
        <h2>Order Management</h2>
        <p>Welcome, <?= htmlspecialchars($current_user) ?>!</p>
    </div>

    <section class="order-status-tabs">
        <div class="status-tab active" data-status="all">
            <h4>All Orders</h4>
            <span class="count"><?= $countAll ?></span>
        </div>
        <div class="status-tab" data-status="Order Placed">
            <h4>New / Pending</h4>
            <span class="count"><?= $countPending ?></span>
        </div>
        <div class="status-tab" data-status="Processed">
            <h4>Processing</h4>
            <span class="count"><?= $countProcessing ?></span>
        </div>
        <div class="status-tab" data-status="Shipped">
            <h4>Shipped</h4>
            <span class="count"><?= $countShipped ?></span>
        </div>
        <div class="status-tab" data-status="Delivered">
            <h4>Delivered</h4>
            <span class="count"><?= $countDelivered ?></span>
        </div>
    </section>

    <div class="filter-bar">
        <div style="display: flex; gap: 10px;">
            <select id="paymentMethod">
                <option value="all">All Payments</option>
                <option value="GCash">GCash</option>
                <option value="COD">COD</option>
                <option value="Credit Card">Credit Card</option>
            </select>
            <button id="filterBtn" style="background:var(--accent); color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer;">
                <i class="fa fa-filter"></i> Filter
            </button>
        </div>
    </div>

    <div class="order-container">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="orderTableBody"></tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.status-tab');
    const filterBtn = document.getElementById('filterBtn');
    const paymentSelect = document.getElementById('paymentMethod');
    let currentStatus = 'all';
    let currentPage = 1;
    let selectedOrderId = null;
    let selectedOrderCurrentStatus = null;

    // Fetch Table Data
    function fetchOrders() {
        const payment = paymentSelect.value;
        const url = `fetch_orders.php?status=${encodeURIComponent(currentStatus)}&payment=${encodeURIComponent(payment)}&page=${currentPage}`;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('orderTableBody').innerHTML = data;
                // I-attach ang click event sa bawat Action Button pagka-load
                attachActionEvents();
            });
    }

    function attachActionEvents() {
        document.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.onclick = function() {
                openDetails(this.dataset.id);
            };
        });
    }

    // Modal Logic
    window.openDetails = function(id) {
        selectedOrderId = id;
        fetch(`?action=get_details&id=${id}`)
            .then(res => res.json())
            .then(data => {
                const o = data.order;
                selectedOrderCurrentStatus = o.status;
                
                document.getElementById('m-order-id').innerText = `ORD-${o.order_id}`;
                document.getElementById('m-customer-name').innerText = `${o.first_name} ${o.last_name}`;
                document.getElementById('m-customer-contact').innerText = `${o.phone} | ${o.email}`;
                document.getElementById('m-date').innerText = new Date(o.order_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
                document.getElementById('m-address').innerText = o.shipping_address;
                
                const pill = document.getElementById('m-status-pill');
                pill.innerText = o.status;
                pill.className = `status-pill status-${o.status.replace(/\s+/g, '')}`;

                // Items list
                let subtotal = 0;
                let itemsHtml = '';
                data.items.forEach(item => {
                    const total = item.price * item.quantity;
                    subtotal += total;
                    itemsHtml += `
                        <div class="item-row">
                            <img src="uploads/${item.product_image}" class="item-img" onerror="this.src='https://via.placeholder.com/50'">
                            <div style="flex:1">
                                <div style="font-weight:600">${item.product_name}</div>
                                <small style="color:#888">Qty: ${item.quantity} × ₱${parseFloat(item.price).toLocaleString()}</small>
                            </div>
                            <div style="font-weight:700">₱${total.toLocaleString()}</div>
                        </div>`;
                });
                document.getElementById('m-items-list').innerHTML = itemsHtml;
                document.getElementById('m-subtotal').innerText = `₱${subtotal.toLocaleString()}`;
                document.getElementById('m-total').innerText = `₱${(subtotal + 50).toLocaleString()}`;

                // Update Button logic
                const btn = document.getElementById('updateStatusBtn');
                if (o.status === "Delivered") {
                    btn.disabled = true;
                    btn.innerText = "Order Completed";
                } else {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fa fa-sync-alt"></i> Update Status`;
                }

                document.getElementById('orderDrawer').classList.add('active');
                document.getElementById('backdrop').style.display = 'block';
            });
    }

    window.closeDetails = function() {
        document.getElementById('orderDrawer').classList.remove('active');
        document.getElementById('backdrop').style.display = 'none';
    }

    window.updateOrderStatus = function() {
        if (!selectedOrderId) return;

        Swal.fire({
            title: 'Update order status?',
            text: "This will move the order to the next stage.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#e07a5f',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('order_id', selectedOrderId);
                formData.append('current_status', selectedOrderCurrentStatus);

                fetch('', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success!', `Order is now ${data.next_status}`, 'success');
                            closeDetails();
                            fetchOrders(); // I-refresh ang main table
                            // Optional: Refresh counts via location.reload() or additional AJAX
                        }
                    });
            }
        });
    }

    // Tab Clicking Logic
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.getAttribute('data-status');
            currentPage = 1;
            fetchOrders();
        });
    });

    filterBtn.addEventListener('click', () => { currentPage = 1; fetchOrders(); });

    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('page-link')) {
            e.preventDefault();
            const p = parseInt(e.target.getAttribute('data-page'));
            if (!isNaN(p)) {
                currentPage = p;
                fetchOrders();
            }
        }
    });

    fetchOrders();
});
</script>

</body>
</html>