<?php
// Kunin ang filename para sa active menu highlight
$current_page = basename($_SERVER['PHP_SELF']);

// Path helper para sa images (kung nasa loob ng inventory subfolder)
$is_inventory_folder = (strpos($_SERVER['PHP_SELF'], 'inventory') !== false);
$img_base = $is_inventory_folder ? "../images/" : "images/";

/** * DYNAMIC PROFILE LOGIC 
 * Sinisiguro nito na kahit nasa subfolder, makikita ang in-upload na pic.
 * Kung wala, gagamit ng UI Avatars base sa session name.
**/
$sidebar_admin_name = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'Admin User';
$session_pic = $_SESSION['admin_pic'] ?? $_SESSION['employee_pic'] ?? '';
$pic_path = $is_inventory_folder ? "../uploads/profile/" : "uploads/profile/";

$sidebar_display_pic = !empty($session_pic) 
    ? ($pic_path . $session_pic) 
    : "https://ui-avatars.com/api/?name=" . urlencode($sidebar_admin_name) . "&background=e95a24&color=fff";
?>

<style>
    :root {
        --sidebar-width: 260px;
        --accent-orange: #e95a24;
        --sidebar-bg: #f8fafc; 
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    .sidebar {
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed; 
        left: 0;
        top: 0;
        z-index: 1100;
        box-shadow: none;
        border-right: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .order-sidebar-header {
        padding: 15px;
        background: #fff;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sidebar-header {
        padding: 30px 10px 20px 10px;
        text-align: center;
    }

    .logo-icon {
        width: 40px;   
        height: 40px;
        object-fit: contain;
        margin-bottom: 10px;
    }

    .logo {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-dark);
        margin: 0;
        letter-spacing: -0.5px;
    }
    .logo span { color: var(--accent-orange); }

    .admin-profile {
        text-align: center;
        padding: 20px 15px;
        margin-bottom: 10px;
    }

    .admin-profile img {
        width: 75px;
        height: 75px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 12px;
        background: #fff;
    }

    .admin-name {
        font-weight: 700;
        font-size: 16px;
        margin: 0;
        color: var(--text-dark);
    }

    .admin-profile small {
        font-size: 12px;
        color: var(--text-muted);
        display: block;
        margin-top: 2px;
    }

    .order-menu {
        list-style: none;
        padding: 0 15px;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .menu-label {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        margin: 25px 0 10px 10px;
        letter-spacing: 0.8px;
    }

    .order-menu li {
        margin-bottom: 4px;
    }

    .order-menu li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        border-radius: 12px;
        transition: 0.2s all ease;
    }

    .order-menu li a i {
        width: 20px;
        font-size: 16px;
        text-align: center;
    }

    .order-menu li:hover a {
        background: #f1f5f9;
        color: var(--text-dark);
    }

    .order-menu li.active a {
        background: #fff1eb;
        color: var(--accent-orange);
        font-weight: 700;
    }

    .order-menu li.active i {
        color: var(--accent-orange);
    }

    .order-menu::-webkit-scrollbar { width: 4px; }
    .order-menu::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    
</style>

<aside class="sidebar">
    <div class="order-sidebar-header">
        Management Portal
    </div>

    <div class="admin-profile">
        <img src="<?= $sidebar_display_pic ?>" 
             onerror="this.src='<?= $img_base ?>profile-pic.jpg'" 
             alt="Admin">
        <p class="admin-name"><?php echo htmlspecialchars($sidebar_admin_name); ?></p>
        <small><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'inventory@localflair.com'); ?></small>
    </div>

    <div class="menu-label">Main Inventory</div>
    <ul class="order-menu">
        <li class="<?= ($current_page == 'inventory_management.php') ? 'active' : ''; ?>">
            <a href="inventory_management.php">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Inventory Management</span>
            </a>
        </li>

        <li class="<?= ($current_page == 'add_product.php') ? 'active' : ''; ?>">
            <a href="add_product.php">
                <i class="fa-solid fa-square-plus"></i>
                <span>Add Product</span>
            </a>
        </li>

        <li class="<?= ($current_page == 'product_directory.php') ? 'active' : ''; ?>">
            <a href="product_directory.php">
                <i class="fa-solid fa-folder-open"></i>
                <span>Product Directory</span>
            </a>
        </li>

        <li class="<?= ($current_page == 'low_stock_alert.php') ? 'active' : ''; ?>">
            <a href="low_stock_alert.php">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Low Stock Alerts</span>
            </a>
        </li>
        <li class="<?= ($current_page == 'archived_products.php') ? 'active' : ''; ?>">
            <a href="archived_products.php">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archived Products</span>
            </a>
        </li>
    </ul>
</aside>
