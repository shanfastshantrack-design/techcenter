<?php
// public/register.php (no email verification)
$page_title = "Register";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /techcenter/public/profile.php');
    exit;
}

$errors = [];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password2 = $_POST['password2'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($username === '' || strlen($username) < 2) {
        $errors[] = "Username must be at least 2 characters.";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if ($password === '' || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
        $check->execute(['email' => $email, 'username' => $username]);
        if ($check->fetch()) {
            $errors[] = "Email or username already exists.";
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $insert = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, created_at, email_verified)
            VALUES (:username, :email, :hash, NOW(), 1)
        ");

        $insert->execute([
            'username' => $username,
            'email' => $email,
            'hash' => $passwordHash
        ]);

        set_flash("Account created. You can now log in.");
        header("Location: /techcenter/public/login.php");
        exit;
    }
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:600px;margin:0 auto">
  <h2>Create Your Account</h2>

  <?php if ($flash = get_flash()): ?>
    <div style="background:#ffe680;color:#111;padding:10px;border-radius:8px;margin-bottom:10px;">
      <?= e($flash) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div style="color:#ff6b6b;margin-bottom:10px;">
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <label>Username
      <input type="text" name="username" value="<?= e($username) ?>" required>
    </label>

    <label>Email Address
      <input type="email" name="email" value="<?= e($email) ?>" required>
    </label>

    <label>Password
      <input type="password" name="password" required>
    </label>

    <label>Confirm Password
      <input type="password" name="password2" required>
    </label>

    <div style="margin-top:12px;display:flex;gap:10px;">
      <button class="btn primary" type="submit">Create Account</button>
      <a href="/techcenter/public/login.php" class="btn ghost">Already have an account?</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
