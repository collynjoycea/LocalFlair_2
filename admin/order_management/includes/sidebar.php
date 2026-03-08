<?php
$current_user = $_SESSION['admin_name'] ?? 'User';
echo htmlspecialchars($current_user);
?>
<style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
    }

    .order-sidebar {
        width: 240px;
        background: linear-gradient(135deg, #fff4e6, #ffe2c7);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        border-right: 2px solid #f3caa5;
    }

    /* HEADER */
    .order-sidebar-header {
        padding: 10px;
        background: linear-gradient(135deg, #ffd8b5, #ffc48c);
        text-align: center;
        font-weight: 600;
        font-size: 16px;
        color: #333;
    }

    /* LOGO SECTION (FIXED SPACING) */
   .sidebar-header {
        background: linear-gradient(135deg, #ffdfcb, #ffc697);
        padding: 10px;
        display: flex;
        align-items: center; 
        gap: 5px;
    }

    .logo-icon {
        width: 45px;   
        height: 45px;
        object-fit: contain;
        margin-bottom: 5px;
    }

   .logo {
    font-size: 18px;
    font-weight: 700;   
    color: #4a2c1d;    
    margin: 0;
    white-space: nowrap; 
}

    /* ADMIN PROFILE (MORE COMPACT) */
    .admin-profile {
        text-align: center;
        padding: 12px 10px;
        border-bottom: 1px solid #e0c9a5;
    }

    .admin-profile img {
        width: 65px;
        height: 65px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #c98a4a;
        margin-bottom: 6px;
    }

    .admin-name {
        font-weight: 600;
        font-size: 14px;
        margin: 0;
        color: #333;
    }

    .admin-profile small {
        font-size: 12px;
        color: #6f5b44;
        display: block;
        margin-top: 2px;
    }

    /* MENU */
    .order-menu {
        list-style: none;
        padding: 12px;
        margin: 0;
    }

    .order-menu li {
        margin-bottom: 6px;
        border-radius: 8px;
        transition: 0.3s;
    }

    .order-menu li a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        font-weight: 500;
    }

    .order-menu li:hover,
    .order-menu li.active {
        background: linear-gradient(135deg, #ffdfcb, #ffc697);
    }
</style>

<aside class="order-sidebar">


    <div class="sidebar-header">
        <img src="images/basket-icon.png" alt="LocalFlair Logo" class="logo-icon">
        <h4 class="logo">LocalFlair</h4>
    </div>

    <div class="admin-profile">
    <img src="images/profile-pic.jpg" alt="Admin">
    <p class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'User'); ?></p>
    <small><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'user@example.com'); ?></small>
</div>

    <ul class="order-menu">
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'order_management.php') ? 'active' : ''; ?>">
            <a href="order_management.php">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>Orders Dashboard</span>
            </a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'payment_verification.php') ? 'active' : ''; ?>">
            <a href="payment_verification.php">
                <i class="fa-solid fa-money-check"></i>
                <span>Payment Verification</span>
            </a>
        </li>

        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'tracking_order.php') ? 'active' : ''; ?>">
            <a href="tracking_order.php">
                <i class="fa-solid fa-truck"></i>
                <span>Tracking Order</span>
            </a>
        </li>
    </ul>

</aside>