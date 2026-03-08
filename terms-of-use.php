<?php
session_start();
error_reporting(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Terms of Use</title>

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
    </style>
</head>

<body>

<?php include('includes/header.php'); ?>

<!-- PAGE HEADER -->
<div class="terms-header">
    <h1>Terms of Use</h1>
    <p>Please read these terms carefully before using our curated local marketplace.</p>
</div>

<!-- TERMS CONTENT CARD -->
<div class="terms-card">

    <h2>1. Acceptance of Terms</h2>
    <p>
        By accessing and using LocalFlair, you agree to be bound by these Terms of Use
        and all applicable laws and regulations. If you do not agree with any of these terms,
        you are prohibited from using or accessing this site.
    </p>
    <p>
        The materials contained in this website are protected by applicable copyright
        and trademark law. We reserve the right to update or modify these terms at any time
        without prior notice.
    </p>

    <div class="section-divider"></div>

    <h2>2. User Responsibilities</h2>
    <p>
        As a user of LocalFlair, you are responsible for maintaining the confidentiality
        of your account and password and for all activities under your account.
    </p>
    <ul>
        <li>Provide accurate and current information during registration.</li>
        <li>Do not use the platform for illegal or unauthorized purposes.</li>
        <li>Do not attempt to disrupt the integrity or performance of the service.</li>
    </ul>

    <div class="section-divider"></div>

    <h2>3. Orders and Payments</h2>
    <p>
        All orders placed through LocalFlair are subject to acceptance and availability.
        Prices are listed in the local currency and are subject to change without notice.
    </p>
    <p>
        Payments are processed through secure third-party gateways.
        We do not store full credit card information on our servers.
    </p>

    <div class="section-divider"></div>

    <h2>4. Intellectual Property</h2>
    <p>
        The service and its original content, features, and functionality remain the
        exclusive property of LocalFlair and its licensors.
        Content provided by sellers remains their property but grants us a non-exclusive
        license for promotional use.
    </p>

   

    <!-- FOOTER INFO -->
    <div class="terms-footer">
        <p>Last updated: February 2026</p>
        <p>
            If you have any questions regarding these terms,
            please contact our legal team at
            <a href="mailto:legal@localflair.com">legal@localflair.com</a>
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>