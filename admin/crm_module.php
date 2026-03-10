<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost"; $user = "root"; $pass = "lily1245"; $db = "localflair_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ── SEGMENT LOGIC ─────────────────────────────────────────────
// VIP: total spent >= 10000
// At-Risk: last order > 30 days ago AND has orders
// New: registered within last 30 days
// Regular: everyone else

$segment_filter = $_GET['segment'] ?? 'all';
$search = $_GET['search'] ?? '';

$seg_where = "";
if ($segment_filter === 'vip') {
    $seg_where = "AND (SELECT SUM(total_amount) FROM orders WHERE user_id = u.user_id AND status='Delivered') >= 10000";
} elseif ($segment_filter === 'at-risk') {
    $seg_where = "AND (SELECT MAX(order_date) FROM orders WHERE user_id = u.user_id) < NOW() - INTERVAL 30 DAY
                  AND (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) > 0";
} elseif ($segment_filter === 'new') {
    $seg_where = "AND u.created_at >= NOW() - INTERVAL 30 DAY";
} elseif ($segment_filter === 'inactive') {
    $seg_where = "AND (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) = 0";
}

$search_where = "";
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $search_where = "AND (u.first_name LIKE '%$s%' OR u.last_name LIKE '%$s%' OR u.email LIKE '%$s%')";
}

// ── SUMMARY METRICS ───────────────────────────────────────────
$total_customers = $conn->query("SELECT COUNT(*) as t FROM users")->fetch_assoc()['t'] ?? 0;

$prev_month_customers = $conn->query("SELECT COUNT(*) as t FROM users WHERE created_at < DATE_FORMAT(NOW(),'%Y-%m-01')")->fetch_assoc()['t'] ?? 1;
$customer_growth = $prev_month_customers > 0 ? round((($total_customers - $prev_month_customers) / $prev_month_customers) * 100, 1) : 0;

$avg_ltv = $conn->query("SELECT AVG(total_spent) as t FROM (SELECT user_id, SUM(total_amount) as total_spent FROM orders WHERE status='Delivered' GROUP BY user_id) x")->fetch_assoc()['t'] ?? 0;

// Retention: users who ordered in last 30 days / users who ordered in prev 30 days
$active_now = $conn->query("SELECT COUNT(DISTINCT user_id) as t FROM orders WHERE order_date >= NOW() - INTERVAL 30 DAY")->fetch_assoc()['t'] ?? 0;
$active_prev = $conn->query("SELECT COUNT(DISTINCT user_id) as t FROM orders WHERE order_date BETWEEN NOW()-INTERVAL 60 DAY AND NOW()-INTERVAL 30 DAY")->fetch_assoc()['t'] ?? 1;
$retention = $active_prev > 0 ? round(($active_now / $active_prev) * 100, 1) : 0;
$retention_change = round($retention - 100, 1);

// ── SEGMENT COUNTS ────────────────────────────────────────────
$vip_count = $conn->query("SELECT COUNT(DISTINCT user_id) as t FROM (SELECT user_id, SUM(total_amount) as ts FROM orders WHERE status='Delivered' GROUP BY user_id HAVING ts >= 10000) x")->fetch_assoc()['t'] ?? 0;
$at_risk_count = $conn->query("SELECT COUNT(*) as t FROM users u WHERE (SELECT MAX(order_date) FROM orders WHERE user_id=u.user_id) < NOW()-INTERVAL 30 DAY AND (SELECT COUNT(*) FROM orders WHERE user_id=u.user_id)>0")->fetch_assoc()['t'] ?? 0;
$new_count = $conn->query("SELECT COUNT(*) as t FROM users WHERE created_at >= NOW()-INTERVAL 30 DAY")->fetch_assoc()['t'] ?? 0;
$regular_count = max(0, $total_customers - $vip_count - $at_risk_count - $new_count);

// Percentages for chart
$vip_pct  = $total_customers > 0 ? round($vip_count/$total_customers*100) : 0;
$reg_pct  = $total_customers > 0 ? round($regular_count/$total_customers*100) : 0;
$risk_pct = $total_customers > 0 ? round($at_risk_count/$total_customers*100) : 0;
$new_pct  = $total_customers > 0 ? round($new_count/$total_customers*100) : 0;

// ── CUSTOMER DIRECTORY ────────────────────────────────────────
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$count_q = "SELECT COUNT(*) as t FROM users u WHERE 1=1 $seg_where $search_where";
$total_rows = $conn->query($count_q)->fetch_assoc()['t'] ?? 0;
$total_pages = ceil($total_rows / $limit);

$customers = $conn->query("
    SELECT u.user_id,
           CONCAT(u.first_name,' ',u.last_name) AS full_name,
           u.email, u.created_at,
           COALESCE((SELECT SUM(total_amount) FROM orders WHERE user_id=u.user_id AND status='Delivered'),0) AS total_spent,
           (SELECT MAX(order_date) FROM orders WHERE user_id=u.user_id) AS last_order,
           (SELECT COUNT(*) FROM orders WHERE user_id=u.user_id) AS order_count
    FROM users u
    WHERE 1=1 $seg_where $search_where
    ORDER BY total_spent DESC
    LIMIT $limit OFFSET $offset
");

// ── RECENT ACTIVITY ───────────────────────────────────────────
$activity = $conn->query("
    SELECT o.order_id, CONCAT(u.first_name,' ',u.last_name) AS name,
           o.total_amount, o.order_date, o.status,
           (SELECT COUNT(*) FROM order_items WHERE order_id=o.order_id) AS items
    FROM orders o JOIN users u ON o.user_id=u.user_id
    ORDER BY o.order_date DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | CRM Module</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --sidebar-w: 260px;
    --topbar-h: 80px;
    --bg: #f8fafc;
    --surface: #ffffff;
    --surface2: #f8fafc;
    --border: #edf2f7;
    --orange: #f05a28;
    --orange2: #ff8c5a;
    --text: #1e293b;
    --muted: #64748b;
    --green: #059669;
    --red: #dc2626;
    --gold: #d97706;
    --blue: #2563eb;
    --teal: #0891b2;
}

* { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans',sans-serif; }

body {
    background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

.main {
    margin-left: var(--sidebar-w);
    padding: calc(var(--topbar-h) + 30px) 36px 60px;
    min-height: 100vh;
}

/* ── PAGE HEADER ── */
.page-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 28px; flex-wrap: wrap; gap: 14px;
}
.page-header h2 { font-size: 24px; font-weight: 800; letter-spacing: -.4px; color: var(--text); }
.page-header p  { font-size: 13px; color: var(--muted); margin-top: 3px; }
.btn-add {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 20px; background: var(--orange); color: #fff;
    border: none; border-radius: 11px; font-size: 13px; font-weight: 700;
    cursor: pointer; text-decoration: none; transition: opacity .2s; white-space: nowrap;
}
.btn-add:hover { opacity: .85; }

/* ── METRIC CARDS ── */
.metrics { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 24px; }
.metric-card {
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.8);
    border-radius: 18px;
    padding: 26px 28px;
    position: relative; overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,.02);
    transition: transform .25s, box-shadow .25s;
}
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,.05); }
.metric-card::before {
    content:''; position:absolute; top:0; right:0;
    width:120px; height:120px; border-radius:50%;
    background: radial-gradient(circle, rgba(240,90,40,.05) 0%, transparent 70%);
}
.metric-label { font-size:11px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:var(--muted); margin-bottom:12px; }
.metric-value { font-size:36px; font-weight:800; color:var(--text); line-height:1; }
.metric-change { margin-top:10px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:5px; }
.up   { color: var(--green); }
.down { color: var(--red); }

/* ── MAIN LAYOUT ── */
.crm-layout { display: grid; grid-template-columns: 1fr 320px; gap: 18px; }

/* ── DIRECTORY PANEL ── */
.dir-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    overflow: hidden;
}
.dir-header {
    padding: 20px 22px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px;
}
.dir-header h3 { font-size: 16px; font-weight: 700; }
.dir-actions { display: flex; gap: 8px; }
.btn-sm {
    padding: 8px 14px; border-radius: 9px; font-size: 12px; font-weight: 700;
    border: 1px solid var(--border); background: #f8fafc; color: var(--text);
    cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all .2s;
    text-decoration: none;
}
.btn-sm:hover { background: var(--orange); border-color: var(--orange); color: #fff; }

/* Search */
.dir-search {
    padding: 14px 22px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.search-wrap {
    flex:1; display:flex; align-items:center; gap:10px;
    background:#f8fafc; border:1px solid var(--border);
    border-radius:10px; padding:9px 14px;
}
.search-wrap input {
    background:none; border:none; outline:none;
    color:var(--text); font-size:13px; font-family:inherit; width:100%;
}
.search-wrap input::placeholder { color:var(--muted); }
.search-wrap i { color:var(--muted); font-size:13px; }

/* Segment Filter Tabs */
.seg-tabs {
    padding: 12px 22px; border-bottom: 1px solid var(--border);
    display: flex; gap: 8px; flex-wrap: wrap;
}
.seg-tab {
    padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
    border: 1.5px solid var(--border); background: transparent; color: var(--muted);
    cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 6px;
    transition: all .2s;
}
.seg-tab .dot { width:7px; height:7px; border-radius:50%; }
.seg-tab.active, .seg-tab:hover { border-color: var(--orange); color: var(--text); background: #fff5f2; }
.seg-tab[data-seg="all"].active    { border-color:#94a3b8; background:#f8fafc; color:var(--text); }
.seg-tab[data-seg="vip"].active    { border-color:#d97706; background:#fffbeb; color:#d97706; }
.seg-tab[data-seg="at-risk"].active{ border-color:#dc2626; background:#fef2f2; color:#dc2626; }
.seg-tab[data-seg="new"].active    { border-color:#059669; background:#ecfdf5; color:#059669; }
.seg-tab[data-seg="inactive"].active{ border-color:#94a3b8; background:#f8fafc; color:#64748b; }

/* Table */
table { width:100%; border-collapse:collapse; }
thead th {
    padding:12px 18px; text-align:left;
    font-size:11px; font-weight:800; letter-spacing:1px;
    text-transform:uppercase; color:var(--muted);
    background: #fcfdfe; border-bottom:1px solid var(--border);
}
tbody td { padding:14px 18px; border-bottom:1px solid #f8fafc; font-size:13px; vertical-align:middle; }
tbody tr:hover td { background: #fafcff; }
tbody tr:last-child td { border-bottom:none; }

/* Avatar */
.avatar {
    width:38px; height:38px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:800; flex-shrink:0;
}
.customer-cell { display:flex; align-items:center; gap:12px; }
.customer-info .name { font-weight:700; font-size:14px; color:var(--text); }
.customer-info .email { font-size:12px; color:var(--muted); margin-top:1px; }

/* Segment badge */
.seg-badge {
    padding:4px 10px; border-radius:7px; font-size:11px; font-weight:700;
    display:inline-flex; align-items:center; gap:5px; white-space:nowrap;
}
.seg-vip      { background:#fffbeb; color:#d97706; }
.seg-regular  { background:#eff6ff; color:#2563eb; }
.seg-at-risk  { background:#fef2f2; color:#dc2626; }
.seg-new      { background:#ecfdf5; color:#059669; }
.seg-inactive { background:#f8fafc; color:#64748b; }

/* Status badge */
.status-badge {
    padding:4px 10px; border-radius:7px; font-size:11px; font-weight:700;
}
.status-active   { background:#ecfdf5; color:#059669; }
.status-inactive { background:#fef2f2; color:#dc2626; }

/* Pagination */
.dir-footer {
    padding:16px 22px; border-top:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
}
.dir-footer span { font-size:12px; color:var(--muted); }
.pg-links { display:flex; gap:5px; }
.pg-links a, .pg-links span {
    width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; text-decoration:none;
    border:1px solid var(--border); color:var(--text); background:#fff;
    transition:all .2s;
}
.pg-links a:hover { background:var(--orange); border-color:var(--orange); color:#fff; }
.pg-links span.cur { background:var(--orange); border-color:var(--orange); color:#fff; }
.pg-links span.dots { border:none; background:transparent; color:var(--muted); }

/* ── RIGHT COLUMN ── */
.right-col { display:flex; flex-direction:column; gap:18px; }

/* Segment Breakdown */
.panel {
    background:var(--surface); border:1px solid var(--border);
    border-radius:18px; padding:22px 24px;
}
.panel h3 { font-size:15px; font-weight:700; margin-bottom:20px; }

.donut-wrap { position:relative; width:160px; height:160px; margin:0 auto 20px; }
.donut-center {
    position:absolute; inset:0; display:flex; flex-direction:column;
    align-items:center; justify-content:center; pointer-events:none;
}
.donut-center .big { font-size:26px; font-weight:800; }
.donut-center .lbl { font-size:11px; color:var(--muted); margin-top:2px; }

.seg-legend { display:flex; flex-direction:column; gap:10px; }
.seg-legend-item { display:flex; align-items:center; justify-content:space-between; font-size:13px; }
.seg-legend-item .left { display:flex; align-items:center; gap:8px; }
.seg-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
.seg-pct { font-weight:700; color:var(--text); }

/* Activity */
.activity-list { display:flex; flex-direction:column; gap:0; }
.activity-item {
    display:flex; align-items:flex-start; gap:12px;
    padding:13px 0; border-bottom:1px solid rgba(255,255,255,.04);
}
.activity-item:last-child { border-bottom:none; }
.act-icon {
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:15px;
}
.act-info .act-title { font-size:13px; font-weight:600; color:var(--text); }
.act-info .act-sub   { font-size:11px; color:var(--muted); margin-top:2px; }
.act-time { font-size:11px; color:var(--muted); white-space:nowrap; margin-left:auto; flex-shrink:0; }

/* ── MODAL ── */
.modal-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.4);
    z-index:9999; align-items:center; justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal-box {
    background:#fff; border:1px solid var(--border); border-radius:20px;
    padding:32px; width:440px; max-width:95vw;
    box-shadow:0 30px 80px rgba(0,0,0,.12);
}
.modal-box h4 { font-size:18px; font-weight:800; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; color:var(--text); }
.modal-close { background:none; border:none; color:var(--muted); font-size:18px; cursor:pointer; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.6px; margin-bottom:7px; }
.form-group input, .form-group select {
    width:100%; padding:11px 14px; background:#f8fafc;
    border:1px solid var(--border); border-radius:10px;
    color:var(--text); font-size:13px; font-family:inherit; outline:none;
    transition:border-color .2s;
}
.form-group input:focus, .form-group select:focus { border-color:var(--orange); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.modal-actions { display:flex; gap:10px; margin-top:20px; }
.btn-cancel { flex:1; padding:11px; background:#f8fafc; border:1px solid var(--border); border-radius:10px; color:var(--text); font-size:13px; font-weight:700; cursor:pointer; }
.btn-save   { flex:1; padding:11px; background:var(--orange); border:none; border-radius:10px; color:#fff; font-size:13px; font-weight:700; cursor:pointer; }

@media(max-width:1100px) { .crm-layout { grid-template-columns:1fr; } }
@media(max-width:768px)  { .main { margin-left:0; padding:100px 16px 40px; } .metrics { grid-template-columns:1fr; } }
</style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>
<?php include('includes/topbar.php'); ?>

<main class="main">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <h2>Customer Relationship Management</h2>
            <p>Track customer profiles, interactions, and lifetime value</p>
        </div>
        <button class="btn-add" onclick="document.getElementById('addModal').classList.add('open')">
            <i class="fa-solid fa-plus"></i> Add Customer
        </button>
    </div>

    <!-- METRIC CARDS -->
    <div class="metrics">
        <div class="metric-card">
            <div class="metric-label">Total Customers</div>
            <div class="metric-value"><?= number_format($total_customers) ?></div>
            <div class="metric-change <?= $customer_growth >= 0 ? 'up' : 'down' ?>">
                <i class="fa-solid fa-arrow-trend-<?= $customer_growth >= 0 ? 'up' : 'down' ?>"></i>
                <?= ($customer_growth >= 0 ? '+' : '') . $customer_growth ?>% this month
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Avg. Lifetime Value</div>
            <div class="metric-value">₱<?= number_format($avg_ltv, 0) ?></div>
            <div class="metric-change up">
                <i class="fa-solid fa-arrow-trend-up"></i> 12% vs last quarter
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Retention Rate</div>
            <div class="metric-value"><?= $retention ?>%</div>
            <div class="metric-change <?= $retention_change >= 0 ? 'up' : 'down' ?>">
                <i class="fa-solid fa-arrow-trend-<?= $retention_change >= 0 ? 'up' : 'down' ?>"></i>
                <?= ($retention_change >= 0 ? '+' : '') . $retention_change ?>% vs last month
            </div>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="crm-layout">

        <!-- CUSTOMER DIRECTORY -->
        <div class="dir-panel">
            <div class="dir-header">
                <h3>Customer Directory</h3>
                <div class="dir-actions">
                    <a href="?segment=<?= $segment_filter ?>&search=<?= urlencode($search) ?>&export=csv" class="btn-sm">
                        <i class="fa-solid fa-file-export"></i> Export
                    </a>
                    <button class="btn-sm" onclick="document.getElementById('addModal').classList.add('open')">
                        <i class="fa-solid fa-sliders"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Search -->
            <form method="GET" class="dir-search">
                <input type="hidden" name="segment" value="<?= htmlspecialchars($segment_filter) ?>">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>

            <!-- Segment Tabs -->
            <div class="seg-tabs">
                <?php
                $tabs = [
                    'all'      => ['label'=>'All',      'dot'=>'#888'],
                    'vip'      => ['label'=>'VIP',      'dot'=>'#f5c842'],
                    'at-risk'  => ['label'=>'At-Risk',  'dot'=>'#f87171'],
                    'new'      => ['label'=>'New',      'dot'=>'#34d399'],
                    'inactive' => ['label'=>'Inactive', 'dot'=>'#7a8099'],
                ];
                foreach($tabs as $k=>$v):
                ?>
                <a href="?segment=<?= $k ?>&search=<?= urlencode($search) ?>"
                   class="seg-tab <?= $segment_filter===$k?'active':'' ?>" data-seg="<?= $k ?>">
                    <span class="dot" style="background:<?= $v['dot'] ?>"></span>
                    <?= $v['label'] ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Segment</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($c = $customers->fetch_assoc()):
                    $initials = strtoupper(substr(explode(' ',$c['full_name'])[0],0,1) . substr(explode(' ',$c['full_name'])[1]??'X',0,1));
                    $colors = ['#f05a28','#2563eb','#059669','#d97706','#7c3aed','#e11d48','#0891b2'];
                    $bg = $colors[crc32($c['full_name']) % count($colors)];

                    // Determine segment
                    if ($c['total_spent'] >= 10000) { $seg='vip'; $seg_label='VIP'; $seg_icon='⭐'; }
                    elseif (!$c['last_order'] || strtotime($c['last_order']) < strtotime('-30 days')) {
                        if($c['order_count'] == 0) { $seg='inactive'; $seg_label='Inactive'; $seg_icon=''; }
                        else { $seg='at-risk'; $seg_label='At-Risk'; $seg_icon='⚠'; }
                    }
                    elseif(strtotime($c['created_at']) >= strtotime('-30 days')) { $seg='new'; $seg_label='New'; $seg_icon='🌱'; }
                    else { $seg='regular'; $seg_label='Regular'; $seg_icon=''; }

                    // Status
                    $is_active = $c['last_order'] && strtotime($c['last_order']) >= strtotime('-30 days');
                    $days_inactive = $c['last_order'] ? floor((time()-strtotime($c['last_order']))/86400) : null;
                ?>
                <tr>
                    <td>
                        <div class="customer-cell">
                            <div class="avatar" style="background:<?= $bg ?>20;color:<?= $bg ?>"><?= $initials ?></div>
                            <div class="customer-info">
                                <div class="name"><?= htmlspecialchars($c['full_name']) ?></div>
                                <div class="email"><?= htmlspecialchars($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="seg-badge seg-<?= $seg ?>"><?= $seg_icon ? $seg_icon.' ' : '' ?><?= $seg_label ?></span></td>
                    <td style="font-weight:700;">₱<?= number_format($c['total_spent'],2) ?></td>
                    <td style="color:<?= ($seg==='at-risk'||$seg==='inactive') ? '#dc2626' : 'var(--muted)' ?>;font-size:13px;">
                        <?= $c['last_order'] ? date('M d', strtotime($c['last_order'])) : '—' ?>
                    </td>
                    <td>
                        <?php if($is_active): ?>
                            <span class="status-badge status-active">Active</span>
                        <?php elseif($days_inactive): ?>
                            <span class="status-badge status-inactive">Inactive <?= $days_inactive ?>d</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive">No orders</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="dir-footer">
                <span>Showing <?= min(($page-1)*$limit+1,$total_rows) ?>–<?= min($page*$limit,$total_rows) ?> of <?= number_format($total_rows) ?></span>
                <div class="pg-links">
                    <?php
                    $base = "?segment=$segment_filter&search=".urlencode($search)."&page=";
                    if($page>1) echo "<a href='{$base}".($page-1)."'><i class='fa-solid fa-chevron-left' style='font-size:10px'></i></a>";
                    for($p=1;$p<=$total_pages;$p++){
                        if($p==$page) echo "<span class='cur'>$p</span>";
                        elseif($p==1||$p==$total_pages||abs($p-$page)<=1) echo "<a href='{$base}$p'>$p</a>";
                        elseif(abs($p-$page)==2) echo "<span class='dots'>…</span>";
                    }
                    if($page<$total_pages) echo "<a href='{$base}".($page+1)."'><i class='fa-solid fa-chevron-right' style='font-size:10px'></i></a>";
                    ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-col">

            <!-- Segment Breakdown -->
            <div class="panel">
                <h3>Segment Breakdown</h3>
                <div class="donut-wrap">
                    <canvas id="segChart"></canvas>
                    <div class="donut-center">
                        <div class="big"><?= number_format($total_customers) ?></div>
                        <div class="lbl">customers</div>
                    </div>
                </div>
                <div class="seg-legend">
                    <div class="seg-legend-item">
                        <div class="left"><span class="seg-dot" style="background:#d97706"></span> VIP</div>
                        <span class="seg-pct"><?= $vip_pct ?>%</span>
                    </div>
                    <div class="seg-legend-item">
                        <div class="left"><span class="seg-dot" style="background:#2563eb"></span> Regular</div>
                        <span class="seg-pct"><?= $reg_pct ?>%</span>
                    </div>
                    <div class="seg-legend-item">
                        <div class="left"><span class="seg-dot" style="background:#dc2626"></span> At-Risk</div>
                        <span class="seg-pct"><?= $risk_pct ?>%</span>
                    </div>
                    <div class="seg-legend-item">
                        <div class="left"><span class="seg-dot" style="background:#059669"></span> New</div>
                        <span class="seg-pct"><?= $new_pct ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="panel">
                <h3>Recent Activity</h3>
                <div class="activity-list">
                <?php
                $act_icons = [
                    'Delivered'   => ['icon'=>'fa-bag-shopping','bg'=>'rgba(240,90,40,.15)','color'=>'#f05a28'],
                    'Order Placed'=> ['icon'=>'fa-cart-plus','bg'=>'rgba(96,165,250,.15)','color'=>'#60a5fa'],
                    'Cancelled'   => ['icon'=>'fa-xmark','bg'=>'rgba(248,113,113,.15)','color'=>'#f87171'],
                    'Shipped'     => ['icon'=>'fa-truck','bg'=>'rgba(52,211,153,.15)','color'=>'#34d399'],
                ];
                while($act = $activity->fetch_assoc()):
                    $ic = $act_icons[$act['status']] ?? $act_icons['Order Placed'];
                    $ago = human_time_diff(strtotime($act['order_date']));
                ?>
                <div class="activity-item">
                    <div class="act-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['color'] ?>">
                        <i class="fa-solid <?= $ic['icon'] ?>"></i>
                    </div>
                    <div class="act-info">
                        <div class="act-title"><?= htmlspecialchars($act['name']) ?> placed an order</div>
                        <div class="act-sub">₱<?= number_format($act['total_amount'],2) ?> · <?= $act['items'] ?> item<?= $act['items']!=1?'s':'' ?></div>
                    </div>
                    <div class="act-time"><?= $ago ?></div>
                </div>
                <?php endwhile; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- ADD CUSTOMER MODAL -->
<div class="modal-overlay" id="addModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <h4>Add Customer <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">✕</button></h4>
        <form method="POST" action="crm_add_customer.php">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Ana" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Lim" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="ana@example.com" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="+63 9XX XXX XXXX">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Temporary password" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-save">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#64748b';
const segCtx = document.getElementById('segChart').getContext('2d');
new Chart(segCtx, {
    type: 'doughnut',
    data: {
        labels: ['VIP','Regular','At-Risk','New'],
        datasets: [{
            data: [<?= $vip_count ?>, <?= $regular_count ?>, <?= $at_risk_count ?>, <?= $new_count ?>],
            backgroundColor: ['#d97706','#2563eb','#dc2626','#059669'],
            hoverOffset: 8,
            borderWidth: 0,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: {
            callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} customers` }
        }}
    }
});
</script>

<?php
function human_time_diff($ts) {
    $diff = time() - $ts;
    if($diff < 60)     return $diff.'s ago';
    if($diff < 3600)   return floor($diff/60).'m ago';
    if($diff < 86400)  return floor($diff/3600).'h ago';
    return floor($diff/86400).'d ago';
}
?>
</body>
</html>
