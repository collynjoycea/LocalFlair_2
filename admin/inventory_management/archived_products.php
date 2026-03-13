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

$conn = new mysqli($host,$user,$pass,$db);
if ($conn->connect_error) {
    die("Connection failed: ".$conn->connect_error);
}


/* ================= RESTORE PRODUCT ================= */

if(isset($_POST['restore_id'])){

    $id = (int)$_POST['restore_id'];

    $stmt = $conn->prepare("UPDATE products SET status='active' WHERE product_id=?");
    $stmt->bind_param("i",$id);

    echo $stmt->execute() ? "restored" : "error";
    exit();
}


/* ================= GET ARCHIVED PRODUCTS ================= */

$sql = "SELECT p.*,c.category_name,pr.province_name
        FROM products p
        JOIN categories c ON p.category_id=c.category_id
        JOIN provinces pr ON p.province_id=pr.province_id
        WHERE p.status='archived'
        ORDER BY p.product_id DESC";

$result = $conn->query($sql);

if(isset($_POST['delete_permanent_id'])) {

    $id = (int)$_POST['delete_permanent_id'];

    // Get image filename to delete
    $imgQuery = $conn->prepare("SELECT image FROM products WHERE product_id=?");
    $imgQuery->bind_param("i", $id);
    $imgQuery->execute();
    $imgResult = $imgQuery->get_result();
    $imageName = $imgResult->fetch_assoc()['image'] ?? '';

    // Delete product from DB
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id=?");
    $stmt->bind_param("i", $id);

    if($stmt->execute()) {
        // Delete image file if not default
        if($imageName && $imageName != "default.png" && file_exists(__DIR__ . "/uploads/" . $imageName)) {
            unlink(__DIR__ . "/uploads/" . $imageName);
        }
        echo "deleted";
    } else {
        echo "error";
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>LocalFlair | Archived Products</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>

    :root {
        --bg-color: #f8fafc;
        --accent-orange: #e95a24;
        --accent-red: #ef4444;
        --accent-yellow: #f59e0b;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --white: #ffffff;
        --border-color: #f1f5f9;
        --sidebar-width: 260px;
        --topbar-height: 70px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--bg-color); color: var(--text-dark); display: flex; min-height: 100vh; }


    body{
    background:linear-gradient(135deg,#f8fafd 0%,#e2e8f0 100%);
    min-height:100vh;
    color:var(--text-dark);
    }
    .main{
        margin-left:var(--sidebar-width);
        padding:calc(var(--topbar-height) + 30px) 40px 40px 40px;
        width:calc(100% - var(--sidebar-width));
    }

   .page-header { 
        display: flex;
        flex-direction: column;  
        align-items: flex-start; 
        margin-bottom: 35px;
        gap:6px;
    }
    
    .page-header h1 { font-size: 28px; font-weight: 800; color: var(--text-dark); }
    .page-header p { color: var(--text-muted); font-size: 14px; margin-top: 5px; }
            

    .table-container{
    background:#fff;
    border-radius:24px;
    box-shadow:0 10px 40px rgba(0,0,0,0.03);
    border:1px solid #edf2f7;
    overflow:hidden;
    margin-top:30px;
    }

    table{
    width:100%;
    border-collapse:collapse;
    }

    thead th{
    text-align:left;
    padding:20px 25px;
    background:#fcfdfe;
    color:#94a3b8;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:1px;
    border-bottom:1px solid #f1f5f9;
    }

    tbody td{
    padding:18px 25px;
    border-bottom:1px solid #f8fafc;
    font-size:14px;
    font-weight:600; /* makes content bold */
    }

    tbody tr:hover td{
    background:#fcfdfe;
    }

    .sku-text{
    font-family:monospace;
    font-weight:800;
    color:var(--primary-orange);
    background:#fff5f2;
    padding:4px 8px;
    border-radius:6px;
    }

    .product-img{
    width:45px;
    height:45px;
    object-fit:cover;
    border-radius:10px;
    border:1px solid #eee;
    }

    .act-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        transition: 0.2s;
        font-size: 14px;
        font-weight: 700;
        gap: 6px;
        padding: 6px 12px;
    }

    .btn-restore {
        background: #ecfdf5;
        color: #059669;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-restore i {
        margin-right: 4px;
    }

    .btn-restore:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

</style>

</head>

<body>

<?php include "includes/sidebar.php"; ?>
<?php include "includes/topbar.php"; ?>

<main class="main">
    <div class="header-flex animate__animated animate__fadeIn">
        <div class="page-header">
            <h1>Archived Products</h1>
            <p>Products removed from inventory but still stored in the system.</p>
        </div>
    </div>

<div class="table-container animate__animated animate__fadeInUp">

<table>

<thead>

<tr>
<th>SKU</th>
<th>Product Details</th>
<th>Category</th>
<th>Location</th>
<th>Price</th>
<th>Stock</th>
<th style="text-align:right;">Action</th>
</tr>

</thead>

<tbody>

<?php if($result->num_rows>0): ?>

<?php while($row=$result->fetch_assoc()): ?>

<tr>

<td>
<span class="sku-text">
<?= !empty($row['sku']) ? $row['sku'] : "LF-" . str_pad($row['product_id'],4,'0',STR_PAD_LEFT) ?>
</span>
</td>

<td>
<div style="display:flex;align-items:center;gap:12px;">
<img src="uploads/<?= $row['image'] ?>" class="product-img">
<div>
<strong><?= $row['product_name'] ?></strong><br>
<small style="color:#64748b"><?= $row['net_content'] ?></small>
</div>
</div>
</td>

<td><?= $row['category_name'] ?></td>

<td>
<i class="fa-solid fa-location-dot" style="color:#f05a28"></i>
<?= $row['province_name'] ?>
</td>

<td>₱<?= number_format($row['price'],2) ?></td>

<td><?= $row['stock'] ?></td>

<td style="text-align:right">

<button onclick="restoreProduct(<?= $row['product_id'] ?>)" class="act-btn btn-restore" title="Restore">
    <i class="fa-solid fa-rotate-left"></i>
</button>

<button onclick="deletePermanentProduct(<?= $row['product_id'] ?>)" class="act-btn btn-delete" title="Delete Permanently" style="background:#fef2f2; color:#dc2626; margin-left:6px;">
<i class="fa-solid fa-trash-can"></i>
</button>

</td>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="7" style="text-align:center;padding:60px;color:#64748b">

No archived products found

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</main>


<script>

function restoreProduct(id){

if(!confirm("Restore this product?")) return;

const fd = new FormData();
fd.append("restore_id",id);

fetch(window.location.href,{
method:"POST",
body:fd
})
.then(res=>res.text())
.then(data=>{

if(data.trim()=="restored"){

alert("Product Restored");

location.reload();

}
function deletePermanentProduct(id){
    if(!confirm("Are you sure? This will permanently delete the product!")) return;

    const fd = new FormData();
    fd.append("delete_permanent_id", id);

    fetch(window.location.href,{
        method:"POST",
        body:fd
    })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === "deleted"){
            alert("Product Permanently Deleted");
            location.reload();
        } else {
            alert("Error deleting product");
        }
    });
}

});

}

</script>

</body>
</html>