<?php 
include "../db.php"; 
session_start();

if (!isset($_SESSION['admin_name'])) {
    header("Location: login.php");
    exit();
}

// Logic para sa summary stats
$res_count = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM orders WHERE status = 'Delivered'");
$totalLoyal = mysqli_fetch_assoc($res_count)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Suki Program</title>
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
        transition: all 0.3s ease;
    }

    /* HEADER */
    .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
    .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

    /* STATS CARD (Patterned after your order summary style) */
    .stats-container {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }

    .stat-mini-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        gap: 15px;
        min-width: 250px;
    }

    .stat-icon {
        width: 45px;
        height: 45px;
        background: #fff5f2;
        color: var(--primary-orange);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 20px;
    }

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

    .cust-name { font-weight: 700; color: var(--text-dark); }
    .order-count { color: var(--text-muted); font-size: 13px; font-weight: 600; }
    .amount { font-weight: 800; color: var(--text-dark); }

    /* STATUS/TIER PILLS */
    .tier-pill { 
        padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; 
        text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
    }
    .gold { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
    .silver { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .bronze { background: #fff5f2; color: var(--primary-orange); border: 1px solid #ffe4de; }

    .points-badge {
        background: var(--text-dark);
        color: white;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

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
            <h2>Suki Program Management</h2>
            <p>Monitor your most loyal customers and their reward points.</p>
        </div>
    </div>

    <div class="stats-container animate__animated animate__fadeInUp">
        <div class="stat-mini-card">
            <div class="stat-icon">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div>
                <p style="color:var(--text-muted); font-size:12px; font-weight:700; text-transform:uppercase;">Total Loyal Customers</p>
                <h3 style="font-size:22px; font-weight:800;"><?= $totalLoyal ?></h3>
            </div>
        </div>
    </div>

    <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9;">
            <h3 style="font-size: 16px; font-weight: 800; color: var(--text-dark);">
                <i class="fa-solid fa-ranking-star" style="color: #d97706; margin-right: 10px;"></i>Top Sukis Leaderboard
            </h3>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Total Orders</th>
                    <th>Total Spend</th>
                    <th>Flair Points</th>
                    <th>Loyalty Tier</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT u.first_name, u.last_name, u.flair_points, COUNT(o.order_id) as total_orders, SUM(o.total_amount) as total_spent 
                          FROM users u 
                          JOIN orders o ON u.user_id = o.user_id 
                          WHERE o.status = 'Delivered'
                          GROUP BY u.user_id 
                          ORDER BY total_orders DESC";
                
                $res = mysqli_query($conn, $query);
                if(mysqli_num_rows($res) > 0):
                    while($row = mysqli_fetch_assoc($res)):
                        $orders = $row['total_orders'];
                        
                        // Tier Logic
                        if($orders >= 10) { $tier = "Gold"; $class = "gold"; $icon = "fa-medal"; }
                        elseif($orders >= 5) { $tier = "Silver"; $class = "silver"; $icon = "fa-award"; }
                        else { $tier = "Bronze"; $class = "bronze"; $icon = "fa-certificate"; }
                ?>
                <tr>
                    <td class="cust-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td class="order-count">
                        <i class="fa-solid fa-bag-shopping" style="margin-right:8px; opacity:0.5;"></i><?= $orders ?> Orders
                    </td>
                    <td class="amount">₱<?= number_format($row['total_spent'], 2) ?></td>
                    <td><span class="points-badge"><i class="fa-solid fa-star" style="color:#fbbf24; margin-right:5px;"></i><?= $row['flair_points'] ?></span></td>
                    <td>
                        <span class="tier-pill <?= $class ?>">
                            <i class="fa-solid <?= $icon ?>"></i> <?= $tier ?> Suki
                        </span>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 80px 0;">
                        <i class="fa-solid fa-award" style="font-size:40px; color:#e2e8f0; margin-bottom:15px; display:block;"></i>
                        <p style="color:var(--text-muted); font-weight:600;">No Suki records found yet.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>