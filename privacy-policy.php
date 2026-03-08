<?php
session_start();
error_reporting(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Privacy Policy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Import modern font para sa premium look */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', sans-serif;
            margin: 0;
            padding-top: 80px;
            color: #1f2937;
            /* Soft Animated Background */
            background: linear-gradient(-45deg, #f8fafc, #f1f5f9, #fff7ed, #f0f9ff);
            background-size: 400% 400%;
            animation: gradientFlow 10s ease infinite;
            min-height: 100vh;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* HEADER SECTION */
        .terms-header {
            max-width: 1000px;
            margin: 60px auto 20px;
            padding: 0 20px;
            text-align: center;
            animation: fadeInDown 0.8s ease-out;
        }

        .terms-header h1 {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(to right, #f26a3d, #ff8c69);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        /* THE UNIQUE GLASS CARD */
        .terms-card {
            max-width: 900px;
            margin: 20px auto 60px;
            background: rgba(255, 255, 255, 0.7); /* Transparent white */
            backdrop-filter: blur(15px); /* Frosted glass effect */
            -webkit-backdrop-filter: blur(15px);
            padding: 50px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            animation: cardFloat 5s ease-in-out infinite, fadeInUp 1s ease-out;
        }

        @keyframes cardFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .terms-card h2 {
            font-size: 22px;
            font-weight: 700;
            margin-top: 30px;
            color: #111827;
            display: flex;
            align-items: center;
        }

        .terms-card h2::before {
            content: "";
            width: 6px;
            height: 22px;
            background: #f26a3d;
            margin-right: 12px;
            border-radius: 10px;
        }

        .terms-card p, .terms-card li {
            color: #4b5563;
            line-height: 1.8;
            font-size: 16px;
        }

        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            margin: 40px 0;
        }

        /* FOOTER SECTION */
        .terms-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .terms-footer a {
            color: #f26a3d;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .terms-footer a:hover {
            color: #ff8c69;
            text-shadow: 0 0 10px rgba(242, 106, 61, 0.2);
        }

        /* BACK BUTTON STYLE */
.back-nav {
    max-width: 1000px;
    margin: 20px auto -40px; /* Nilapit natin sa header */
    padding: 0 20px;
    animation: fadeIn 1s ease;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: 0.3s;
    padding: 8px 16px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.btn-back:hover {
    color: #f26a3d; /* LocalFlair Orange */
    background: rgba(255, 255, 255, 0.8);
    transform: translateX(-5px); /* Konting galaw pabalik */
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
    </style>
</head>

<body>

<?php include('includes/header.php'); ?>

<div class="back-nav">
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>
</div>

<div class="terms-header">
    <h1>Privacy Policy</h1>
    <p>Your privacy is important to us. Learn how we protect your personal data.</p>
</div>

<div class="terms-card">

    <h2>1. Information Collection</h2>
    <p>
        We collect information to provide better services to all our users. This includes 
        information you provide to us, such as your name, email address, and payment 
        details when you create an account or make a purchase.
    </p>
    <p>
        We also collect information automatically when you use LocalFlair, including 
        your IP address, device type, and browsing behavior on our platform.
    </p>

    <div class="section-divider"></div>

    <h2>2. Use of Information</h2>
    <p>
        The data we collect is used to personalize your experience, process transactions, 
        and improve our marketplace functionality. Specifically, we use it to:
    </p>
    <ul>
        <li>Deliver the products and services you request.</li>
        <li>Send updates, security alerts, and support messages.</li>
        <li>Monitor and analyze trends and usage of our platform.</li>
    </ul>

    <div class="section-divider"></div>

    <h2>3. Data Protection</h2>
    <p>
        We implement a variety of security measures to maintain the safety of your 
        personal information. We use SSL (Secure Sockets Layer) encryption to protect 
        your data during transmission.
    </p>
    <p>
        While we strive to use commercially acceptable means to protect your personal 
        information, we cannot guarantee its absolute security.
    </p>

    <div class="section-divider"></div>

    <h2>4. Cookies and Tracking</h2>
    <p>
        LocalFlair uses cookies to help us remember and process the items in your 
        shopping cart, understand your preferences for future visits, and compile 
        aggregate data about site traffic.
    </p>

    <div class="terms-footer">
        <p>Last updated: March 2026</p>
        <p>
            If you have any concerns regarding your data, 
            please contact our privacy officer at 
            <a href="mailto:privacy@localflair.com">privacy@localflair.com</a>
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>