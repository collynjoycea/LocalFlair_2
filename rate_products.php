<?php
session_start();
include('db.php'); // Gamitin ang existing db config mo

$order_id = $_GET['order_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$order_id || !$user_id) {
    header("Location: order-history.php");
    exit();
}

// Fetch items
$sql = "SELECT oi.product_id, p.product_name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = '$order_id'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Products | LocalFlair</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary-orange: #f05a28; }
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
        .product-card { background: white; border-radius: 20px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* Fixed Star Rating */
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 30px; color: #e2e8f0; cursor: pointer; transition: 0.2s; }
        .star-rating input:checked ~ label { color: #ffbc0b; }
        .star-rating label:hover, .star-rating label:hover ~ label { color: #ffbc0b; }
        
        .btn-submit { background: var(--primary-orange); color: white; border-radius: 12px; font-weight: 800; padding: 15px; border: none; transition: 0.3s; width: 100%; font-size: 1.1rem; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(240, 90, 40, 0.2); }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="d-flex align-items-center mb-4">
                <a href="order-history.php" class="text-dark me-3"><i class="fa-solid fa-arrow-left"></i></a>
                <div>
                    <h3 class="fw-bold mb-0">Rate & Review</h3>
                    <p class="text-muted mb-0">Order #LF-<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></p>
                </div>
            </div>

            <form action="submit_review.php" method="POST">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">

                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="card product-card p-4 animate__animated animate__fadeInUp">
                            <div class="d-flex align-items-center mb-4">
                                <img src="admin/uploads/<?= $row['image'] ?>" width="70" height="70" style="object-fit: cover; border-radius: 12px;" class="me-3" onerror="this.src='images/placeholder.jpg'">
                                <div>
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($row['product_name']) ?></h5>
                                    <input type="hidden" name="product_id[]" value="<?= $row['product_id'] ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Quality Rating</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5-<?= $row['product_id'] ?>" name="rating[<?= $row['product_id'] ?>]" value="5" required /><label for="star5-<?= $row['product_id'] ?>" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star4-<?= $row['product_id'] ?>" name="rating[<?= $row['product_id'] ?>]" value="4" /><label for="star4-<?= $row['product_id'] ?>" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star3-<?= $row['product_id'] ?>" name="rating[<?= $row['product_id'] ?>]" value="3" /><label for="star3-<?= $row['product_id'] ?>" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star2-<?= $row['product_id'] ?>" name="rating[<?= $row['product_id'] ?>]" value="2" /><label for="star2-<?= $row['product_id'] ?>" class="fa-solid fa-star"></label>
                                    <input type="radio" id="star1-<?= $row['product_id'] ?>" name="rating[<?= $row['product_id'] ?>]" value="1" /><label for="star1-<?= $row['product_id'] ?>" class="fa-solid fa-star"></label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Review Comment</label>
                                <textarea name="comment[<?= $row['product_id'] ?>]" class="form-control" rows="3" placeholder="How's the material? Does it fit well?" style="border-radius: 12px; border: 1px solid #edf2f7;" required></textarea>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <button type="submit" class="btn-submit mb-5">Submit All Reviews</button>

                <?php else: ?>
                    <div class="text-center py-5">
                        <p class="text-muted">No products found for this order.</p>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

</body>
</html>