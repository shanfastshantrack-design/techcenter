<?php
// public/index.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// featured: latest 6 products
$featured = $pdo->query('SELECT id,title,brand,price,image,category FROM products ORDER BY id DESC LIMIT 6')->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<section class="panel" style="text-align:center">
  <h1 style="margin:0 0 8px 0">TECHCENTER</h1>
  <p class="muted">Brand new phones & accessories • Minimalist storefront</p>
  <div style="margin-top:12px;display:flex;gap:10px;justify-content:center">
    <a class="btn primary" href="/techcenter/public/products.php">Shop all</a>
    <a class="btn ghost" href="/techcenter/public/products.php?category=accessories">Accessories</a>
    <?php if(!empty($_SESSION['user_id'])): ?>
      <a class="btn ghost" href="/techcenter/public/profile.php">My profile</a>
    <?php else: ?>
      <a class="btn ghost" href="/techcenter/public/login.php">Login</a>
    <?php endif; ?>
  </div>
</section>

<section class="panel" style="margin-top:18px">
  <h2>Featured</h2>
  <div class="products" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:12px">
    <?php if(empty($featured)): ?>
      <div class="muted">No featured items. Add products in Admin.</div>
    <?php else: foreach($featured as $p): ?>
      <article class="card">
        <?php if($p['image'] && file_exists(__DIR__.'/uploads/'.$p['image'])): ?>
          <img src="/techcenter/public/uploads/<?= e($p['image']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px" alt="">
        <?php else: ?>
          <div class="img"><?= e($p['brand']) ?></div>
        <?php endif; ?>
        <h3><?= e($p['title']) ?></h3>
        <div class="muted"><?= e($p['brand']) ?> • <?= e($p['category']) ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
          <div style="font-weight:700;color:var(--accent)">₹<?= number_format($p['price']) ?></div>
          <a class="btn ghost" href="/techcenter/public/product.php?id=<?= e($p['id']) ?>">View</a>
        </div>
      </article>
    <?php endforeach; endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
