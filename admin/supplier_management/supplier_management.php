<?php
session_start();

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['employee_id'])) {
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

// Add Supplier Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supplier'])) {
    $name     = $conn->real_escape_string($_POST['supplier_name']);
    $cat_id   = (int)$_POST['category_id'];
    $prov_id  = (int)$_POST['province_id'];
    $contact  = $conn->real_escape_string($_POST['contact_number']);
    $terms    = $conn->real_escape_string($_POST['terms']);

    $insertQuery = "INSERT INTO suppliers (supplier_name, category_id, province_id, contact_number, terms) 
                    VALUES ('$name', $cat_id, $prov_id, '$contact', '$terms')";
    
    if ($conn->query($insertQuery)) {
        echo "<script>alert('Supplier added successfully!'); window.location.href='supplier_management.php';</script>";
    }
}

// Stats
$countSuppliers = $conn->query("SELECT COUNT(*) as total FROM suppliers")->fetch_assoc()['total'];
$countActive = $conn->query("SELECT COUNT(*) as total FROM suppliers WHERE deliveries > 0")->fetch_assoc()['total']; // Kunyari active kung may deliveries na
$countCats = $conn->query("SELECT COUNT(*) as total FROM categories")->fetch_assoc()['total'];

$result = $conn->query("
    SELECT s.*, c.category_name, p.province_name 
    FROM suppliers s
    LEFT JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN provinces p ON s.province_id = p.province_id
    ORDER BY s.supplier_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Directory | LocalFlair</title>
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
        }

        /* Header Section */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        /* Stats Cards */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 20px; 
            margin-bottom: 30px; 
        }

        .stat-card { 
            background: #fff; 
            padding: 25px; 
            border-radius: 20px; 
            border: 1px solid var(--border); 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }

        .stat-card h4 { 
            margin: 0; font-size: 11px; text-transform: uppercase; 
            color: var(--text-muted); letter-spacing: 1px; font-weight: 700;
        }

        .stat-card .count { 
            display: block; margin-top: 12px; font-size: 28px; 
            font-weight: 800; color: var(--text-dark); 
        }

        /* Table & Search */
        .search-bar {
            padding: 12px 20px;
            width: 320px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
            font-family: inherit;
            margin-bottom: 20px;
        }

        .table-container { 
            background: #fff; border-radius: 24px; border: 1px solid var(--border); 
            padding: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
        }

        table { width: 100%; border-collapse: collapse; }
        th { padding: 18px 15px; font-size: 12px; color: var(--text-muted); text-align: left; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid var(--border); }
        td { padding: 18px 15px; border-bottom: 1px solid #f8fafc; font-size: 14px; color: #475569; }

        .btn-add { 
            background: var(--accent); color: white; padding: 12px 25px; 
            border-radius: 15px; font-weight: 700; text-decoration: none;
            display: inline-flex; align-items: center; gap: 10px; transition: 0.3s;
            border: none; cursor: pointer;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-2px); }

        .rating-badge { background: #fef9c3; color: #854d0e; padding: 4px 10px; border-radius: 8px; font-weight: 700; font-size: 12px; }

        .action-btn {
            width: 36px; height: 36px; border-radius: 10px; background: #f8fafc;
            color: #64748b; display: inline-flex; align-items: center; justify-content: center;
            transition: 0.2s; text-decoration: none; border: 1px solid #e2e8f0; cursor: pointer;
        }
        .action-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

        /* Modal Styling */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-content { 
            background: #fff; margin: 5% auto; padding: 35px; width: 450px; 
            border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2);
            position: relative;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--text-dark); }
        .form-group input, .form-group select { 
            width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; 
            border-radius: 12px; box-sizing: border-box; font-family: inherit; font-size: 14px;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/topbar.php'; ?>

<div class="main">
    <div class="header-flex">
        <div>
            <h1 style="margin:0; font-weight: 800; font-size: 28px; letter-spacing: -1px;">Supplier Directory</h1>
            <p style="color:var(--text-muted); margin: 5px 0 0 0; font-size: 14px;">Manage your supplier records based on categories and provinces.</p>
        </div>
        <button onclick="openModal()" class="btn-add">
            <i class="fa fa-plus"></i> Add Supplier
        </button>
    </div>

    <section class="stats-grid">
        <div class="stat-card" style="border-bottom: 4px solid #e95a24;">
            <h4>Total Suppliers</h4>
            <span class="count"><?= number_format($countSuppliers) ?></span>
        </div>
        <div class="stat-card" style="border-bottom: 4px solid #10b981;">
            <h4>Active Vendors</h4>
            <span class="count"><?= number_format($countActive) ?></span>
        </div>
        <div class="stat-card" style="border-bottom: 4px solid #6366f1;">
            <h4>Categories</h4>
            <span class="count"><?= number_format($countCats) ?></span>
        </div>
    </section>

    <input type="text" class="search-bar" id="supplierSearch" placeholder="Search Supplier Name..." onkeyup="searchTable()">

    <div class="table-container">
        <table id="supplierTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier Name</th>
                    <th>Category</th>
                    <th>Province</th>
                    <th>Contact</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-size: 12px; font-weight: 600; color: var(--text-muted);">#SUP-<?= str_pad($row['supplier_id'], 3, "0", STR_PAD_LEFT) ?></td>
                        <td><strong style="color: var(--text-dark);"><?= htmlspecialchars($row['supplier_name']) ?></strong></td>
                        <td><span style="font-weight: 500;"><?= htmlspecialchars($row['category_name'] ?: 'Uncategorized') ?></span></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:5px;">
                                <i class="fa-solid fa-location-dot" style="color: #ef4444; font-size: 12px;"></i>
                                <?= htmlspecialchars($row['province_name'] ?: 'N/A') ?>
                            </div>
                        </td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($row['contact_number']) ?></td>
                        <td><span class="rating-badge"><i class="fa fa-star"></i> <?= number_format($row['rating'], 1) ?></span></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <button class="action-btn" title="View"><i class="fa fa-eye"></i></button>
                                <button class="action-btn" title="Edit"><i class="fa fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding: 50px; color: #94a3b8;">No suppliers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="supplierModal" class="modal">
    <div class="modal-content">
        <h2 style="margin-top: 0; font-weight: 800; letter-spacing: -1px;">Add New Supplier</h2>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 25px;">Fill in the details to register a new local partner.</p>
        
        <form method="POST">
            <div class="form-group">
                <label>Supplier Name</label>
                <input type="text" name="supplier_name" placeholder="e.g. Laguna Handicrafts Inc." required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        $cats = $conn->query("SELECT * FROM categories");
                        while($c = $cats->fetch_assoc()) echo "<option value='{$c['category_id']}'>{$c['category_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <select name="province_id" required>
                        <option value="">Select Province</option>
                        <?php 
                        $provs = $conn->query("SELECT * FROM provinces");
                        while($p = $provs->fetch_assoc()) echo "<option value='{$p['province_id']}'>{$p['province_name']}</option>";
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" placeholder="0912 345 6789" required>
            </div>
            <div class="form-group">
                <label>Payment Terms</label>
                <input type="text" name="terms" placeholder="e.g. Net 30, COD">
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" name="add_supplier" class="btn-add" style="flex:2; justify-content:center;">Save Supplier</button>
                <button type="button" onclick="closeModal()" style="flex:1; background: #f1f5f9; border:none; padding:12px; border-radius:15px; font-weight:700; color:#64748b; cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('supplierModal').style.display = 'block'; }
    function closeModal() { document.getElementById('supplierModal').style.display = 'none'; }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        let modal = document.getElementById('supplierModal');
        if (event.target == modal) modal.style.display = "none";
    }

    // Live Search Function
    function searchTable() {
        let input = document.getElementById("supplierSearch");
        let filter = input.value.toUpperCase();
        let table = document.getElementById("supplierTable");
        let tr = table.getElementsByTagName("tr");

        for (let i = 1; i < tr.length; i++) {
            let tdName = tr[i].getElementsByTagName("td")[1];
            if (tdName) {
                let txtValue = tdName.textContent || tdName.innerText;
                tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
            }
        }
    }
</script>

</body>
</html>