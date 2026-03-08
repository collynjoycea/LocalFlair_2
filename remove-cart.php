<?php
session_start();
include 'db.php'; // Siguraduhin na tama ang path ng database connection mo

// 1. Check kung logged in ang user
if (!isset($_SESSION['user_id'])) {
    // Kung hindi logged in, check muna sa session para sa guest users
    if (isset($_GET['id']) && isset($_SESSION['cart'][$_GET['id']])) {
        unset($_SESSION['cart'][$_GET['id']]);
    }
    header("Location: cart.php");
    exit();
}

// 2. Kung logged in, gamitin ang database
if (isset($_GET['id'])) {
    $cart_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // SQL DELETE: Burahin ang item base sa cart_id at user_id (para security na rin)
    $sql = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        // Success
    } else {
        // Optional: Error handling dito
    }
}

// 3. Balik sa cart page
header("Location: cart.php");
exit();
?>