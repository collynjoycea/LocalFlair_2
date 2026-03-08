<?php
include('../db.php');

if (isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $query = "DELETE FROM reviews WHERE review_id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>