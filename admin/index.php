<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Database Connection
$host = "localhost";
$user = "root";
$pass = "lily1245"; 
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- 1. SUMMARY CARDS QUERIES ---
$userCount = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;
$orderCount = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'] ?? 0;
$salesResult = $conn->query("SELECT SUM(total_amount) as total FROM orders")->fetch_assoc()['total'] ?? 0;
$pendingCount = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'Order Placed'")->fetch_assoc()['total'] ?? 0;

// --- 2. SALES CHART DATA ---
$salesData = [];
$salesLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $monthLabel = date('M', strtotime("-$i months"));
    $monthNum = date('m', strtotime("-$i months"));
    $yearNum = date('Y', strtotime("-$i months"));
    $sQuery = "SELECT SUM(total_amount) as monthly FROM orders WHERE MONTH(order_date) = '$monthNum' AND YEAR(order_date) = '$yearNum'";
    $sRes = $conn->query($sQuery)->fetch_assoc();
    $salesLabels[] = $monthLabel;
    $salesData[] = $sRes['monthly'] ?? 0;
}

// --- 3. CATEGORY CHART DATA ---
$catLabels = []; $catCounts = [];
$cQuery = "SELECT c.category_name, COUNT(oi.product_id) as count 
           FROM categories c 
           LEFT JOIN products p ON c.category_id = p.category_id 
           LEFT JOIN order_items oi ON p.product_id = oi.product_id 
           GROUP BY c.category_name";
$cRes = $conn->query($cQuery);
while($row = $cRes->fetch_assoc()) {
    $catLabels[] = $row['category_name'];
    $catCounts[] = $row['count'] ?? 0;
}

// --- 4. RECENT ORDERS TABLE ---
$recentOrders = $conn->query("
    SELECT o.order_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, o.order_date, o.total_amount 
    FROM orders o JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_date DESC LIMIT 5");

// --- 5. TOP SELLING PRODUCTS ---
$topProducts = $conn->query("
    SELECT p.product_name, c.category_name, SUM(oi.quantity) as sold 
    FROM order_items oi JOIN products p ON oi.product_id = p.product_id 
    JOIN categories c ON p.category_id = c.category_id 
    GROUP BY p.product_id, p.product_name, c.category_name 
    ORDER BY sold DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalFlair | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-orange: #f05a28;
            --bg-color: #f8fafc;
            --sidebar-width: 260px;
            --topbar-height: 80px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%); 
            min-height: 100vh; 
            color: var(--text-dark);
            overflow-x: hidden;
        }

        .main { 
            margin-left: var(--sidebar-width); 
            padding: calc(var(--topbar-height) + 30px) 40px 40px 40px; 
            width: calc(100% - var(--sidebar-width));
            transition: all 0.3s ease;
        }

        /* PAGE HEADER */
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .page-header p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* SUMMARY CARDS */
        .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        
        .stat-card { 
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px; 
            border-radius: 24px; 
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.05); }

        .stat-card .icon-box { 
            width: 50px; height: 50px; border-radius: 15px; 
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 15px;
        }

        .icon-users { background: #eff6ff; color: #2563eb; }
        .icon-orders { background: #ecfdf5; color: #059669; }
        .icon-sales { background: #fff5f2; color: var(--primary-orange); }
        .icon-pending { background: #fffbeb; color: #d97706; }

        .stat-card h4 { font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-top: 5px; }

        /* CHARTS SECTION */
        .charts-container { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 30px; }
        
        .glass-panel { 
            background: #ffffff; 
            padding: 25px; 
            border-radius: 24px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid #edf2f7;
        }

        .glass-panel h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* TABLES SECTION */
        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left; padding: 15px; background: #fcfdfe;
            color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; border-bottom: 1px solid #f1f5f9;
        }

        tbody td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 14px; vertical-align: middle; }
        
        .badge-sold { background: #fff5f2; color: var(--primary-orange); padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 12px; }
        
        @media (max-width: 1200px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-container, .data-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 992px) {
            .main { margin-left: 0; width: 100%; padding: 100px 20px 20px 20px; }
        }
    </style>
</head>
<body>

    <?php include('includes/sidebar.php'); ?>
    <?php include('includes/topbar.php'); ?>

    <main class="main">
        <div class="page-header animate__animated animate__fadeIn">
            <h2>Admin Dashboard</h2>
            <p>Welcome back! Here's what's happening with your store today.</p>
        </div>

        <section class="cards-grid">
            <div class="stat-card animate__animated animate__fadeInUp">
                <div class="icon-box icon-users"><i class="fa-solid fa-users"></i></div>
                <h4>Total Users</h4>
                <p><?php echo number_format($userCount); ?></p>
            </div>
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="icon-box icon-orders"><i class="fa-solid fa-box"></i></div>
                <h4>Total Orders</h4>
                <p><?php echo number_format($orderCount); ?></p>
            </div>
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="icon-box icon-sales"><i class="fa-solid fa-peso-sign"></i></div>
                <h4>Total Sales</h4>
                <p>₱<?php echo number_format($salesResult, 2); ?></p>
            </div>
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="icon-box icon-pending"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h4>Pending Orders</h4>
                <p><?php echo number_format($pendingCount); ?></p>
            </div>
        </section>

        <section class="charts-container animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            <div class="glass-panel">
                <h3><i class="fa-solid fa-chart-line" style="color:var(--primary-orange)"></i> Sales Overview</h3>
                <div style="height: 300px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="glass-panel">
                <h3><i class="fa-solid fa-chart-pie" style="color:var(--primary-orange)"></i> Category Distribution</h3>
                <div style="height: 300px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </section>

        <section class="data-grid animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
            <div class="glass-panel">
                <h3>Recent Orders</h3>
                <table>
                    <thead><tr><th>ID</th><th>Customer</th><th>Date</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php while($row = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:700; color:var(--primary-orange);">#<?php echo str_pad($row['order_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><strong style="color:var(--text-dark);"><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                            <td style="color:var(--text-muted); font-size:13px;"><?php echo date('M d, Y', strtotime($row['order_date'])); ?></td>
                            <td style="font-weight:800;">₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="glass-panel">
                <h3>Top Selling Products</h3>
                <table>
                    <thead><tr><th>Product Name</th><th>Category</th><th style="text-align:right;">Sold</th></tr></thead>
                    <tbody>
                        <?php while($row = $topProducts->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:600;"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td style="color:var(--text-muted);"><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td style="text-align:right;"><span class="badge-sold"><?php echo $row['sold']; ?> pcs</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        // Set Default Chart Font
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#64748b';

        // Sales Line Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($salesLabels); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode($salesData); ?>,
                    borderColor: '#f05a28',
                    backgroundColor: (context) => {
                        const gradient = context.chart.ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, 'rgba(240, 90, 40, 0.2)');
                        gradient.addColorStop(1, 'rgba(240, 90, 40, 0)');
                        return gradient;
                    },
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#f05a28',
                    pointBorderWidth: 2
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Category Doughnut Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($catLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($catCounts); ?>,
                    backgroundColor: ['#f05a28', '#2563eb', '#059669', '#d97706', '#7c3aed'],
                    hoverOffset: 10,
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { 
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } } 
                }
            }
        });
    </script>
</body>
</html>