<?php
session_start();
error_reporting(E_ALL);
include __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// --- KUNIN ANG MGA INPUT MULA SA FORM ---
$is_buy_now = isset($_POST['is_buy_now']) && $_POST['is_buy_now'] == '1';
$points_to_redeem = isset($_POST['redeem_points']) ? (int)$_POST['redeem_points'] : 0;
$items = [];
$subtotal = 0.0;
$cart_ids = $_POST['cart_ids'] ?? [];

// --- DUAL LOGIC: BUY NOW O CART CHECKOUT ---
if ($is_buy_now) {
    // DIRECT BUY LOGIC (Mula sa Product Modal)
    $product_id = (int)($_POST['buy_now_product_id'] ?? 0);
    $qty = (int)($_POST['buy_now_qty'] ?? 1);

    if ($product_id <= 0) {
        echo "<script>alert('Invalid product.'); window.location='index.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT product_id, product_name, price, image, stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($product = $res->fetch_assoc()) {
        $product['quantity'] = $qty;
        $product['price'] = (float)$product['price'];
        $product['stock'] = (int)$product['stock'];
        $subtotal = $product['price'] * $qty;
        $items[] = $product;
    }
} else {
    // STANDARD CART LOGIC
    if (empty($cart_ids)) {
        echo "<script>alert('No items selected.'); window.location='cart.php';</script>";
        exit();
    }

    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $cartStmt = $conn->prepare("
        SELECT c.cart_id, c.product_id, c.quantity, p.product_name, p.price, p.image, p.stock
        FROM cart c
        INNER JOIN products p ON p.product_id = c.product_id
        WHERE c.user_id = ? AND c.cart_id IN ($placeholders)
    ");

    $types = "i" . str_repeat("i", count($cart_ids));
    $params = array_merge([$user_id], $cart_ids);
    $cartStmt->bind_param($types, ...$params);
    $cartStmt->execute();
    $cartRes = $cartStmt->get_result();

    while ($row = $cartRes->fetch_assoc()) {
        $row['quantity'] = (int)$row['quantity'];
        $row['price'] = (float)$row['price'];
        $row['stock'] = (int)$row['stock'];
        $subtotal += $row['price'] * $row['quantity'];
        $items[] = $row;
    }
}

if (count($items) === 0) {
    echo "<script>alert('Your selection is empty or invalid.'); window.location='index.php';</script>";
    exit();
}

// --- FIELDS VALIDATION ---
$fullname = trim($_POST['fullname'] ?? '');
$street = trim($_POST['street_address'] ?? '');
$city = trim($_POST['city_municipality'] ?? '');
$province = trim($_POST['province'] ?? '');
$contact = trim($_POST['contact_number'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');

if ($fullname === '' || $street === '' || $city === '' || $province === '' || $contact === '' || $payment_method === '') {
    echo "<script>alert('Please complete all required fields.'); window.history.back();</script>";
    exit();
}

// --- CALCULATIONS ---
$shippingFee = 150.00;
$vatRate = 0.12;
$vatAmount = round($subtotal * $vatRate, 2);
$discount = (float)$points_to_redeem;
$total_before_discount = $subtotal + $shippingFee + $vatAmount;
$total = round($total_before_discount - $discount, 2);
if ($total < 0) $total = 0;

try {
    $conn->begin_transaction();

    // --- SECURITY: Flair Points Verification ---
    if ($points_to_redeem > 0) {
        $checkPoints = $conn->prepare("SELECT flair_points FROM users WHERE user_id = ? FOR UPDATE");
        $checkPoints->bind_param("i", $user_id);
        $checkPoints->execute();
        $userPoints = $checkPoints->get_result()->fetch_assoc()['flair_points'];

        if ($points_to_redeem > $userPoints) {
            throw new RuntimeException("Kulang ang iyong Flair Points.");
        }

        $deductStmt = $conn->prepare("UPDATE users SET flair_points = flair_points - ? WHERE user_id = ?");
        $deductStmt->bind_param("ii", $points_to_redeem, $user_id);
        $deductStmt->execute();
    }

    // Update Contact & Address
    $upUser = $conn->prepare("UPDATE users SET contact_number = ? WHERE user_id = ?");
    $upUser->bind_param("si", $contact, $user_id);
    $upUser->execute();

    $addrSel = $conn->prepare("SELECT address_id FROM addresses WHERE user_id = ? ORDER BY address_id DESC LIMIT 1");
    $addrSel->bind_param("i", $user_id);
    $addrSel->execute();
    $addrRow = $addrSel->get_result()->fetch_assoc();

    if ($addrRow) {
        $address_id = (int)$addrRow['address_id'];
        $addrUpd = $conn->prepare("UPDATE addresses SET street_address=?, city_municipality=?, province=? WHERE address_id=? AND user_id=?");
        $addrUpd->bind_param("sssii", $street, $city, $province, $address_id, $user_id);
        $addrUpd->execute();
    } else {
        $addrIns = $conn->prepare("INSERT INTO addresses (user_id, street_address, city_municipality, province) VALUES (?, ?, ?, ?)");
        $addrIns->bind_param("isss", $user_id, $street, $city, $province);
        $addrIns->execute();
        $address_id = (int)$conn->insert_id;
    }

    // --- STOCK MANAGEMENT ---
    $decStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
    foreach ($items as $it) {
        $decStmt->bind_param("iii", $it['quantity'], $it['product_id'], $it['quantity']);
        $decStmt->execute();
        if ($decStmt->affected_rows <= 0) {
            throw new RuntimeException("Insufficient stock for " . $it['product_name']);
        }
    }

    // --- CREATE ORDER ---
    $orderStmt = $conn->prepare("INSERT INTO orders (user_id, address_id, payment_method, total_amount, status) VALUES (?, ?, ?, ?, 'Order Placed')");
    $orderStmt->bind_param("iisd", $user_id, $address_id, $payment_method, $total);
    $orderStmt->execute();
    $order_id = (int)$conn->insert_id;

    // Insert Order Items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, product_image) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $itemStmt->bind_param("iiids", $order_id, $it['product_id'], $it['quantity'], $it['price'], $it['image']);
        $itemStmt->execute();
    }

    // --- EARN POINTS ---
    $actual_subtotal = $subtotal - $discount;
    $earned_points = ($actual_subtotal > 0) ? floor($actual_subtotal / 100) : 0;
    if ($earned_points > 0) {
        $pointsStmt = $conn->prepare("UPDATE users SET flair_points = flair_points + ? WHERE user_id = ?");
        $pointsStmt->bind_param("ii", $earned_points, $user_id);
        $pointsStmt->execute();
    }

    // --- CLEANUP: Clear cart only if NOT Buy Now ---
    if (!$is_buy_now && !empty($cart_ids)) {
        $clr = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
        $clr->bind_param($types, ...$params);
        $clr->execute();
    }

    $conn->commit();
    header("Location: view-order.php?id=" . $order_id);
    exit();

} catch (Throwable $e) {
    $conn->rollback();
    echo "<script>alert('Checkout failed: " . $e->getMessage() . "'); window.location='index.php';</script>";
    exit();
}