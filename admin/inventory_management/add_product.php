<?php
session_start();

// 1. SECURITY & DATABASE CONNECTION
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php"); 
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

// 2. PRODUCT ACTIONS (ADD, EDIT, DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("UPDATE products SET status='ARCHIVED' WHERE product_id=?");
        $stmt->bind_param("i", $id);
        echo $stmt->execute() ? "success" : "error";
        exit();
    }
    $p_name = trim($_POST['product_name']);
    $cat    = $_POST['category'];
    $prov   = $_POST['province'];
    $stock  = (int)$_POST['stock'];
    $price  = (float)$_POST['price'];
    $desc   = $_POST['description'] ?? '';
    $status = ($stock == 0) ? "out_of_stock" : (($stock <= 10) ? "low" : "active");

    $cQ = $conn->prepare("SELECT category_id FROM categories WHERE category_name=?");
    $cQ->bind_param("s", $cat); $cQ->execute();
    $cat_id = $cQ->get_result()->fetch_assoc()['category_id'] ?? 1;

    $pQ = $conn->prepare("SELECT province_id FROM provinces WHERE province_name=?");
    $pQ->bind_param("s", $prov); $pQ->execute();
    $prov_id = $pQ->get_result()->fetch_assoc()['province_id'] ?? 1;

    $uploadDir = "../uploads/"; 
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (!empty($_POST['edit_id'])) {
        $edit_id = (int)$_POST['edit_id'];
        $imgQ = $conn->prepare("SELECT image FROM products WHERE product_id=?");
        $imgQ->bind_param("i", $edit_id); $imgQ->execute();
        $imgName = $imgQ->get_result()->fetch_assoc()['image'] ?? 'default.png';

        if (!empty($_FILES['productImage']['name'])) {
            $newImg = time() . "_" . basename($_FILES['productImage']['name']);
            if (move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $newImg)) {
                $imgName = $newImg;
            }
        }
        $stmt = $conn->prepare("UPDATE products SET product_name=?, stock=?, price=?, category_id=?, province_id=?, image=?, description=?, status=? WHERE product_id=?");
        $stmt->bind_param("sidiisssi", $p_name, $stock, $price, $cat_id, $prov_id, $imgName, $desc, $status, $edit_id);
    } else {
        $imgName = "default.png";
        if (!empty($_FILES['productImage']['name'])) {
            $imgName = time() . "_" . basename($_FILES['productImage']['name']);
            move_uploaded_file($_FILES['productImage']['tmp_name'], $uploadDir . $imgName);
        }
        $stmt = $conn->prepare("INSERT INTO products (product_name, stock, price, category_id, province_id, image, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sidiisss", $p_name, $stock, $price, $cat_id, $prov_id, $imgName, $desc, $status);
    }

    echo $stmt->execute() ? "success" : "error";
    exit();
}

// 3. FETCH DATA
$sql = "SELECT p.*, c.category_name, pr.province_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN provinces pr ON p.province_id = pr.province_id 
        WHERE p.status IS NULL OR p.status != 'ARCHIVED'
        ORDER BY p.product_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | LocalFlair</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; }

        /* --- MAIN CONTENT AREA --- */
        .main { 
            flex: 1; 
            margin-left: var(--sidebar-width); 
            padding: 110px 40px 40px 40px; /* Space for fixed Topbar */
            width: calc(100% - var(--sidebar-width)); 
        }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; font-weight: 800; }

        /* --- TABLE --- */
        .table-container { background: var(--white); border-radius: 20px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #fafafa; padding: 18px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); letter-spacing: 0.5px; }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .prod-img { width: 50px; height: 50px; object-fit: cover; border-radius: 12px; background: #f1f5f9; }
        
        .badge { padding: 6px 12px; border-radius: 10px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge.active { background: #dcfce7; color: #15803d; }
        .badge.low { background: #fef3c7; color: #b45309; }
        .badge.out { background: #fee2e2; color: #b91c1c; }
        .btn-edit { color: #f59e0b; background: #fffbeb; padding: 8px; border-radius: 8px; border:none; cursor:pointer; }
        .btn-delete { color: #ef4444; background: #fef2f2; padding: 8px; border-radius: 8px; border:none; cursor:pointer; }

        /* --- MODAL --- */
        .modal { display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px); }
        .modal-content { background:white; padding:35px; border-radius:24px; width:500px; max-height: 90vh; overflow-y: auto; }
        
        label { font-size: 11px; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; }
        #pForm input, 
#pForm select, 
#pForm textarea { 
    width: 100%; 
    padding: 12px 15px; 
    margin-bottom: 15px; 
    border: 1px solid #e2e8f0; 
    border-radius: 12px; 
    outline: none; 
    transition: 0.3s; 
    font-size: 14px; 
}
        input:focus { border-color: var(--accent-orange); }
        
        .btn-save { padding: 12px; background: var(--accent-orange); color: white; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; }

        @media (max-width: 1024px) { .main { margin-left: 0; width: 100%; padding-top: 130px; } }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

    <div class="main">
        <div class="page-header">
            <div>
                <h1>Product Management</h1>
                <p style="color:var(--text-muted); font-size:14px;">Create and maintain your local product catalog.</p>
            </div>
            <button onclick="showAddModal()" style="background:var(--accent-orange); color:white; padding:12px 24px; border-radius:14px; border:none; cursor:pointer; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px;">
                <i class="fa fa-plus"></i> Add New Product
            </button>
        </div>

        <div class="table-container">
            <table id="inventoryTable">
                <thead>
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="../uploads/<?= !empty($row['image']) ? $row['image'] : 'default.png' ?>" class="prod-img" onerror="this.src='../uploads/default.png'">
                        </td>
                        <td>
                            <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($row['product_name']) ?></div>
                            <div style="font-size:11px; color:var(--text-muted);"><?= $row['province_name'] ?></div>
                        </td>
                        <td style="color: var(--text-muted); font-weight:500;"><?= $row['category_name'] ?></td>
                        <td><span style="font-weight:600;"><?= $row['stock'] ?></span> <small>pcs</small></td>
                        <td style="font-weight:700;">₱<?= number_format($row['price'], 2) ?></td>
                        <td>
                            <?php 
                                $s = ($row['stock'] == 0) ? 'out' : (($row['stock'] <= 10) ? 'low' : 'active');
                                $t = ($row['stock'] == 0) ? 'Out of Stock' : (($row['stock'] <= 10) ? 'Low Stock' : 'In Stock');
                            ?>
                            <span class="badge <?= $s ?>"><?= $t ?></span>
                        </td>
                        <td>
                            <div style="display:flex; gap:10px; justify-content:center;">
                                <button onclick='editProduct(<?= json_encode($row) ?>)' class="btn-edit"><i class="fa fa-edit"></i></button>
                                <button onclick="deleteProduct(<?= $row['product_id'] ?>)" class="btn-delete"><i class="fa fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="pModal" class="modal">
        <div class="modal-content">
            <h3 id="mTitle" style="margin-bottom:20px;">Add New Product</h3>
            <form id="pForm">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <label>Product Name</label>
                <input type="text" name="product_name" required>
                
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Category</label>
                        <select name="category">
                            <option>Food Delicacies</option>
                            <option>Handcrafted Crafts</option>
                            <option>Eco-Friendly Goods</option>
                            <option>Featured Products</option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label>Province</label>
                        <select name="province">
                            <option>Cebu</option><option>Benguet</option><option>Davao</option><option>Guimaras</option><option>Laguna</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;"><label>Stock</label><input type="number" name="stock" value="0" required></div>
                    <div style="flex:1;"><label>Price (₱)</label><input type="number" name="price" step="0.01" value="0.00" required></div>
                </div>

                <label>Description</label>
                <textarea name="description" rows="3"></textarea>

                <label>Product Image</label>
                <input type="file" name="productImage" accept="image/*" style="border:none; padding:0;">

                <div style="display:flex; gap:12px; margin-top:10px;">
                    <button type="button" onclick="saveData()" class="btn-save" style="flex:2;">Save Product</button>
                    <button type="button" onclick="closeModal()" style="flex:1; background:#f1f5f9; border:none; border-radius:12px; cursor:pointer; font-weight:600;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('pModal');
        
        function showAddModal() {
            document.getElementById('pForm').reset();
            document.getElementById('edit_id').value = '';
            document.getElementById('mTitle').innerText = 'Add New Product';
            modal.style.display = 'flex';
        }

        function closeModal() { modal.style.display = 'none'; }
        
        function saveData() {
            const fd = new FormData(document.getElementById('pForm'));
            fetch(window.location.href, { method: 'POST', body: fd })
            .then(res => res.text())
            .then(data => data.trim() === 'success' ? location.reload() : alert("Error: " + data));
        }

        function editProduct(p) {
            document.getElementById('edit_id').value = p.product_id;
            document.querySelector('[name="product_name"]').value = p.product_name;
            document.querySelector('[name="category"]').value = p.category_name;
            document.querySelector('[name="province"]').value = p.province_name;
            document.querySelector('[name="stock"]').value = p.stock;
            document.querySelector('[name="price"]').value = p.price;
            document.querySelector('[name="description"]').value = p.description || '';
            document.getElementById('mTitle').innerText = 'Update Product';
            modal.style.display = 'flex';
        }
        function deleteProduct(id) {
            if(!confirm("Archived this product?")) return;
            const fd = new FormData(); 
            fd.append('delete_id', id);
            fetch(window.location.href, { method: 'POST', body: fd }).then(() => location.reload());
        }

        // Search link to topbar
        const topSearch = document.getElementById('dirSearch');
        if(topSearch) {
            topSearch.addEventListener('keyup', function() {
                let filter = this.value.toUpperCase();
                let rows = document.querySelectorAll("#inventoryTable tbody tr");
                rows.forEach(row => {
                    row.style.display = row.innerText.toUpperCase().includes(filter) ? "" : "none";
                });
            });
        }
    </script>
</body>
</html>