<?php
session_start();

// Redirect already logged-in users
if (isset($_SESSION['admin_id'])) {
    switch ($_SESSION['role']) {
        case 'Inventory_Manager':
            header("Location: inventory_management/inventory_management.php");
            exit();
        case 'Supplier_Manager':
            header("Location: supplier_management/supplier_management.php");
            exit();
        case 'Order_Manager':
            header("Location: order_management/order_management.php");
            exit();
        case 'Admin':
            header("Location: index.php");
            exit();
        default:
            header("Location: login.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LocalFlair | Admin Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
/* =================== UI STYLES =================== */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Segoe UI; }

body {
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), 
                url('images/login-bg.png') no-repeat center center/cover;
}

.login-card {
    background: #f4ece2; 
    padding: 50px 40px;
    border-radius: 15px;
    width: 440px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    text-align: center;
}

.brand-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 40px;
}

.brand-icon { width: 45px; height: auto; }

.brand-text-wrapper { display: flex; flex-direction: column; text-align: left; }

.brand-name { font-size: 2rem; font-weight: 600; color: #4a2c1d; line-height: 1; }

.brand-tagline { font-size: 0.95rem; color: #7a5a3a; font-weight: 400; }

.input-group { margin-bottom: 15px; position: relative; }

.input-group input {
    width: 100%;
    padding: 15px 20px;
    border-radius: 10px;
    border: none;
    background-color: #dec29b;
    color: #4a2c1d;
    font-size: 1.05rem;
    outline: none;
}

.input-group input::placeholder { color: #7a5a3a; opacity: 0.7; }

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #4a2c1d;
    font-size: 14px;
    z-index: 2;
}

.btn-signin {
    width: 45%;
    padding: 12px;
    background: #b7855b;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 20px;
}

.btn-signin:hover { opacity: 0.9; transform: scale(1.02); }

.error-msg { color:red; margin-bottom:15px; font-size:0.9rem; }

@media (max-width: 850px) {
    .login-card { width: 90%; }
}
</style>
</head>
<body>

<div class="login-card">
    <div class="brand-container">
        <img src="images/basket-icon.png" alt="LocalFlair Icon" class="brand-icon">
        <div class="brand-text-wrapper">
            <span class="brand-name">LocalFlair</span>
            <span class="brand-tagline">Authentic Pinoy Finds</span>
        </div>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <p class="error-msg"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="login_process.php" method="POST">
        <div class="input-group">
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>
        <div class="input-group">
            <input type="password" id="password" name="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
        </div>
        <button type="submit" class="btn-signin">Login</button>
    </form>

    <p style="margin-top:15px; font-size:0.9rem; color:#4a2c1d;">
        Staff members, use your company email and password to log in.
    </p>
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