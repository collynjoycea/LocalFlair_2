<?php
session_start();
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(empty($email) || empty($password)){
        header("Location: login.php?error=Please fill in all fields");
        exit();
    }

    // Query admins table (includes all staff + admin)
    $sql = "SELECT * FROM admins WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if($user = mysqli_fetch_assoc($result)){
        if(password_verify($password, trim($user['password']))){
            // Set session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['name']; 
            $_SESSION['role'] = $user['name'];       
            
            switch($user['name']){
                case 'Inventory_Manager':
                    header("Location: inventory_management/inventory_management.php");
                    break;
                case 'Supplier_Manager':
                    header("Location: supplier_management/supplier_management.php");
                    break;
                case 'Order_Manager':
                    header("Location: order_management/order_management.php");
                    break;
                case 'Admin':
                    header("Location: index.php");
                    break;
                default:
                    header("Location: login.php?error=Invalid role");
                    break;
            }
            exit();
        } else {
            header("Location: login.php?error=Incorrect password");
            exit();
        }
    } else {
        header("Location: login.php?error=No account found with this email");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>