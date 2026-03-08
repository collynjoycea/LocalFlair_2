<?php
// Kunin ang product_id mula sa URL
$p_id = mysqli_real_escape_string($conn, $_GET['id']);

// 1. QUERY PARA SA RATING SUMMARY (Average Stars)
$summary_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM reviews 
                WHERE product_id = '$p_id' AND status = 'Published'";
$summary_res = mysqli_query($conn, $summary_sql);
$summary_data = mysqli_fetch_assoc($summary_res);
$avg = round($summary_data['avg_rating'], 1);
$total = $summary_data['total_reviews'];

// 2. QUERY PARA SA MGA REVIEWS
$review_sql = "SELECT r.*, u.first_name, u.last_name 
               FROM reviews r 
               JOIN users u ON r.user_id = u.user_id 
               WHERE r.product_id = '$p_id' AND r.status = 'Published' 
               ORDER BY r.created_at DESC";
$review_res = mysqli_query($conn, $review_sql);
?>

<style>
    .review-card {
        border-radius: 20px;
        background: #ffffff;
        border: 1px solid #f1f5f9;
        transition: transform 0.2s;
    }
    .rating-box {
        background: #fff7ed;
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        border: 1px solid #ffedd5;
    }
    .avg-number { font-size: 3rem; font-weight: 800; color: #e95a24; }
    .stars-display { color: #fbbf24; font-size: 1.2rem; }
    .user-avatar {
        width: 45px; height: 45px;
        background: #e2e8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #64748b;
    }
</style>

<div class="container mt-5 mb-5">
    <hr class="mb-5 opacity-10">
    
    <div class="row align-items-center mb-5">
        <div class="col-md-4">
            <h3 class="fw-800 mb-0">Ratings & Reviews</h3>
            <p class="text-muted small">Verified feedback from our community.</p>
        </div>
        
        <?php if($total > 0): ?>
        <div class="col-md-8">
            <div class="rating-box d-flex align-items-center justify-content-center gap-4">
                <div>
                    <div class="avg-number"><?= $avg ?></div>
                    <div class="stars-display">
                        <?php 
                        for($i=1; $i<=5; $i++) {
                            echo ($i <= floor($avg)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <div class="text-muted small mt-1"><?= $total ?> Reviews</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if(mysqli_num_rows($review_res) > 0): ?>
        <div class="row">
            <?php while($rev = mysqli_fetch_assoc($review_res)): ?>
                <div class="col-12 mb-4">
                    <div class="card review-card shadow-sm p-4">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?= strtoupper(substr($rev['first_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= $rev['first_name'] . " " . $rev['last_name'] ?></h6>
                                    <div class="stars-display small" style="font-size: 0.8rem;">
                                        <?php 
                                        for($i=1; $i<=5; $i++) {
                                            echo ($i <= $rev['rating']) ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <span class="text-muted small"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                        </div>
                        <div class="p-3 bg-light rounded-3">
                            <p class="mb-0 text-dark italic" style="font-style: italic;">
                                <i class="fa fa-quote-left text-muted me-2 small"></i>
                                <?= htmlspecialchars($rev['comment']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <img src="https://cdn-icons-png.flaticon.com/512/5038/5038550.png" width="80" style="opacity: 0.2;">
            <p class="text-muted mt-3">No reviews yet. Be the first to share your thoughts!</p>
        </div>
    <?php endif; ?>
</div>