<?php
// public/my_orders.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

require_login();
$uid = (int)$_SESSION['user_id'];

// fetch orders for the user
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC');
$stmt->execute(['uid' => $uid]);
$orders = $stmt->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:1000px;margin:0 auto">
  <h2>My orders</h2>

  <?php if (empty($orders)): ?>
    <div class="muted">You have no orders yet.</div>
    <div style="margin-top:12px"><a class="btn primary" href="/techcenter/public/products.php">Browse products</a></div>
  <?php else: ?>
    <?php foreach($orders as $o): ?>
      <div style="border:1px solid rgba(255,255,255,0.03);padding:12px;border-radius:8px;margin-bottom:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <strong>Order #<?= e($o['id']) ?></strong>
            <div class="muted" style="font-size:13px">Placed: <?= e($o['created_at']) ?></div>
          </div>
          <div style="text-align:right">
            <div class="muted" style="font-size:13px">Status</div>
            <div style="font-weight:800;color:var(--accent)"><?= e(ucfirst($o['status'])) ?></div>
          </div>
        </div>

        <?php
          // fetch items for this order
          $it = $pdo->prepare('SELECT oi.*, p.title, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid');
          $it->execute(['oid' => $o['id']]);
          $items = $it->fetchAll();
        ?>

        <div style="margin-top:10px">
          <ul>
            <?php foreach($items as $it): ?>
              <li style="margin-bottom:8px;display:flex;gap:10px;align-items:center">
                <?php if (!empty($it['image']) && file_exists(__DIR__.'/uploads/'.$it['image'])): ?>
                  <img src="/techcenter/public/uploads/<?= e($it['image']) ?>" style="width:72px;height:56px;object-fit:cover;border-radius:6px" alt="">
                <?php else: ?>
                  <div style="width:72px;height:56px;border-radius:6px;background:linear-gradient(180deg,#111,#222);display:flex;align-items:center;justify-content:center;color:var(--muted)"><?= e(substr($it['title'] ?? 'Item',0,10)) ?></div>
                <?php endif; ?>

                <div style="flex:1">
                  <div style="font-weight:700"><?= e($it['title'] ?? 'Deleted product') ?></div>
                  <div class="muted" style="font-size:13px">Qty: <?= e($it['qty']) ?> · ₹<?= number_format($it['price']) ?></div>
                </div>

                <div style="text-align:right;font-weight:700">₹<?= number_format($it['price'] * $it['qty']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div style="text-align:right;font-weight:800;color:var(--accent);margin-top:6px">
          Total: ₹<?= number_format($o['total_amount']) ?>
        </div>

        <div style="margin-top:8px;display:flex;gap:8px;justify-content:flex-end">
          <a class="btn ghost" href="/techcenter/public/order_success.php">View receipt</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
