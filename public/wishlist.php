<?php
// public/wishlist.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

require_login();
$uid = (int)$_SESSION['user_id'];

// handle add/remove via POST (form submissions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $action = $_POST['action'];

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:u,:p)');
        $stmt->execute(['u'=>$uid,'p'=>$pid]);
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = :u AND product_id = :p');
        $stmt->execute(['u'=>$uid,'p'=>$pid]);
    }
    header('Location: /techcenter/public/wishlist.php');
    exit;
}

// fetch wishlist products
$stmt = $pdo->prepare('SELECT w.product_id, p.title, p.price, p.image, p.stock FROM wishlist w JOIN products p ON p.id = w.product_id WHERE w.user_id = :u ORDER BY w.created_at DESC');
$stmt->execute(['u' => $uid]);
$list = $stmt->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:1000px;margin:0 auto">
  <h2>My wishlist</h2>

  <?php if (empty($list)): ?>
    <div class="muted">Your wishlist is empty.</div>
    <div style="margin-top:12px"><a class="btn primary" href="/techcenter/public/products.php">Browse products</a></div>
  <?php else: ?>
    <div class="products" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px">
      <?php foreach($list as $p): ?>
        <article class="card">
          <?php if (!empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image'])): ?>
            <img src="/techcenter/public/uploads/<?= e($p['image']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:8px" alt="">
          <?php else: ?>
            <div class="img"><?= e($p['title']) ?></div>
          <?php endif; ?>

          <h3><?= e($p['title']) ?></h3>
          <div style="font-weight:700;color:var(--accent)">₹<?= number_format($p['price']) ?></div>

          <div style="display:flex;gap:8px;margin-top:8px">
            <a class="btn ghost" href="/techcenter/public/product.php?id=<?= e($p['product_id']) ?>">View</a>

            <form method="post" style="display:inline">
              <input type="hidden" name="product_id" value="<?= e($p['product_id']) ?>">
              <input type="hidden" name="action" value="remove">
              <button class="btn" type="submit">Remove</button>
            </form>

            <form method="post" action="/techcenter/public/cart.php" style="display:inline">
              <input type="hidden" name="product_id" value="<?= e($p['product_id']) ?>">
              <input type="hidden" name="qty" value="1">
              <button class="btn primary" type="submit">Add to cart</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
