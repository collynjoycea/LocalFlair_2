<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- GET FILTERS ---
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';
$page      = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit     = 10; 
$offset    = ($page - 1) * $limit;

// --- BUILD WHERE ---
$whereClauses = [];
if ($startDate && $endDate) {
    $whereClauses[] = "DATE(o.order_date) BETWEEN '$startDate' AND '$endDate'";
} elseif ($startDate) {
    $whereClauses[] = "DATE(o.order_date) >= '$startDate'";
} elseif ($endDate) {
    $whereClauses[] = "DATE(o.order_date) <= '$endDate'";
}
$where = $whereClauses ? "WHERE " . implode(' AND ', $whereClauses) : "";

// --- PAGINATION LOGIC ---
$countSql = "SELECT COUNT(*) AS total FROM orders o $where";
$totalRecords = $conn->query($countSql)->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRecords / $limit);

// --- FETCH RESULTS ---
$sql = "SELECT o.order_id, CONCAT(u.first_name,' ',u.last_name) AS customer, 
               o.order_date, o.total_amount, o.status, o.payment_status, o.payment_method
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        $where
        ORDER BY o.order_date DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$totalPaid = $totalUnpaid = $totalAmount = 0;
$summarySql = "SELECT total_amount, payment_status FROM orders o $where";
$summaryResult = $conn->query($summarySql);
while($row = $summaryResult->fetch_assoc()) {
    $totalAmount += $row['total_amount'];
    if($row['payment_status'] === "Paid") $totalPaid += $row['total_amount'];
    if($row['payment_status'] === "Unpaid") $totalUnpaid += $row['total_amount'];
}
$averageOrder = $totalRecords > 0 ? $totalAmount / $totalRecords : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | System Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    :root {
        --primary-orange: #f05a28;
        --bg-color: #f8fafc;
        --sidebar-width: 260px;
        --topbar-height: 80px;
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
    body { background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%); min-height: 100vh; color: var(--text-dark); }

    .main {
        margin-left: var(--sidebar-width);
        padding: calc(var(--topbar-height) + 30px) 40px 40px 40px;
        width: calc(100% - var(--sidebar-width));
    }

    /* HEADER */
    .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
    .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

    /* FILTERS CARD (Orders.php Style) */
    .filter-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .date-inputs { display: flex; align-items: center; gap: 10px; }
    .date-inputs input { 
        padding: 8px 12px; border-radius: 10px; border: 1px solid #edf2f7; font-size: 13px; 
        outline: none; color: var(--text-dark); font-weight: 600;
    }
    .apply-btn { 
        background: var(--text-dark); color: white; border: none; padding: 9px 18px; 
        border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.3s;
    }
    .apply-btn:hover { background: #000; }

    /* SUMMARY GRID */
    .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-card { 
        background: #fff; padding: 20px; border-radius: 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #edf2f7;
        display: flex; align-items: center; gap: 15px;
    }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    
    .ic-orders { background: #eff6ff; color: #2563eb; }
    .ic-amount { background: #ecfdf5; color: #059669; }
    .ic-paid { background: #fff5f2; color: var(--primary-orange); }
    .ic-avg { background: #fffbeb; color: #d97706; }

    .stat-info span { display: block; color: var(--text-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-info h3 { font-size: 20px; font-weight: 800; color: var(--text-dark); }

    /* TABLE CONTAINER */
    .table-container {
        background: #fff; border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03); border: 1px solid #edf2f7;
        overflow: hidden;
    }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        text-align: left; padding: 20px 25px; background: #fcfdfe;
        color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; border-bottom: 1px solid #f1f5f9;
    }
    tbody td { padding: 18px 25px; border-bottom: 1px solid #f8fafc; font-size: 14px; vertical-align: middle; }
    
    .order-id { font-weight: 800; color: var(--primary-orange); font-size: 13px; }
    .cust-name { font-weight: 700; color: var(--text-dark); }

    /* PILLS */
    .status-pill { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .s-paid { background: #ecfdf5; color: #059669; }
    .s-unpaid { background: #fef2f2; color: #dc2626; }

    /* PAGINATION */
    .pagination-wrapper { padding: 25px; display: flex; justify-content: center; border-top: 1px solid #f1f5f9; }
    .pg-link { 
        min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
        border-radius: 10px; background: #f1f5f9; color: var(--text-dark); text-decoration: none;
        font-size: 13px; font-weight: 700; margin: 0 3px;
    }
    .pg-link.active { background: var(--primary-orange); color: white; }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="header-flex animate__animated animate__fadeIn">
        <div class="page-title">
            <h2>System Reports</h2>
            <p>Comprehensive overview of your boutique's financial performance.</p>
        </div>
        <form class="date-inputs filter-card" method="GET">
            <input type="date" name="start_date" value="<?= $startDate ?>">
            <span style="color:var(--text-muted); font-weight:700;">to</span>
            <input type="date" name="end_date" value="<?= $endDate ?>">
            <button type="submit" class="apply-btn">Generate Report</button>
        </form>
    </div>

    <div class="summary-grid animate__animated animate__fadeInUp">
        <div class="stat-card">
            <div class="stat-icon ic-orders"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="stat-info"><span>Total Orders</span><h3><?= $totalRecords ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-amount"><i class="fa-solid fa-coins"></i></div>
            <div class="stat-info"><span>Gross Revenue</span><h3>₱<?= number_format($totalAmount, 2) ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-paid"><i class="fa-solid fa-check-double"></i></div>
            <div class="stat-info"><span>Collected</span><h3>₱<?= number_format($totalPaid, 2) ?></h3></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-avg"><i class="fa-solid fa-arrow-up-right-dots"></i></div>
            <div class="stat-info"><span>Avg. Order</span><h3>₱<?= number_format($averageOrder, 2) ?></h3></div>
        </div>
    </div>

    <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): while($o = $result->fetch_assoc()): ?>
                <tr>
                    <td class="order-id">#ORD-<?= str_pad($o['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td class="cust-name"><?= htmlspecialchars($o['customer']) ?></td>
                    <td style="color: var(--text-muted); font-size: 13px;"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                    <td style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase;"><?= $o['payment_method'] ?></td>
                    <td style="font-weight: 800;">₱<?= number_format($o['total_amount'], 2) ?></td>
                    <td><span class="status-pill" style="background:#f1f5f9; color:#475569;"><?= $o['status'] ?></span></td>
                    <td><span class="status-pill <?= $o['payment_status']=='Paid'?'s-paid':'s-unpaid' ?>"><?= $o['payment_status'] ?></span></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center; padding: 50px; color: var(--text-muted);">No records found for the selected range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination-wrapper">
            <?php for($i=1; $i<=$totalPages; $i++): ?>
                <a href="?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&page=<?= $i ?>" class="pg-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>
<script>
function fetchRealtimeData() {
    // Kukunin lang natin ang current page mula sa URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const currentPage = urlParams.get('page') || 1;
    const startDate = "<?= $startDate ?>";
    const endDate = "<?= $endDate ?>";
    
    // Dagdagan natin ng &page sa fetch para tama ang data na babalik
    fetch(`api/get_stats.php?start_date=${startDate}&end_date=${endDate}&page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            if(data.error) return;

            // 1. I-update ang Summary Cards (Laging up-to-date)
            document.querySelector('.ic-orders + .stat-info h3').innerText = data.totalRecords;
            document.querySelector('.ic-amount + .stat-info h3').innerText = '₱' + data.totalAmount;
            document.querySelector('.ic-paid + .stat-info h3').innerText = '₱' + data.totalPaid;
            document.querySelector('.ic-avg + .stat-info h3').innerText = '₱' + data.average;

            // 2. I-update ang Table Rows
            const tbody = document.querySelector('table tbody');
            let rows = '';
            
            data.orders.forEach(o => {
                const payCls = o.payment_status === 'Paid' ? 's-paid' : 's-unpaid';
                
                // Format date to 'Jan 01, 2024' style para parehas sa PHP
                const d = new Date(o.order_date);
                const formattedDate = d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

                rows += `
                <tr>
                    <td class="order-id">#ORD-${String(o.order_id).padStart(4, '0')}</td>
                    <td class="cust-name">${o.customer}</td>
                    <td style="color: var(--text-muted); font-size: 13px;">${formattedDate}</td>
                    <td style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">${o.payment_method}</td>
                    <td style="font-weight: 800;">₱${parseFloat(o.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td><span class="status-pill" style="background:#f1f5f9; color:#475569;">${o.status}</span></td>
                    <td><span class="status-pill ${payCls}">${o.payment_status}</span></td>
                </tr>`;
            });
            
            if(data.orders.length > 0) {
                tbody.innerHTML = rows;
            }
        })
        .catch(err => console.error("Error fetching data:", err));
}

// Mag-update tuwing 5 segundo
setInterval(fetchRealtimeData, 5000);
</script>
</body>
</html>