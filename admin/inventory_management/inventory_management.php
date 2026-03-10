<?php
session_start();

// 1. SECURITY & DATABASE CONNECTION
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
    header("Location: ../login.php"); 
    exit();
}

$host = "localhost";
$user = "root";
$pass = "bastaCode";
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
        .stock-low { background: #fef3c7; color: #f2f2f2; }
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

        .modal-content.orange-theme {
        background: var(--accent-orange);
        color: white;
        padding: 40px;
        border-radius: 30px;
        width: 500px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(233, 90, 36, 0.4);
    }

  /* Clean White Modal Box */
    .modal-content.view-theme {
        background-color: #ffffff !important; 
        color: #1e293b; /* Dark text for readability on white */
        padding: 40px;
        border-radius: 30px;
        width: 500px;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    /* Vibrant Orange Info Boxes */
    .info-box {
        background: #e95a24; /* LocalFlair Orange */
        padding: 15px;
        border-radius: 15px;
        border: none;
    }

    /* Labels inside the orange boxes */
    .info-box label {
        display: block;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 3px;
        color: rgba(255, 255, 255, 0.8); /* Faded white label */
    }

    /* Data text inside the orange boxes */
    .info-box p {
        font-weight: 600;
        font-size: 15px;
        margin: 0;
        color: #ffffff; /* Solid white text */
    }

    /* Title and Description on the white background */
    .modal-content.view-theme h2 { 
        color: #1e293b; 
        margin-top: 20px; 
        font-size: 26px; 
        font-weight: 800;
    }

    .view-desc-text {
        color: #64748b;
        font-weight: 400;
        font-size: 13px;
        line-height: 1.6;
    }

    /* Close Button: Orange circle with White X */
    .close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        border: none;
        background: #e95a24;
        color: #ffffff;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
    }

    .close-btn:hover { 
        transform: rotate(90deg); 
        background: #d34e1f;
    }
    </style>
<div id="viewModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div class="modal-content view-theme">
        <button class="close-btn" onclick="closeViewModal()">&times;</button>
        
        <div style="text-align:center; margin-bottom:25px;">
            <div style="position: relative; display: inline-block;">
                <img id="viewImg" src="" style="width:130px; height:130px; border-radius:25px; object-fit:cover; border: 4px solid #e95a24; box-shadow: 0 8px 15px rgba(233, 90, 36, 0.2);">
            </div>
            <h2 id="viewName"></h2>
            
            <div id="viewStatus" style="margin-top: 10px; display: inline-block; padding: 6px 16px; border-radius: 10px; font-weight: 800; font-size: 11px; background: #fff7ed; color: #e95a24;"></div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-bottom: 20px;">
            <div class="info-box"><label>Price</label><p id="viewPrice"></p></div>
            <div class="info-box"><label>Stock</label><p id="viewStock"></p></div>
            <div class="info-box"><label>Category</label><p id="viewCat"></p></div>
            <div class="info-box"><label>Province</label><p id="viewProv"></p></div>
        </div>
        
        <div style="padding: 15px; border-radius: 15px; background: #f8fafc; border: 1px solid #f1f5f9;">
            <label style="display:block; font-size:10px; font-weight:800; color:#94a3b8; text-transform:uppercase; margin-bottom:5px;">Description</label>
            <p id="viewDesc" class="view-desc-text"></p>
        </div>
    </div>
</div>
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
        <button type="button" onclick='openViewModal(<?= json_encode($row) ?>)' class="btn-action" title="View">
            <i class="fa fa-eye"></i>
        </button>
    </div>
</td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

   <script>
    // 1. SELECT ELEMENTS (Declared only once!)
    const topSearch = document.getElementById('dirSearch');
    const cards = document.querySelectorAll('.status-card');
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    const viewModal = document.getElementById('viewModal');

    // 2. FILTERING & SEARCH LOGIC
    function filterTable() {
        const searchTerm = topSearch ? topSearch.value.toLowerCase() : "";
        const activeCard = document.querySelector('.status-card.active');
        const statusFilter = activeCard ? activeCard.getAttribute('data-filter').toUpperCase() : 'ALL';

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const statusCell = row.querySelector('.status-cell');
            const statusText = statusCell ? statusCell.innerText.toUpperCase() : '';

            const matchesSearch = text.includes(searchTerm);
            const matchesStatus = (statusFilter === 'ALL' || statusText.includes(statusFilter));

            row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
        });
    }

    // Event Listeners for Search and Cards
    if(topSearch) topSearch.addEventListener('keyup', filterTable);

    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            filterTable();
        });
    });

    // 3. MODAL LOGIC
    function openViewModal(p) {
        // Fill data
        document.getElementById('viewName').innerText = p.product_name;
        document.getElementById('viewPrice').innerText = '₱' + parseFloat(p.price).toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('viewStock').innerText = p.stock + ' pcs';
        document.getElementById('viewCat').innerText = p.category_name;
        document.getElementById('viewProv').innerText = p.province_name;
        document.getElementById('viewDesc').innerText = p.description || 'No description provided.';
        
        // Handle Image
        const imgPath = p.image ? "../uploads/" + p.image : "../uploads/default.png";
        document.getElementById('viewImg').src = imgPath;
        
        // Handle Status Badge
        const statusPill = document.getElementById('viewStatus');
        const stock = parseInt(p.stock);
        
        if(stock === 0) {
            statusPill.innerText = 'OUT OF STOCK';
            statusPill.style.color = '#ef4444'; 
        } else if(stock <= 10) {
            statusPill.innerText = 'LOW STOCK';
            statusPill.style.color = '#f59e0b'; 
        } else {
            statusPill.innerText = 'IN STOCK';
            statusPill.style.color = '#10b981'; 
        }

        viewModal.style.display = 'flex';
    }

    function closeViewModal() {
        viewModal.style.display = 'none';
    }

    // Close modal if clicking outside the white content box
    window.onclick = function(event) {
        if (event.target == viewModal) closeViewModal();
    }
</script>
</body>
</html>