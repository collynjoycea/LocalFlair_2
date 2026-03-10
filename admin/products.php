<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "lily1245";
$db   = "localflair_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- LOGIC FOR POST REQUESTS (Add/Edit/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id      = (int)$_POST['edit_id'];
    $product_name = trim($_POST['product_name']);
    $category     = $_POST['category'];
    $province     = $_POST['province'];
    $net_content  = $_POST['net_content'] ?? '';
    $description  = $_POST['description'] ?? '';
    $stock        = (int)$_POST['stock'];
    $price        = (float)$_POST['price'];
    $packaging    = $_POST['packaging'] ?? '';
    $status       = ($stock <= 10) ? "low" : "active";

    $catQuery = $conn->prepare("SELECT category_id FROM categories WHERE category_name=?");
    $catQuery->bind_param("s", $category);
    $catQuery->execute();
    $category_id = $catQuery->get_result()->fetch_assoc()['category_id'];

    $provQuery = $conn->prepare("SELECT province_id FROM provinces WHERE province_name=?");
    $provQuery->bind_param("s", $province);
    $provQuery->execute();
    $province_id = $provQuery->get_result()->fetch_assoc()['province_id'];

    $getImage = $conn->prepare("SELECT image FROM products WHERE product_id=?");
    $getImage->bind_param("i", $edit_id);
    $getImage->execute();
    $currentImage = $getImage->get_result()->fetch_assoc()['image'];
    $imageName = $currentImage;

    if (!empty($_FILES['productImage']['name'])) {
        $uploadDir = __DIR__ . "/uploads/";
        $imageName = time() . "_" . basename($_FILES['productImage']['name']);
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $imageName)) {
            if ($currentImage !== "default.png" && file_exists($uploadDir . $currentImage)) {
                unlink($uploadDir . $currentImage);
            }
        }
    }

    $stmt = $conn->prepare("UPDATE products SET product_name=?, stock=?, price=?, category_id=?, province_id=?, image=?, net_content=?, packaging=?, description=?, status=? WHERE product_id=?");
    $stmt->bind_param("sidiisssssi", $product_name, $stock, $price, $category_id, $province_id, $imageName, $net_content, $packaging, $description, $status, $edit_id);
    echo $stmt->execute() ? "updated" : "error";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name']) && !isset($_POST['edit_id'])) {
    $product_name = trim($_POST['product_name']);
    $category     = $_POST['category'];
    $province     = $_POST['province'];
    $net_content  = $_POST['net_content'] ?? '';
    $packaging    = $_POST['packaging'] ?? '';
    $description  = $_POST['description'] ?? '';
    $stock        = (int)$_POST['stock'];
    $price        = (float)$_POST['price'];
    $status       = $_POST['status'] ?? 'active';

    $imageName = "default.png";
    if (!empty($_FILES['productImage']['name'])) {
        $uploadDir = __DIR__ . "/uploads/";
        $imageName = time() . "_" . basename($_FILES['productImage']['name']);
        move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $imageName);
    }

    $catQuery = $conn->prepare("SELECT category_id FROM categories WHERE category_name=?");
    $catQuery->bind_param("s", $category);
    $catQuery->execute();
    $category_id = $catQuery->get_result()->fetch_assoc()['category_id'] ?? null;

    $provQuery = $conn->prepare("SELECT province_id FROM provinces WHERE province_name=?");
    $provQuery->bind_param("s", $province);
    $provQuery->execute();
    $province_id = $provQuery->get_result()->fetch_assoc()['province_id'] ?? null;

    if (!$category_id || !$province_id) {
        echo "Error: Category o Province hindi nahanap.";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO products (product_name, stock, price, category_id, province_id, image, net_content, packaging, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidiisssss", $product_name, $stock, $price, $category_id, $province_id, $imageName, $net_content, $packaging, $description, $status);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id; 
        $formatted_sku = "LF-" . str_pad($new_id, 4, '0', STR_PAD_LEFT); 
        $updateSku = $conn->prepare("UPDATE products SET sku = ? WHERE product_id = ?");
        $updateSku->bind_param("si", $formatted_sku, $new_id);
        $updateSku->execute();
        echo "success";
    } else {
        echo "error";
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("UPDATE products SET status = 'archived' WHERE product_id = ?");
    $stmt->bind_param("i", $delete_id);
    echo $stmt->execute() ? "deleted" : "error";
    exit();
}

$sql = "SELECT p.*, c.category_name, pr.province_name FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        JOIN provinces pr ON p.province_id = pr.province_id 
        WHERE p.status != 'archived' ORDER BY p.product_id DESC";
$result = $conn->query($sql);

// ================= EXPORT CSV =================
if(isset($_GET['export']) && $_GET['export'] == "csv"){

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_export.csv"');

    $output = fopen("php://output", "w");

    fputcsv($output, ["SKU","Product Name","Category","Province","Price","Stock"]);

    $sql = "SELECT p.*, c.category_name, pr.province_name 
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            JOIN provinces pr ON p.province_id = pr.province_id
            WHERE p.status != 'archived'";

    $res = $conn->query($sql);

    while($row = $res->fetch_assoc()){

        $sku = !empty($row['sku']) ? $row['sku'] : "LF-" . str_pad($row['product_id'],4,'0',STR_PAD_LEFT);

        fputcsv($output,[
            $sku,
            $row['product_name'],
            $row['category_name'],
            $row['province_name'],
            $row['price'],
            $row['stock']
        ]);
    }

    fclose($output);
    exit();
}

// ================= EXPORT PDF =================
if(isset($_GET['export']) && $_GET['export'] == "pdf"){

    require('fpdf/fpdf.php');

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);

    $pdf->Cell(190,10,"LocalFlair Inventory Report",0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',10);

    $pdf->Cell(30,8,"SKU",1);
    $pdf->Cell(50,8,"Product",1);
    $pdf->Cell(35,8,"Category",1);
    $pdf->Cell(35,8,"Province",1);
    $pdf->Cell(20,8,"Price",1);
    $pdf->Cell(20,8,"Stock",1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',10);

    $sql = "SELECT p.*, c.category_name, pr.province_name 
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            JOIN provinces pr ON p.province_id = pr.province_id
            WHERE p.status != 'archived'";

    $res = $conn->query($sql);

    while($row = $res->fetch_assoc()){

        $sku = !empty($row['sku']) ? $row['sku'] : "LF-" . str_pad($row['product_id'],4,'0',STR_PAD_LEFT);

        $pdf->Cell(30,8,$sku,1);
        $pdf->Cell(50,8,$row['product_name'],1);
        $pdf->Cell(35,8,$row['category_name'],1);
        $pdf->Cell(35,8,$row['province_name'],1);
        $pdf->Cell(20,8,"P".$row['price'],1);
        $pdf->Cell(20,8,$row['stock'],1);
        $pdf->Ln();
    }

    $pdf->Output();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalFlair | Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --primary-orange: #f05a28;
            --bg-color: #f8fafc;
            --sidebar-width: 260px;
            --topbar-height: 80px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: linear-gradient(135deg, #f8fafd 0%, #e2e8f0 100%); min-height: 100vh; color: var(--text-dark); }

        .main {
            margin-left: var(--sidebar-width);
            padding: calc(var(--topbar-height) + 30px) 40px 40px 40px;
            width: calc(100% - var(--sidebar-width));
            transition: all 0.3s ease;
        }

        /* HEADER */
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px; }
        .page-title p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* FILTER CARD (Gaya ng sa Orders) */
        .filter-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }

        .filter-group { display: flex; gap: 8px; background: #fff; padding: 5px; border-radius: 12px; border: 1px solid #edf2f7; }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #edf2f7;
            background: white;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            outline: none;
            cursor: pointer;
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            max-width: 350px;
        }
        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 12px;
            border: 1px solid #edf2f7;
            outline: none;
            font-size: 13px;
        }

        /* BUTTONS */
        .btn-add {
            background: var(--primary-orange);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(240, 90, 40, 0.2);
        }
        .btn-add:hover { transform: translateY(-2px); filter: brightness(1.1); }

        .btn-export {
            padding: 9px 15px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
        }
        .csv { background: #ecfdf5; color: #059669; }
        .pdf { background: #fef2f2; color: #dc2626; }

        /* TABLE CONTAINER */
        .table-container {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            border: 1px solid #edf2f7;
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left; padding: 20px 25px; background: #fcfdfe;
            color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; border-bottom: 1px solid #f1f5f9;
        }

        tbody td { padding: 18px 25px; border-bottom: 1px solid #f8fafc; font-size: 14px; vertical-align: middle; }
        tbody tr:hover td { background-color: #fcfdfe; }

        .sku-text { font-family: monospace; font-weight: 800; color: var(--primary-orange); background: #fff5f2; padding: 4px 8px; border-radius: 6px; }
        .product-img { width: 45px; height: 45px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; }

        /* STATUS PILLS */
        .status-pill { 
            padding: 6px 14px; border-radius: 10px; font-size: 11px; font-weight: 700; 
            text-transform: uppercase; display: inline-block;
        }
        .status-active { background: #ecfdf5; color: #059669; }
        .status-low { background: #fffbeb; color: #d97706; }
        .status-out { background: #fef2f2; color: #dc2626; }

        /* ACTIONS */
        .act-btn { 
            width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; 
            justify-content: center; text-decoration: none; transition: 0.2s; font-size: 13px; border:none; cursor:pointer;
        }
        .btn-edit { background: #eff6ff; color: #2563eb; margin-right: 5px; }
        .btn-delete { background: #fef2f2; color: #dc2626; }
        .act-btn:hover { transform: translateY(-2px); filter: brightness(0.95); }

        /* MODAL (Original structure kept) */
        .modal {
            display: none; position: fixed; inset: 0; background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center;
        }
        .modal-content {
            background: white; width: 95%; max-width: 700px; border-radius: 24px;
            padding: 35px; position: relative; animation: zoomIn 0.3s ease;
            max-height: 90vh; overflow-y: auto;
        }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        .input-group label { font-size: 11px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block; }
        .input-group input, .input-group select, .input-group textarea {
            width: 100%; padding: 12px; border: 1px solid #edf2f7; border-radius: 10px; font-size: 14px; outline:none;
        }
        .input-group input:focus { border-color: var(--primary-orange); }

        #toast {
            position: fixed; bottom: 30px; right: 30px; padding: 15px 25px;
            border-radius: 12px; color: white; font-weight: 700; opacity: 0;
            transition: 0.4s; z-index: 9999;
        }

        @media (max-width: 992px) {
            .main { margin-left: 0; width: 100%; padding: 100px 20px 20px 20px; }
        }
    </style>
</head>
<body>

    <?php include "includes/sidebar.php"; ?>
    <?php include "includes/topbar.php"; ?>
     <?php require ('fpdf/fpdf.php'); ?>
    

    <main class="main">
        <div class="header-flex animate__animated animate__fadeIn">
            <div class="page-title">
                <h2>Inventory Management</h2>
                <p>Monitor your stock levels, product details, and regional origins.</p>
            </div>
            <button class="btn-add" id="openModal">
                <i class="fa-solid fa-plus"></i> Add New Product
            </button>
        </div>

        <div class="filter-card animate__animated animate__fadeInUp">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="tableSearch" class="search-input" placeholder="Search by name or SKU...">
            </div>
            
            <div class="filter-group">
                <select class="filter-select" id="sortFilter">
                    <option value="">Sort By</option>
                    <option value="name">Name (A-Z)</option>
                    <option value="price">Price (Low-High)</option>
                    <option value="stock">Stock (Low-High)</option>
                </select>
                <select class="filter-select" id="provinceFilter">
                    <option value="">All Provinces</option>
                    <?php 
                    $pRes = $conn->query("SELECT province_name FROM provinces");
                    while($p = $pRes->fetch_assoc()) echo "<option value='{$p['province_name']}'>{$p['province_name']}</option>";
                    ?>
                </select>
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php 
                    $cRes = $conn->query("SELECT category_name FROM categories");
                    while($c = $cRes->fetch_assoc()) echo "<option value='{$c['category_name']}'>{$c['category_name']}</option>";
                    ?>
                </select>
                <button class="btn-export csv" onclick="exportCSV()">
                    <i class="fa-solid fa-file-csv"></i> CSV
                </button>

                <button class="btn-export pdf" onclick="exportPDF()">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </button>
            </div>
        </div>

        <div class="table-container animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr class="product-row" 
                        data-name="<?= strtolower($row['product_name']) ?>" 
                        data-sku="<?= strtolower($row['sku'] ?? '') ?>"
                        data-province="<?= $row['province_name'] ?>"
                        data-category="<?= $row['category_name'] ?>"
                        data-price="<?= $row['price'] ?>"
                        data-stock="<?= $row['stock'] ?>">
                        
                        <td><span class="sku-text"><?= !empty($row['sku']) ? $row['sku'] : "LF-" . str_pad($row['product_id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                        
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <img src="uploads/<?= $row['image'] ?>" class="product-img">
                                <div>
                                    <strong style="color:var(--text-dark); font-size:14px;"><?= $row['product_name'] ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= $row['net_content'] ?></small>
                                </div>
                            </div>
                        </td>
                        
                        <td><span style="color:var(--text-muted); font-weight:600; font-size:12px;"><?= $row['category_name'] ?></span></td>
                        
                        <td><i class="fa-solid fa-location-dot" style="color:var(--primary-orange); font-size:12px;"></i> <?= $row['province_name'] ?></td>
                        
                        <td><strong style="color:var(--text-dark);">₱<?= number_format($row['price'], 2) ?></strong></td>
                        
                        <td><b style="font-weight:800;"><?= $row['stock'] ?></b></td>
                        
                        <td>
                            <?php 
                                $sClass = $row['stock'] == 0 ? 'out' : ($row['stock'] <= 10 ? 'low' : 'active');
                                $sText = $row['stock'] == 0 ? 'Out of Stock' : ($row['stock'] <= 10 ? 'Low Stock' : 'In Stock');
                            ?>
                            <span class="status-pill status-<?= $sClass ?>"><?= $sText ?></span>
                        </td>
                        
                        <td style="text-align:right;">
                            <button onclick='editProduct(<?= json_encode($row) ?>)' class="act-btn btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
                            <button onclick="deleteProduct(<?= $row['product_id'] ?>)" class="act-btn btn-delete" title="Archive"><i class="fa-solid fa-trash-can"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:80px; color:var(--text-muted);">No products found in the inventory.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom:25px; color:var(--text-dark); font-weight:800;">Product Specifications</h3>
            <form id="modalProductForm" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Image Preview & Upload</label>
                        <div style="display:flex; align-items:center; gap:20px; background:#f8fafc; padding:15px; border-radius:15px; border:1px solid #edf2f7;">
                            <img id="imagePreview" src="images/profile-pic.jpg" style="width:70px; height:70px; border-radius:10px; object-fit:cover; border:2px solid white; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                            <input type="file" name="productImage" id="productImage" accept="image/*" style="border:none; padding:0; font-size:12px;">
                        </div>
                    </div>
                    <div class="input-group full-width">
                        <label>Product Name</label>
                        <input type="text" name="product_name" required>
                    </div>
                    <div class="input-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php 
                            $cRes = $conn->query("SELECT category_name FROM categories");
                            while($c = $cRes->fetch_assoc()) echo "<option value='{$c['category_name']}'>{$c['category_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Province</label>
                        <select name="province" required>
                            <option value="">Select Province</option>
                            <?php 
                            $pRes = $conn->query("SELECT province_name FROM provinces");
                            while($p = $pRes->fetch_assoc()) echo "<option value='{$p['province_name']}'>{$p['province_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Net Content</label>
                        <input type="text" name="net_content">
                    </div>
                    <div class="input-group">
                        <label>Packaging</label>
                        <input type="text" name="packaging">
                    </div>
                    <div class="input-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" id="stock" required>
                    </div>
                    <div class="input-group">
                        <label>Price (PHP)</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>
                    <div class="input-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="3" style="resize:none;"></textarea>
                    </div>
                </div>
                <div style="display:flex; gap:12px; margin-top:30px;">
                    <button type="button" id="submitBtnText" onclick="addProductFromModal()" class="btn-add" style="flex:2; justify-content:center;">Save Product</button>
                    <button type="button" onclick="closeModalFunc()" style="flex:1; background:#f1f5f9; border:none; border-radius:12px; cursor:pointer; font-weight:700; color:var(--text-dark);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        const modal = document.getElementById('productModal');
        const openBtn = document.getElementById('openModal');
        const toast = document.getElementById('toast');
        const searchInput = document.getElementById('tableSearch');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const province = document.getElementById('provinceFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const rows = document.querySelectorAll('.product-row');

            rows.forEach(row => {
                const name = row.dataset.name;
                const sku = row.dataset.sku;
                const rowProvince = row.dataset.province;
                const rowCategory = row.dataset.category;

                const matchesSearch = name.includes(searchTerm) || sku.includes(searchTerm);
                const matchesProvince = province === "" || rowProvince === province;
                const matchesCategory = category === "" || rowCategory === category;

                row.style.display = (matchesSearch && matchesProvince && matchesCategory) ? "" : "none";
            });
        }

        searchInput.onkeyup = filterTable;
        document.getElementById('provinceFilter').onchange = filterTable;
        document.getElementById('categoryFilter').onchange = filterTable;

        document.getElementById('sortFilter').onchange = function() {
            const val = this.value;
            const tbody = document.getElementById('productTableBody');
            const rows = Array.from(tbody.querySelectorAll('.product-row'));

            rows.sort((a, b) => {
                if (val === 'name') return a.dataset.name.localeCompare(b.dataset.name);
                if (val === 'price') return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                if (val === 'stock') return parseInt(a.dataset.stock) - parseInt(b.dataset.stock);
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));
        };

        openBtn.onclick = () => {
            document.getElementById('modalProductForm').reset();
            document.getElementById('imagePreview').src = 'images/profile-pic.jpg';
            document.querySelector('#submitBtnText').innerText = "Add Product"; 
            if(document.getElementById('edit_id')) document.getElementById('edit_id').remove();
            modal.style.display = 'flex';
        }

        function closeModalFunc() { modal.style.display = 'none'; }

        function showToast(msg, type) {
            toast.innerText = msg;
            toast.style.backgroundColor = type === 'success' ? '#059669' : '#dc2626';
            toast.style.opacity = 1;
            setTimeout(() => { toast.style.opacity = 0; }, 3000);
        }

        document.getElementById('productImage').onchange = (e) => {
            const [file] = e.target.files;
            if (file) document.getElementById('imagePreview').src = URL.createObjectURL(file);
        }

        function addProductFromModal() {
            const formData = new FormData(document.getElementById('modalProductForm'));
            fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === "success" || data.trim() === "updated") {
                    showToast("Success!", "success");
                    setTimeout(() => location.reload(), 800);
                } else { showToast(data, "error"); }
            });
        }

        function editProduct(product) {
            modal.style.display = "flex";
            const f = document.getElementById('modalProductForm');
            f.product_name.value = product.product_name;
            f.category.value = product.category_name;
            f.province.value = product.province_name;
            f.net_content.value = product.net_content;
            f.packaging.value = product.packaging;
            f.stock.value = product.stock;
            f.price.value = product.price;
            f.description.value = product.description;
            document.getElementById('imagePreview').src = "uploads/" + product.image;

            if(!document.getElementById('edit_id')) {
                const hidden = document.createElement("input");
                hidden.type = "hidden"; hidden.name = "edit_id"; hidden.id = "edit_id";
                f.appendChild(hidden);
            }
            document.getElementById('edit_id').value = product.product_id;
            document.querySelector('#submitBtnText').innerText = "Update Product";
        }

        function deleteProduct(id) {
            if(!confirm("Archive this product?")) return;
            const fd = new FormData(); fd.append("delete_id", id);
            fetch(window.location.href, { method: "POST", body: fd })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === "deleted") {
                    showToast("Product Archived", "success");
                    setTimeout(() => location.reload(), 800);
                }
            });
        }

        function exportCSV(){
            window.location.href = "?export=csv";
        }

        function exportPDF(){
            window.location.href = "?export=pdf";
        }
    </script>
</body>
</html>