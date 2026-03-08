<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch summary
$totalSuppliersQuery = $conn->query("SELECT COUNT(*) as total FROM suppliers");
$totalSuppliers = $totalSuppliersQuery->fetch_assoc()['total'] ?? 0;

$activeSuppliersQuery = $conn->query("SELECT COUNT(*) as active FROM suppliers WHERE rating >= 4"); 
$activeSuppliers = $activeSuppliersQuery->fetch_assoc()['active'] ?? 0;

$totalSpendQuery = $conn->query("SELECT SUM(total_spend) as spend FROM suppliers");
$totalSpend = $totalSpendQuery->fetch_assoc()['spend'] ?? 0;

// Fetch suppliers list
$suppliersQuery = "
SELECT s.supplier_id, s.supplier_name, c.category_name, p.province_name, s.contact_number, 
       s.rating, s.deliveries, s.total_spend, s.terms
FROM suppliers s
JOIN categories c ON s.category_id = c.category_id
JOIN provinces p ON s.province_id = p.province_id
ORDER BY s.supplier_name ASC
";
$suppliersResult = $conn->query($suppliersQuery);
$suppliers = [];
if ($suppliersResult && $suppliersResult->num_rows > 0) {
    while($row = $suppliersResult->fetch_assoc()){
        $suppliers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Suppliers Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    :root {
        --primary-orange: #f05a28;
        --bg-color: #f8fafc;
        --sidebar-width: 260px;
        --topbar-height: 80px; /* Ang sukat ng topbar mo */
        --text-dark: #1e293b;
        --text-muted: #64748b;
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
    
    body { 
        background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    /* FIX: Dagdag na padding-top para hindi matakpan ng Topbar */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: calc(var(--topbar-height) + 30px) 40px 40px 40px; 
        width: calc(100% - var(--sidebar-width));
        transition: all 0.3s ease;
    }

    /* HEADER SECTION */
    .header-section { margin-bottom: 35px; }
    .header-section h2 { font-size: 32px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
    .header-section p { color: var(--text-muted); font-size: 16px; }

    /* STAT CARDS - Glassmorphism style katulad ng Reviews */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        padding: 25px;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.8);
        transition: transform 0.3s ease;
    }

    .stat-card:hover { transform: translateY(-5px); }

    .stat-card .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 20px;
    }

    .icon-total { background: #fff5f2; color: #f05a28; }
    .icon-active { background: #ecfdf5; color: #059669; }
    .icon-spend { background: #fffbeb; color: #d97706; }
    .icon-status { background: #f1f5f9; color: #475569; }

    .stat-card span { display: block; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; }
    .stat-card h3 { font-size: 1.8rem; font-weight: 800; color: var(--text-dark); }

    /* TABLE CONTAINER */
    .table-container {
        background: #fff;
        border-radius: 28px;
        padding: 0; /* Changed to 0 para sa better alignment */
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        border: 1px solid #edf2f7;
        overflow: hidden;
    }

    .table-header {
        padding: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-header h3 { font-size: 20px; font-weight: 700; color: var(--text-dark); }

    .add-supplier-btn {
        background: var(--primary-orange);
        color: white;
        padding: 12px 24px;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        box-shadow: 0 4px 14px rgba(240, 90, 40, 0.3);
    }

    .add-supplier-btn:hover { background: #d94e20; transform: translateY(-2px); }

    /* TABLE STYLING */
    .table-responsive { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    
    thead th {
        text-align: left;
        padding: 20px 25px;
        background: #fcfdfe;
        color: #94a3b8;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #f1f5f9;
    }

    tbody td {
        padding: 20px 25px;
        border-bottom: 1px solid #f8fafc;
        font-size: 14px;
        color: #475569;
        vertical-align: middle;
    }

    tbody tr:hover td { background-color: #f8fafc; }

    .supplier-name { font-weight: 700; color: #1e293b; font-size: 15px; }
    .category-tag { background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; }
    .rating-stars { color: #f59e0b; font-weight: 700; display: flex; align-items: center; gap: 5px; }

    .edit-btn {
        color: var(--primary-orange);
        background: #fff5f2;
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        transition: 0.2s;
    }

    .edit-btn:hover { background: var(--primary-orange); color: white; }

    @media (max-width: 1200px) {
        .summary-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 992px) {
        .main-content { margin-left: 0; width: 100%; padding-top: 100px; }
    }
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main-content">
    <div class="header-section animate__animated animate__fadeIn">
        <h2>Suppliers Dashboard</h2>
        <p>Manage your local boutique supplier partnerships and inventory sourcing.</p>
    </div>

    <div class="summary-grid animate__animated animate__fadeInUp">
        <div class="stat-card">
            <div class="icon-box icon-total"><i class="fa-solid fa-box"></i></div>
            <span>Total Suppliers</span>
            <h3><?= number_format($totalSuppliers) ?></h3>
        </div>
        <div class="stat-card">
            <div class="icon-box icon-active"><i class="fa-solid fa-circle-check"></i></div>
            <span>Top Rated</span>
            <h3><?= number_format($activeSuppliers) ?></h3>
        </div>
        <div class="stat-card">
            <div class="icon-box icon-spend"><i class="fa-solid fa-money-bill-wave"></i></div>
            <span>Total Spend</span>
            <h3>₱<?= number_format($totalSpend, 2) ?></h3>
        </div>
        <div class="stat-card">
            <div class="icon-box icon-status"><i class="fa-solid fa-filter"></i></div>
            <span>Availability</span>
            <h3>ACTIVE</h3>
        </div>
    </div>

    <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
        <div class="table-header">
            <h3>Suppliers List</h3>
            <a href="add_supplier.php" class="add-supplier-btn"><i class="fa-solid fa-plus"></i> Add Supplier</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Category</th>
                        <th>Province</th>
                        <th>Contact</th>
                        <th>Rating</th>
                        <th>Deliveries</th>
                        <th>Total Spend</th>
                        <th>Terms</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($suppliers)): ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state" style="padding: 60px 0; text-align: center;">
                                    <i class="fa-solid fa-users-viewfinder" style="font-size: 50px; color: #e2e8f0; margin-bottom: 20px; display: block;"></i>
                                    <p style="color: #94a3b8;">No suppliers found. Get started by adding your first supplier.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($suppliers as $s): ?>
                        <tr>
                            <td><span class="supplier-name"><?= htmlspecialchars($s['supplier_name']) ?></span></td>
                            <td><span class="category-tag"><?= htmlspecialchars($s['category_name']) ?></span></td>
                            <td><?= htmlspecialchars($s['province_name']) ?></td>
                            <td><i class="fa-solid fa-phone-flip me-2" style="font-size: 11px; opacity: 0.5;"></i><?= htmlspecialchars($s['contact_number']) ?></td>
                            <td>
                                <div class="rating-stars">
                                    <i class="fa-solid fa-star"></i> <?= number_format($s['rating'], 1) ?>
                                </div>
                            </td>
                            <td><?= number_format($s['deliveries']) ?></td>
                            <td style="font-weight: 700; color: #1e293b;">₱<?= number_format($s['total_spend'], 2) ?></td>
                            <td><small class="fw-bold"><?= htmlspecialchars($s['terms']) ?></small></td>
                            <td class="text-center">
                                <a href="edit_supplier.php?id=<?= $s['supplier_id'] ?>" class="edit-btn">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>