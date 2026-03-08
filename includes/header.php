<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// error_reporting(0); // I-comment muna habang nag-dedevelop para makita kung may error sa DB connection

include 'db.php'; // Sinisiguro nito na connected tayo sa localflair_db

$cart_count = 0;

// REAL-TIME CHECK: Kunin ang count mula sa database table na 'cart'
if (isset($_SESSION['user_id'])) {
    $u_id = $_SESSION['user_id'];
    
    // Gamit ang SUM(quantity) para makuha ang kabuuang bilang ng items
    $cart_query = "SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $u_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && $row['total_items'] > 0) {
        $cart_count = $row['total_items'];
    }
} else {
    // Kung hindi naka-login, pwedeng fallback ang session count
    $cart_count = isset($_SESSION['cart_count']) ? intval($_SESSION['cart_count']) : 0;
}
?>

<style>
/* Cart icon sizing */
.cart-icon {
    width: 28px;   /* adjust icon size */
    height: 28px;
}

/* Badge styling */
.navbar .badge {
    font-size: 0.65rem;      
    padding: 0.25em 0.4em;
    background-color: #ead9bd !important; /* custom localflair color */
    color: #000 !important;               /* ensure text is visible */
}

/* Dropdown avatar size */
.header-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}
</style>

<nav class="navbar navbar-expand-lg custom-navbar fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="images/basket-icon.png" alt="Basket Icon" class="me-2 brand-icon">

            <div class="brand-text">
                <span class="brand-title fw-bold">LocalFlair</span>
                <span class="brand-tagline">Authentic Pinoy Finds</span>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon">&#9776;</span>
        </button>

        <div class="collapse navbar-collapse ms-auto" id="navbarNav">
            <div class="ms-auto d-flex flex-column flex-lg-row align-items-lg-center gap-lg-2">

                <a class="nav-link" href="index.php">Home</a>
                <a class="nav-link" href="index.php#categories">Categories</a>
                <a class="nav-link" href="provinces.php">Provinces</a>
                <a class="nav-link" href="about-us.php">About Us</a>

                <?php if (isset($_SESSION['user_id'])): ?>

                <?php
                    $headerAvatar = !empty($_SESSION['profile_picture'])
                                    ? $_SESSION['profile_picture']
                                    : "default-avatar.png";
                ?>

                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                    href="#" role="button" data-bs-toggle="dropdown">

                        <img src="images/<?php echo htmlspecialchars($headerAvatar); ?>"
                            class="header-avatar">

                        <?= htmlspecialchars($_SESSION['first_name']); ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                        <li><a class="dropdown-item" href="view-order.php">View Order</a></li>
                        <li><a class="dropdown-item" href="order-history.php">Order History</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Log Out</a></li>
                    </ul>
                </div>

                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                <?php endif; ?>

                <a href="cart.php" class="nav-link position-relative d-flex align-items-center ms-2">
                    <img src="images/cart.png" alt="Cart" class="cart-icon">
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartBadgeCount">
                        <?= $cart_count; ?>
                        <span class="visually-hidden">items in cart</span>
                    </span>
                </a>

            </div>
        </div>
    </div>
</nav>
