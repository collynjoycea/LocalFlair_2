<?php
session_start();
include('db.php'); 
include('includes/header.php');

// 1. ADD TO CART LOGIC (Dito papasok yung galing sa Shop Now/Modal)
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1;

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Check kung existing na sa DB cart
        $check = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $check->bind_param("ii", $user_id, $product_id);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $new_qty = $row['quantity'] + $qty;
            $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $update->bind_param("ii", $new_qty, $row['cart_id']);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->bind_param("iii", $user_id, $product_id, $qty);
            $insert->execute();
        }
    } else {
        // Session-based cart kung hindi naka-login
        if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if (isset($item['product_id']) && $item['product_id'] == $product_id) {
                $item['quantity'] += $qty;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = ['product_id' => $product_id, 'quantity' => $qty];
        }
    }
    // IMPORTANTE: I-redirect pabalik sa cart.php para malinis ang URL
    header("Location: cart.php");
    exit();
}

$totalPrice = 0;
$totalItems = 0;
$cartItems = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT c.cart_id, c.product_id, c.quantity, p.product_name, p.price, p.image 
              FROM cart c 
              JOIN products p ON c.product_id = p.product_id 
              WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
} else {
    $cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container { width: 90%; margin: 120px auto 30px auto; background: #fff; border: 2px solid #555; padding: 15px; }
        .cart-header { display: flex; justify-content: space-between; font-weight: bold; border: 2px solid #555; padding: 8px 10px; margin-bottom: 10px; background: #fdfdfd; }
        .cart-header div { flex: 1; text-align: center; }
        .cart-header div:first-child { flex: 3; text-align: left; padding-left: 10px; }
        .cart-item { border: 2px solid #555; margin-bottom: 12px; padding: 15px; display: flex; align-items: center; }
        .cart-item .product-col { flex: 3; display: flex; align-items: center; gap: 15px; }
        .cart-item img { width: 100px; height: 80px; object-fit: cover; border: 1px solid #999; }
        .cart-item .product-name { font-weight: bold; color: #4a2c1d; }
        .cart-item .col { flex: 1; text-align: center; font-weight: 600; }
        .qty-controls{ display:inline-flex; align-items:center; gap:8px; }
        .qty-controls button{ width:30px; height:30px; border:none; border-radius:6px; background:#ead9bd; font-weight:800; }
        .qty-controls input{ width:54px; text-align:center; border:1px solid #ccc; border-radius:6px; padding:4px 6px; }
        .cart-item .delete-btn { color: #dc3545; font-weight: bold; text-decoration: none; }
        .cart-footer { width: 100%; background: #e7d5bc; padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; border: 2px solid #555; margin-top: 20px; }
        .place-btn { background: #e07b28; border: none; padding: 10px 25px; font-size: 18px; font-weight: bold; color: white; cursor: pointer; text-decoration: none; }
        .place-btn:hover { background: #c9651d; color: white; }
    </style>
</head>

<body style="background:#f8f5f2;">

<form action="place-order.php" method="POST">
    <div class="cart-container">
        <div class="cart-header">
            <div>Product</div>
            <div>Quantity</div>
            <div>Unit Price</div>
            <div>Total</div>
            <div>Action</div>
        </div>

        <?php if (empty($cartItems)) { ?>
            <div class="text-center py-5">
                <p class="fw-bold">Your cart is empty.</p>
                <a href="index.php" class="btn btn-outline-dark">Go Shopping</a>
            </div>
        <?php } else { ?>

            <?php foreach ($cartItems as $item) { 
                $id = isset($item['cart_id']) ? $item['cart_id'] : 0;
                $name = $item['product_name'] ?? $item['name'];
                $price = floatval($item['price']);
                $qty = intval($item['quantity'] ?? $item['qty']);
                $imageFilename = trim($item['image']); 
                $image = (!empty($imageFilename)) ? "admin/uploads/" . $imageFilename : "images/no-image.png";
                $subtotal = $price * $qty;
                $totalPrice += $subtotal;
                $totalItems += $qty;
            ?>
            <div class="cart-item">
                <div class="product-col">
                    <input type="checkbox" name="selected_items[]" value="<?php echo $id; ?>" class="item-checkbox" checked>
                    <img src="<?php echo $image; ?>" alt="Product">
                    <div class="product-name"><?php echo htmlspecialchars($name); ?></div>
                </div>

                <div class="col">
                    <?php if (isset($_SESSION['user_id']) && isset($item['cart_id'])) { ?>
                        <div class="qty-controls" data-cart-id="<?php echo (int)$item['cart_id']; ?>">
                            <button type="button" onclick="changeCartQty(this, -1)">-</button>
                            <input type="text" value="<?php echo $qty; ?>" readonly>
                            <button type="button" onclick="changeCartQty(this, 1)">+</button>
                        </div>
                    <?php } else { ?>
                        <?php echo $qty; ?>
                    <?php } ?>
                </div>
                <div class="col">₱<?php echo number_format($price, 2); ?></div>
                <div class="col">₱<?php echo number_format($subtotal, 2); ?></div>
                <div class="col">
                    <a href="remove-cart.php?id=<?php echo $id; ?>" 
                       onclick="return confirm('Remove this item?')" 
                       class="delete-btn">Delete</a>
                </div>
            </div>
            <?php } ?>

        <?php } ?>

        <div class="cart-footer">
            <div>
               <input type="checkbox" id="selectAll" checked> <b>Select all</b>
            </div>

            <div style="font-weight:bold; font-size:16px;">
                Total (<?php echo $totalItems; ?> item): 
                <span style="font-size:20px; color: #e07b28;">₱<?php echo number_format($totalPrice, 2); ?></span>
            </div>

            <button type="submit" class="place-btn">
                Place Order
            </button>
        </div>
    </div>
</form>

<script>
// --- SELECT ALL LOGIC ---
const selectAll = document.getElementById('selectAll');
const itemCheckboxes = document.querySelectorAll('.item-checkbox');

if(selectAll) {
    selectAll.addEventListener('change', function() {
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}

itemCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const totalChecked = document.querySelectorAll('.item-checkbox:checked').length;
        selectAll.checked = (totalChecked === itemCheckboxes.length);
    });
});

async function changeCartQty(btn, delta){
    const wrap = btn.closest('.qty-controls');
    if(!wrap) return;
    const cartId = wrap.getAttribute('data-cart-id');
    const input = wrap.querySelector('input');
    const current = parseInt(input.value || '1', 10);
    let next = current + delta;
    if(next < 1) next = 1;
    input.value = String(next);

    const body = new URLSearchParams();
    body.set('cart_id', cartId);
    body.set('qty', String(next));

    const res = await fetch('update-cart.php', { method: 'POST', body });
    const text = (await res.text()).trim();
    if(text !== 'success'){
        alert(text || 'Failed to update cart.');
        return;
    }
    location.reload();
}
</script>

</body>
</html>