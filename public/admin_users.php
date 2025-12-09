<?php
// admin_users.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';
if (!is_logged_in()) { header('Location: /techcenter/public/login.php'); exit; }
$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = :id LIMIT 1'); $stmt->execute(['id'=>$_SESSION['user_id']]); $me = $stmt->fetch();
if (!$me || !$me['is_admin']) { http_response_code(403); echo 'Forbidden'; exit; }

// ensure CSRF token
if (empty($_SESSION['admin_csrf'])) {
    try { $_SESSION['admin_csrf'] = bin2hex(random_bytes(32)); }
    catch (Exception $e) { $_SESSION['admin_csrf'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}

$errors = []; $msg = null;

// handle POST actions: promote/demote/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $token = $_POST['admin_csrf'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'] ?? '', $token)) $errors[] = 'Invalid request.';
    else {
        $action = $_POST['action'];
        $uid = (int)($_POST['user_id'] ?? 0);

        if ($action === 'delete') {
            // prevent self-delete
            if ($uid === (int)$_SESSION['user_id']) $errors[] = 'You cannot delete yourself.';
            else {
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id'=>$uid]);
                $msg = 'User deleted.';
            }
        } elseif ($action === 'promote') {
            $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = :id')->execute(['id'=>$uid]);
            $msg = 'User promoted to admin.';
        } elseif ($action === 'demote') {
            // prevent demoting self
            if ($uid === (int)$_SESSION['user_id']) $errors[] = 'You cannot demote yourself.';
            else {
                $pdo->prepare('UPDATE users SET is_admin = 0 WHERE id = :id')->execute(['id'=>$uid]);
                $msg = 'User demoted.';
            }
        }
    }
}

// search & pagination
$q = trim($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$per = 20;
$offset = ($page-1)*$per;

$countSql = "SELECT COUNT(*) FROM users";
$where = ""; $params = [];
if ($q !== '') {
    $where = " WHERE username LIKE :q OR email LIKE :q";
    $params['q'] = "%$q%";
}
$total = (int)$pdo->prepare($countSql . $where)->execute($params) ? (int)$pdo->prepare($countSql . $where)->fetchColumn() : 0;
// simpler: fetch total properly
$stmtCount = $pdo->prepare($countSql . $where);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$stmt = $pdo->prepare("SELECT id,username,email,is_admin,created_at FROM users $where ORDER BY id DESC LIMIT :lim OFFSET :off");
foreach ($params as $k=>$v) $stmt->bindValue(":$k",$v);
$stmt->bindValue(':lim', (int)$per, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Users</h2>
    <div>
      <a class="btn ghost" href="/techcenter/public/admin_dashboard.php">Dashboard</a>
      <a class="btn ghost" href="/techcenter/public/admin_products.php">Products</a>
    </div>
  </div>

  <?php if ($msg): ?><div style="color:var(--accent);margin-top:10px"><?= e($msg) ?></div><?php endif; ?>
  <?php if (!empty($errors)): ?><div style="color:#ff7474;margin-top:10px"><ul><?php foreach($errors as $err) echo '<li>'.e($err).'</li>'; ?></ul></div><?php endif; ?>

  <form method="get" action="" style="margin-top:12px;display:flex;gap:8px">
    <input type="text" name="q" placeholder="Search username or email" value="<?= e($q) ?>" style="padding:8px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.04)">
    <button class="btn ghost" type="submit">Search</button>
  </form>

  <table style="width:100%;border-collapse:collapse;margin-top:12px">
    <thead style="color:var(--muted);text-align:left"><tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($users as $u): ?>
        <tr style="border-top:1px solid rgba(255,255,255,0.03)"><td><?= e($u['id']) ?></td>
        <td><?= e($u['username']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= $u['is_admin'] ? 'Yes' : 'No' ?></td>
        <td><?= e($u['created_at']) ?></td>
        <td>
          <?php if (!$u['is_admin']): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="admin_csrf" value="<?= e($_SESSION['admin_csrf']) ?>">
              <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
              <input type="hidden" name="action" value="promote">
              <button class="btn" type="submit">Promote</button>
            </form>
          <?php else: ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="admin_csrf" value="<?= e($_SESSION['admin_csrf']) ?>">
              <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
              <input type="hidden" name="action" value="demote">
              <button class="btn ghost" type="submit">Demote</button>
            </form>
          <?php endif; ?>

          <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete user?');">
              <input type="hidden" name="admin_csrf" value="<?= e($_SESSION['admin_csrf']) ?>">
              <input type="hidden" name="user_id" value="<?= e($u['id']) ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn ghost" type="submit">Delete</button>
            </form>
          <?php endif; ?>
        </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- simple pagination -->
  <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center">
    <div class="muted">Showing <?= e(count($users)) ?> of <?= e($total) ?> users</div>
    <div>
      <?php
        $pages = max(1, ceil($total / $per));
        for ($i=1;$i<=$pages;$i++):
      ?>
        <a class="btn ghost" href="?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
