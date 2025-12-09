<?php
// public/profile.php
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

// require user to be logged in
require_login();

$uid = (int)$_SESSION['user_id'];
$errors = [];
$success = null;

// load current user data
$stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $uid]);
$user = $stmt->fetch();
if (!$user) {
    // unlikely, but handle it
    session_unset();
    session_destroy();
    header('Location: /techcenter/public/login.php');
    exit;
}

// handle profile info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($username === '') $errors[] = 'Username cannot be empty.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($errors)) {
        // check uniqueness of email / username for OTHER users
        $check = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR username = :username) AND id != :id LIMIT 1');
        $check->execute(['email' => $email, 'username' => $username, 'id' => $uid]);
        if ($check->fetch()) {
            $errors[] = 'Email or username already in use by another account.';
        } else {
            $upd = $pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
            $upd->execute(['username' => $username, 'email' => $email, 'id' => $uid]);
            $_SESSION['username'] = $username; // update session display name
            $success = 'Profile updated successfully.';
            // reload user data for display
            $user['username'] = $username;
            $user['email'] = $email;
        }
    }
}

// handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current'] ?? '';
    $new1 = $_POST['new1'] ?? '';
    $new2 = $_POST['new2'] ?? '';

    if ($current === '' || $new1 === '' || $new2 === '') {
        $errors[] = 'All password fields are required.';
    } elseif ($new1 !== $new2) {
        $errors[] = 'New passwords do not match.';
    } else {
        // fetch current hash
        $pwstmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $pwstmt->execute(['id' => $uid]);
        $row = $pwstmt->fetch();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            // update
            $newHash = password_hash($new1, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = :ph WHERE id = :id')->execute(['ph' => $newHash, 'id' => $uid]);
            $success = 'Password changed successfully.';
        }
    }
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:900px;margin:0 auto">
  <h2>My profile</h2>

  <?php if ($success): ?>
    <div style="background:linear-gradient(90deg,#fff2a0,#ffd400);color:#111;padding:10px;border-radius:8px;margin-bottom:12px">
      <?= e($success) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div style="color:#ff6b6b;margin-bottom:12px">
      <ul>
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section style="display:grid;grid-template-columns:1fr 360px;gap:18px">
    <div>
      <form method="post" action="">
        <input type="hidden" name="action" value="update_info">
        <label>Username
          <input type="text" name="username" required value="<?= e($user['username']) ?>">
        </label>

        <label>Email
          <input type="email" name="email" required value="<?= e($user['email']) ?>">
        </label>

        <div style="margin-top:12px">
          <button class="btn primary" type="submit">Save changes</button>
        </div>
      </form>

      <hr style="margin:18px 0;border-color:rgba(255,255,255,0.03)">

      <h3>Change password</h3>
      <form method="post" action="">
        <input type="hidden" name="action" value="change_password">
        <label>Current password
          <input type="password" name="current" required>
        </label>

        <label>New password
          <input type="password" name="new1" required>
        </label>

        <label>Confirm new password
          <input type="password" name="new2" required>
        </label>

        <div style="margin-top:12px">
          <button class="btn primary" type="submit">Change password</button>
        </div>
      </form>
    </div>

    <aside class="panel" style="min-height:160px">
      <h4>Account</h4>
      <div style="margin-top:8px">
        <div><strong>Member since</strong></div>
        <div class="muted"><?= e($user['created_at']) ?></div>

        <div style="margin-top:12px">
          <a class="btn ghost" href="/techcenter/public/my_orders.php">My orders</a>
          <a class="btn ghost" href="/techcenter/public/wishlist.php">My wishlist</a>
        </div>

        <div style="margin-top:14px">
          <form method="post" action="/techcenter/public/logout.php">
            <button class="btn" type="submit">Logout</button>
          </form>
        </div>
      </div>
    </aside>
  </section>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
