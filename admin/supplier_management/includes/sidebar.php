<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kunin ang session data para sa profile - Updated to handle both Admin and Employee
$current_user = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'Supplier_Manager';
$current_email = $_SESSION['admin_email'] ?? $_SESSION['employee_email'] ?? 'suppliermanagement@gmail.com';
$session_pic = $_SESSION['admin_pic'] ?? $_SESSION['employee_pic'] ?? ''; 

// Pathing logic
$top_img_path = (strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? "../uploads/profile/" : "uploads/profile/";

// Default Image Logic - UI Avatars para malinis tignan kung walang pic
$display_pic = !empty($session_pic) ? ($top_img_path . $session_pic) : "https://ui-avatars.com/api/?name=" . urlencode($current_user) . "&background=e95a24&color=fff";
?>

<style>
    /* UI Color Palette matching Inventory Management */
    :root {
        --bg-sidebar: #ffffff;
        --border-color: #f8fafc;
        --accent-orange: #e95a24;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --hover-bg: #fff7ed;
    }

    .order-sidebar {
        width: 260px;
        background: var(--bg-sidebar);
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        border-right: 1px solid var(--border-color);
        font-family: 'Poppins', sans-serif;
    }

    /* LOGO SECTION - Pinatangkad at Centered gaya ng nasa SS */
    .sidebar-header {
        padding: 40px 20px 20px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .logo-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        margin-bottom: 5px;
    }

    .logo {
        font-size: 24px;
        font-weight: 800;   
        color: var(--text-dark);   
        margin: 0;
        letter-spacing: -0.5px;
    }

    .sidebar-subtitle {
        color: var(--accent-orange); 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 10px; 
        letter-spacing: 1.2px;
        margin-top: 2px;
    }

    /* ADMIN PROFILE - Malaki at Pabilog gaya ng nasa Product Management */
    .admin-profile-centered {
        padding: 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-bottom: 1px solid #f8fafc;
        margin-bottom: 15px;
    }

    .profile-img-container {
        width: 90px;
        height: 90px;
        padding: 5px;
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 50%;
        margin-bottom: 15px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        overflow: hidden;
    }

    .profile-img-container img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .admin-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .admin-name-bold {
        font-weight: 800;
        font-size: 16px;
        color: var(--text-dark);
        margin: 0;
    }

    .admin-email-small {
        font-size: 12px;
        color: var(--text-muted);
        font-weight: 400;
    }

    /* MENU SECTION */
    .menu-section {
        flex: 1;
        padding: 0 15px;
    }

    .menu-label {
        font-size: 11px;
        font-weight: 700;
        color: #cbd5e1;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px 15px 10px 15px;
        display: block;
    }

    .order-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .order-menu li {
        margin-bottom: 8px;
    }

    .order-menu li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        text-decoration: none;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 500;
        border-radius: 14px;
        transition: all 0.2s ease;
    }

    .order-menu li a i {
        font-size: 18px;
        width: 22px;
        text-align: center;
    }

    /* ACTIVE STATE - Glassmorphism style gaya ng nasa Inventory */
    .order-menu li.active a {
        background: #fff7ed; /* Light orange tint */
        color: var(--accent-orange);
        font-weight: 700;
    }

    .order-menu li.active i {
        color: var(--accent-orange);
    }

    .order-menu li:hover a:not(.active) {
        background: #f8fafc;
        color: var(--text-dark);
    }
</style>

<aside class="order-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <h4 class="logo">LocalFlair</h4>
        </div>
        <span class="sidebar-subtitle">Supplier Management</span>
    </div>

    <a href="profile.php" style="text-decoration: none; color: inherit;">
        <div class="admin-profile-centered">
            <div class="profile-img-container">
                <img src="<?= $display_pic ?>" alt="User Profile">
            </div>
            <div class="admin-details">
                <p class="admin-name-bold"><?php echo htmlspecialchars($current_user); ?></p>
                <span class="admin-email-small"><?php echo htmlspecialchars($current_email); ?></span>
            </div>
        </div>
    </a>

    <div class="menu-section">
        <span class="menu-label">Main Menu</span>
        <ul class="order-menu">
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'supplier_management.php') ? 'active' : ''; ?>">
                <a href="supplier_management.php">
                    <i class="fa-solid fa-address-book"></i>
                    <span>Suppliers List</span>
                </a>
            </li>

            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'inventory_overview.php') ? 'active' : ''; ?>">
                <a href="inventory_overview.php">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Inventory Stock</span>
                </a>
            </li>

            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'stock_reports.php') ? 'active' : ''; ?>">
                <a href="stock_reports.php">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Stock Reports</span>
                </a>
            </li>

        
        </ul>
    </div>
</aside>