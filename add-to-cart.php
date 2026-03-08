<?php
session_start();
include 'db.php'; // Siguraduhin na tama ang path ng db.php mo

// Guests can browse only
if (!isset($_SESSION['user_id'])) {
    if (isset($_GET['redirect']) && $_GET['redirect'] == '1') {
        echo "<script>alert('Please register/login first to add items to cart.'); window.location='register.php';</script>";
        exit;
    }
    echo "login_required";
    exit;
}

// 1. Kunin ang data mula sa POST
$id    = isset($_POST['id']) ? intval($_POST['id']) : 0; 
$qty   = isset($_POST['qty']) ? intval($_POST['qty']) : 0;

// Validation
if ($id <= 0 || $qty <= 0) {
    echo "Invalid product or quantity.";
    exit;
}

// 2. Logged in user (required)
$user_id = $_SESSION['user_id'];

    // I-check kung ang product ay nasa cart na ng user
    $check_query = "SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // UPDATE: Kung nandoon na, dagdagan lang ang quantity
        $row = $result->fetch_assoc();
        $new_qty = $row['quantity'] + $qty;
        
        $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
        $upd_stmt = $conn->prepare($update_query);
        $upd_stmt->bind_param("ii", $new_qty, $row['cart_id']);
        $upd_stmt->execute();
    } else {
        // INSERT: Kung wala pa, gumawa ng bagong entry
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $ins_stmt = $conn->prepare($insert_query);
        $ins_stmt->bind_param("iii", $user_id, $id, $qty);
        $ins_stmt->execute();
    }

    // If coming from normal form submit (e.g., Featured Products modal), redirect to cart.
    if (isset($_GET['redirect']) && $_GET['redirect'] == '1') {
        header("Location: cart.php");
        exit;
    }

    echo "success";

?>
