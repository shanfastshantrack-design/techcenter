<?php
// public/admin_orders.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// require login
if (!is_logged_in()) {
    header('Location: /techcenter/public/login.php');
    exit;
}

// check admin
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$me = $stmt->fetch();
if (!$me || !$me['is_admin']) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// handle POST for status update
$errors = [];
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $new = $_POST['status'] ?? 'pending';

    if ($oid > 0) {
        $pdo->prepare("UPDATE orders SET status = :s WHERE id = :id")
           ->execute(['s' => $new, 'id' => $oid]);

        $msg = "Order #$oid status updated.";
    }
    header("Location: /techcenter/public/admin_orders.php");
    exit;
}

// fetch all orders
$orders = $pdo->query(
    "SELECT o.*, u.username, u.email 
     FROM orders o 
     LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.id DESC"
)->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="display:flex;justify-content:space-between;align-items:center">
  <h2>Orders</h2>

  <nav style="display:flex;gap:8px">
    <a class="btn ghost" href="/techcenter/public/admin_dashboard.php">Dashboard</a>
    <a class="btn ghost" href="/techcenter/public/admin_products.php">Products</a>
    <a class="btn ghost" href="/techcenter/public/admin_users.php">Users</a>
    <a class="btn" href="/techcenter/public/admin_orders.php">Orders</a>
    <a class="btn ghost" href="/techcenter/public/logout.php">Logout</a>
  </nav>
</div>

<?php if ($msg): ?>
  <div style="color:var(--accent);margin-top:10px"><?= e($msg) ?></div>
<?php endif; ?>

<div class="panel" style="margin-top:12px">

<?php if (empty($orders)): ?>
  <div class="muted">No orders yet.</div>

<?php else: ?>

  <table style="width:100%;border-collapse:collapse">
    <thead style="color:var(--muted);text-align:left">
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Total</th>
        <th>Status</th>
        <th>Placed</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr style="border-top:1px solid rgba(255,255,255,0.03)">
          <td><?= e($o['id']) ?></td>

          <td>
            <?= e($o['username']) ?><br>
            <span class="muted" style="font-size:12px"><?= e($o['email']) ?></span>
          </td>

          <td style="font-weight:700;color:var(--accent)">
            ₹<?= number_format($o['total_amount']) ?>
          </td>

          <td><?= e(ucfirst($o['status'])) ?></td>

          <td><?= e($o['created_at']) ?></td>

          <td style="display:flex;gap:6px;align-items:center">
            <a class="btn ghost" href="/techcenter/public/admin_order_detail.php?id=<?= e($o['id']) ?>">View</a>

            <form method="post" style="display:inline">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
              <select name="status">
                <?php foreach(['pending','processing','shipped','completed','cancelled'] as $s): ?>
                  <option value="<?= e($s) ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn ghost" type="submit">Update</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

<?php endif; ?>

</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
