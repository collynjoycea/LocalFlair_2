<?php
include('../db.php');

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE reviews SET status = '$status' WHERE review_id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>