<?php
$page_title = "About Us";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';
include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:900px;margin:0 auto;">

    <h2>About Techcenter</h2>

    <p class="muted" style="margin-bottom:20px;font-size:15px;">
        A modern marketplace for Smartphones & Accessories.
    </p>

    <section style="margin-bottom:30px;">
        <h3 style="color:var(--accent);font-weight:800;">Who We Are</h3>
        <p style="line-height:1.7;margin-top:10px;">
            Techcenter was created to bring a clean, modern, and seamless shopping experience
            for users looking to buy smartphones and accessories online.  
            We focus on minimal UI, transparent pricing, and smooth user experience — 
            from browsing to checkout.
        </p>
    </section>

    <section style="margin-bottom:30px;">
        <h3 style="color:var(--accent);font-weight:800;">What We Offer</h3>

        <ul style="line-height:1.8;margin-top:10px;">
            <li>Brand new smartphones of top brands</li>
            <li>Premium accessories — chargers, cases, cables, earphones</li>
            <li>Fast and secure checkout experience</li>
            <li>User accounts, wishlist, and order tracking</li>
            <li>Admin panel for product & order management</li>
        </ul>
    </section>

    <section style="margin-bottom:30px;">
        <h3 style="color:var(--accent);font-weight:800;">Our Mission</h3>
        <p style="line-height:1.7;margin-top:10px;">
            To provide an experience that feels fast, modern, and premium —  
            without unnecessary clutter.  
            We focus on smooth animations, minimal design, and user-first flow.
        </p>
    </section>

    <section style="margin-bottom:30px;">
        <h3 style="color:var(--accent);font-weight:800;">Developer’s Note</h3>
        <p style="line-height:1.7;margin-top:10px;">
            This website was built from scratch using:
            <br><br>
            <strong>PHP + MySQL</strong> (backend) <br>
            <strong>HTML • CSS • JS</strong> (frontend) <br>
            A fully custom admin dashboard, user account system, and product management system.
            <br><br>
            Designed with minimalism and high-performance UI in mind.
        </p>
    </section>

    <div style="margin-top:25px;">
        <a href="/techcenter/public/products.php" class="btn primary">Start Shopping</a>
        <a href="/techcenter/public/contact.php" class="btn ghost">Contact Us</a>
    </div>

</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
