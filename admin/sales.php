<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost"; $user = "root"; $pass = "lily1245"; $db = "localflair_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ── TIME FILTER ────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'monthly';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

switch ($filter) {
    case 'daily':
        $where = "DATE(o.order_date) = CURDATE()";
        $prev_where = "DATE(o.order_date) = CURDATE() - INTERVAL 1 DAY";
        break;
    case 'weekly':
        $where = "YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE(), 1)";
        $prev_where = "YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1)";
        break;
    case 'monthly':
        $where = "MONTH(o.order_date) = MONTH(CURDATE()) AND YEAR(o.order_date) = YEAR(CURDATE())";
        $prev_where = "MONTH(o.order_date) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(o.order_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
        break;
    case 'custom':
        $df = $conn->real_escape_string($date_from);
        $dt = $conn->real_escape_string($date_to);
        $where = "DATE(o.order_date) BETWEEN '$df' AND '$dt'";
        $prev_where = "1=0";
        break;
    default:
        $where = "1=1";
        $prev_where = "1=0";
}

// ── SUMMARY CARDS ──────────────────────────────────────────────
$total_sales   = $conn->query("SELECT SUM(total_amount) as t FROM orders o WHERE $where")->fetch_assoc()['t'] ?? 0;
$total_orders  = $conn->query("SELECT COUNT(*) as t FROM orders o WHERE $where")->fetch_assoc()['t'] ?? 0;
$avg_order     = $total_orders > 0 ? $total_sales / $total_orders : 0;
$unique_buyers = $conn->query("SELECT COUNT(DISTINCT o.user_id) as t FROM orders o WHERE $where")->fetch_assoc()['t'] ?? 0;

$prev_sales    = $conn->query("SELECT SUM(total_amount) as t FROM orders o WHERE $prev_where")->fetch_assoc()['t'] ?? 0;
$prev_orders   = $conn->query("SELECT COUNT(*) as t FROM orders o WHERE $prev_where")->fetch_assoc()['t'] ?? 0;

$sales_pct  = $prev_sales  > 0 ? round((($total_sales  - $prev_sales)  / $prev_sales)  * 100, 1) : 0;
$orders_pct = $prev_orders > 0 ? round((($total_orders - $prev_orders) / $prev_orders) * 100, 1) : 0;

// ── CHART: Sales Over Time ──────────────────────────────────────
$chart_labels = []; $chart_data = [];
if ($filter === 'daily') {
    for ($h = 0; $h < 24; $h++) {
        $chart_labels[] = str_pad($h,2,'0',STR_PAD_LEFT).':00';
        $r = $conn->query("SELECT SUM(total_amount) as t FROM orders WHERE DATE(order_date)=CURDATE() AND HOUR(order_date)=$h")->fetch_assoc();
        $chart_data[] = $r['t'] ?? 0;
    }
} elseif ($filter === 'weekly') {
    $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    for ($d = 1; $d <= 7; $d++) {
        $chart_labels[] = $days[$d-1];
        $r = $conn->query("SELECT SUM(total_amount) as t FROM orders WHERE YEARWEEK(order_date,1)=YEARWEEK(CURDATE(),1) AND DAYOFWEEK(order_date)=".($d==7?1:$d+1))->fetch_assoc();
        $chart_data[] = $r['t'] ?? 0;
    }
} elseif ($filter === 'monthly') {
    $days_in_month = date('t');
    for ($d = 1; $d <= $days_in_month; $d++) {
        $chart_labels[] = $d;
        $r = $conn->query("SELECT SUM(total_amount) as t FROM orders WHERE MONTH(order_date)=MONTH(CURDATE()) AND YEAR(order_date)=YEAR(CURDATE()) AND DAY(order_date)=$d")->fetch_assoc();
        $chart_data[] = $r['t'] ?? 0;
    }
} else {
    for ($i = 5; $i >= 0; $i--) {
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $m = date('m', strtotime("-$i months"));
        $y = date('Y', strtotime("-$i months"));
        $r = $conn->query("SELECT SUM(total_amount) as t FROM orders WHERE MONTH(order_date)=$m AND YEAR(order_date)=$y")->fetch_assoc();
        $chart_data[] = $r['t'] ?? 0;
    }
}

// ── TRANSACTIONS TABLE ─────────────────────────────────────────
$page  = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$total_rows = $conn->query("SELECT COUNT(*) as t FROM orders o WHERE $where")->fetch_assoc()['t'] ?? 0;
$total_pages = ceil($total_rows / $limit);

$transactions = $conn->query("
    SELECT o.order_id, CONCAT(u.first_name,' ',u.last_name) AS customer,
           o.order_date, o.total_amount, o.status,
           COUNT(oi.order_item_id) AS items
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE $where
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
    LIMIT $limit OFFSET $offset
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Sales Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --orange: #f05a28;
    --orange-light: #fff5f2;
    --sidebar-w: 260px;
    --topbar-h: 80px;
    --bg: #f8fafc;
    --dark: #1e293b;
    --muted: #64748b;
    --border: #edf2f7;
    --green: #059669;
    --red: #dc2626;
}
* { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans',sans-serif; }
body { background: linear-gradient(135deg,#f8fafd,#e2e8f0); min-height:100vh; color:var(--dark); }

.main { margin-left:var(--sidebar-w); padding:calc(var(--topbar-h) + 30px) 40px 60px; }

/* ── PAGE HEADER ── */
.page-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
.page-header-left h2 { font-size:26px; font-weight:800; letter-spacing:-.5px; }
.page-header-left p { color:var(--muted); font-size:13px; margin-top:3px; }

/* ── FILTER BAR ── */
.filter-bar {
    background:#fff;
    border-radius:16px;
    padding:16px 20px;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:26px;
    border:1px solid var(--border);
    box-shadow:0 4px 20px rgba(0,0,0,.03);
}
.filter-tabs { display:flex; gap:6px; }
.filter-tabs a {
    padding:8px 16px; border-radius:10px; font-size:13px; font-weight:600;
    text-decoration:none; color:var(--muted); background:#f8fafc; transition:all .2s;
}
.filter-tabs a.active, .filter-tabs a:hover { background:var(--orange); color:#fff; }
.divider { width:1px; height:30px; background:var(--border); margin:0 6px; }
.date-inputs { display:flex; align-items:center; gap:8px; }
.date-inputs input[type=date] {
    padding:8px 12px; border-radius:10px; border:1px solid var(--border);
    font-size:13px; font-family:inherit; color:var(--dark); background:#f8fafc;
    outline:none;
}
.btn-apply {
    padding:8px 18px; background:var(--orange); color:#fff;
    border:none; border-radius:10px; font-size:13px; font-weight:700;
    cursor:pointer; transition:opacity .2s;
}
.btn-apply:hover { opacity:.88; }

/* ── SUMMARY CARDS ── */
.cards { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:24px; }
.card {
    background:#fff; border-radius:20px; padding:22px 24px;
    border:1px solid var(--border);
    box-shadow:0 6px 24px rgba(0,0,0,.03);
    transition:transform .25s,box-shadow .25s;
}
.card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.06); }
.card-icon { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:14px; }
.card-label { font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.6px; }
.card-value { font-size:22px; font-weight:800; margin:4px 0 6px; }
.card-change { font-size:12px; font-weight:600; display:flex; align-items:center; gap:4px; }
.up { color:var(--green); } .down { color:var(--red); } .neutral { color:var(--muted); }

/* ── CHART PANEL ── */
.chart-panel {
    background:#fff; border-radius:20px; padding:24px 28px;
    border:1px solid var(--border);
    box-shadow:0 6px 24px rgba(0,0,0,.03);
    margin-bottom:24px;
}
.chart-panel h3 { font-size:16px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
.chart-wrap { height:280px; }

/* ── TABLE PANEL ── */
.table-panel {
    background:#fff; border-radius:20px;
    border:1px solid var(--border);
    box-shadow:0 6px 24px rgba(0,0,0,.03);
    overflow:hidden;
}
.table-panel-header {
    padding:20px 24px;
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:1px solid var(--border);
    flex-wrap:wrap; gap:12px;
}
.table-panel-header h3 { font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px; }
.btn-export {
    display:flex; align-items:center; gap:7px;
    padding:9px 18px; background:var(--orange); color:#fff;
    border:none; border-radius:10px; font-size:13px; font-weight:700;
    cursor:pointer; text-decoration:none; transition:opacity .2s;
}
.btn-export:hover { opacity:.88; }

table { width:100%; border-collapse:collapse; }
thead th {
    text-align:left; padding:13px 18px;
    background:#fcfdfe; color:#94a3b8;
    font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:1px;
    border-bottom:1px solid var(--border);
}
tbody td { padding:14px 18px; border-bottom:1px solid #f8fafc; font-size:14px; vertical-align:middle; }
tbody tr:last-child td { border-bottom:none; }
tbody tr:hover td { background:#fafcff; }

.status-badge {
    padding:4px 10px; border-radius:7px; font-size:11px; font-weight:700; white-space:nowrap;
}
.status-delivered { background:#ecfdf5; color:#059669; }
.status-placed    { background:#fffbeb; color:#d97706; }
.status-cancelled { background:#fef2f2; color:#dc2626; }
.status-shipped   { background:#eff6ff; color:#2563eb; }
.status-processing { background:#f5f3ff; color:#7c3aed; }

/* ── PAGINATION ── */
.pagination { padding:18px 24px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid var(--border); flex-wrap:wrap; gap:10px; }
.pagination-info { font-size:13px; color:var(--muted); }
.pagination-links { display:flex; gap:6px; }
.pagination-links a, .pagination-links span {
    width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:600; text-decoration:none; border:1px solid var(--border);
    color:var(--dark); background:#fff; transition:all .2s;
}
.pagination-links a:hover { background:var(--orange); color:#fff; border-color:var(--orange); }
.pagination-links span.current { background:var(--orange); color:#fff; border-color:var(--orange); }
.pagination-links span.dots { border:none; background:transparent; color:var(--muted); }

/* ── EXPORT MODAL ── */
.modal-overlay {
    display:none; position:fixed; inset:0; background:rgba(0,0,0,.4);
    z-index:9999; align-items:center; justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal-box {
    background:#fff; border-radius:20px; padding:32px;
    width:360px; box-shadow:0 20px 60px rgba(0,0,0,.15);
}
.modal-box h4 { font-size:18px; font-weight:800; margin-bottom:6px; }
.modal-box p { font-size:13px; color:var(--muted); margin-bottom:22px; }
.modal-options { display:flex; gap:12px; }
.modal-btn {
    flex:1; padding:13px; border-radius:12px; border:2px solid var(--border);
    font-size:14px; font-weight:700; cursor:pointer; background:#fff;
    display:flex; flex-direction:column; align-items:center; gap:7px;
    transition:all .2s; color:var(--dark);
}
.modal-btn:hover { border-color:var(--orange); color:var(--orange); background:var(--orange-light); }
.modal-btn i { font-size:22px; }
.modal-close { float:right; background:none; border:none; font-size:18px; cursor:pointer; color:var(--muted); margin-top:-10px; }

@media(max-width:1200px) { .cards { grid-template-columns:repeat(2,1fr); } }
@media(max-width:768px) { .main { margin-left:0; padding:100px 16px 40px; } .cards { grid-template-columns:1fr 1fr; } }
</style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>
<?php include('includes/topbar.php'); ?>

<main class="main">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-left">
            <h2>Sales Report</h2>
            <p>Track revenue, orders, and transaction history.</p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" id="filterForm">
        <div class="filter-bar">
            <div class="filter-tabs">
                <?php foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','custom'=>'Custom'] as $k=>$v): ?>
                <a href="?filter=<?= $k ?>" class="<?= $filter===$k?'active':'' ?>"><?= $v ?></a>
                <?php endforeach; ?>
            </div>

            <?php if($filter==='custom'): ?>
            <div class="divider"></div>
            <div class="date-inputs">
                <i class="fa-solid fa-calendar-days" style="color:var(--muted);font-size:14px;"></i>
                <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                <span style="color:var(--muted);font-size:13px;">to</span>
                <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                <input type="hidden" name="filter" value="custom">
                <button type="submit" class="btn-apply">Apply</button>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- SUMMARY CARDS -->
    <div class="cards">
        <?php
        $cards = [
            ['icon'=>'fa-peso-sign','bg'=>'#fff5f2','color'=>'#f05a28','label'=>'Total Revenue','value'=>'₱'.number_format($total_sales,2),'pct'=>$sales_pct],
            ['icon'=>'fa-cart-shopping','bg'=>'#ecfdf5','color'=>'#059669','label'=>'Total Orders','value'=>number_format($total_orders),'pct'=>$orders_pct],
        ];
        foreach($cards as $c):
            $pct = $c['pct'];
            if($pct === null) { $dir='neutral'; $arrow=''; $txt='—'; }
            elseif($pct >= 0) { $dir='up'; $arrow='<i class="fa-solid fa-arrow-trend-up"></i>'; $txt='+'.$pct.'% vs prev'; }
            else { $dir='down'; $arrow='<i class="fa-solid fa-arrow-trend-down"></i>'; $txt=$pct.'% vs prev'; }
        ?>
        <div class="card">
            <div class="card-icon" style="background:<?=$c['bg']?>;color:<?=$c['color']?>"><i class="fa-solid <?=$c['icon']?>"></i></div>
            <div class="card-label"><?=$c['label']?></div>
            <div class="card-value"><?=$c['value']?></div>
            <div class="card-change <?=$dir?>"><?=$arrow?> <?=$txt?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SALES CHART -->
    <div class="chart-panel">
        <h3><i class="fa-solid fa-chart-area" style="color:var(--orange)"></i> Revenue Over Time</h3>
        <div class="chart-wrap">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- TRANSACTIONS TABLE -->
    <div class="table-panel">
        <div class="table-panel-header">
            <h3><i class="fa-solid fa-table-list" style="color:var(--orange)"></i> Transactions
                <span style="font-size:12px;font-weight:600;color:var(--muted);margin-left:6px;"><?= number_format($total_rows) ?> records</span>
            </h3>
            <button class="btn-export" onclick="document.getElementById('exportModal').classList.add('open')">
                <i class="fa-solid fa-file-export"></i> Export
            </button>
        </div>

        <table id="salesTable">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date & Time</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $transactions->fetch_assoc()):
                    $st = strtolower(str_replace(' ','-',$row['status']));
                    $st = str_replace('order-placed','placed',$st);
                ?>
                <tr>
                    <td style="font-weight:700;color:var(--orange);">#<?= str_pad($row['order_id'],4,'0',STR_PAD_LEFT) ?></td>
                    <td><strong><?= htmlspecialchars($row['customer']) ?></strong></td>
                    <td style="color:var(--muted);font-size:13px;"><?= date('M d, Y · g:i A', strtotime($row['order_date'])) ?></td>
                    <td style="color:var(--muted);"><?= $row['items'] ?> item<?= $row['items']!=1?'s':'' ?></td>
                    <td style="font-weight:800;">₱<?= number_format($row['total_amount'],2) ?></td>
                    <td><span class="status-badge status-<?= $st ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <div class="pagination">
            <div class="pagination-info">
                Showing <?= min(($page-1)*$limit+1,$total_rows) ?>–<?= min($page*$limit,$total_rows) ?> of <?= number_format($total_rows) ?>
            </div>
            <div class="pagination-links">
                <?php
                $base = "?filter=$filter&date_from=$date_from&date_to=$date_to&page=";
                if($page>1) echo "<a href='{$base}".($page-1)."'><i class='fa-solid fa-chevron-left' style='font-size:11px;'></i></a>";
                for($p=1;$p<=$total_pages;$p++){
                    if($p==$page) echo "<span class='current'>$p</span>";
                    elseif($p==1||$p==$total_pages||abs($p-$page)<=1) echo "<a href='{$base}$p'>$p</a>";
                    elseif(abs($p-$page)==2) echo "<span class='dots'>…</span>";
                }
                if($page<$total_pages) echo "<a href='{$base}".($page+1)."'><i class='fa-solid fa-chevron-right' style='font-size:11px;'></i></a>";
                ?>
            </div>
        </div>
    </div>
</main>

<!-- EXPORT MODAL -->
<div class="modal-overlay" id="exportModal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('exportModal').classList.remove('open')">✕</button>
        <h4>Export Sales Report</h4>
        <p>Choose your preferred export format.</p>
        <div class="modal-options">
            <button class="modal-btn" onclick="exportCSV()">
                <i class="fa-solid fa-file-csv"></i> CSV
            </button>
            <button class="modal-btn" onclick="exportPDF()">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
</div>

<!-- jsPDF for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<script>
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#64748b';

const salesCtx = document.getElementById('salesChart').getContext('2d');
const gradient = salesCtx.createLinearGradient(0,0,0,280);
gradient.addColorStop(0,'rgba(240,90,40,.18)');
gradient.addColorStop(1,'rgba(240,90,40,0)');

new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue (₱)',
            data: <?= json_encode($chart_data) ?>,
            borderColor: '#f05a28',
            backgroundColor: gradient,
            fill: true,
            tension: 0.45,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#f05a28',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ₱' + Number(ctx.raw).toLocaleString('en-PH', {minimumFractionDigits:2})
                }
            }
        },
        scales: {
            y: { beginAtZero:true, grid:{ borderDash:[5,5], color:'#f1f5f9' },
                 ticks: { callback: v => '₱'+v.toLocaleString() } },
            x: { grid:{ display:false } }
        }
    }
});

// ── CSV EXPORT ──────────────────────────────────────────────────
function exportCSV() {
    const table = document.getElementById('salesTable');
    let csv = [];
    for(let row of table.rows) {
        let cols = [];
        for(let cell of row.cells) cols.push('"' + cell.innerText.replace(/"/g,'""') + '"');
        csv.push(cols.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'sales_report_<?= date('Ymd') ?>.csv';
    a.click();
    document.getElementById('exportModal').classList.remove('open');
}

// ── PDF EXPORT ──────────────────────────────────────────────────
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation:'landscape' });

    doc.setFontSize(18);
    doc.setTextColor(240, 90, 40);
    doc.text('LocalFlair — Sales Report', 14, 18);

    doc.setFontSize(10);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: <?= date('F d, Y') ?> | Filter: <?= ucfirst($filter) ?>', 14, 26);

    const summaryY = 34;
    const summaryData = [
        ['Total Revenue', '₱<?= number_format($total_sales,2) ?>'],
        ['Total Orders', '<?= number_format($total_orders) ?>'],
   
    ];
    doc.autoTable({
        startY: summaryY,
        head: [['Metric','Value']],
        body: summaryData,
        theme: 'grid',
        headStyles: { fillColor:[240,90,40], textColor:255, fontSize:9 },
        bodyStyles: { fontSize:9 },
        columnStyles: { 1: { fontStyle:'bold' } },
        tableWidth: 80,
        margin: { left:14 }
    });

    const table = document.getElementById('salesTable');
    const headers = Array.from(table.querySelectorAll('thead th')).map(th=>th.innerText);
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr=>
        Array.from(tr.querySelectorAll('td')).map(td=>td.innerText)
    );

    doc.autoTable({
        startY: doc.lastAutoTable.finalY + 10,
        head: [headers],
        body: rows,
        theme: 'striped',
        headStyles: { fillColor:[240,90,40], textColor:255, fontSize:8 },
        bodyStyles: { fontSize:8 },
        alternateRowStyles: { fillColor:[255,245,242] },
        margin: { left:14, right:14 }
    });

    doc.save('sales_report_<?= date('Ymd') ?>.pdf');
    document.getElementById('exportModal').classList.remove('open');
}
</script>
</body>
</html>
