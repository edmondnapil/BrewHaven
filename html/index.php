<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew Haven — Coffee Shop Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&family=Fraunces:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/brew-haven.css">
    <script src="../js/security.js?v=<?php echo @filemtime(__DIR__ . '/../js/security.js'); ?>"></script>
</head>

<body style="background:var(--bh-cream);">

    <div class="bh-landing-topbar">
        <div class="bh-brand"><img src="../images/brew-haven-logo.svg" alt="" style="width:28px;height:28px;"> Brew Haven</div>
        <div class="bh-landing-actions">
            <a href="register.php" class="bh-btn bh-btn-secondary">Register</a>
            <a href="login.php" class="bh-btn bh-btn-primary">Login</a>
        </div>
    </div>

    <section class="bh-landing-hero" aria-live="polite">
        <div class="bh-landing-hero-bg" style="background-image:url('https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=1600&q=80');"></div>
        <div class="bh-landing-hero-content">
            <span class="bh-hero-kicker"><i class="fa-solid fa-mug-hot"></i> Coffee Shop Management System</span>
            <h1>Brew <span class="bh-accent">&amp; enjoy</span></h1>
            <p>Create an account or log in to unlock a calm, secure space made for true coffee enthusiasts.</p>
            <div class="bh-landing-cta">
                <a href="register.php" class="bh-btn bh-btn-primary">Create Account <i class="fa-solid fa-arrow-right"></i></a>
                <a href="login.php" class="bh-btn bh-btn-secondary">I already have an account</a>
            </div>
        </div>
        <div class="bh-landing-scroll-cue"><i class="fa-solid fa-chevron-down"></i></div>
    </section>

    <div class="bh-main" style="max-width:1150px;margin:0 auto;">
        <div class="bh-section-heading">
            <span class="bh-section-kicker">Why Brew Haven</span>
            <h2>Crafted for coffee people</h2>
            <p>Everything about your visit, made simple and secure.</p>
        </div>
        <div class="bh-why-grid">
            <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-mug-hot"></i></div><h3>Freshly Brewed</h3><p>Every cup made to order, roasted with care.</p></div>
            <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-bolt"></i></div><h3>Order in Seconds</h3><p>Browse the menu and customize your order in a few taps.</p></div>
            <div class="bh-why-card"><div class="bh-why-icon"><i class="fa-solid fa-shield-halved"></i></div><h3>Secure Account</h3><p>Your details are protected with modern account security.</p></div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-content">
            <span>&copy; 2025 Brew Haven</span>
        </div>
    </footer>
</body>

</html>
