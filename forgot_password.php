<?php
session_start();
require_once "db.php";

// I-load ang PHPMailer classes
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? "");

    if ($email === "") {
        $error = "Please enter your email address.";
    } else {
        $sql = "SELECT user_id, first_name FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            $token = bin2hex(random_bytes(50)); // Gumawa ng unique token
            $user_id = $user['user_id'];

            // I-save ang token sa database (dapat may 'reset_token' column ka sa 'users' table)
            $update_sql = "UPDATE users SET reset_token = ? WHERE user_id = ?";
            $upd_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($upd_stmt, "si", $token, $user_id);
            mysqli_stmt_execute($upd_stmt);

            // PHPMailer Configuration
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'conda.abbybsis2023@gmail.com'; // Iyong Gmail
                $mail->Password   = 'eltjkrimjydgyhbm';    // Iyong Google App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('no-reply@localflair.com', 'LocalFlair');
                $mail->addAddress($email);

                $reset_link = "http://localhost/LocalFlair_2/reset_password.php?token=" . $token;

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request - LocalFlair';
                $mail->Body    = "
    <div style='font-family: Arial, sans-serif; background-color: #f3eadf; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #4a2c1d;'>LocalFlair</h2>
        <p style='color: #7a5a3a;'>Hi <strong>" . $user['first_name'] . "</strong>,</p>
        <p style='color: #7a5a3a;'>Nakakatanggap kami ng request na i-reset ang iyong password. I-click ang button sa ibaba para magtuloy:</p>
        <br>
        <a href='$reset_link' style='background-color: #b7855b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Reset My Password</a>
        <br><br>
        <p style='font-size: 12px; color: #999;'>Kung hindi gumagana ang button, i-copy at i-paste ang link na ito sa iyong browser:</p>
        <p style='font-size: 12px; color: #b7855b;'>$reset_link</p>
        <hr style='border: 0; border-top: 1px solid #dec29b;'>
        <p style='font-size: 11px; color: #7a5a3a;'>Kung hindi ikaw ito, balewalain lang ang email na ito. Mananatiling safe ang iyong account.</p>
    </div>
";

                $mail->send();
                $message = "A reset link has been sent to your email address.";
            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Email address not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/login-bg.png') center/cover no-repeat;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .forgot-box {
            background: #f3eadf;
            padding: 40px;
            width: 350px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0,0,0,.2);
            border-radius: 8px;
        }

        .forgot-box h2 {
            margin: 0 0 10px 0;
            color: #4a2c1d;
            font-size: 24px;
        }

        .forgot-box p {
            font-size: 14px;
            color: #7a5a3a;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .input-group label {
            font-size: 14px;
            font-weight: 600;
            color: #4a2c1d;
            display: block;
            margin-bottom: 5px;
        }

        .forgot-box input {
            width: 100%;
            padding: 12px;
            border: none;
            background: #dec29b;
            outline: none;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .forgot-box button {
            width: 100%;
            padding: 12px;
            background: #b7855b;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            transition: 0.3s;
        }

        .forgot-box button:hover {
            opacity: 0.9;
        }

        .error { color: #d9534f; font-size: 14px; margin-top: 15px; }
        .success { color: #5cb85c; font-size: 14px; margin-top: 15px; }

        .back-to-login {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #7a5a3a;
            font-size: 14px;
            transition: 0.3s;
        }

        .back-to-login:hover {
            color: #4a2c1d;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="forgot-box">
    <h2>Forgot Password?</h2>
    <p>Enter your email address and we'll send you a link to reset your password.</p>

    <form method="POST">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="e.g. artisan@localflair.com" required>
        </div>

        <button type="submit">Send Reset Link <i class="fa-solid fa-arrow-right" style="font-size: 13px; margin-left: 5px;"></i></button>

        <?php if($error): ?>
            <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?></p>
        <?php endif; ?>

        <?php if($message): ?>
            <p class="success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></p>
        <?php endif; ?>
    </form>

    <a href="login.php" class="back-to-login"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
</div>

</body>
</html>