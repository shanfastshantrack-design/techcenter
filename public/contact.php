<?php
// public/contact.php
$page_title = "Contact";
require_once __DIR__ . '/../src/init.php';
require_once __DIR__ . '/../src/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// create a simple CSRF token for the form
if (empty($_SESSION['contact_csrf'])) {
    try { $_SESSION['contact_csrf'] = bin2hex(random_bytes(24)); }
    catch (Exception $e) { $_SESSION['contact_csrf'] = bin2hex(openssl_random_pseudo_bytes(24)); }
}

$errors = [];
$success = null;
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['contact_csrf'] ?? '';
    if (!hash_equals($_SESSION['contact_csrf'] ?? '', $token)) {
        $errors[] = 'Invalid form submission (security token).';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '') $errors[] = 'Please enter your name.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if ($subject === '') $errors[] = 'Please enter a subject.';
        if ($message === '') $errors[] = 'Please write your message.';

        if (empty($errors)) {
            // Prepare message payload
            $when = date('Y-m-d H:i:s');
            $payload = [
                'when' => $when,
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];

            // 1) Try to send an email (this may not work on local XAMPP unless mail configured)
            $to = 'admin@techcenter.local'; // change to your real support email when deploying
            $mail_subject = "[Contact] " . $subject;
            $mail_body = "New contact form submission:\n\n"
                . "When: {$payload['when']}\n"
                . "Name: {$payload['name']}\n"
                . "Email: {$payload['email']}\n"
                . "IP: {$payload['ip']}\n\n"
                . "Message:\n{$payload['message']}\n";
            $headers = "From: " . $payload['name'] . " <" . $payload['email'] . ">\r\n"
                     . "Reply-To: " . $payload['email'] . "\r\n";

            $mail_sent = false;
            try {
                // @ to suppress warnings on local dev if mail not configured
                $mail_sent = @mail($to, $mail_subject, $mail_body, $headers);
            } catch (Exception $e) {
                $mail_sent = false;
            }

            // 2) Always log message locally so you don't lose it (safe for local dev)
            $storageDir = __DIR__ . '/../storage';
            if (!file_exists($storageDir)) @mkdir($storageDir, 0755, true);
            $logFile = $storageDir . '/contacts.log';
            $line = "[" . $payload['when'] . "] " . $payload['name'] . " <" . $payload['email'] . "> (" . $payload['ip'] . ") Subject: " . $payload['subject'] . PHP_EOL
                  . str_replace("\r\n", "\n", $payload['message']) . PHP_EOL
                  . str_repeat('-', 80) . PHP_EOL;
            @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

            // success message
            $success = 'Thanks — your message has been sent. We will respond shortly.';
            if (!$mail_sent) {
                $success .= ' (Note: email not sent — saved locally for now.)';
            }

            // clear the form
            $name = $email = $subject = $message = '';
            // regenerate token to avoid duplicate submits
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(24));
        }
    }
}

include __DIR__ . '/../src/views/header.php';
?>
<div class="panel" style="max-width:900px;margin:0 auto">
  <h2>Contact Us</h2>

  <p class="muted">Have a question, feedback, or need help with an order? Send us a message using the form below.</p>

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

  <form method="post" action="">
    <input type="hidden" name="contact_csrf" value="<?= e($_SESSION['contact_csrf']) ?>">

    <label>Name
      <input type="text" name="name" value="<?= e($name) ?>" required>
    </label>

    <label>Email
      <input type="email" name="email" value="<?= e($email) ?>" required>
    </label>

    <label>Subject
      <input type="text" name="subject" value="<?= e($subject) ?>" required>
    </label>

    <label>Message
      <textarea name="message" rows="7" required><?= e($message) ?></textarea>
    </label>

    <div style="margin-top:12px;display:flex;gap:10px;align-items:center">
      <button class="btn primary" type="submit">Send message</button>
      <a class="btn ghost" href="/techcenter/public/index.php">Back to home</a>
    </div>
  </form>

  <hr style="margin:18px 0;border-color:rgba(255,255,255,0.03)">

  <div style="display:grid;grid-template-columns:1fr 320px;gap:18px">
    <div>
      <h4 style="color:var(--accent)">Other ways to reach us</h4>
      <p class="muted">
        Email: <strong>support@techcenter.local</strong><br>
        Phone: <strong>+91 99999 99999</strong><br>
        Address: 123 Tech Street, Your City
      </p>
    </div>

    <aside class="panel">
      <h4>Message log (local)</h4>
      <p class="muted" style="font-size:13px;margin-bottom:8px">All submitted messages are saved locally at <code>storage/contacts.log</code> so you won't lose them while testing on XAMPP.</p>
      <div style="font-size:13px">
        <?php
          $logFile = __DIR__ . '/../storage/contacts.log';
          if (file_exists($logFile)) {
              $lines = array_slice(array_reverse(file($logFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)), 0, 6);
              foreach ($lines as $ln) {
                  echo '<div style="margin-bottom:6px;color:var(--muted);font-family:monospace;font-size:12px;">' . e($ln) . '</div>';
              }
          } else {
              echo '<div class="muted">No messages yet.</div>';
          }
        ?>
      </div>
    </aside>
  </div>
</div>

<?php include __DIR__ . '/../src/views/footer.php'; ?>
