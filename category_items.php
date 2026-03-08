<?php
session_start();
include 'db.php'; 

if (!isset($_GET['province_id'])) {
    header("Location: provinces.php");
    exit();
}

$province_id = intval($_GET['province_id']);

/* 1. Get Province Name */
$provinceQuery = $conn->prepare("SELECT province_name FROM provinces WHERE province_id = ?");
$provinceQuery->bind_param("i", $province_id);
$provinceQuery->execute();
$provinceResult = $provinceQuery->get_result();
$province = $provinceResult->fetch_assoc();

if (!$province) {
    echo "Province not found.";
    exit();
}

/* 2. Get Products - Optimized Query to prevent duplicates and handle only_full_group_by */
$stmt = $conn->prepare("
    SELECT p.*, c.category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.province_id = ? 
    AND p.product_id IN (
        SELECT MAX(product_id) 
        FROM products 
        WHERE province_id = ? 
        AND status = 'Active'
        GROUP BY product_name
    )
");
$stmt->bind_param("ii", $province_id, $province_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($province['province_name']); ?> | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(-45deg, #fdf6f0, #f3ede6, #e8f6f3, #fdf3e7);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .main-container {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .product-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: .3s ease;
            background: #fff;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .product-card img {
            height: 250px;
            width: 100%;
            object-fit: cover;
            background-color: #f8f9fa;
        }

        .btn-view {
            border: 1px solid #e25b2d;
            color: #e25b2d;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: .3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-view:hover {
            background: #e25b2d;
            color: #fff !important;
        }

        .back-btn {
            border-radius: 12px;
            padding: 8px 22px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container mt-5 mb-5">

    <a href="provinces.php" class="btn btn-outline-dark back-btn mb-4">
        ← Back to Provinces
    </a>

    <div class="main-container">

        <h2 class="page-title">
            Products from <?php echo htmlspecialchars($province['province_name']); ?>
        </h2>

        <div class="row g-4">

            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    
                    <div class="col-md-3">
                        <div class="card product-card h-100">

                            <?php 
                                $image_filename = $row['image'];
                                
                                // Dinefine ang mga folders base sa VS Code structure mo
                                $admin_path = "admin/uploads/" . $image_filename;
                                $root_path = "uploads/" . $image_filename;
                                $images_path = "images/" . $image_filename;

                                if (!empty($image_filename) && file_exists($admin_path)) {
                                    $final_path = $admin_path;
                                } elseif (!empty($image_filename) && file_exists($root_path)) {
                                    $final_path = $root_path;
                                } elseif (!empty($image_filename) && file_exists($images_path)) {
                                    $final_path = $images_path;
                                } else {
                                    $final_path = "https://via.placeholder.com/300x250?text=No+Image+Found";
                                }
                            ?>

                            <img src="<?php echo $final_path; ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/300x250?text=Image+Not+Found'">

                            <div class="card-body d-flex flex-column">
                                <h5 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($row['product_name']); ?>
                                </h5>

                                <p class="text-muted small mb-3">
                                    <?php echo htmlspecialchars($row['category_name'] ?? 'Local Goods'); ?>
                                </p>

                                <div class="mt-auto">
                                    <p class="fw-bold text-danger fs-5 mb-3">
                                        ₱<?php echo number_format($row['price'], 2); ?>
                                    </p>

                                    <a href="product_details.php?id=<?php echo $row['product_id']; ?>" 
                                       class="btn btn-view w-100">
                                        View Product →
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-light text-center py-5 shadow-sm" style="border-radius: 20px;">
                        <h4 class="text-muted">No products found for this province yet.</h4>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>