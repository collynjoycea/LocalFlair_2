<?php
session_start();
include('db.php');
require_once __DIR__ . '/includes/product_helpers.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: order-history.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

// 1. Kunin ang main order details at address
$order_query = mysqli_query($conn, "
    SELECT o.*, a.* FROM orders o 
    JOIN addresses a ON o.address_id = a.address_id 
    WHERE o.order_id = '$order_id' AND o.user_id = '$user_id'
");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    echo "Order not found.";
    exit();
}

// 2. Kalkulahin ang na-earn na points (1 Point for every ₱100)
$earned_points = floor($order['total_amount'] / 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= $order_id ?> | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #e95a24;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
        }

        body { 
            min-height: 100vh;
            background: linear-gradient(135deg, #fdf6f0 0%, #fff1eb 25%, #f8fafc 50%, #f1f5f9 100%);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container { max-width: 1000px; margin: 40px auto; padding-bottom: 50px; }

        .back-btn {
            text-decoration: none; color: #64748b; font-weight: 600;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 15px;
            background: var(--glass-bg); backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border); transition: 0.3s;
            margin-bottom: 20px;
        }
        .back-btn:hover { color: var(--primary-color); transform: translateX(-5px); }

        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(15px);
            border-radius: 28px; padding: 30px; margin-bottom: 25px;
            border: 1px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        }

        .stepper { display: flex; justify-content: space-between; margin: 40px 0; position: relative; }
        .stepper::before { content: ""; position: absolute; top: 15px; left: 0; width: 100%; height: 2px; background: #e2e8f0; z-index: 1; }
        .step { z-index: 2; text-align: center; font-size: 0.75rem; font-weight: 700; color: #94a3b8; }
        .step-circle { width: 32px; height: 32px; background: #f1f5f9; border-radius: 50%; margin: 0 auto 10px; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; }
        .step.active .step-circle { background: var(--primary-color); border-color: var(--primary-color); color: white; }
        .step.active { color: var(--primary-color); }

        .prod-row { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .prod-img { width: 80px; height: 80px; border-radius: 18px; object-fit: cover; margin-right: 20px; }
        
        .summary-label { color: #64748b; font-weight: 500; }
        .summary-value { font-weight: 700; color: #0f172a; }
        .grand-total { font-size: 1.5rem; color: var(--primary-color); font-weight: 800; }
        .badge-status { padding: 8px 16px; border-radius: 100px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; }

        /* MODAL STYLES */
        .modal-content { border-radius: 30px; border: none; }
        .points-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: white; padding: 15px 25px; border-radius: 20px;
            font-size: 2rem; font-weight: 800; display: inline-block;
            box-shadow: 0 10px 20px rgba(255, 165, 0, 0.3);
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container animate__animated animate__fadeIn">
    <a href="order-history.php" class="back-btn">← Back to History</a>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h1 class="fw-bold mb-0">Order Details</h1>
            <p class="text-muted">Order #LF-<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?> • <?= date('M d, Y', strtotime($order['order_date'])) ?></p>
        </div>
        <span class="badge-status bg-white shadow-sm text-primary"><?= $order['status'] ?></span>
    </div>

    <div class="glass-card text-center">
        <div class="stepper">
            <?php 
            $steps = ['Order Placed', 'Processed', 'Shipped', 'Delivered'];
            $current_status = $order['status'];
            $reached = true;
            foreach ($steps as $s): 
                $active_class = $reached ? 'active' : '';
            ?>
                <div class="step <?= $active_class ?>">
                    <div class="step-circle"><?= $reached ? '✓' : '' ?></div>
                    <?= strtoupper($s) ?>
                </div>
            <?php 
                if ($s == $current_status) $reached = false;
            endforeach; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="glass-card">
                <h5 class="fw-bold mb-4">Items in Order</h5>
                <?php
                $items_stmt = $conn->prepare("
                    SELECT oi.*, p.product_name
                    FROM order_items oi
                    LEFT JOIN products p ON p.product_id = oi.product_id
                    WHERE oi.order_id = ?
                ");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_res = $items_stmt->get_result();
                while ($item = $items_res->fetch_assoc()):
                    $imgUrl = lf_product_image_url($item['product_image'] ?? '');
                ?>
                    <div class="prod-row">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" class="prod-img" onerror="this.src='images/no-image.png'">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($item['product_name'] ?: ('Product #' . $item['product_id'])) ?></h6>
                            <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                        </div>
                        <div class="fw-bold">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="glass-card h-100">
                        <h6 class="fw-bold mb-3">📍 Shipping Address</h6>
                        <p class="small text-muted mb-0">
                            <?= $order['street_address'] ?><br>
                            <?= $order['city_municipality'] ?>, <?= $order['province'] ?>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card h-100">
                        <h6 class="fw-bold mb-3">💳 Payment</h6>
                        <p class="small text-muted mb-0">
                            Method: <?= $order['payment_method'] ?><br>
                            Status: <span class="text-success fw-bold">Paid</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="glass-card">
                <h5 class="fw-bold mb-4">Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="summary-label">Shipping Fee</span>
                    <span class="summary-value">₱0.00</span>
                </div>
                
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span class="fw-bold"><i class="fa-solid fa-gift"></i> Points Earned</span>
                    <span class="fw-bold">+<?= $earned_points ?> Points</span>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Grand Total</span>
                    <span class="grand-total">₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <a href="index.php" class="btn w-100 mt-4 py-3 border-0 text-white fw-bold shadow-lg" style="background: var(--primary-color); border-radius: 18px;">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content animate__animated animate__zoomIn">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 80px;"></i>
                </div>
                <h2 class="fw-bold">Order Confirmed!</h2>
                <p class="text-muted">Thank you for your purchase. You've earned:</p>
                
                <div class="points-badge">
                    <i class="fa-solid fa-star"></i> <?= $earned_points ?>
                </div>
                
                <p class="fw-bold mb-4">Flair Points Added!</p>
                <button type="button" class="btn btn-dark w-100 py-3 rounded-pill fw-bold" data-bs-dismiss="modal">Awesome!</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // I-show ang modal pagka-load ng page
        var myModal = new bootstrap.Modal(document.getElementById('successOrderModal'));
        myModal.show();

        // Confetti Effect
        var end = Date.now() + (3 * 1000);
        var colors = ['#e95a24', '#FFD700', '#ffffff'];

        (function frame() {
            confetti({
                particleCount: 3,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: colors
            });
            confetti({
                particleCount: 3,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: colors
            });

            if (Date.now() < end) {
                requestAnimationFrame(frame);
            }
        }());
    });
</script>

</body>
</html>