<?php
session_start();
include('db.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Security: Clean the user_id
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e95a24;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        body { 
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #1e293b;
            min-height: 100vh;
            background: linear-gradient(135deg, #fdf6f0 0%, #fff1eb 25%, #f8fafc 50%, #f1f5f9 100%);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            overflow-x: hidden;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .order-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; position: relative; z-index: 1; }

        .back-home {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            padding: 10px 18px;
            border-radius: 15px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .back-home:hover {
            color: var(--primary-color);
            transform: translateX(-5px);
            background: white;
        }

        .history-title { font-weight: 800; font-size: 2.8rem; letter-spacing: -1.5px; margin-bottom: 8px; color: #0f172a; }
        .history-sub { color: #64748b; margin-bottom: 40px; font-size: 1.05rem; }
        
        .nav-tabs { border: none; margin-bottom: 30px; display: flex; gap: 8px; flex-wrap: wrap; }
        .nav-link { 
            border: none !important; 
            border-radius: 12px !important; 
            padding: 10px 18px !important; 
            font-weight: 700; 
            font-size: 0.85rem;
            color: #64748b !important;
            background: var(--glass-bg) !important;
            backdrop-filter: blur(5px);
            border: 1px solid var(--glass-border) !important;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .nav-link.active { 
            background: var(--primary-color) !important; 
            color: white !important;
            box-shadow: 0 8px 20px rgba(233, 90, 36, 0.3);
            border: none !important;
        }

        .order-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(15px);
            border-radius: 28px; 
            padding: 28px; 
            margin-bottom: 25px; 
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .order-card:hover { 
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.08);
            background: rgba(255, 255, 255, 0.95);
        }

        .status-badge { 
            font-size: 10px; font-weight: 800; text-transform: uppercase; 
            padding: 6px 14px; border-radius: 100px; letter-spacing: 0.8px; 
        }
        .s-placed { background: #eff6ff; color: #2563eb; }
        .s-processed { background: #f1f5f9; color: #475569; }
        .s-shipped { background: #fffbeb; color: #d97706; }
        .s-out { background: #f0fdf4; color: #16a34a; font-weight: 900; border: 1px dashed #16a34a; }
        .s-delivered { background: #ecfdf5; color: #059669; }
        .s-cancelled { background: #fef2f2; color: #dc2626; }

        .order-id { font-weight: 800; font-size: 1.35rem; margin: 12px 0; color: #0f172a; }
        .order-meta { font-size: 0.95rem; color: #64748b; }
        .order-total { font-weight: 800; color: #e95a24; font-size: 1.2rem; }

        .prod-img { 
            width: 75px; height: 75px; 
            border-radius: 20px; 
            object-fit: cover; 
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .btn-action { 
            border-radius: 16px; font-size: 0.9rem; font-weight: 700; 
            padding: 10px 20px; border: none; transition: all 0.3s ease; 
            text-decoration: none; display: inline-block;
        }
        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-view:hover { background: #1e293b; color: white; }
        .btn-buy { background: #fff1eb; color: var(--primary-color); }
        .btn-buy:hover { background: var(--primary-color); color: white; transform: scale(1.05); }

        .shape { position: fixed; z-index: 0; filter: blur(80px); border-radius: 50%; opacity: 0.6; }
        .shape-1 { width: 300px; height: 300px; background: #ffe6db; top: -100px; left: -100px; }
        .shape-2 { width: 400px; height: 400px; background: #e0f2fe; bottom: -150px; right: -100px; }
    </style>
</head>
<body>

<div class="shape shape-1"></div>
<div class="shape shape-2"></div>

<div class="order-container">
    <a href="index.php" class="back-home animate__animated animate__fadeInLeft">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
        </svg>
        Back to Home
    </a>

    <h1 class="history-title animate__animated animate__fadeInDown">Order History</h1>
    <p class="history-sub animate__animated animate__fadeInDown">Your curated journey of local Philippine finds.</p>

    <ul class="nav nav-tabs animate__animated animate__fadeIn">
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'All' ? 'active' : '' ?>" href="order-history.php?status=All">All Orders</a></li>
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'Order Placed' ? 'active' : '' ?>" href="order-history.php?status=Order+Placed">Placed</a></li>
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'Processed' ? 'active' : '' ?>" href="order-history.php?status=Processed">Processed</a></li>
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'Shipped' ? 'active' : '' ?>" href="order-history.php?status=Shipped">Shipped</a></li>
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'Out for Delivery' ? 'active' : '' ?>" href="order-history.php?status=Out+for+Delivery">Out for Delivery</a></li>
        <li class="nav-item"><a class="nav-link <?= $status_filter == 'Delivered' ? 'active' : '' ?>" href="order-history.php?status=Delivered">Delivered</a></li>
    </ul>

    <div class="animate__animated animate__fadeInUp">
        <?php
        // Updated SQL to check if order is already reviewed
        $sql = "SELECT o.*, 
                (SELECT product_image FROM order_items WHERE order_id = o.order_id LIMIT 1) as first_item_img,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as total_items,
                (SELECT COUNT(*) FROM reviews WHERE order_id = o.order_id AND user_id = '$user_id') as is_reviewed
                FROM orders o WHERE o.user_id = '$user_id'";
        
        if ($status_filter != 'All') {
            $sql .= " AND o.status = '$status_filter'";
        }
        $sql .= " ORDER BY o.order_date DESC";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $status = $row['status'];
                
                $class = 's-processed';
                if ($status == 'Order Placed') $class = 's-placed';
                if ($status == 'Processed') $class = 's-processed';
                if ($status == 'Shipped') $class = 's-shipped';
                if ($status == 'Out for Delivery') $class = 's-out';
                if ($status == 'Delivered') $class = 's-delivered';
                if ($status == 'Cancelled') $class = 's-cancelled';

                $img_filename = trim($row['first_item_img']);
                $image_src = (!empty($img_filename)) ? "admin/uploads/" . $img_filename : "images/no-image.png";
        ?>
            <div class="order-card d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-badge <?= $class ?>"><?= htmlspecialchars($status) ?></span>
                        <span class="order-meta">Ordered on <?= date('M d, Y', strtotime($row['order_date'])) ?></span>
                    </div>
                    <div class="order-id">Order #LF-<?= str_pad($row['order_id'], 6, '0', STR_PAD_LEFT) ?></div>
                    <div class="order-meta">Total Amount: <span class="order-total">₱<?= number_format($row['total_amount'], 2) ?></span></div>
                </div>

                <div class="d-flex align-items-center mt-4 mt-md-0">
                    <div class="me-4 position-relative">
                        <img src="<?= $image_src ?>" class="prod-img" onerror="this.src='images/placeholder.jpg'">
                        <?php if ($row['total_items'] > 1): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-dark">
                                +<?= $row['total_items'] - 1 ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2"> 
                        <?php if ($status == 'Delivered'): ?>
                            <?php if ($row['is_reviewed'] > 0): ?>
                                <button class="btn btn-action" style="background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; cursor: default;" disabled>
                                    <i class="fas fa-check-circle me-1"></i> Rated
                                </button>
                            <?php else: ?>
                                <a href="rate_products.php?order_id=<?= $row['order_id'] ?>" class="btn btn-action btn-rate" style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a;">
                                    <i class="fas fa-star me-1"></i> Rate
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-action btn-buy">Buy Again</a>
                        <a href="view-order.php?id=<?= $row['order_id'] ?>" class="btn btn-action btn-view">Details</a>
                    </div>
                </div>
            </div>
        <?php 
            } 
        } else {
            echo "
            <div class='text-center py-5 bg-white rounded-5 shadow-sm animate__animated animate__zoomIn'>
                <img src='https://cdn-icons-png.flaticon.com/512/6108/6108471.png' style='width: 120px; opacity: 0.3;'>
                <h4 class='mt-4 fw-bold'>No orders found</h4>
                <p class='text-muted'>No orders match the status: <b>$status_filter</b></p>
                <a href='index.php' class='btn btn-action btn-buy mt-2 px-5'>Shop Now</a>
            </div>";
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>