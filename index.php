<?php
session_start();
include __DIR__ . '/db.php';
require_once __DIR__ . '/includes/product_helpers.php';

// 1. Initialize ang variable (Ito ang solusyon sa Warning: null given)
$featuredProducts = [];

// 2. Setup optional columns para sa SQL
$cols = lf_products_optional_columns($conn);
$net  = $cols['net_content'] ? 'p.net_content' : "NULL AS net_content";
$pack = $cols['packaging'] ? 'p.packaging' : "NULL AS packaging";
$desc = $cols['description'] ? 'p.description' : "NULL AS description";

// 3. SQL Query - Diretso sa database
$sql = "
    SELECT 
        p.product_id, p.product_name, p.price, p.stock, p.image, 
        $net, $pack, $desc,
        c.category_name, pr.province_name
    FROM products p
    INNER JOIN categories c ON c.category_id = p.category_id
    INNER JOIN provinces pr ON pr.province_id = p.province_id  /* DAPAT P.PROVINCE_ID ITO */
    WHERE c.category_name = 'Featured Products'
    AND p.status = 'active'
    ORDER BY p.product_id DESC
    LIMIT 4";

$res = $conn->query($sql);

// 4. Punuin ang array kung may nahanap na products
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $row['price'] = (float)$row['price'];
        $row['stock'] = (int)$row['stock'];
        $row['image_url'] = lf_product_image_url($row['image'] ?? '');
        
        $featuredProducts[] = ['product' => $row];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>LocalFlair | Filipino Local Products</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">

<style>
body{
    font-family:'Segoe UI',sans-serif;
    background-color:#f8f5f2;
    margin:0;
}

/* HERO (unchanged) */
.hero{
    margin-top:56px;
    position:relative;
    text-align:center;
    min-height:95vh;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    padding:0 20px;
    background:linear-gradient(rgba(0,0,0,0.25), rgba(0,0,0,0.25)),
    url('images/hero-bg.png') center top/cover no-repeat;
    color:white;
}

.highlight{color:#D6A24A;}
.hero-btn{
    background:#D6A24A;
    color:#fff;
    border:none;
    padding:8px 24px;
    border-radius:6px;
}
.hero-btn:hover{
    background:#fff;
    color:#D6A24A;
    border:2px solid #D6A24A;
    transition:.5s;
}

/* ========= NEW CATEGORY STYLE ========= */
.category-modern{
    position:relative;
    border-radius:20px;
    overflow:hidden;
    height:280px;
}

.category-modern img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:0.4s;
}

.category-modern::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(to top, rgba(0,0,0,0.6), rgba(0,0,0,0.1));
}

.category-text{
    position:absolute;
    bottom:20px;
    left:20px;
    color:white;
    z-index:2;
}

.category-modern:hover img{
    transform:scale(1.08);
}

/* ========= SECOND SECTION ========= */
.support-section{
    background:#fff;
    padding:80px 0;
}

.support-title span{
    color:#E85A1F;
}

.stat-box{
    background:#f3e1d2;
    border-radius:18px;
    padding:30px;
    margin-top:20px;
}

.stat-box h2{
    color:#E85A1F;
    font-size:40px;
    font-weight:bold;
}

/* ===== FEATURED PRODUCT NEW CARD DESIGN ===== */
.product-card{
    border:none;
    border-radius:20px;
    overflow:hidden;
    background:#fff;
    transition:0.3s ease;
}

.product-card img{
    height:260px;
    width:100%;
    object-fit:cover;
}

.product-card:hover{
    transform:translateY(-6px);
    box-shadow:0 12px 25px rgba(0,0,0,0.08);
}

.product-location {
    font-size: 11px !important; /* Pinaliit mula 13px */
    font-weight: 700;
    color: #E85A1F;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.product-title {
    font-size: 15px !important; /* Pinaliit mula 18px */
    font-weight: 700;
    color: #1e293b;
    margin-top: 4px;
    line-height: 1.2;
    /* Para hindi lumampas ang text kung mahaba ang title */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 36px; 
}



/* Typography Adjustments */
.breadcrumb-text {
    font-size: 11px;
    color: #e95a24;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 8px;
}

.product-title {
    font-size: 28px; /* Binabaan mula 36px */
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 5px;
    line-height: 1.2;
}

.product-price {
    font-size: 22px; /* Binabaan mula 28px */
    font-weight: 700;
    color: #e95a24;
    margin-bottom: 20px;
}

/* Product Info Section */
.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 15px;
}

.info-label {
    font-weight: 700;
    font-size: 14px;
    color: #1e293b;
    margin: 0;
}

.info-value {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
    line-height: 1.4;
}

/* 4. QUANTITY SELECTOR - Compact Pill Style */
.qty-label {
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 10px;
    display: block;
    color: #1e293b;
}

.qty-wrapper {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 12px;
    width: fit-content;
    padding: 4px;
    margin-bottom: 25px;
}

.qty-wrapper button {
    border: none;
    background: none;
    width: 36px;
    height: 36px;
    font-weight: bold;
    font-size: 18px;
    color: #475569;
    transition: 0.2s;
}

.qty-wrapper button:hover {
    color: #e95a24;
}

.qty-wrapper input {
    border: none;
    background: none;
    width: 45px;
    text-align: center;
    font-weight: 800;
    font-size: 16px;
    outline: none;
}

/* 5. ACTION BUTTONS - High-end look */
.btn-buy-now {
    background: #ead9bd;
    color: black;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-weight: 700;
    font-size: 15px;
    flex: 1;
    transition: 0.3s;
    
}

.btn-add-cart {
    background: white;
    background: #ead9bd;
    color: #000000;
    border: 2px solid #ead9bd;
    border-radius: 12px;
    padding: 14px;
    font-weight: 700;
    font-size: 15px;
    flex: 1;
    transition: 0.3s;
    
}

.btn-buy-now:hover { background: #ead9bd; transform: translateY(-2px); }
.btn-add-cart:hover { background: #ead9bd; transform: translateY(-2px); }

/* ===== CLEAN ASYMMETRICAL GRID ===== */
.artisan-grid{
display:grid;
grid-template-columns: 2fr 1fr;
grid-template-rows: auto auto;
gap:20px;
}

.artisan-img{
overflow:hidden;
border-radius:18px;
}

.artisan-img img{
width:100%;
height:100%;
object-fit:cover;
display:block;
}

.artisan-img.large{
grid-row:1 / span 2;
height:100%;
}

.artisan-img.small{
height:220px;
}

.stat-box{
grid-column:2;
background:#f3e1d2;
border-radius:18px;
padding:25px;
display:flex;
flex-direction:column;
justify-content:center;
}

#categories {
    scroll-margin-top: 70px; 
}

/* ===== BEIGE BUTTON STYLE ===== */
.btn-buy,
.btn-cart{
    background:#c9b28a;   /* beige */
    color:#000;
    border:none;
    font-weight:600;
    border-radius:8px;
    transition:0.3s ease;
}

.btn-buy:hover,
.btn-cart:hover{
    background:#b79c70;   /* darker beige */
    color:#000;
}

.hero {
    position: relative;
    overflow: hidden;
}

/* subtle animated gradient glow */
.hero::before{
    content:"";
    position:absolute;
    width:500px;
    height:500px;
    background:radial-gradient(circle, rgba(214,162,74,0.4), transparent 70%);
    top:-100px;
    right:-100px;
    animation:floatGlow 8s ease-in-out infinite alternate;
}

@keyframes floatGlow{
    from{ transform:translateY(0px);}
    to{ transform:translateY(40px);}
}
.category-modern{
    backdrop-filter: blur(10px);
    border:1px solid rgba(255,255,255,0.2);
    transition:0.4s ease;
}

.category-modern:hover{
    transform:translateY(-10px);
    box-shadow:0 20px 40px rgba(0,0,0,0.15);
}

body {
    background: linear-gradient(120deg, #fdf6f0, #f8e9dc, #fdf6f0);
    background-size: 600% 600%;
    animation: gradientShift 30s ease infinite;
}

@keyframes gradientShift {
    0% {background-position:0% 50%;}
    50% {background-position:100% 50%;}
    100% {background-position:0% 50%;}
}


</style>
</head>

<body>
<?php include __DIR__ . '/includes/_product_modal.php'; ?>

<?php include 'includes/header.php'; ?>

<!-- HERO -->
<section class="hero">
<div class="container">
<div class="hero-content text-start">
<h1 class="fw-bold display-5">
Discover the <span class="highlight">Best of</span><br>
<span class="highlight">Filipino Local</span> Products
</h1>
<p class="lead">Support Local • Shop Local • Love Local</p>
<a href="products.php" class="btn hero-btn btn-lg mt-3">Shop Now</a>
</div>
</div>
</section>

<!-- ===== MODERN CATEGORIES ===== -->
<div id="categories" class="container my-5">
<h3 class="fw-bold mb-4">Shop by Category</h3>
<p class="text-muted mb-4">Curated collections from local communities</p>

<div class="row g-4">

<div class="col-md-3">
<a href="food-delacacies.php" class="text-decoration-none">
<div class="category-modern shadow">
<img src="images/food-delecacies.avif">
<div class="category-text">
<h5 class="fw-bold mb-1">Food Delicacies</h5>
<small>Taste of the islands</small>
</div>
</div>
</a>
</div>

<div class="col-md-3">
<a href="handcrafted-crafts.php" class="text-decoration-none">
<div class="category-modern shadow">
<img src="images/handcrafted-crafts.avif">
<div class="category-text">
<h5 class="fw-bold mb-1">Handcrafted Crafts</h5>
<small>Artisan made</small>
</div>
</div>
</a>
</div>

<div class="col-md-3">
<a href="eco-friendly goods.php" class="text-decoration-none">
<div class="category-modern shadow">
<img src="images/Eco-Friendly goods.avif">
<div class="category-text">
<h5 class="fw-bold mb-1">Eco-Friendly Goods</h5>
<small>Sustainable living</small>
</div>
</div>
</a>
</div>

<div class="col-md-3">
<a href="bestsellers.php" class="text-decoration-none">
<div class="category-modern shadow">
<img src="images/bestsellers.avif">
<div class="category-text">
<h5 class="fw-bold mb-1">Bestsellers</h5>
<small>Community favorites</small>
</div>
</div>
</a>
</div>

</div>
</div>

<!-- ===== FEATURED PRODUCTS ===== -->
<div class="container mb-5">
    <h2 class="text-center fw-bold mb-5">
        Featured Products
        <div style="width:60px;height:4px;background:#E85A1F;margin:10px auto;border-radius:5px;"></div>
    </h2>

    <div class="row g-4">
        <?php foreach ($featuredProducts as $fp): 
            $p = $fp['product'];
            $img = $p['image_url'];
            // DITO NATIN BINABAGO: Ginagawa nating JSON para sa bagong modal
            $product_json = htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        ?>
        <div class="col-md-3 col-6">
            <div class="card product-card shadow-sm h-100" 
                 style="cursor: pointer;"
                 data-bs-toggle="modal" 
                 data-bs-target="#productModal"
                 data-product='<?= $product_json ?>'
                 data-image="<?= htmlspecialchars($img) ?>">

                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">

                <div class="card-body text-start p-3">
                    <div class="product-location"><?= htmlspecialchars($p['province_name']) ?></div>
                    <div class="product-title"><?= htmlspecialchars($p['product_name']) ?></div>
                    <div class="price fw-bold mt-2" style="color: #E85A1F;">₱<?= number_format($p['price'], 2) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div> <!-- close row -->
</div> <!-- close container mb-5 -->

<!-- ===== SECOND SECTION (ADDED BELOW FEATURED PRODUCTS) ===== -->
<section class="support-section">
<div class="container">
<div class="row align-items-center g-5">

<div class="col-md-6">
<div class="artisan-grid">

<div class="artisan-img large">
<img src="images/weaver.avif">
</div>

<div class="artisan-img small">
<img src="images/potter.avif">
</div>

<div class="stat-box shadow-sm">
<h2>500+</h2>
<p class="mb-0 fw-semibold">Active Local Artisans</p>
</div>

</div>
</div>
<div class="col-md-6">
<h2 class="fw-bold support-title">
Supporting Local Artisans<br>
<span>Across the Archipelago</span>
</h2>

<p class="mt-4 text-muted">
LocalFlair is more than just a marketplace; it’s a bridge connecting traditional Filipino craftsmanship to the modern world.
</p>

<p class="text-muted">
Every purchase directly supports families and keeps the spirit of Filipino artistry alive.
</p>

<a href="#" class="btn hero-btn mt-3">Read Our Story</a>
</div>

</div>
</div>
</section>

<?php include 'includes/footer.php'; ?>


</body>
</html>
