<?php
session_start();
include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/product_helpers.php';

$products = lf_fetch_bestsellers($conn, 24);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bestsellers | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body{ padding-top: 100px; background:#f8f5f2; font-family: 'Segoe UI', sans-serif; }
        .product-card{ border:none; border-radius:16px; overflow:hidden; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.06); transition:.2s; height:100%; }
        .product-card:hover{ transform: translateY(-4px); box-shadow:0 14px 34px rgba(0,0,0,0.12); }
        .product-card img{ height:240px; width:100%; object-fit:cover; }
        .product-card .card-body{ padding:16px; display:flex; flex-direction:column; gap:6px; }
        .product-card h6{ margin:0; font-weight:800; font-size:18px; color:#1f2937; }
        .meta{ font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.6px; }
        .price{ font-weight:800; color:#e25b2d; }
        .btn-row{ display:flex; gap:10px; margin-top:10px; }
        .btn-buy,.btn-cart{ flex:1; border:1px solid #d4b996; background:#ead9bd; color:#2d1b10; border-radius:10px; font-weight:700; padding:9px 0; }
        .btn-buy:disabled,.btn-cart:disabled{ opacity:.55; cursor:not-allowed; }
        .back-btn{ display:inline-block; padding:8px 16px; border-radius:12px; border:1px solid #111827; text-decoration:none; color:#111827; }
        .back-btn:hover{ background:#111827; color:#fff; }
        .sold{ font-size:12px; color:#0f766e; font-weight:700; }
    </style>
</head>
<body>

<div class="container my-5">
    <a href="index.php#categories" class="back-btn mb-4">← Back to categories</a>
    <div class="d-flex align-items-end justify-content-between mb-3">
        <div>
            <h2 class="fw-bold mb-1">Bestsellers</h2>
            <div class="text-muted">Top selling items (based on completed orders)</div>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-light border text-center">No products available yet.</div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
                <?php
                    $img = lf_product_image_url($p['image'] ?? '');
                    $stock = (int)($p['stock'] ?? 0);
                    $sold = (int)($p['sold_qty'] ?? 0);
                ?>
                <div class="col-6 col-md-3">
                    <div class="card product-card">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>">
                        <div class="card-body">
                            <h6><?= htmlspecialchars($p['product_name']) ?></h6>
                            <div class="meta"><?= htmlspecialchars($p['province_name'] ?? 'Philippines') ?> • <?= htmlspecialchars($p['category_name'] ?? '') ?></div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="price">₱<?= number_format((float)$p['price'], 2) ?></div>
                                <?php if ($stock <= 0): ?>
                                    <span class="badge bg-danger">Out of stock</span>
                                <?php else: ?>
                                    <span class="sold"><?= $sold ?> sold</span>
                                <?php endif; ?>
                            </div>
                            <div class="btn-row">
                                <button
                                    class="btn-buy"
                                    type="button"
                                    <?= $stock <= 0 ? 'disabled' : '' ?>
                                    data-bs-toggle="modal"
                                    data-bs-target="#productModal"
                                    data-product='<?= htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                    data-image="<?= htmlspecialchars($img) ?>"
                                >Buy</button>
                                <button
                                    class="btn-cart"
                                    type="button"
                                    <?= $stock <= 0 ? 'disabled' : '' ?>
                                    data-bs-toggle="modal"
                                    data-bs-target="#productModal"
                                    data-product='<?= htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                    data-image="<?= htmlspecialchars($img) ?>"
                                >Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/_product_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

