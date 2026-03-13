<?php
session_start();

// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";     
$pass = "lily1245";            
$db   = "localflair_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// 2. SECURITY
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// 3. FETCH PRODUCTS WITH FULL DETAILS
$query = "SELECT p.*, c.category_name, pr.province_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          LEFT JOIN provinces pr ON p.province_id = pr.province_id 
          WHERE COALESCE(p.status,'') != 'ARCHIVED'
          ORDER BY p.product_name ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Directory | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --accent-orange: #e95a24;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #f1f5f9;
            --sidebar-width: 260px; /* Synchronized with topbar */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* --- MAIN CONTENT ADJUSTMENT --- */
        .main { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 110px 40px 40px 40px; /* Space for fixed topbar */
            width: calc(100% - var(--sidebar-width)); 
        }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: var(--text-dark); }
        .page-header p { color: var(--text-muted); font-size: 14px; margin-top: 5px; }
        
        .add-btn {
            background: var(--accent-orange); color: white; text-decoration: none; padding: 12px 24px; border-radius: 14px;
            font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .add-btn:hover { background: #d44d1d; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(233, 90, 36, 0.2); }

        /* --- DIRECTORY GRID --- */
       .directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08); }

        .image-container {
            width: 100%;
            height: 160px;
        }

        .image-container img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s ease; }
        .product-card:hover .image-container img { scale: 1.1; }

        .category-badge {
            position: absolute;
            top: 15px; left: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 800;
            color: var(--accent-orange);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            z-index: 1;
        }

        .card-content { padding: 25px; flex-grow: 1; }
        .card-content h3 { font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
        .province-info { font-size: 13px; color: var(--text-muted); margin-bottom: 15px; display: flex; align-items: center; gap: 6px; font-weight: 500; }
        .price-tag { font-size: 22px; font-weight: 800; color: var(--text-dark); }
        .price-tag span { color: var(--accent-orange); }

        .card-actions { display: flex; gap: 12px; padding: 20px 25px 25px; }

        .btn-view, .btn-edit {
            flex: 1; padding: 10px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 13px;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s;
        }

        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-view:hover { background: #e2e8f0; color: var(--text-dark); }
        
        .btn-edit { border: 1.5px solid #f1f5f9; color: var(--text-muted); }
        .btn-edit:hover { border-color: var(--accent-orange); color: var(--accent-orange); background: #fff4f0; }

        @media (max-width: 1024px) {
            .main { margin-left: 0; width: 100%; padding-top: 130px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Product Directory</h1>
                <p>Browse and explore all products registered in the system.</p>
            </div>
        </div>

        <div class="directory-grid" id="directoryGrid">
            <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
            <div class="product-card">
                <div class="image-container">
                    <span class="category-badge"><?= htmlspecialchars($row['category_name'] ?? 'General') ?></span>
                    <?php 
                        $image_filename = !empty($row['image']) ? $row['image'] : 'default.png';
                        $img_path = "../uploads/" . $image_filename; 
                    ?>
                    <img src="<?= $img_path ?>" onerror="this.src='../uploads/default.png'">
                </div>
                
                <div class="card-content">
                    <h3><?= htmlspecialchars($row['product_name'] ?? 'Unnamed Product') ?></h3>
                    <div class="province-info">
                        <i class="fa fa-location-dot" style="color: var(--accent-orange);"></i> 
                        <?= htmlspecialchars($row['province_name'] ?? 'Philippines') ?>
                    </div>
                    <div class="price-tag"><span>₱</span><?= number_format((float)($row['price'] ?? 0), 2) ?></div>
                </div>

                <div class="card-actions">
                    <a href="view_product.php?id=<?= $row['product_id'] ?>" class="btn-view">
                        <i class="fa fa-eye"></i> View
                    </a>
                    <a href="add_product.php?id=<?= $row['product_id'] ?>" class="btn-edit">
                        <i class="fa fa-pen-to-square"></i> Edit
                    </a>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 20px; background: white; border-radius: 30px; border: 2px dashed #e2e8f0;">
                <div style="background: #f8fafc; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    <i class="fa fa-box-open" style="font-size: 32px; color: #cbd5e1;"></i>
                </div>
                <h3 style="color: var(--text-dark); margin-bottom: 5px;">No Products Found</h3>
                <p style="color: var(--text-muted); font-size: 14px;">Try adding your first product to see it in the directory.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Live Search Synchronization with Topbar
        const topSearch = document.getElementById('dirSearch');
        
        if (topSearch) {
            topSearch.addEventListener('keyup', function() {
                let filter = this.value.toUpperCase();
                let cards = document.querySelectorAll(".product-card");
                
                cards.forEach(card => {
                    // Search in Product Name, Province, and Category
                    let text = card.textContent.toUpperCase();
                    card.style.display = text.indexOf(filter) > -1 ? "" : "none";
                });
            });
        }
    </script>
</body>
</html>