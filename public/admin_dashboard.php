<?php
// admin_dashboard.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';
if (!is_logged_in()) { header('Location: /techcenter/public/login.php'); exit; }
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = :id LIMIT 1'); $stmt->execute(['id'=>$_SESSION['user_id']]); $me = $stmt->fetch();
if (!$me || !$me['is_admin']) { http_response_code(403); echo 'Forbidden'; exit; }

// gather stats
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$recentOrders = $pdo->query('SELECT o.id,o.total_amount,o.status,o.created_at,u.username FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 6')->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Admin Dashboard</h2>
    <div style="display:flex;gap:8px">
      <a class="btn ghost" href="/techcenter/public/admin_products.php">Products</a>
      <a class="btn ghost" href="/techcenter/public/admin_users.php">Users</a>
      <a class="btn ghost" href="/techcenter/public/admin_orders.php">Orders</a>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px">
    <div class="panel">
      <h4>Total users</h4>
      <div style="font-size:28px;font-weight:800"><?= e($totalUsers) ?></div>
    </div>
    <div class="panel">
      <h4>Total products</h4>
      <div style="font-size:28px;font-weight:800"><?= e($totalProducts) ?></div>
    </div>
    <div class="panel">
      <h4>Total orders</h4>
      <div style="font-size:28px;font-weight:800"><?= e($totalOrders) ?></div>
    </div>
  </div>

  <div style="margin-top:18px">
    <h3>Recent orders</h3>
    <?php if (empty($recentOrders)): ?><div class="muted">No recent orders.</div><?php else: ?>
      <table style="width:100%;border-collapse:collapse;margin-top:8px">
        <thead style="color:var(--muted);text-align:left"><tr><th>#</th><th>User</th><th>Total</th><th>Status</th><th>When</th><th></th></tr></thead>
        <tbody>
          <?php foreach($recentOrders as $o): ?>
            <tr style="border-top:1px solid rgba(255,255,255,0.03)">
              <td><?= e($o['id']) ?></td>
              <td><?= e($o['username']) ?></td>
              <td>₹<?= number_format($o['total_amount']) ?></td>
              <td><?= e($o['status']) ?></td>
              <td><?= e($o['created_at']) ?></td>
              <td><a class="btn ghost" href="/techcenter/public/admin_order_detail.php?id=<?= e($o['id']) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
