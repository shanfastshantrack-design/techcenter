<?php
// admin_order_detail.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';
if (!is_logged_in()) { header('Location: /techcenter/public/login.php'); exit; }
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = :id LIMIT 1'); $stmt->execute(['id'=>$_SESSION['user_id']]); $me = $stmt->fetch();
if (!$me || !$me['is_admin']) { http_response_code(403); echo 'Forbidden'; exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /techcenter/public/admin_orders.php'); exit; }

$errors = []; $msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='update_status') {
    $new = $_POST['status'] ?? 'pending';
    $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id')->execute(['s'=>$new,'id'=>$id]);
    $msg = 'Status updated.';
}

// fetch order + user
$ord = $pdo->prepare('SELECT o.*, u.username,u.email FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id = :id LIMIT 1');
$ord->execute(['id'=>$id]); $order = $ord->fetch();
if (!$order) { header('Location: /techcenter/public/admin_orders.php'); exit; }

// fetch items
$it = $pdo->prepare('SELECT oi.*, p.title, p.image FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = :oid');
$it->execute(['oid'=>$id]); $items = $it->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:900px;margin:0 auto">
  <h2>Order #<?= e($order['id']) ?></h2>
  <?php if ($msg): ?><div style="color:var(--accent)"><?= e($msg) ?></div><?php endif; ?>
  <div style="display:flex;justify-content:space-between;gap:12px">
    <div>
      <strong>User:</strong> <?= e($order['username']) ?> (<?= e($order['email']) ?>) <br>
      <strong>Placed:</strong> <?= e($order['created_at']) ?>
    </div>
    <div style="text-align:right">
      <strong>Total:</strong> ₹<?= number_format($order['total_amount']) ?><br>
      <strong>Status:</strong> <?= e($order['status']) ?>
    </div>
  </div>

  <h4 style="margin-top:12px">Items</h4>
  <table style="width:100%;border-collapse:collapse">
    <thead style="color:var(--muted)"><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
    <tbody>
      <?php foreach($items as $it): ?>
        <tr style="border-top:1px solid rgba(255,255,255,0.03)"><td>
          <?php if ($it['image'] && file_exists(__DIR__.'/uploads/'.$it['image'])): ?>
            <img src="/techcenter/public/uploads/<?= e($it['image']) ?>" style="height:48px;border-radius:6px;margin-right:8px;vertical-align:middle" alt="">
          <?php endif; ?>
          <?= e($it['title'] ?? 'Deleted product') ?>
        </td>
        <td><?= e($it['qty']) ?></td>
        <td>₹<?= number_format($it['price']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top:12px;display:flex;gap:8px;align-items:center">
    <form method="post">
      <input type="hidden" name="action" value="update_status">
      <select name="status">
        <?php foreach(['pending','processing','shipped','completed','cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $order['status']===$s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn ghost" type="submit">Update</button>
    </form>

    <a class="btn ghost" href="/techcenter/public/admin_orders.php">Back to orders</a>
  </div>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
