<?php
session_start();
error_reporting(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LocalFlair | Frequently Asked Questions</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Import Modern Font */
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    margin: 0;
    padding-top: 80px;
    /* Soft Animated Gradient Background */
    background: linear-gradient(-45deg, #f8fafc, #f1f5f9, #fff7ed, #f0f9ff);
    background-size: 400% 400%;
    animation: gradientFlow 10s ease infinite;
    min-height: 100vh;
    color: #1e293b;
}

@keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* HERO SECTION UPGRADE */
.faq-hero {
    background: transparent; /* Removed solid color */
    padding: 80px 20px 100px;
    text-align: center;
    animation: fadeInDown 0.8s ease-out;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.faq-badge {
    display: inline-block;
    background: rgba(242, 106, 61, 0.1);
    color: #f26a3d;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.5px;
    padding: 8px 20px;
    border-radius: 30px;
    margin-bottom: 20px;
    border: 1px solid rgba(242, 106, 61, 0.2);
}

.faq-hero h1 {
    font-weight: 800;
    font-size: 52px;
    margin-bottom: 20px;
    background: linear-gradient(to right, #0f172a, #334155);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -1.5px;
}

.faq-hero p {
    color: #64748b;
    max-width: 600px;
    margin: auto;
    font-size: 17px;
    line-height: 1.6;
}

/* FAQ WRAPPER & ACCORDION */
.faq-wrapper {
    max-width: 800px;
    margin: -60px auto 80px;
    padding: 0 20px;
    animation: fadeInUp 1s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

.accordion-item {
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 20px !important;
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
    overflow: hidden;
}

.accordion-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.06);
    background: rgba(255, 255, 255, 0.8);
}

.accordion-button {
    font-weight: 700;
    font-size: 18px;
    padding: 25px;
    background: transparent !important;
    color: #1e293b !important;
    border: none !important;
    transition: all 0.3s ease;
}

.accordion-button:not(.collapsed) {
    color: #f26a3d !important;
    box-shadow: none;
}

/* Custom Icon Color */
.accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%231e293b'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.accordion-button:not(.collapsed)::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23f26a3d'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-body {
    padding: 0 25px 30px;
    color: #64748b;
    font-size: 16px;
    line-height: 1.7;
}

/* Micro-interaction: Strong tags highlight */
.accordion-body strong {
    color: #f26a3d;
}

/* Mobile Adjustments */
@media (max-width: 768px) {
    .faq-hero h1 { font-size: 36px; }
    .faq-wrapper { margin-top: -30px; }
    .accordion-button { font-size: 16px; padding: 20px; }
}

    </style>
</head>

<body>

<?php include('includes/header.php'); ?>

<!-- HERO SECTION -->
<div class="faq-hero">
    <div class="faq-badge">SUPPORT CENTER</div>
    <h1>Frequently Asked Questions</h1>
    <p>
        Everything you need to know about the LocalFlair experience.
        Can't find what you're looking for? Our boutique concierge is always ready to help.
    </p>
</div>

<!-- FAQ CONTENT -->
<div class="faq-wrapper">
    <div class="accordion" id="faqAccordion">

        <!-- FAQ 1 -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    How do I create an account?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Click the <strong>Register</strong> button in the navigation bar.
                    Provide your full name, email address, and create a secure password.
                    Check your inbox for a verification email to activate your account.
                </div>
            </div>
        </div>

        <!-- FAQ 2 -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    How can I place an order?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Browse products, click <strong>Add to Cart</strong>, and proceed to checkout.
                    Follow the payment process to complete your order.
                </div>
            </div>
        </div>

        <!-- FAQ 3 -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    What payment methods are accepted?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    We accept secure online payment methods available during checkout.
                    Supported gateways will be displayed before you confirm your order.
                </div>
            </div>
        </div>

        <!-- FAQ 4 -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    How do I track my delivery?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    After placing your order, you can check its status inside
                    <strong>Order History</strong> in your account dashboard.
                </div>
            </div>
        </div>

        <!-- FAQ 5 -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    What is your return policy?
                </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Items may be returned within the allowed return period.
                    Please visit our Returns page or contact support for assistance.
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>