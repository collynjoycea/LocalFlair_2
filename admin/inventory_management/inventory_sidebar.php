<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. CONNECTION CHECK (Paakyat sa main db.php)
if (!isset($conn)) {
    include __DIR__ . "/../../db.php"; 
}

// 2. FETCH ADMIN DATA (Fixed: 'id' instead of 'admin_id')
$inv_admin = null;
if (isset($_SESSION['admin_id'])) {
    $aid = $_SESSION['admin_id'];
    // FIX: 'id' ang column name sa table mo
    $stmt = $conn->prepare("SELECT name, email, profile_pic FROM admins WHERE id = ?");
    $stmt->bind_param("i", $aid);
    $stmt->execute();
    $inv_admin = $stmt->get_result()->fetch_assoc();
}

// 3. FALLBACKS (Para iwas TypeError sa htmlspecialchars)
$side_name  = (is_array($inv_admin) && isset($inv_admin['name'])) ? (string)$inv_admin['name'] : 'Admin';
$side_email = (is_array($inv_admin) && isset($inv_admin['email'])) ? (string)$inv_admin['email'] : 'admin@gmail.com';
$side_pic   = (is_array($inv_admin) && !empty($inv_admin['profile_pic'])) ? (string)$inv_admin['profile_pic'] : 'default.png';

// 4. SUKI BADGE LOGIC
$suki_count_query = "SELECT COUNT(*) as total FROM users u 
                     WHERE (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.user_id AND o.status = 'Delivered') >= 5";
$suki_count_res = mysqli_query($conn, $suki_count_query);
$suki_count_row = mysqli_fetch_assoc($suki_count_res);
$suki_badge = $suki_count_row['total'] ?? 0;
?>

<style>
    .sidebar { width: 280px; background: #ffffff; height: 100vh; position: fixed; left: 0; top: 0; padding: 30px 20px; border-right: 1px solid #f1f5f9; display: flex; flex-direction: column; align-items: center; z-index: 1000; }
    .sidebar-brand { font-weight: 800; font-size: 20px; color: #1e293b; margin-bottom: 25px; text-decoration: none; }
    .profile-section { text-align: center; margin-bottom: 30px; width: 100%; padding-bottom: 20px; border-bottom: 1px solid #fdf2f0; }
    .profile-img { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; border: 3px solid #f8c291; margin-bottom: 12px; }
    .profile-name { font-weight: 700; font-size: 15px; color: #1e293b; display: block; }
    .profile-email { font-size: 12px; color: #64748b; word-break: break-all; }
    .sidebar-nav { width: 100%; list-style: none; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; text-decoration: none; color: #1e293b; font-size: 14px; border-radius: 12px; margin-bottom: 8px; transition: all 0.3s ease; }
    .nav-item i { width: 20px; text-align: center; font-size: 16px; }
    .nav-item.active { background-color: #f8c291; color: #1e293b; font-weight: 600; }
    .nav-item:hover:not(.active) { background-color: #fff5ed; }
</style>

<div class="sidebar">
    <a href="../index.php" class="sidebar-brand">LocalFlair</a>
    
    <div class="profile-section">
        <img src="uploads/<?php echo htmlspecialchars($side_pic); ?>" 
             class="profile-img" 
             alt="Profile" 
             onerror="this.src='uploads/default.png'">
             
        <span class="profile-name"><?php echo htmlspecialchars($side_name); ?></span>
        <span class="profile-email"><?php echo htmlspecialchars($side_email); ?></span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="inventory_management.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'inventory_management.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Inventory Management</span>
        </a>
        <a href="add_product.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'add_product.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-box-archive"></i>
            <span>Add Product</span>
        </a>
        <a href="product_directory.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'product_directory.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-address-book"></i>
            <span>Product Directory</span>
        </a>
        <a href="low_stock_alerts.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'low_stock_alerts.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Low Stock Alerts</span>
        </a>
    </nav>
</div>