<?php
// public/login.php
$page_title = "Login";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

if (!empty($_SESSION['user_id'])) {
    // If already logged in → check admin
    if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header('Location: /techcenter/public/admin_products.php');
    } else {
        header('Location: /techcenter/public/index.php');
    }
    exit;
}

$errors = [];
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    if ($password === '') {
        $errors[] = "Enter your password.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // set session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['is_admin']  = (int)$user['is_admin'];

            // Redirect based on admin role
            if ($_SESSION['is_admin'] == 1) {
                header('Location: /techcenter/public/admin_products.php');
                exit;
            } else {
                header('Location: /techcenter/public/index.php');
                exit;
            }

        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:600px;margin:0 auto">
  <h2>Login</h2>

  <?php if (!empty($errors)): ?>
    <div style="color:#ff6b6b;margin-bottom:10px;">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= e($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <label>Email Address
      <input type="email" name="email" value="<?= e($email) ?>" required>
    </label>

    <label>Password
      <input type="password" name="password" required>
    </label>

    <div style="margin-top:12px;display:flex;gap:10px;">
      <button class="btn primary" type="submit">Login</button>
      <a href="/techcenter/public/register.php" class="btn ghost">Create Account</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>