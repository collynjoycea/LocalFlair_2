<?php
session_start();

// 1. SECURITY & DATABASE CONNECTION
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: ../login.php"); 
    exit();
}

$host = "localhost";
$user = "root";
$pass = "WelCome145";
$db   = "localflair_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. FETCH DASHBOARD COUNTS
$countTotal = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'] ?? 0;
$countLow   = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock > 0 AND stock <= 10")->fetch_assoc()['total'] ?? 0;
$countOut   = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock = 0")->fetch_assoc()['total'] ?? 0;

// 3. FETCH PRODUCTS LIST
$query = "SELECT p.*, c.category_name, pr.province_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          LEFT JOIN provinces pr ON p.province_id = pr.province_id 
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management | LocalFlair</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --accent-orange: #e95a24;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #f1f5f9;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-color); 
            display: flex; 
            min-height: 100vh; 
            color: var(--text-dark); 
        }

        /* --- MAIN CONTENT ADJUSTMENT --- */
        .main {
            flex: 1;
            margin-left: var(--sidebar-width); 
            /* Nagdagdag ng margin-top dahil fixed ang topbar.php (approx 100px) */
            padding: 110px 40px 40px 40px; 
            width: calc(100% - var(--sidebar-width));
        }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 800; color: #333; }
        
        /* --- STATS CARDS --- */
        .status-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .status-card { 
            background: var(--white); 
            border: 1px solid var(--border-color); 
            padding: 25px; 
            border-radius: 20px; 
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
        }
        .status-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .status-card.active { border: 2px solid var(--accent-orange); background: #fffcfb; }
        .status-card .count { font-size: 32px; font-weight: 800; display: block; margin-bottom: 5px; }
        .status-card .label { font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- TABLE STYLE --- */
        .table-container { 
            background: var(--white); border-radius: 20px; border: 1px solid var(--border-color); 
            overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); 
        }
        table { width: 100%; border-collapse: collapse; }
        th { 
            background: #fafafa; padding: 18px 20px; text-align: left; font-size: 11px; 
            text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); 
        }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        .product-img { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; background: #f1f5f9; }
        
        .status-pill { padding: 6px 12px; border-radius: 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .stock-ok { background: #dcfce7; color: #15803d; }
        .stock-low { background: #fef3c7; color: #b45309; }
        .stock-out { background: #fee2e2; color: #b91c1c; }

        .btn-action { 
            width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; 
            border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: var(--text-muted); 
            text-decoration: none; transition: 0.2s; 
        }
        .btn-action:hover { background: var(--accent-orange); color: white; border-color: var(--accent-orange); }

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
                <h1>Product Inventory</h1>
                <p style="color:var(--text-muted); font-size:14px;">Monitor and manage your shop's stock levels.</p>
            </div>
            <a href="add_product.php" style="background:var(--accent-orange); color:white; padding:12px 24px; border-radius:14px; text-decoration:none; font-weight:700; font-size:14px; box-shadow: 0 4px 12px rgba(233, 90, 36, 0.2);">
                <i class="fa fa-plus"></i> Add New Product
            </a>
        </div>

        <div class="status-grid">
            <div class="status-card active" data-filter="all">
                <span class="count"><?= $countTotal ?></span>
                <span class="label">Total Items</span>
            </div>
            <div class="status-card" data-filter="LOW STOCK">
                <span class="count" style="color: #f59e0b;"><?= $countLow ?></span>
                <span class="label">Low Stock</span>
            </div>
            <div class="status-card" data-filter="OUT OF STOCK">
                <span class="count" style="color: #ef4444;"><?= $countOut ?></span>
                <span class="label">Out of Stock</span>
            </div>
        </div>

        <div class="table-container">
            <table id="inventoryTable">
                <thead>
                    <tr>
                        <th style="width: 35%;">Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr data-category="<?= htmlspecialchars($row['category_name']) ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <?php $img_path = "../uploads/" . (!empty($row['image']) ? $row['image'] : 'default.png'); ?>
                                <img src="<?= $img_path ?>" class="product-img" onerror="this.src='../uploads/default.png'">
                                <div>
                                    <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($row['product_name']) ?></div>
                                    <div style="font-size:11px; color:var(--text-muted); font-weight:500;">
                                        ID: #<?= str_pad($row['product_id'], 4, '0', STR_PAD_LEFT) ?> | <?= htmlspecialchars($row['province_name']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="color: var(--text-muted); font-weight:500;"><?= htmlspecialchars($row['category_name']) ?></td>
                        <td style="font-weight:700; font-size:15px;">₱<?= number_format($row['price'], 2) ?></td>
                        <td>
                            <span style="font-weight:700; font-size:15px;"><?= $row['stock'] ?></span>
                            <span style="font-size:12px; color:#94a3b8;">pcs</span>
                        </td>
                        <td class="status-cell">
                            <?php 
                                $s = (int)$row['stock'];
                                if($s == 0) echo '<span class="status-pill stock-out">Out of Stock</span>';
                                elseif($s <= 10) echo '<span class="status-pill stock-low">Low Stock</span>';
                                else echo '<span class="status-pill stock-ok">In Stock</span>';
                            ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:8px;">
                                <a href="view_product.php?id=<?= $row['product_id'] ?>" class="btn-action" title="View"><i class="fa fa-eye"></i></a>
                                <a href="add_product.php?id=<?= $row['product_id'] ?>" class="btn-action" title="Edit"><i class="fa fa-edit"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Synchronize topbar search with the table
        const topSearch = document.getElementById('dirSearch');
        const cards = document.querySelectorAll('.status-card');
        const rows = document.querySelectorAll('#inventoryTable tbody tr');

        function filterTable() {
            const searchTerm = topSearch.value.toLowerCase();
            const activeCard = document.querySelector('.status-card.active');
            const statusFilter = activeCard ? activeCard.getAttribute('data-filter').toUpperCase() : 'ALL';

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const statusCell = row.querySelector('.status-cell');
                const status = statusCell ? statusCell.innerText.toUpperCase() : '';

                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = (statusFilter === 'ALL' || status.includes(statusFilter));

                row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
            });
        }

        if(topSearch) {
            topSearch.addEventListener('keyup', filterTable);
        }

        cards.forEach(card => {
            card.addEventListener('click', function() {
                cards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                filterTable();
            });
        });
    </script>
</body>
</html>