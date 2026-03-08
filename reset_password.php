<?php
session_start();
require_once "db.php";

$message = "";
$error = "";

$token = $_GET['token'] ?? "";

if (empty($token)) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_pass = trim($_POST['password'] ?? "");
    $confirm_pass = trim($_POST['confirm_password'] ?? "");

    if (strlen($new_pass) < 5) {
        $error = "Password must be at least 5 characters.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "Passwords do not match.";
    } else {
        $sql = "SELECT user_id FROM users WHERE reset_token = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            $user_id = $user['user_id'];
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

            $update_sql = "UPDATE users SET password = ?, reset_token = NULL WHERE user_id = ?";
            $upd_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($upd_stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($upd_stmt)) {
                $message = "Your password has been reset successfully!";
            } else {
                $error = "Something went wrong. Please try again later.";
            }
        } else {
            $error = "This password reset link is invalid or has already been used.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/login-bg.png') center/cover no-repeat;
            height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .reset-box {
            background: #f3eadf; padding: 40px; width: 320px;
            text-align: center; box-shadow: 0 0 20px rgba(0,0,0,.2); border-radius: 8px;
        }
        .reset-box h2 { margin: 0 0 20px 0; color: #4a2c1d; font-size: 24px; }
        
        /* CSS para sa Password Container */
        .password-container {
            position: relative;
            width: 100%;
            margin: 10px 0;
        }
        .reset-box input {
            width: 100%; padding: 12px;
            border: none; background: #dec29b; outline: none; border-radius: 4px; box-sizing: border-box;
        }
        .toggle-password {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #4a2c1d; font-size: 14px; z-index: 2;
        }
        
        .reset-box button {
            width: 100%; padding: 12px; background: #b7855b;
            border: none; color: white; cursor: pointer; border-radius: 4px;
            font-size: 16px; font-weight: 600; margin-top: 10px; transition: 0.3s;
        }
        .reset-box button:hover { opacity: 0.9; }
        .error { color: #d9534f; font-size: 14px; margin-top: 15px; }
        .success { color: #5cb85c; font-size: 14px; margin-top: 15px; }
        .back-link { display: block; margin-top: 20px; text-decoration: none; color: #7a5a3a; font-size: 14px; }
    </style>
</head>
<body>

<div class="reset-box">
    <h2>New Password</h2>

    <?php if ($message): ?>
        <p class="success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></p>
        <a href="login.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Proceed to Login</a>
    <?php else: ?>
        <form method="POST">
            <div class="password-container">
                <input type="password" id="new_password" name="password" placeholder="Enter New Password" required>
                <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('new_password', this)"></i>
            </div>

            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('confirm_password', this)"></i>
            </div>
            
            <button type="submit">Update Password</button>

            <?php if ($error): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></p>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleVisibility(inputId, icon) {
    const inputField = document.getElementById(inputId);
    if (inputField.type === "password") {
        inputField.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        inputField.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

</body>
</html>