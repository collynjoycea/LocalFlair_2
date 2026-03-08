<?php
session_start();
include __DIR__ . '/db.php'; // Database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Shop Now | LocalFlair</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">

<style>
/* --- STYLES FOR SHOP NOW PAGE (NO CHANGES AS REQUESTED) --- */
.shop-header{ display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; }
.search-box{ width:320px; position:relative; }
.search-box input{ border-radius:30px; padding-right:45px; }
.search-box button{ position:absolute; right:5px; top:50%; transform:translateY(-50%); border:none; background:#ead9bd; color:white; width:38px; height:38px; border-radius:50%; }
.shop-card{ border:none; border-radius:25px; overflow:hidden; transition:0.3s; box-shadow:0 4px 15px rgba(0,0,0,0.05); background: #fff; padding: 20px; height: 100%; }
.shop-card:hover{ transform:translateY(-6px); box-shadow:0 12px 25px rgba(0,0,0,0.12); }
.shop-card img{ height:220px; width: 100%; object-fit:cover; border-radius: 20px; margin-bottom: 15px; }
.shop-card h5 { font-weight: 800; color: #1a2a40; font-size: 1.25rem; margin-bottom: 5px; }
.category-tag { font-size: 0.85rem; color: #7d8da1; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
.shop-price{ font-weight: 800; color:#e46c4d; font-size: 1.3rem; margin-bottom: 20px; }
.btn-buy, .btn-cart { background: #ead9bd !important; border: 1px solid #dcc4a3 !important; color: #222 !important; font-weight: 700 !important; border-radius: 12px !important; padding: 10px 5px !important; transition: 0.2s; font-size: 0.95rem; }
body{ padding-top: 100px; margin: 0; font-family: 'Segoe UI', sans-serif; background: linear-gradient(-45deg, #fdf6f0, #f3ede6, #e8f6f3, #fdf3e7); background-size: 400% 400%; animation: gradientMove 15s ease infinite; }
@keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
.back-btn { display: inline-block; padding: 8px 18px; border-radius: 12px; border: 1px solid #333; text-decoration: none; color: #333; font-weight: 500; margin-bottom: 20px; }

/* --- CONSISTENT MODAL UI (AS PER FRUIT WINES SCREENSHOT) --- */
#productModal .modal-content {
    border-radius: 30px;
    border: none;
    overflow: hidden; /* Important for the split look */
}

#productModal .modal-body {
    padding: 0; /* Remove default padding for edge-to-edge image */
}

.modal-split-container {
    display: flex;
    min-height: 500px;
}

.modal-img-side {
    flex: 1;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-img-side img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* Makes the image fill the container nicely */
}

.modal-info-side {
    flex: 1.2;
    padding: 40px;
    background: #fff;
    position: relative;
}

.modal-category-badge {
    color: #e46c4d;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
}

.modal-available {
    color: #28a745;
    font-weight: 700;
    font-size: 0.85rem;
}

.modal-info-side h2 {
    font-weight: 800;
    color: #1a2a40;
    margin-top: 10px;
}

.modal-info-side .price {
    font-size: 1.8rem;
    color: #e46c4d;
    font-weight: 800;
    margin-bottom: 20px;
}

.qty-label { font-weight: 700; margin-bottom: 10px; display: block; }

.qty-control {
    display: flex;
    align-items: center;
    background: #f1f1f1;
    border-radius: 10px;
    width: fit-content;
    padding: 5px;
    margin-bottom: 30px;
}

.qty-control button {
    border: none;
    background: none;
    width: 35px;
    height: 35px;
    font-size: 1.2rem;
    font-weight: bold;
}

.qty-control input {
    width: 50px;
    text-align: center;
    border: none;
    background: none;
    font-weight: 800;
}

.modal-close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #f1f1f1;
    border: none;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    font-weight: bold;
    z-index: 10;
}
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="modal-close-btn" data-bs-dismiss="modal">&times;</button>
                <div class="modal-split-container">
                    <div class="modal-img-side">
                        <img id="modalImage" src="" alt="Product">
                    </div>
                    <div class="modal-info-side">
                        <div class="d-flex align-items:center gap-2">
                            <span id="modalCategoryTag" class="modal-category-badge"></span>
                            <span class="modal-available">• AVAILABLE</span>
                        </div>
                        <h2 id="modalTitle"></h2>
                        <div id="modalPrice" class="price"></div>

                        <div class="mb-3">
                            <small class="text-muted d-block fw-bold">Origin</small>
                            <span id="modalOrigin"></span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block fw-bold">Product Details</small>
                            <p class="mb-0">Net Content: <span id="modalContent"></span></p>
                            <p>Packaging: <span id="modalPackaging"></span></p>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block fw-bold">Description</small>
                            <p id="modalDescription" style="font-size: 0.9rem; color: #555;"></p>
                        </div>

                        <span class="qty-label">Quantity</span>
                        <div class="qty-control">
                            <button onclick="modalQty(-1)">−</button>
                            <input type="text" id="modalQtyInput" value="0" readonly>
                            <button onclick="modalQty(1)">+</button>
                        </div>

                        <div class="d-flex gap-3">
                            <button onclick="handleAction('buy')" class="btn btn-buy w-50 py-3 shadow-sm">Buy Now</button>
                            <button onclick="handleAction('cart')" class="btn btn-cart w-50 py-3 shadow-sm">Add to Cart</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <a href="index.php" class="back-btn">← Back to Home</a>
    <div class="shop-header">
        <h2 class="fw-bold">Shop Now</h2>
        <form method="GET" action="products.php" class="search-box">
            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">🔍</button>
        </form>
    </div>

    <div class="row g-4">
    <?php
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $query = "SELECT p.*, c.category_name, pr.province_name 
              FROM products p 
              INNER JOIN categories c ON p.category_id = c.category_id 
              INNER JOIN provinces pr ON p.province_id = pr.province_id 
              WHERE p.status != 'archived'"; 

    if($search != '') { $query .= " AND (p.product_name LIKE '%$search%' OR c.category_name LIKE '%$search%')"; }
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $imgName = $row['image'];
            $imgPath = "images/" . $imgName;
            if(!file_exists($imgPath)) { $imgPath = "admin/uploads/" . $imgName; }
            $price_fmt = number_format($row['price'], 2);
            
            echo '
            <div class="col-md-3">
                <div class="shop-card d-flex flex-column">
                    <img src="'.$imgPath.'" onerror="this.src=\'https://via.placeholder.com/300\'">
                    <div class="category-tag">'.$row['province_name'].' • '.$row['category_name'].'</div>
                    <h5 class="flex-grow-1">'.$row['product_name'].'</h5>
                    <div class="shop-price">₱'.$price_fmt.'</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-buy w-50" data-bs-toggle="modal" data-bs-target="#productModal" 
                            onclick="loadProduct(\''.addslashes($row['product_name']).'\', \''.$imgPath.'\', \''.$row['category_name'].'\', \''.$row['net_content'].'\', \''.$row['packaging'].'\', \''.$row['province_name'].'\', \''.addslashes($row['description']).'\', \'₱'.$price_fmt.'\', \''.$row['product_id'].'\')">Buy</button>
                        <button class="btn btn-cart w-50" data-bs-toggle="modal" data-bs-target="#productModal" 
                            onclick="loadProduct(\''.addslashes($row['product_name']).'\', \''.$imgPath.'\', \''.$row['category_name'].'\', \''.$row['net_content'].'\', \''.$row['packaging'].'\', \''.$row['province_name'].'\', \''.addslashes($row['description']).'\', \'₱'.$price_fmt.'\', \''.$row['product_id'].'\')">Add to Cart</button>
                    </div>
                </div>
            </div>';
        }
    }
    ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentProductId = null;

function modalQty(amount){
    let input = document.getElementById("modalQtyInput");
    let current = parseInt(input.value) || 0;
    let newQty = current + amount;
    if(newQty < 0) newQty = 0; // Starts from 0 as requested
    input.value = newQty;
}

function loadProduct(title, image, category, content, packaging, origin, description, price, id){
    currentProductId = id;
    document.getElementById("modalTitle").textContent = title;
    document.getElementById("modalImage").src = image; 
    document.getElementById("modalCategoryTag").textContent = category;
    document.getElementById("modalContent").textContent = content;
    document.getElementById("modalPackaging").textContent = packaging;
    document.getElementById("modalOrigin").textContent = origin;
    document.getElementById("modalDescription").textContent = description;
    document.getElementById("modalPrice").textContent = price;
    document.getElementById("modalQtyInput").value = 0; // Quantity starts at 0
}

function handleAction(type) {
    let qty = document.getElementById("modalQtyInput").value;
    if(!currentProductId || qty <= 0) {
        alert("Please select a quantity.");
        return;
    }

    if(type === 'buy') {
        // Gagawa tayo ng temporary form para mag-POST sa place-order.php
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = 'place-order.php';

        // Input para sa Product ID (kailangang array 'selected_items[]' base sa code mo)
        let idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'selected_items[]';
        idInput.value = currentProductId;
        form.appendChild(idInput);

        // Input para sa Quantity
        let qtyInput = document.createElement('input');
        qtyInput.type = 'hidden';
        qtyInput.name = 'buy_now_qty';
        qtyInput.value = qty;
        form.appendChild(qtyInput);

        document.body.appendChild(form);
        form.submit();
    } else {
        // Redirection sa cart (Dito, okay lang ang GET dahil inayos natin ang cart.php kanina)
        window.location.href = "cart.php?action=add&id=" + currentProductId + "&qty=" + qty;
    }
}
</script>

</body>
</html>