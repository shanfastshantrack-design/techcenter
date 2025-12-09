<?php
// public/product.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /techcenter/public/products.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$p = $stmt->fetch();
if (!$p) {
    header('Location: /techcenter/public/products.php');
    exit;
}

// handle quick buy (POST) -> add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    // accumulate quantity
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    // redirect to cart
    header('Location: /techcenter/public/cart.php');
    exit;
}

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:900px;margin:0 auto">
  <div style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap">
    <div style="flex:1;min-width:260px">
      <?php if ($p['image'] && file_exists(__DIR__ . '/uploads/' . $p['image'])): ?>
        <img src="/techcenter/public/uploads/<?= e($p['image']) ?>" style="width:100%;max-height:520px;object-fit:cover;border-radius:10px" alt="<?= e($p['title']) ?>">
      <?php else: ?>
        <div style="height:420px;border-radius:10px;background:linear-gradient(180deg,#111,#222);display:flex;align-items:center;justify-content:center;color:var(--muted);font-weight:700">
          <?= e($p['brand']) ?> <?= e(explode(' ',$p['title'])[0] ?? '') ?>
        </div>
      <?php endif; ?>
    </div>

    <div style="width:360px;">
      <h2 style="margin-top:0"><?= e($p['title']) ?></h2>
      <div class="muted"><?= e($p['brand']) ?> • <?= e($p['category'] ?? '') ?> • <?= e($p['color'] ?? '') ?></div>
      <div style="font-weight:800;color:var(--accent);font-size:22px;margin-top:10px">₹<?= number_format((int)$p['price']) ?></div>
      <div style="margin-top:12px;color:var(--muted)"><?= e($p['short_desc'] ?? '') ?></div>

      <form method="post" style="margin-top:18px;display:flex;gap:8px;align-items:center">
        <label style="display:flex;gap:8px;align-items:center">
          Qty
          <input type="number" name="qty" value="1" min="1" max="<?= max(1,(int)$p['stock']) ?>" style="width:72px;padding:8px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04);color:inherit">
        </label>
        <button class="btn primary" name="add_to_cart" type="submit">Add to cart</button>
        <a class="btn ghost" href="/techcenter/public/products.php">Back to shop</a>
      </form>

      <hr style="margin:18px 0;border-color:rgba(255,255,255,0.03)">
      <h4>Details</h4>
      <div style="white-space:pre-wrap;color:var(--muted)"><?= e($p['long_desc'] ?: 'No further description.') ?></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
