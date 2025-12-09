<?php
// public/resend_verification.php
$page_title = "Resend Verification";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email_verified, last_verification_sent FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "No account exists with that email.";
        } else {
            if ((int)$user['email_verified'] === 1) {
                $errors[] = "This email is already verified.";
            } else {
                // Rate limit: 5 minutes
                $last = strtotime($user['last_verification_sent'] ?? '1970-01-01');
                $diff = time() - $last;

                if ($diff < 300) { // 5 minutes
                    $errors[] = "You can resend verification only once every 5 minutes.";
                } else {
                    // Create new token
                    $token = bin2hex(random_bytes(32));
                    $expires = date("Y-m-d H:i:s", time() + 24 * 3600);
                    $now = date("Y-m-d H:i:s");

                    // Save token
                    $upd = $pdo->prepare("
                        UPDATE users 
                        SET verification_token = :t, token_expires = :e, last_verification_sent = :s 
                        WHERE id = :id
                    ");
                    $upd->execute([
                        't' => $token,
                        'e' => $expires,
                        's' => $now,
                        'id' => $user['id']
                    ]);

                    // Send email
                    $sent = send_verification_email($email, $user['username'], $token);

                    if ($sent) {
                        $success = "Verification email has been resent. Please check your inbox.";
                    } else {
                        $errors[] = "Could not send email. Try again in a moment.";
                    }
                }
            }
        }
    }
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:600px;margin:0 auto">
    <h2>Resend Verification Email</h2>

    <?php if ($success): ?>
        <div style="background:#d4ffb2;color:#111;padding:12px;border-radius:8px;margin-bottom:12px;">
            <?= e($success) ?>
        </div>
        <a href="/techcenter/public/login.php" class="btn primary">Go to Login</a>
    <?php else: ?>

        <?php if (!empty($errors)): ?>
            <div style="background:#ffb2b2;color:#111;padding:12px;border-radius:8px;margin-bottom:12px;">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Email Address
                <input type="email" name="email" required>
            </label>

            <div style="margin-top:12px;">
                <button class="btn primary">Resend Email</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
