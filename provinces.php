<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Categories</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* KEEP YOUR ORIGINAL SPACING */
        .main-content {
            margin-top: 80px;
            margin-bottom: 100px;
        }

        /* SECTION HEADER */
        .section-header {
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-weight: 700;
            position: relative;
            padding-left: 15px;
        }

        .section-header h2::before {
            content: "";
            position: absolute;
            left: 0;
            top: 5px;
            height: 28px;
            width: 4px;
            background: #e25b2d;
            border-radius: 3px;
        }

        .section-header p {
            color: #777;
            margin-left: 15px;
            margin-top: 5px;
        }

        /* CARD DESIGN (MATCHING IMAGE STYLE) */
        .category-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: #f3f1ee;
            transition: all .3s ease;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .category-card img {
            height: 260px;
            object-fit: cover;
        }

        .card-body {
            padding: 25px;
        }

        .region-badge {
            position: absolute;
            top: 18px;
            left: 18px;
            background: #fff;
            color: #e25b2d;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .5px;
        }

        .btn-explore {
            border: 1px solid #e25b2d;
            color: #e25b2d;
            border-radius: 12px;
            padding: 8px 22px;
            font-weight: 600;
            background: transparent;
            transition: .3s;
        }

        .btn-explore:hover {
            background: #e25b2d;
            color: #fff;
        }

        /* SECOND SECTION */
        .empower-section {
            background: #f3ede6;
            padding: 90px 0;
        }

        .empower-section h2 {
            font-weight: 700;
            font-size: 36px;
        }

        .empower-section p {
            color: #666;
            line-height: 1.8;
            margin: 20px 0;
        }

        .empower-img {
            border-radius: 20px;
            width: 100%;
            object-fit: cover;
        }

        .empower-list li {
            margin-bottom: 12px;
            font-weight: 500;
        }

        /* ===== ANIMATED BACKGROUND ===== */
body {
    background: linear-gradient(-45deg, #fdf6f0, #f3ede6, #e8f6f3, #fdf3e7);
    background-size: 400% 400%;
    animation: gradientMove 15s ease infinite;
}

@keyframes gradientMove {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Floating Light Blobs */
body::before,
body::after {
    content: "";
    position: fixed;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(226,91,45,0.15) 0%, transparent 70%);
    border-radius: 50%;
    z-index: -1;
    animation: floatBlob 12s ease-in-out infinite alternate;
}

body::before {
    top: -150px;
    left: -150px;
}

body::after {
    bottom: -150px;
    right: -150px;
    animation-delay: 5s;
}

@keyframes floatBlob {
    from { transform: translateY(0px) translateX(0px); }
    to { transform: translateY(60px) translateX(40px); }
}
.main-content {
    margin-top: 80px;
    margin-bottom: 100px;
    background: rgba(255,255,255,0.7);
    backdrop-filter: blur(20px);
    padding: 40px;
    border-radius: 25px;
}
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- FIRST SECTION -->
<div class="container main-content">

    <div class="section-header">
        <h2>Shop by Provinces</h2>
        <p>Explore regional specialities from across the archipelago</p>
    </div>

    <div class="row g-4">

        <!-- Cebu -->
        <div class="col-md-3">
            <div class="card category-card position-relative">
                <span class="region-badge">VISAYAS</span>
                <img src="images/cebu.jpg" class="card-img-top" alt="Cebu">
                <div class="card-body">
                    <h5 class="fw-bold">Cebu</h5>
                    <p class="text-muted small">Guitars, Dried Mangoes & Crafts</p>
                    <a href="category_items.php?province_id=1" class="btn btn-explore mt-2">Explore →</a>
                </div>
            </div>
        </div>

        <!-- Guimaras -->
        <div class="col-md-3">
            <div class="card category-card position-relative">
                <span class="region-badge">VISAYAS</span>
                <img src="images/guimaras.jpg" class="card-img-top" alt="Guimaras">
                <div class="card-body">
                    <h5 class="fw-bold">Guimaras</h5>
                    <p class="text-muted small">World's Sweetest Mangoes</p>
                    <a href="category_items.php?province_id=2" class="btn btn-explore mt-2">Explore →</a>
                </div>
            </div>
        </div>

        <!-- Laguna -->
        <div class="col-md-3">
            <div class="card category-card position-relative">
                <span class="region-badge">LUZON</span>
                <img src="images/laguna.jpg" class="card-img-top" alt="Laguna">
                <div class="card-body">
                    <h5 class="fw-bold">Laguna</h5>
                    <p class="text-muted small">Woodcarving & Embroideries</p>
                    <a href="category_items.php?province_id=3" class="btn btn-explore mt-2">Explore →</a>
                </div>
            </div>
        </div>

        <!-- Davao -->
        <div class="col-md-3">
            <div class="card category-card position-relative">
                <span class="region-badge">MINDANAO</span>
                <img src="images/davao.jpeg" class="card-img-top" alt="Davao">
                <div class="card-body">
                    <h5 class="fw-bold">Davao</h5>
                    <p class="text-muted small">Fine Cacao & Tropical Fruits</p>
                    <a href="category_items.php?province_id=4" class="btn btn-explore mt-2">Explore →</a>
                </div>
            </div>
        </div>

        <!-- Benguet -->
        <div class="col-md-3">
            <div class="card category-card position-relative">
                <span class="region-badge">LUZON</span>
                <img src="images/benguet.jpg" class="card-img-top" alt="Benguet">
                <div class="card-body">
                    <h5 class="fw-bold">Benguet</h5>
                    <p class="text-muted small">Highland Coffee & Weaving</p>
                    <a href="category_items.php?province_id=5" class="btn btn-explore mt-2">Explore →</a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- SECOND SECTION -->
<div class="empower-section">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-md-6">
                <div class="row g-4">
                    <div class="col-6">
                        <img src="images/weaving.avif" class="empower-img" alt="Weaving">
                    </div>
                    <div class="col-6">
                        <img src="images/pottery.avif" class="empower-img" alt="Pottery">
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <small class="text-uppercase text-danger fw-bold">EMPOWERING COMMUNITIES</small>
                <h2 class="mt-3">Supporting Local Artisans Across the Archipelago</h2>
                <p>
                    Every purchase on LocalFlair goes directly back to weaving centers,
                    mountain farms, and coastal workshops of the Philippines.
                    We ensure fair trade and celebrate the stories behind every piece.
                </p>

                <ul class="list-unstyled empower-list">
                    <li>Authentic Heritage Crafts</li>
                    <li>Ethically Sourced Materials</li>
                    <li>Direct Support to Rural Communities</li>
                </ul>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>