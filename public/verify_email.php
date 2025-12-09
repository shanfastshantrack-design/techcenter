<?php
// public/verify_email.php
$page_title = "Verify Email";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

$token = $_GET['token'] ?? '';
$verified = false;
$error = '';

if ($token !== '') {
    try {
        // lookup user by token
        $stmt = $pdo->prepare("SELECT id, token_expires FROM users WHERE verification_token = :t LIMIT 1");
        $stmt->execute(['t' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            $expires = strtotime($user['token_expires']);
            $now = time();

            if ($expires >= $now) {
                // verify user
                $upd = $pdo->prepare("
                    UPDATE users 
                    SET email_verified = 1, verification_token = NULL, token_expires = NULL 
                    WHERE id = :id
                ");
                $upd->execute(['id' => $user['id']]);

                $verified = true;
            } else {
                $error = "Your verification link has expired. Please request a new one.";
            }
        } else {
            $error = "Invalid or already used verification link.";
        }
    } catch (Exception $e) {
        $error = "Unexpected error: " . $e->getMessage();
    }
} else {
    $error = "Invalid verification request.";
}

include __DIR__ . '/../src/views/header.php';
?>

<div class="panel" style="max-width:600px;margin:0 auto">
    <h2>Email Verification</h2>

    <?php if ($verified): ?>
        <div style="background:#d4ffb2;color:#111;padding:12px;border-radius:8px;margin-bottom:12px;">
            Your email has been verified successfully! You may now log in.
        </div>

        <a href="/techcenter/public/login.php" class="btn primary">Go to Login</a>

    <?php else: ?>
        <div style="background:#ffb2b2;color:#111;padding:12px;border-radius:8px;margin-bottom:12px;">
            <?= e($error) ?>
        </div>

        <a href="/techcenter/public/resend_verification.php" class="btn primary">
            Resend Verification Email
        </a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
