<?php
// Gagamitin natin ang header.php mo para saktong kopya ang Cart at Dropdown
include('includes/header.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us | LocalFlair</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* === ABOUT US EXACT UI === */
        .about-wrapper {
            background-color: #f4eadf;
            padding: 150px 0 80px; /* Tinaasan ang top padding dahil fixed ang navbar */
        }

        .about-title {
            font-family: "Georgia", serif;
            font-size: 3rem;
            font-weight: bold;
            color: #6b3f1d;
        }

        .about-quote {
            font-style: italic;
            color: #352111;
            max-width: 530px;
            margin-bottom: 30px;
        }

        .about-img-left,
        .about-img-right {
            border-radius: 25px;
            overflow: hidden;
            position: relative;
        }

        .about-img-left img,
        .about-img-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* === ANIMATIONS === */
        .fade-up {
            opacity: 0;
            transform: translateY(40px);
            transition: all 1s ease;
        }

        .fade-up.show {
            opacity: 1;
            transform: translateY(0);
        }

        .about-title {
            animation: slideDown 1s ease forwards;
            opacity: 0;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .about-img-right img { transition: transform 0.8s ease; }
        .about-img-right:hover img { transform: scale(1.08); }
    </style>
</head>
<body>

<section class="about-wrapper">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-6">
                <h1 class="about-title mb-3">Our story</h1>

                <p class="about-quote fade-up">
                    LocalFlair is an online marketplace dedicated to showcasing the best Filipino-made products. From handcrafted goods and traditional delicacies to modern local brands, we connect Filipino creators with customers who value quality, culture, and community.
                </p>
                <p class="about-quote fade-up">
                    "Born from the earth and shaped by the hardworking hands of our community—
                    bringing nature’s best to your family table."
                </p>

                <div class="about-img-right shadow fade-up" style="height: 420px;">
                    <img src="images/about-basket.avif" alt="Basket Image">
                </div>
            </div>

            <div class="col-lg-6">
                <div class="about-img-right shadow" style="height: 420px;">
                    <img src="images/about-us.avif" alt="Weaving Image">
                </div>
            </div>

        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const faders = document.querySelectorAll('.fade-up');
    const appearOptions = { threshold: 0.3 };

    const appearOnScroll = new IntersectionObserver(function(entries, observer){
        entries.forEach(entry => {
            if(!entry.isIntersecting){
                return;
            } else {
                entry.target.classList.add('show');
                observer.unobserve(entry.target);
            }
        });
    }, appearOptions);

    faders.forEach(fader => {
        appearOnScroll.observe(fader);
    });
</script>

</body>
</html>