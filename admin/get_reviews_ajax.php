<?php
session_start();
include('../db.php'); 

// 1. KUNIN ANG STATS
// Total Reviews
$total_res = $conn->query("SELECT COUNT(*) as total FROM reviews");
$total = $total_res->fetch_assoc()['total'] ?? 0;

// Average Rating
$avg_res = $conn->query("SELECT AVG(rating) as average FROM reviews");
$average = round($avg_res->fetch_assoc()['average'] ?? 0, 1);

// Sentiment (Positive %) - Halimbawa: 4 stars pataas
$pos_res = $conn->query("SELECT COUNT(*) as pos FROM reviews WHERE rating >= 4");
$pos_count = $pos_res->fetch_assoc()['pos'] ?? 0;
$pos_percent = ($total > 0) ? round(($pos_count / $total) * 100) : 0;

// Stars Summary (e.g., "120 reviews with 5 stars")
$stars_summary = "Based on community feedback";

// 2. KUNIN ANG REVIEW LIST (Join sa Products at Users)
$sql = "SELECT r.*, p.product_name, p.image as p_img, u.first_name, u.last_name 
        FROM reviews r
        JOIN products p ON r.product_id = p.product_id
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    // Generate Stars HTML
    $stars_html = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars_html .= ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
    }
    
    $row['stars_html'] = $stars_html;
    $data[] = $row;
}

// 3. I-SEND BILANG JSON
echo json_encode([
    'total' => $total,
    'average' => $average,
    'pos_percent' => $pos_percent,
    'stars_summary' => $stars_summary,
    'data' => $data
]);