<?php
include __DIR__ . "/../../db.php";
$suki_count_query = "SELECT COUNT(*) as total FROM users u 
                     WHERE (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id AND o.status = 'Delivered') >= 5";
$suki_count_res = mysqli_query($conn, $suki_count_query);
$suki_count_row = mysqli_fetch_assoc($suki_count_res);
$suki_badge = $suki_count_row['total'];
?>

<style>
    :root {
        --primary-accent: #e07a5f;
        --sidebar-bg: #fffcf9; 
        --hover-bg: #fdf2e9;
        --text-dark: #2d2d2d;
        --text-muted: #a0a0a0;
    }

    .sidebar {
        width: 260px;
        background-color: var(--sidebar-bg);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed; 
        left: 0;
        top: 0;
        z-index: 1000;
        overflow: hidden; 
        border-right: 1px solid #f0f0f0;
    }

    .sidebar-header {
        padding: 40px 25px 20px; /* Mas maluwag na header */
        display: flex;
        align-items: center; 
        gap: 15px;
    }

    .logo-icon {
        width: 35px;   
        height: 35px;
        object-fit: contain;
    }

    .logo {
        font-size: 24px;
        font-weight: 800;
        color: #2d2d2d;
        margin: 0;
        letter-spacing: -0.5px;
    }

    /* Admin Profile Section - No Border/Card */
    .admin-profile {
        padding: 10px 25px 20px; 
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .admin-profile img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
    }

    .admin-name {
        font-weight: 700;
        margin-bottom: 0px;
        color: var(--text-dark);
        font-size: 14px;
    }

    .admin-profile small {
        color: var(--text-muted);
        display: block;
        font-size: 12px;
    }

    .menu {
        list-style: none;
        padding: 0 15px;
        flex-grow: 1;
        overflow-y: auto; 
        scrollbar-width: none;
    }

    .menu::-webkit-scrollbar { display: none; }

    /* Section Labels - May extra spacing sa taas */
    .menu-section-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #b0b0b0;
        margin: 35px 0 12px 12px; 
        font-weight: 700;
    }

    .menu li {
        margin-bottom: 10px; /* Spacing sa pagitan ng items */
    }

    .menu li a {
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 18px; 
        color: #666;
        font-size: 15px;
        font-weight: 500;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .menu li i {
        width: 22px;
        text-align: center;
        font-size: 18px;
        color: #a0a0a0;
    }

    /* Hover State */
    .menu li:hover a {
        background-color: var(--hover-bg);
        color: var(--primary-accent);
    }

    .menu li:hover i {
        color: var(--primary-accent);
    }

    /* Active State */
    .menu li.active a {
        background-color: #fdf2e9; 
        color: var(--primary-accent);
        font-weight: 700;
    }

    .menu li.active i {
        color: var(--primary-accent);
    }

    /* Badges */
    .badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 6px;
        margin-left: auto;
        font-weight: 800;
    }

    .badge-new { background-color: #ff5e3a; color: #fff; }
    .badge-notif { background-color: #f9d3b4; color: #e07a5f; }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="images/basket-icon.png" alt="LocalFlair Logo" class="logo-icon">
        <h4 class="logo">LocalFlair</h4>
    </div>

    <div class="admin-profile">
        <img src="images/profile-pic.jpg" alt="Admin">
        <div>
            <p class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
            <small><?php echo htmlspecialchars($_SESSION['admin_email']); ?></small>
        </div>
    </div>

    <ul class="menu">
        <div class="menu-section-label">Main Inventory</div>
        
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <a href="index.php"><i class="fa-solid fa-th-large"></i> <span>Dashboard</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>">
            <a href="products.php"><i class="fa-solid fa-box"></i> <span>Products</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
            <a href="orders.php"><i class="fa-solid fa-cart-shopping"></i> <span>Orders</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'archived_products.php') ? 'active' : ''; ?>">
            <a href="archived_products.php"><i class="fa-solid fa-box-archive"></i> <span>Archived Products</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'sales.php') ? 'active' : ''; ?>">
            <a href="sales.php"><i class="fa-solid fa-chart-line"></i> <span>Sales</span></a>
        </li>
      

        <div class="menu-section-label">Enhancements</div>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'crm_module.php') ? 'active' : ''; ?>">
            <a href="crm_module.php"><i class="fa-solid fa-user-gear"></i> <span>CRM Module</span> <span class="badge badge-new">NEW</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'loyalty.php') ? 'active' : ''; ?>">
    <a href="loyalty.php">
        <i class="fa-solid fa-crown"></i> <span>Suki Program</span>
        <?php if($suki_badge > 0): ?>
            <span class="badge badge-notif"><?php echo $suki_badge; ?></span>
        <?php endif; ?>
    </a>
</li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'active' : ''; ?>">
            <a href="reviews.php"><i class="fa-solid fa-star"></i> <span>Reviews</span> <span class="badge badge-notif">4</span></a>
        </li>

        <div class="menu-section-label">Management</div>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
            <a href="users.php"><i class="fa-solid fa-users"></i> <span>Customers</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>">
            <a href="employees.php"><i class="fa-solid fa-fingerprint"></i> <span>Staff Roles</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'suppliers.php') ? 'active' : ''; ?>">
            <a href="suppliers.php"><i class="fa-solid fa-truck"></i> <span>Suppliers</span></a>
        </li>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <a href="reports.php"><i class="fa-solid fa-chart-simple"></i> <span>Reports</span></a>
        </li>
    </ul>
</aside>

