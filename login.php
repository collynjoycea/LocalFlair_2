<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    if ($email === "" || $password === "") {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($password) < 5) {

        $error = "Password must be at least 5 characters.";

    } else {

        $sql = "SELECT user_id, first_name, last_name, password, profile_picture
            FROM users 
            WHERE email = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            // Verify hashed password
            if (password_verify($password, $user['password'])) {

                // Login success → store user session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['profile_picture'] = !empty($user['profile_picture']) ? $user['profile_picture'] : "default-avatar.png";

                header("Location: index.php");
                exit();

            } else {
                $error = "Invalid email or password.";
            }

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Login</title>

    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>

        body{
        margin:0;
        font-family:Segoe UI;
        background:url('images/login-bg.png') center/cover no-repeat;
        height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        }

        .login-box{
        background:#f3eadf;
        padding:40px;
        width:320px;
        text-align:center;
        box-shadow:0 0 20px rgba(0,0,0,.2);
        border-radius:8px;
        }

        .login-box input{
            width:100%;
            padding:10px;
            margin:10px 0;
            border:none;
            background:#dec29b;
            outline:none;
            border-radius:4px;
            box-sizing: border-box; 
        }

        .login-box button{
            width: 100%; /* Ginawa nating full width para mas balance tignan */
            padding:10px 25px;
            background:#b7855b;
            border:none;
            color:white;
            cursor:pointer;
            margin-top:10px;
            border-radius:4px;
            font-weight: 600;
        }

        .login-box button:hover{
            opacity:0.9;
            transition: 0.5s;
        }

        .error{
        color:red;
        font-size:14px;
        margin-top:10px;
        }

        .login-header{
        display:flex;
        align-items:center;
        justify-content:center;
        margin-bottom:25px;
        gap:12px;
        }

        .login-header img{
        width:55px;          
        height:auto;
        }

        .brand-text{
        text-align:left;
        }

        .brand-text h2{
        margin:0;
        font-size:24px;
        color:#4a2c1d;
        font-weight:600;
        }

        .brand-text p{
        margin:0;
        font-size:13px;
        color:#7a5a3a;
        letter-spacing:0.5px;
        }

        .password-container {
            position: relative;
            width: 100%;
        }

        .login-box .password-container input {
            padding-right: 40px; 
            margin: 10px 0;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #4a2c1d;
            font-size: 14px;
            z-index: 2;
        }

        /* --- STYLING PARA SA FORGOT PASSWORD --- */
        .forgot-link {
            display: block;
            text-align: right;
            font-size: 12px;
            color: #7a5a3a;
            text-decoration: none;
            margin-bottom: 15px;
            margin-top: -5px;
        }

        .forgot-link:hover {
            color: #4a2c1d;
            text-decoration: underline;
        }

        /* --- STYLING PARA SA BACK BUTTON --- */
        .back-home {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #7a5a3a;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-home:hover {
            color: #4a2c1d;
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="login-box">

<div class="login-header">
    <img src="images/basket-icon.png" alt="Basket Icon">
    <div class="brand-text">
        <h2>LocalFlair</h2>
        <p>Authentic Pinoy Finds</p>
    </div>
</div>

<form method="POST" onsubmit="return validateForm()">

<input type="email" id="email" name="email" placeholder="Email">

<div class="password-container">
    <input type="password" id="password" name="password" placeholder="Password">
    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
</div>

<a href="forgot_password.php" class="forgot-link">Forgot Password?</a>

<button type="submit">Login</button>

<?php if($error): ?>
<p class="error"><?php echo $error; ?></p>
<?php endif; ?>

</form>
<a href="index.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
</div>

<script>

// Frontend validation
function validateForm(){

let email = document.getElementById("email").value.trim();
let pass = document.getElementById("password").value.trim();

if(email === "" || pass === ""){
    alert("Please fill in all fields.");
    return false;
}

if(!email.includes("@")){
    alert("Please enter a valid email address.");
    return false;
}

if(pass.length < 5){
    alert("Password must be at least 5 characters.");
    return false;
}

return true;
}

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