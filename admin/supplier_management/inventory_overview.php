<?php
session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['admin_name'] ?? $_SESSION['employee_name'] ?? 'User';

$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stats Queries
$countTotalProducts = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$countLowStock = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 10")->fetch_assoc()['total'];
$countOutOfStock = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock = 0")->fetch_assoc()['total'];
$countCategories = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];
$countProvinces = $conn->query("SELECT COUNT(*) as total FROM provinces")->fetch_assoc()['total'];

$query = "
    SELECT p.product_id, p.product_name, p.stock, p.price, p.image,
           c.category_name, pr.province_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN provinces pr ON p.province_id = pr.province_id
    ORDER BY p.created_at DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --bg: #f8fafc; 
            --accent: #e95a24; 
            --text-dark: #1e293b; 
            --text-muted: #64748b;
            --border: #f1f5f9; 
            --sidebar-width: 260px;
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg); 
            margin: 0; 
            color: var(--text-dark); 
            display: flex; 
        }

        .main { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 30px; 
            padding-top: 110px; 
            min-height: 100vh; 
            box-sizing: border-box; 
            transition: 0.3s;
        }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(5, 1fr); 
            gap: 20px; 
            margin-bottom: 30px; 
        }

        .stat-card { 
            background: #fff; 
            padding: 25px 20px; 
            border-radius: 20px; 
            border: 1px solid var(--border); 
            transition: 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .stat-card h4 { 
            margin: 0; font-size: 11px; text-transform: uppercase; 
            color: var(--text-muted); letter-spacing: 1px; font-weight: 700;
        }

        .stat-card .count { 
            display: block; margin-top: 12px; font-size: 28px; 
            font-weight: 800; color: var(--text-dark); 
        }

        .table-container { 
            background: #fff; border-radius: 24px; border: 1px solid var(--border); 
            padding: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
        }

        table { width: 100%; border-collapse: collapse; }
        th { padding: 20px 15px; font-size: 12px; color: var(--text-muted); text-align: left; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid var(--border); }
        td { padding: 18px 15px; border-bottom: 1px solid #f8fafc; font-size: 14px; color: #475569; }

        .product-img {
            width: 50px; height: 50px; border-radius: 12px;
            object-fit: cover; border: 1px solid #f1f5f9;
        }

        .stock-pill { padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; }
        .stock-low { background: #fff7ed; color: #c2410c; }
        .stock-out { background: #fef2f2; color: #dc2626; }
        .stock-ok { background: #f0fdf4; color: #15803d; }

        .btn-main { 
            background: var(--accent); color: white; padding: 12px 25px; 
            border-radius: 15px; font-weight: 700; text-decoration: none;
            display: inline-flex; align-items: center; gap: 10px; transition: 0.3s;
        }

        .action-btn {
            width: 38px; height: 38px; border-radius: 12px; background: #f8fafc;
            color: #64748b; display: inline-flex; align-items: center; justify-content: center;
            transition: 0.2s; text-decoration: none; border: 1px solid #e2e8f0;
        }
        .action-btn:hover { background: var(--accent); color: #fff; }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main">
        <div class="header-flex" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
            <div>
                <h1 style="margin:0; font-weight: 800; font-size: 28px; letter-spacing: -1px;">Inventory Overview</h1>
                <p style="color:var(--text-muted); margin: 5px 0 0 0; font-size: 14px;">Manage your product stock levels and regional origins.</p>
            </div>
            <a href="add_product.php" class="btn-main"><i class="fa fa-plus"></i> Add New Product</a>
        </div>

        <section class="stats-grid">
            <div class="stat-card" style="border-bottom: 4px solid #cbd5e1;"><h4>Total Items</h4><span class="count"><?= number_format($countTotalProducts) ?></span></div>
            <div class="stat-card" style="border-bottom: 4px solid #f97316;"><h4>Low Stock</h4><span class="count" style="color: #f97316;"><?= number_format($countLowStock) ?></span></div>
            <div class="stat-card" style="border-bottom: 4px solid #ef4444;"><h4>Out of Stock</h4><span class="count" style="color: #ef4444;"><?= number_format($countOutOfStock) ?></span></div>
            <div class="stat-card" style="border-bottom: 4px solid #6366f1;"><h4>Categories</h4><span class="count"><?= number_format($countCategories) ?></span></div>
            <div class="stat-card" style="border-bottom: 4px solid #10b981;"><h4>Provinces</h4><span class="count"><?= number_format($countProvinces) ?></span></div>
        </section>

        <div class="table-container">
            <div style="padding: 20px; font-size: 14px; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                Stock Details Table <span style="font-weight: 400; color: #94a3b8; margin-left: 10px;">Showing <?= $result->num_rows ?> products total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Origin</th>
                        <th>Unit Price</th>
                        <th>Stock Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <?php 
                                            $imageFileName = !empty($row['image']) ? $row['image'] : 'default.png';
                                            
                                            // FIX: Paggamit ng ../ dahil ang uploads ay nasa labas ng admin/ management folder
                                            $imgPath = '../uploads/' . $imageFileName;
                                            
                                            // Fallback kung hindi mahanap ang file sa server
                                            if (!file_exists($imgPath)) { 
                                                $imgPath = '../uploads/default.png'; 
                                            }
                                        ?>
                                        <img src="<?= $imgPath ?>" class="product-img" alt="Product" onerror="this.src='../uploads/default.png'">
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($row['product_name']) ?></div>
                                            <div style="font-size: 11px; color: var(--text-muted);">#<?= str_pad($row['product_id'], 5, '0', STR_PAD_LEFT) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="font-weight: 500;"><?= htmlspecialchars($row['category_name'] ?: 'Uncategorized') ?></span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:5px; color: #ef4444; font-size: 13px;">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <span style="color:var(--text-dark);"><?= htmlspecialchars($row['province_name'] ?: 'N/A') ?></span>
                                    </div>
                                </td>
                                <td style="font-weight: 700; color: var(--text-dark);">₱<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php 
                                        $s = $row['stock'];
                                        $pill = ($s == 0) ? 'stock-out' : (($s <= 10) ? 'stock-low' : 'stock-ok');
                                        $text = ($s == 0) ? 'OUT OF STOCK' : $s . ' units left';
                                    ?>
                                    <span class="stock-pill <?= $pill ?>"><?= $text ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="edit_product.php?id=<?= $row['product_id'] ?>" class="action-btn" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="product_history.php?id=<?= $row['product_id'] ?>" class="action-btn" title="History">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 50px; color: #94a3b8;">No products in inventory.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>