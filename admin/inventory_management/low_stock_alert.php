<?php
session_start();

// 1. SECURITY & DATABASE CONNECTION
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$host = "localhost";
$user = "root";     
$pass = "WelCome145";            
$db   = "localflair_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// 2. FETCH DATA FOR ALERTS
$threshold = 10;
$query = "SELECT p.*, c.category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.stock <= $threshold 
          ORDER BY p.stock ASC";
$result = $conn->query($query);

// Counter for real-time notification
$lowCount = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alerts | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --accent-orange: #e95a24;
            --accent-red: #ef4444;
            --accent-yellow: #f59e0b;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #f1f5f9;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* --- MAIN CONTENT ADJUSTMENT --- */
        .main { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 110px 40px 40px 40px; /* Space for fixed topbar */
            width: calc(100% - var(--sidebar-width)); 
        }

        .alert-card {
            background: var(--white);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--border-color);
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .alert-header h2 { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 12px; color: var(--text-dark); }
        .alert-header h2 i { color: var(--accent-red); }

        .stats-badge { text-align: right; }
        .stats-badge span { font-size: 10px; font-weight: 800; color: var(--accent-red); letter-spacing: 1.5px; text-transform: uppercase; }
        .stats-badge div { font-size: 32px; font-weight: 800; line-height: 1; margin-top: 5px; color: var(--text-dark); }

        /* --- TABLE STYLE --- */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; border-bottom: 2px solid var(--border-color); }
        td { padding: 20px 15px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }

        .product-info { display: flex; align-items: center; gap: 15px; }
        .product-info img { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; background: #f8fafc; border: 1px solid var(--border-color); }
        .product-info b { display: block; font-size: 14px; color: var(--text-dark); font-weight: 600; }
        .product-info small { color: var(--text-muted); font-size: 12px; }

        /* --- STOCK HEALTH BAR --- */
        .health-container { width: 140px; }
        .health-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .health-meta span { font-size: 11px; font-weight: 700; }
        .progress-bg { background: #f1f5f9; border-radius: 10px; height: 8px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 10px; transition: 1s cubic-bezier(0.4, 0, 0.2, 1); }

        .status-badge {
            padding: 6px 14px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
        }
        .critical { background: #fee2e2; color: #ef4444; }
        .warning { background: #fff4e0; color: #d97706; }

        .restock-btn {
            background: var(--text-dark);
            color: white;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }
        .restock-btn:hover { background: var(--accent-orange); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(233, 90, 36, 0.2); }

        .empty-state { text-align: center; padding: 80px 0; }
        .empty-state i { font-size: 60px; color: #10b981; margin-bottom: 20px; opacity: 0.2; }
        .empty-state h3 { font-size: 20px; font-weight: 700; color: var(--text-dark); }
        .empty-state p { color: var(--text-muted); font-size: 14px; }

        @media (max-width: 1024px) {
            .main { margin-left: 0; width: 100%; padding-top: 130px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main">
        <div class="alert-card">
            <div class="alert-header">
                <div>
                    <h2><i class="fa fa-triangle-exclamation"></i> Low Stock Alerts</h2>
                    <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Items listed here are below the threshold of <b><?= $threshold ?> units</b>.</p>
                </div>
                <div class="stats-badge">
                    <span>Active Alerts</span>
                    <div><?= $lowCount ?></div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="alertTable">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Current Stock</th>
                            <th>Stock Health</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
                            $stock = $row['stock'];
                            $is_critical = ($stock <= 3);
                            $status_class = $is_critical ? 'critical' : 'warning';
                            $status_text = $is_critical ? 'Critical' : 'Low Stock';
                            $bar_color = $is_critical ? 'var(--accent-red)' : 'var(--accent-yellow)';
                            $bar_width = min(($stock / $threshold) * 100, 100);
                        ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <?php $img = !empty($row['image']) ? '../uploads/'.$row['image'] : '../uploads/default.png'; ?>
                                    <img src="<?= $img ?>" onerror="this.src='../uploads/default.png'">
                                    <div>
                                        <b><?= htmlspecialchars($row['product_name']) ?></b>
                                        <small><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 16px; font-weight: 800; color: <?= $is_critical ? 'var(--accent-red)' : 'var(--text-dark)' ?>;">
                                    <?= $stock ?> <small style="font-weight: 500; font-size: 12px; color: var(--text-muted);">pcs</small>
                                </span>
                            </td>
                            <td>
                                <div class="health-container">
                                    <div class="health-meta">
                                        <span style="color: <?= $bar_color ?>;"><?= round($bar_width) ?>%</span>
                                    </div>
                                    <div class="progress-bg">
                                        <div class="progress-fill" style="width: <?= $bar_width ?>%; background: <?= $bar_color ?>;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td>
                                <a href="add_product.php?id=<?= $row['product_id'] ?>" class="restock-btn">
                                    <i class="fa fa-boxes-packing"></i> Restock
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fa fa-circle-check" style="opacity: 1; color: #10b981;"></i>
                                <h3>Inventory is Healthy</h3>
                                <p>Great job! All products are currently well-stocked.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Live Search Sync with Topbar
        const topSearch = document.getElementById('dirSearch');
        if (topSearch) {
            topSearch.placeholder = "Search low stock items...";
            topSearch.addEventListener('keyup', function() {
                let filter = this.value.toUpperCase();
                let rows = document.querySelectorAll("#alertTable tbody tr");
                
                rows.forEach(row => {
                    if(!row.classList.contains('empty-state')) {
                        let text = row.textContent.toUpperCase();
                        row.style.display = text.indexOf(filter) > -1 ? "" : "none";
                    }
                });
            });
        }
    </script>
</body>
</html>