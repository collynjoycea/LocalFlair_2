<?php
session_start();
require_once "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email     = trim($_POST['email'] ?? "");
    $fname     = trim($_POST['fname'] ?? "");
    $lname     = trim($_POST['lname'] ?? "");
    $contact   = trim($_POST['contact'] ?? "");
    $password  = trim($_POST['password'] ?? "");
    $confirm   = trim($_POST['confirm'] ?? "");

    // Patterns
    $namePattern = '/^[A-Za-z\s]{2,50}$/';
    $contactPattern = '/^09[0-9]{9}$/';

    // Password rules
    $hasUpper  = preg_match('/[A-Z]/', $password);
    $hasLower  = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);

    if ($email === "" || $fname === "" || $lname === "" || $contact === "" || $password === "" || $confirm === "") {

        $error = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (!preg_match($namePattern, $fname) || !preg_match($namePattern, $lname)) {

        $error = "First and last name must contain letters only.";

    } elseif (!preg_match($contactPattern, $contact)) {

        $error = "Contact number must start with 09 and be exactly 11 digits.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters long.";

    } elseif (!$hasUpper || !$hasLower || !$hasNumber) {

        $error = "Password must include uppercase, lowercase, and a number.";

    } elseif ($password !== $confirm) {

        $error = "Passwords do not match.";

    } else {

    // Check if email already exists
    $checkEmail = "SELECT user_id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $checkEmail);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email is already registered.";
    } else {

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users 
                (first_name, last_name, email, contact_number, password) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $fname,
            $lname,
            $email,
            $contact,
            $hashedPassword
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Account created! Redirecting to login...";
            header("refresh:2;url=login.php");
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Register</title>

    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body{
        margin:0;
        font-family:Segoe UI;
        background:url('images/register-bg.png') center/cover no-repeat;
        height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        }

        .register-box{
        background:#f3eadf;
        padding:40px;
        width:320px;
        text-align:center;
        position:relative;
        border-radius:4px;
        box-shadow:12px 12px 0 #e4d2bd,0 0 25px rgba(0,0,0,.25);
        }

        .register-box input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            background: #dec29b;
            outline: none;
            border-radius: 4px;
            box-sizing: border-box; 
        }

        .register-box button{
            padding:10px 25px;
            background:#b7855b;
            border:none;
            color:white;
            cursor:pointer;
            margin-top:10px;
            border-radius:4px;
        }

        .register-box button:hover{
            opacity:0.9;
            transition: 0.5s;
        }

        .error{color:red;font-size:14px;}
        .success{color:green;font-size:14px;}
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

        .register-box .password-container input {
            margin: 10px 0; 
            padding-right: 40px; 
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

<div class="register-box">

<div class="login-header">
    <img src="images/basket-icon.png" alt="Basket Icon">
    <div class="brand-text">
        <h2>LocalFlair</h2>
        <p>Authentic Pinoy Finds</p>
    </div>
</div>


<br>
<h5>Create an account</h5>

<form method="POST" onsubmit="return validateRegister()">

<input type="text" name="fname" placeholder="First Name"
value="<?php echo htmlspecialchars($_POST['fname'] ?? ''); ?>">

<input type="text" name="lname" placeholder="Last Name"
value="<?php echo htmlspecialchars($_POST['lname'] ?? ''); ?>">

<input type="text" name="contact" placeholder="Contact Number" maxlength="11" inputmode="numeric" pattern="09[0-9]{9}"
    value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">

<input type="email" name="email" placeholder="Email"
value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

<div class="password-container">
    <input type="password" id="password" name="password" placeholder="Password">
    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
</div>

<div class="password-container">
    <input type="password" id="confirm" name="confirm" placeholder="Confirm Password">
    <i class="fa-solid fa-eye toggle-password" onclick="toggleVisibility('confirm', this)"></i>
</div>

<button type="submit">Register</button>

<?php if($error): ?>
<p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<?php if($success): ?>
<p class="success"><?php echo $success; ?></p>
<?php endif; ?>

</form>
<a href="index.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
</div>

<script>
function validateRegister(){

let fname = document.querySelector('[name="fname"]').value.trim();
let lname = document.querySelector('[name="lname"]').value.trim();
let contact = document.querySelector('[name="contact"]').value.trim();
let email = document.querySelector('[name="email"]').value.trim();
let p = password.value.trim();
let c = confirm.value.trim();

let nameRegex = /^[A-Za-z\s]{2,50}$/;
let contactRegex = /^09[0-9]{9}$/;
let upper = /[A-Z]/;
let lower = /[a-z]/;
let number = /[0-9]/;

if(!fname || !lname || !contact || !email || !p || !c){
    alert("All fields are required.");
    return false;
}

if(!nameRegex.test(fname) || !nameRegex.test(lname)){
    alert("Names must contain letters only.");
    return false;
}

if(!contactRegex.test(contact)){
    alert("Contact number must start with 09 and be exactly 11 digits.");
    return false;
}

if(!email.includes("@")){
    alert("Please enter a valid email address.");
    return false;
}

if(p.length < 8){
    alert("Password must be at least 8 characters.");
    return false;
}

if(!upper.test(p) || !lower.test(p) || !number.test(p)){
    alert("Password must contain uppercase, lowercase, and number.");
    return false;
}

if(p !== c){
    alert("Passwords do not match.");
    return false;
}

return true;
}

const contactInput = document.querySelector('[name="contact"]');

contactInput.addEventListener('input', () => {

    let value = contactInput.value.replace(/\D/g, '');

    if (value.length > 11) {
        value = value.slice(0, 11);
    }

    if (value.length > 0 && !value.startsWith('09')) {
        value = '09';
    }

    contactInput.value = value;
});

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
