<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $order_id = $_POST['order_id'];
    
    // Arrays ito dahil sa product_id[] sa form
    $product_ids = $_POST['product_id']; 
    $ratings = $_POST['rating'];
    $comments = $_POST['comment'];

    foreach ($product_ids as $p_id) {
    $rating = mysqli_real_escape_string($conn, $ratings[$p_id]);
    $comment = mysqli_real_escape_string($conn, $comments[$p_id]);

    // Tinanggal ang order_id sa column list at sa values
    $sql = "INSERT INTO reviews (user_id, product_id, rating, comment, status, created_at) 
            VALUES ('$user_id', '$p_id', '$rating', '$comment', 'Pending', NOW())";
    
    mysqli_query($conn, $sql);
}

    echo "<script>
            alert('Salamat! Your reviews are submitted and pending for approval.');
            window.location.href = 'order-history.php';
          </script>";
}
?>