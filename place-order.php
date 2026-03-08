<?php
session_start();
error_reporting(E_ALL);
include __DIR__ . '/db.php';
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/product_helpers.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to place an order.'); window.location='login.php';</script>";
    exit();
}

// ... (keep the session and login checks at the top)

$user_id = (int)$_SESSION['user_id'];
$cartItems = [];
$subtotal = 0.0;

// CHECK KUNG BUY NOW O GALING SA CART
if (isset($_POST['buy_now_qty']) && isset($_POST['selected_items'])) {
    // DIRECT BUY LOGIC (Galing sa Modal)
    $product_id = (int)$_POST['selected_items'][0];
    $qty = (int)$_POST['buy_now_qty'];

    $stmt = $conn->prepare("SELECT product_id, product_name, price, image, stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($product = $res->fetch_assoc()) {
        $product['quantity'] = $qty;
        $product['price'] = (float)$product['price'];
        $product['cart_id'] = 0; // Temporary ID for Buy Now
        $subtotal = $product['price'] * $qty;
        $cartItems[] = $product;
        // Para sa form later
        $selected_items = [0]; 
    }
} elseif (isset($_POST['selected_items']) && !empty($_POST['selected_items'])) {
    // STANDARD CART LOGIC (Galing sa Cart Page)
    $selected_items = $_POST['selected_items'];
    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));

    $cartStmt = $conn->prepare("
        SELECT c.cart_id, c.product_id, c.quantity, p.product_name, p.price, p.image, p.stock
        FROM cart c
        INNER JOIN products p ON p.product_id = c.product_id
        WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
    ");

    $types = "i" . str_repeat("i", count($selected_items));
    $params = array_merge([$user_id], $selected_items);
    $cartStmt->bind_param($types, ...$params);
    $cartStmt->execute();
    $cartRes = $cartStmt->get_result();

    while ($row = $cartRes->fetch_assoc()) {
        $row['quantity'] = (int)$row['quantity'];
        $row['price'] = (float)$row['price'];
        $subtotal += $row['price'] * $row['quantity'];
        $cartItems[] = $row;
    }
} else {
    echo "<script>alert('Please select at least one item.'); window.location='cart.php';</script>";
    exit();
}

if (count($cartItems) === 0) {
    echo "<script>alert('Your selection is invalid or empty.'); window.location='cart.php';</script>";
    exit();
}
// ... (tuloy na sa user_info query mo)

// *** UPDATED: Kunin pati flair_points ng user ***
$user_info = $conn->prepare("
    SELECT u.*, a.address_id, a.street_address, a.city_municipality, a.province
    FROM users u
    LEFT JOIN addresses a ON u.user_id = a.user_id
    WHERE u.user_id = ?
    ORDER BY a.address_id DESC
    LIMIT 1
");
$user_info->bind_param("i", $user_id);
$user_info->execute();
$user_data = $user_info->get_result()->fetch_assoc();

$fullname = trim(($user_data['first_name'] ?? '') . " " . ($user_data['last_name'] ?? ''));
$street = $user_data['street_address'] ?? '';
$city = $user_data['city_municipality'] ?? '';
$province = $user_data['province'] ?? '';
$contact = $user_data['contact_number'] ?? '';
$user_points = (int)($user_data['flair_points'] ?? 0); // User's point balance

$shippingFee = 150.00;
$vatRate = 0.12;
$vatAmount = round($subtotal * $vatRate, 2);
$initial_total = round($subtotal + $shippingFee + $vatAmount, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body{ padding-top: 100px; background:#f6f7fb; font-family: 'Segoe UI', sans-serif; }
        .cardx{ background:#fff; border:1px solid #eef0f5; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,0.04); overflow: hidden; }
        .cardx .card-headerx{ padding:18px 20px; border-bottom:1px solid #f1f3f8; font-weight:800; display:flex; gap:10px; align-items:center; background: #fff; }
        .cardx .card-bodyx{ padding:18px 20px; }
        .field-label{ font-size:12px; color:#6b7280; font-weight:800; text-transform:uppercase; letter-spacing:.7px; margin-bottom:6px; }
        .pm-option{ border:1px solid #eef0f5; border-radius:14px; padding:14px 14px; display:flex; align-items:center; gap:10px; cursor:pointer; }
        .money-row{ display:flex; justify-content:space-between; padding:6px 0; color:#374151; }
        .total-row{ display:flex; justify-content:space-between; align-items:end; padding-top:12px; border-top: 1px solid #eee; margin-top: 10px; }
        .total-row .total{ font-weight:900; color:#e25b2d; font-size:24px; }
        .btn-place{ background:#e25b2d; border:none; color:#fff; font-weight:900; border-radius:14px; padding:14px 18px; transition: 0.3s; }
        .btn-place:hover{ background:#cf4f25; transform: translateY(-2px); }
        
        /* Flair Points Styling */
        .points-box { background: #fffaf0; border: 1px dashed #e25b2d; border-radius: 14px; padding: 15px; margin-bottom: 20px; }
        .points-balance { color: #e25b2d; font-weight: 800; }
        .summary-item img{ width:54px; height:54px; border-radius:14px; object-fit:cover; border:1px solid #f1f3f8; }
    </style>
</head>
<body>

<div class="container my-4">
    <h2 class="fw-bold">Place Order</h2>
    <p class="text-muted">Complete your purchase and earn more Flair Points!</p>

    <form action="process-order.php" method="POST" class="row g-4" id="orderForm">
        <?php foreach ($selected_items as $si_id): ?>
            <input type="hidden" name="cart_ids[]" value="<?= (int)$si_id ?>">
        <?php endforeach; ?>

        <?php if(isset($_POST['buy_now_qty'])): ?>
    <input type="hidden" name="is_buy_now" value="1">
    <input type="hidden" name="buy_now_product_id" value="<?= $cartItems[0]['product_id'] ?>">
    <input type="hidden" name="buy_now_qty" value="<?= $cartItems[0]['quantity'] ?>">
<?php endif; ?>

        <div class="col-lg-7">
            <div class="cardx mb-4">
                <div class="card-headerx"><i class="fa-solid fa-truck"></i> Shipping Information</div>
                <div class="card-bodyx">
                    <div class="mb-3">
                        <div class="field-label">Full Name</div>
                        <input name="fullname" class="form-control form-control-lg" value="<?= htmlspecialchars($fullname) ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="field-label">Street Address</div>
                        <input name="street_address" class="form-control form-control-lg" value="<?= htmlspecialchars($street) ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="field-label">City</div>
                            <input name="city_municipality" class="form-control form-control-lg" value="<?= htmlspecialchars($city) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <div class="field-label">Province</div>
                            <input name="province" class="form-control form-control-lg" value="<?= htmlspecialchars($province) ?>" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="field-label">Contact Number</div>
                        <input name="contact_number" class="form-control form-control-lg" value="<?= htmlspecialchars($contact) ?>" required>
                    </div>
                </div>
            </div>

            <div class="cardx mb-4">
                <div class="card-headerx"><i class="fa-solid fa-star" style="color: #f39c12;"></i> Suki Loyalty Program</div>
                <div class="card-bodyx">
                    <div class="points-box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Your Balance:</span>
                            <span class="points-balance"><i class="fa-solid fa-coins"></i> <?= number_format($user_points) ?> Flair Points</span>
                        </div>
                        <div class="field-label">Redeem Points (1 Point = ₱1.00)</div>
                        <div class="input-group">
                            <input type="number" name="redeem_points" id="redeemInput" class="form-control" 
                                   placeholder="Enter points to use" min="0" max="<?= $user_points ?>">
                            <button type="button" class="btn btn-dark" onclick="applyPoints()">Apply</button>
                        </div>
                        <small class="text-muted mt-2 d-block">Maximum redeemable: <?= number_format($user_points) ?> points</small>
                    </div>
                </div>
            </div>

            <div class="cardx">
                <div class="card-headerx"><i class="fa-solid fa-credit-card"></i> Payment Method</div>
                <div class="card-bodyx">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="pm-option w-100 active">
                                <input type="radio" name="payment_method" value="Credit Card" checked>
                                <div><div class="fw-bold">Credit Card</div><small class="text-muted">Visa/Mastercard</small></div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="pm-option w-100">
                                <input type="radio" name="payment_method" value="GCash">
                                <div><div class="fw-bold">GCash</div><small class="text-muted">Digital Wallet</small></div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="pm-option w-100">
                                <input type="radio" name="payment_method" value="Cash on Delivery">
                                <div><div class="fw-bold">COD</div><small class="text-muted">Pay at doorstep</small></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="cardx sticky-top" style="top: 110px;">
                <div class="card-headerx">Order Summary</div>
                <div class="card-bodyx">
                    <?php foreach ($cartItems as $ci): ?>
                        <div class="summary-item mb-3 d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars(lf_product_image_url($ci['image'] ?? '')) ?>">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($ci['product_name']) ?></h6>
                                <small class="text-muted">Qty: <?= $ci['quantity'] ?></small>
                            </div>
                            <div class="fw-bold text-end">₱<?= number_format($ci['price'] * $ci['quantity'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>

                    <hr>
                    <div class="money-row"><span>Subtotal</span><strong>₱<?= number_format($subtotal, 2) ?></strong></div>
                    <div class="money-row"><span>Shipping Fee</span><strong>₱<?= number_format($shippingFee, 2) ?></strong></div>
                    <div class="money-row"><span>VAT (12%)</span><strong>₱<?= number_format($vatAmount, 2) ?></strong></div>
                    
                    <div class="money-row text-success d-none" id="discountRow">
                        <span>Flair Discount</span>
                        <strong id="discountAmount">-₱0.00</strong>
                    </div>

                    <div class="total-row">
                        <div class="label">Total Payable</div>
                        <div class="total" id="finalTotalDisplay">₱<?= number_format($initial_total, 2) ?></div>
                    </div>

                    <button type="submit" class="btn-place w-100 mt-4">Confirm & Place Order</button>
                    <div class="text-center mt-3">
                        <small class="text-muted"><i class="fa-solid fa-shield-halved"></i> Secure Checkout</small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const initialTotal = <?= $initial_total ?>;
    const maxPoints = <?= $user_points ?>;

    function applyPoints() {
        let points = parseInt(document.getElementById('redeemInput').value) || 0;
        
        if(points > maxPoints) {
            alert("You only have " + maxPoints + " points.");
            points = maxPoints;
            document.getElementById('redeemInput').value = points;
        }

        if(points < 0) points = 0;

        // Update UI
        const discountRow = document.getElementById('discountRow');
        const discountDisplay = document.getElementById('discountAmount');
        const totalDisplay = document.getElementById('finalTotalDisplay');

        if(points > 0) {
            discountRow.classList.remove('d-none');
            discountDisplay.innerText = "-₱" + points.toLocaleString(undefined, {minimumFractionDigits: 2});
            let newTotal = initialTotal - points;
            if(newTotal < 0) newTotal = 0;
            totalDisplay.innerText = "₱" + newTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        } else {
            discountRow.classList.add('d-none');
            totalDisplay.innerText = "₱" + initialTotal.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    }
</script>

</body>
</html>