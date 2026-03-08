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
$filter     = $_GET['filter'] ?? 'all';
$sort       = $_GET['sort'] ?? 'newest';
$startDate  = $_GET['start_date'] ?? '';
$endDate    = $_GET['end_date'] ?? '';

// --- BUILD WHERE CONDITIONS (Tugma sa database values mo) ---
$whereClauses = [];
$statusMap = [
    'placed'           => ["Order Placed"],
    'processed'        => ["Processed"],
    'shipped'          => ["Shipped"],
    'out_for_delivery' => ["Out for Delivery"],
    'delivered'        => ["Delivered"],
    'cancelled'        => ["Cancelled"]
];

if(isset($statusMap[$filter])){
    $statuses = implode("','", $statusMap[$filter]);
    $whereClauses[] = "o.status IN ('$statuses')";
}

if(isset($statusMap[$filter])){
    $statuses = implode("','", $statusMap[$filter]);
    $whereClauses[] = "o.status IN ('$statuses')";
}

if($startDate && $endDate){
    $whereClauses[] = "DATE(o.order_date) BETWEEN '$startDate' AND '$endDate'";
}elseif($startDate){
    $whereClauses[] = "DATE(o.order_date) >= '$startDate'";
}elseif($endDate){
    $whereClauses[] = "DATE(o.order_date) <= '$endDate'";
}

$where = $whereClauses ? "WHERE " . implode(' AND ', $whereClauses) : "";
$orderBy = $sort === 'oldest' ? "o.order_date ASC" : "o.order_date DESC";

// --- PAGINATION ---
$limit  = 8; 
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countSql    = "SELECT COUNT(*) AS total FROM orders o JOIN users u ON o.user_id = u.user_id $where";
$countResult = $conn->query($countSql);
$totalOrders = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages  = ceil($totalOrders / $limit);

$sql = "
SELECT o.order_id AS id, CONCAT(u.first_name,' ',u.last_name) AS customer,
       o.order_date AS date, o.total_amount AS total, o.status, o.payment_status
FROM orders o
JOIN users u ON o.user_id = u.user_id
$where
ORDER BY $orderBy
LIMIT $limit OFFSET $offset
";

$result = $conn->query($sql);
$orders = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

$pillClass = [
    'order placed' => 's-pending',
    'processed' => 's-processing',
    'shipped' => 's-processing',
    'out for delivery' => 's-processing',
    'delivered' => 's-delivered',
    'cancelled' => 's-cancelled'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Orders Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
    
    /* Reset Bootstrap link styles */
    a { text-decoration: none; }

    .main {
        margin-left: var(--sidebar-width);
        padding: calc(var(--topbar-height) + 30px) 40px 40px 40px;
        width: calc(100% - var(--sidebar-width));
        transition: all 0.3s ease;
    }

    /* HEADER */
    .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
    .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

    /* FILTERS CARD */
    .filter-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
        justify-content: space-between;
    }

    .filter-group { display: flex; gap: 8px; background: #fff; padding: 5px; border-radius: 12px; border: 1px solid #edf2f7; }
    .filter-btn { 
        padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; 
        color: var(--text-muted); text-decoration: none; transition: 0.3s; 
    }
    .filter-btn.active { background: var(--primary-orange); color: white; box-shadow: 0 4px 12px rgba(240, 90, 40, 0.2); }
    .filter-btn:not(.active):hover { background: #f1f5f9; color: var(--text-dark); }

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

    

    /* TABLE CONTAINER */
    .table-container {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        border: 1px solid #edf2f7;
        overflow: hidden;
    }

    table { width: 100%; border-collapse: collapse; }
    thead th {
        text-align: left; padding: 20px 25px; background: #fcfdfe;
        color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: 1px; border-bottom: 1px solid #f1f5f9;
    }

    tbody td { padding: 18px 25px; border-bottom: 1px solid #f8fafc; font-size: 14px; vertical-align: middle; }
    tbody tr:hover td { background-color: #fcfdfe; }

    .order-id { font-weight: 800; color: var(--primary-orange); font-size: 13px; }
    .cust-name { font-weight: 700; color: var(--text-dark); }
    .order-date { color: var(--text-muted); font-size: 13px; }
    .amount { font-weight: 800; color: var(--text-dark); }

    /* STATUS PILLS */
    .status-pill { 
        padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; 
        text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;
    }
    .s-pending { background: #eff6ff; color: #2563eb; }
    .s-processing { background: #fffbeb; color: #d97706; }
    .s-delivered { background: #ecfdf5; color: #059669; }
    .s-cancelled { background: #fef2f2; color: #dc2626; }

    /* ACTIONS */
    .action-group { display: flex; gap: 8px; }
    .act-btn { 
        width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; 
        justify-content: center; text-decoration: none; transition: 0.2s; font-size: 14px;
        border: none; cursor: pointer;
    }
    .btn-receipt { background: #f1f5f9; color: #475569; }
    .btn-edit { background: #fff5f2; color: var(--primary-orange); }
    .btn-cancel { background: #fff1f0; color: #f5222d; }
    
    .act-btn:hover { transform: translateY(-2px); filter: brightness(0.95); }

    /* PAGINATION */
    .pagination-wrapper { padding: 25px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; }
    .page-info { color: var(--text-muted); font-size: 13px; font-weight: 600; }
    .page-btns { display: flex; gap: 6px; }
    .pg-link { 
        min-width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
        border-radius: 10px; background: #f1f5f9; color: var(--text-dark); text-decoration: none;
        font-size: 13px; font-weight: 700; transition: 0.2s;
    }
    .pg-link.active { background: var(--primary-orange); color: white; }
    .pg-link:hover:not(.active) { background: #e2e8f0; }

    @media (max-width: 992px) {
        .main { margin-left: 0; width: 100%; padding: 100px 20px 20px 20px; }
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="header-flex animate__animated animate__fadeIn">
        <div class="page-title">
            <h2>Order Management</h2>
            <p>Track, process, and manage your boutique's customer orders.</p>
        </div>
        <div class="sort-group filter-group">
            <a href="orders.php?filter=<?= $filter ?>&sort=newest" class="filter-btn <?= $sort==='newest'?'active':'' ?>">Newest</a>
            <a href="orders.php?filter=<?= $filter ?>&sort=oldest" class="filter-btn <?= $sort==='oldest'?'active':'' ?>">Oldest</a>
        </div>
    </div>

    <div class="filter-card animate__animated animate__fadeInUp">
        <div class="filter-group">
    <a href="orders.php" class="filter-btn <?= $filter==='all'?'active':'' ?>">All</a>
     <a href="orders.php?filter=placed" class="filter-btn <?= $filter==='placed'?'active':'' ?>">Order Placed</a>
    <a href="orders.php?filter=processed" class="filter-btn <?= $filter==='processed'?'active':'' ?>">Processed</a>
    <a href="orders.php?filter=shipped" class="filter-btn <?= $filter==='shipped'?'active':'' ?>">Shipped</a>
    <a href="orders.php?filter=out_for_delivery" class="filter-btn <?= $filter==='out_for_delivery'?'active':'' ?>">Out for Delivery</a>
    <a href="orders.php?filter=delivered" class="filter-btn <?= $filter==='delivered'?'active':'' ?>">Delivered</a>
    <a href="orders.php?filter=cancelled" class="filter-btn <?= $filter==='cancelled'?'active':'' ?>">Cancelled</a>
</div>


        <div class="date-inputs">
            <input type="date" id="start_date" value="<?= htmlspecialchars($startDate) ?>">
            <span style="color:var(--text-muted); font-weight:700;">to</span>
            <input type="date" id="end_date" value="<?= htmlspecialchars($endDate) ?>">
            <button onclick="applyDateFilter()" class="apply-btn">Apply Range</button>
        </div>
    </div>

    <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Date Ordered</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Receipt</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($orders)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding: 80px 0;">
                        <i class="fa-solid fa-box-open" style="font-size:40px; color:#e2e8f0; margin-bottom:15px; display:block;"></i>
                        <p style="color:var(--text-muted); font-weight:600;">No orders found in this criteria.</p>
                    </td>
                </tr>
                <?php else: foreach($orders as $o): 
                    $cls = $pillClass[strtolower($o['status'])] ?? 's-pending';
                ?>
                <tr>
                    <td class="order-id">#ORD-<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td class="cust-name"><?= htmlspecialchars($o['customer']) ?></td>
                    <td class="order-date"><?= date('M d, Y', strtotime($o['date'])) ?></td>
                    <td class="amount">₱<?= number_format($o['total'], 2) ?></td>
                    <td><span class="status-pill <?= $cls ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                    <td>
                        <a href="receipt.php?order_id=<?= $o['id'] ?>" target="_blank" class="act-btn btn-receipt" title="Print Receipt">
                            <i class="fa-solid fa-receipt"></i>
                        </a>
                    </td>
                    <td style="text-align:right;">
                        <div class="action-group" style="justify-content: flex-end;">
                            <button type="button" class="act-btn btn-edit" 
                                    onclick="openUpdateModal(<?= $o['id'] ?>, '<?= $o['status'] ?>')" 
                                    title="Update Status">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            
                            <button type="button" class="act-btn btn-cancel" 
                                    onclick="confirmCancel(<?= $o['id'] ?>)" 
                                    title="Cancel Order">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <div class="pagination-wrapper">
            <div class="page-info">
                Showing <b><?= count($orders) ?></b> of <b><?= $totalOrders ?></b> orders
            </div>
            <div class="page-btns">
                <?php if($page > 1): ?>
                    <a href="?filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="pg-link"><i class="fa-solid fa-chevron-left"></i></a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 1);
                $end = min($totalPages, $page + 1);
                for($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $i ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="pg-link <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if($page < $totalPages): ?>
                    <a href="?filter=<?= $filter ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="pg-link"><i class="fa-solid fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border:none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
      <form action="update_order_status.php" method="POST">
        <div class="modal-header border-0 padding-top-30 px-4">
          <h5 class="modal-title fw-bold" style="color: var(--text-dark);">Update Order Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body px-4 pb-4">
          <input type="hidden" name="order_id" id="modal_order_id">
          <label class="form-label small fw-bold text-muted mb-2">Change status to:</label>
          <select name="status" id="modal_status" class="form-select" style="border-radius: 12px; padding: 12px; border: 1px solid #edf2f7; font-weight: 600;">
            <option value="Order Placed">Order Placed</option>
            <option value="Processed">Processed</option>
            <option value="Shipped">Shipped</option>
            <option value="Out for Delivery">Out for Delivery</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="modal-footer border-0 px-4 pb-4">
          <button type="button" class="btn fw-bold" data-bs-dismiss="modal" style="color: var(--text-muted);">Cancel</button>
          <button type="submit" class="btn text-white fw-bold shadow-sm" style="background: var(--primary-orange); border-radius: 12px; padding: 10px 25px;">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function applyDateFilter(){
    const start = document.getElementById('start_date').value;
    const end   = document.getElementById('end_date').value;
    let url = `orders.php?filter=<?= $filter ?>&sort=<?= $sort ?>`;
    if(start) url += `&start_date=${start}`;
    if(end) url += `&end_date=${end}`;
    window.location.href = url;
}

function openUpdateModal(id, currentStatus) {
    document.getElementById('modal_order_id').value = id;
    document.getElementById('modal_status').value = currentStatus;
    var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
    myModal.show();
}

function confirmCancel(id) {
    Swal.fire({
        title: 'Cancel Order?',
        text: "Are you sure you want to cancel Order #ORD-" + id.toString().padStart(4, '0') + "?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f05a28',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, cancel it!',
        borderRadius: '20px'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cancel_order.php?order_id=' + id;
        }
    })
}
</script>

</body>
</html>