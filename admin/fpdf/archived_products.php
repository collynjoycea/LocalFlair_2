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

    if($stmt->execute()){
        echo "restored";
    }else{
        echo "error";
    }

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

?>


<!DOCTYPE html>
<html>
<head>

<title>Archived Products</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
font-family:Arial;
background:#f5f7fb;
padding:40px;
}

.container{
max-width:1100px;
margin:auto;
background:white;
padding:30px;
border-radius:10px;
box-shadow:0 10px 20px rgba(0,0,0,0.05);
}

h2{
margin-bottom:20px;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
padding:12px;
border-bottom:1px solid #eee;
text-align:left;
}

th{
background:#fafafa;
}

.btn{
padding:7px 12px;
border:none;
border-radius:6px;
cursor:pointer;
}

.restore{
background:#10b981;
color:white;
}

.delete{
background:#ef4444;
color:white;
}

</style>
</head>


<body>

<div class="container">

<h2>Archived Products</h2>

<table>

<thead>

<tr>

<th>SKU</th>
<th>Product</th>
<th>Category</th>
<th>Province</th>
<th>Price</th>
<th>Stock</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if($result->num_rows>0): ?>

<?php while($row=$result->fetch_assoc()): ?>

<tr>

<td>
<?= !empty($row['sku']) ? $row['sku'] : "LF-".str_pad($row['product_id'],4,'0',STR_PAD_LEFT) ?>
</td>

<td><?= $row['product_name'] ?></td>

<td><?= $row['category_name'] ?></td>

<td><?= $row['province_name'] ?></td>

<td>₱<?= number_format($row['price'],2) ?></td>

<td><?= $row['stock'] ?></td>

<td>

<button onclick="restoreProduct(<?= $row['product_id'] ?>)"
class="btn restore">

<i class="fa-solid fa-rotate-left"></i> Restore

</button>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="7" style="text-align:center;padding:40px;">
No archived products
</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>



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

});

}

</script>

</body>

</html>