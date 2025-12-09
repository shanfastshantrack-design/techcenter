<?php
// src/views/header.php
if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($page_title) ? e($page_title).' • Techcenter' : 'Techcenter' ?></title>

  <link rel="stylesheet" href="/techcenter/public/css/style.css">

  <style>
    header.site-header {
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:14px 18px;
      position:sticky;
      top:0;
      z-index:40;
      backdrop-filter: blur(6px);
      background: rgba(0,0,0,0.55);
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .nav-links {
      display:flex;
      gap:12px;
      align-items:center;
    }

    .nav-link {
      text-decoration:none;
      color:white;
      opacity:0.8;
      transition:0.25s;
      font-weight:500;
    }

    .nav-link:hover {
      opacity:1;
      color:var(--accent);
    }

    .header-right {
      display:flex;
      gap:10px;
      align-items:center;
    }

    .user-area {
      display:flex;
      gap:10px;
      align-items:center;
      padding:6px 10px;
      border-radius:8px;
      background:rgba(255,255,255,0.04);
    }

    .admin-badge {
      background:var(--accent);
      padding:2px 6px;
      border-radius:6px;
      color:#111;
      font-weight:800;
      font-size:11px;
    }

    .icon-counter {
      position:relative;
      display:inline-block;
    }
    .icon-counter .count {
      position:absolute;
      top:-6px;
      right:-8px;
      background:var(--accent);
      color:#111;
      font-size:11px;
      padding:2px 6px;
      border-radius:999px;
      font-weight:800;
    }

    @media (max-width:780px) {
      .nav-links { display:none; }
    }
  </style>

</head>
<body>

<header class="site-header">

  <!-- LEFT SIDE -->
  <div style="display:flex;align-items:center;gap:20px">

    <!-- CLEAN MINIMAL LOGO -->
    <a href="/techcenter/public/index.php"
       style="text-decoration:none;color:var(--accent);font-weight:900;font-size:20px;">
       TECHCENTER
    </a>

    <!-- NAV LINKS -->
    <nav class="nav-links">
      <a class="nav-link" href="/techcenter/public/products.php">Shop</a>
      <a class="nav-link" href="/techcenter/public/products.php?category=accessories">Accessories</a>
      <a class="nav-link" href="/techcenter/public/about.php">About</a>
      <a class="nav-link" href="/techcenter/public/contact.php">Contact</a>
    </nav>

  </div>


  <!-- RIGHT SIDE -->
  <div class="header-right">

    <?php
      // CART COUNT
      $cart_count = 0;
      if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; }
      }

      // WISHLIST COUNT
      $wishlist_count = 0;
      if (!empty($_SESSION['user_id'])) {
          try {
              $ws = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = :u");
              $ws->execute(['u'=>$_SESSION['user_id']]);
              $wishlist_count = (int)$ws->fetchColumn();
          } catch (Exception $e) {}
      }
    ?>

    <!-- Cart -->
    <a href="/techcenter/public/cart.php" class="btn ghost icon-counter">
      Cart
      <?php if ($cart_count > 0): ?>
        <span class="count"><?= e($cart_count) ?></span>
      <?php endif; ?>
    </a>


    <?php if (!empty($_SESSION['user_id'])): ?>
      <!-- LOGGED-IN USER AREA -->
      <div class="user-area">

        <span style="color:var(--accent);font-weight:800;">
          <?= e($_SESSION['username']) ?>
        </span>

        <?php if (!empty($_SESSION['is_admin'])): ?>
          <span class="admin-badge">ADMIN</span>
        <?php endif; ?>

        <a class="btn ghost" href="/techcenter/public/profile.php">Profile</a>

        <a class="btn ghost icon-counter" href="/techcenter/public/wishlist.php">
          Wishlist
          <?php if ($wishlist_count > 0): ?>
            <span class="count"><?= e($wishlist_count) ?></span>
          <?php endif; ?>
        </a>

        <a class="btn" href="/techcenter/public/logout.php">Logout</a>
      </div>

    <?php else: ?>
      <!-- NOT LOGGED IN -->
      <a class="btn ghost" href="/techcenter/public/login.php">Login</a>
      <a class="btn primary" href="/techcenter/public/register.php">Register</a>
    <?php endif; ?>

  </div>

</header>

<main style="padding:25px;">
