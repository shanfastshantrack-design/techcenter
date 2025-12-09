<?php
// src/functions.php
// Common helper functions for the project.

// ------------------------------
// HTML escape helper
// ------------------------------
if (!function_exists('e')) {
    function e($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ------------------------------
// Flash messages (session-based)
// ------------------------------
if (!function_exists('set_flash')) {
    function set_flash(string $msg): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = $msg;
    }
}
if (!function_exists('get_flash')) {
    function get_flash(): ?string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $m = $_SESSION['flash'] ?? null;
        if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
        return $m;
    }
}

// ------------------------------
// Redirect helper
// ------------------------------
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

// ------------------------------
// Require login
// ------------------------------
if (!function_exists('require_login')) {
    function require_login(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            // optional: store where user wanted to go
            $_SESSION['after_login_redirect'] = $_SERVER['REQUEST_URI'] ?? '/';
            set_flash('Please login to continue.');
            redirect('/techcenter/public/login.php');
        }
    }
}

// ------------------------------
// Require email verified
// ------------------------------
if (!function_exists('require_email_verified')) {
    function require_email_verified(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            set_flash('Please login first.');
            redirect('/techcenter/public/login.php');
        }
        global $pdo;
        try {
            $stmt = $pdo->prepare('SELECT email_verified FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $r = $stmt->fetch();
            if (!$r || (int)$r['email_verified'] !== 1) {
                set_flash('Please verify your email before accessing that page. <a href="/techcenter/public/resend_verification.php">Resend verification</a>');
                redirect('/techcenter/public/verify_notice.php'); // optional page you can create
            }
        } catch (Exception $e) {
            // on DB error, be safe and redirect to login
            set_flash('Unable to verify account status. Please login again.');
            redirect('/techcenter/public/login.php');
        }
    }
}

// ------------------------------
// Simple CSRF helpers
// ------------------------------
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(string $name = 'csrf_token'): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION[$name])) {
            try {
                $_SESSION[$name] = bin2hex(random_bytes(24));
            } catch (Exception $e) {
                $_SESSION[$name] = bin2hex(openssl_random_pseudo_bytes(24));
            }
        }
        return $_SESSION[$name];
    }
}
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(string $token, string $name = 'csrf_token'): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION[$name]) || empty($token)) return false;
        return hash_equals($_SESSION[$name], $token);
    }
}

// ------------------------------
// send_verification_email()
// Uses PHPMailer via Composer autoload (configured in init.php).
// Edit SMTP config below to your provider (Mailtrap recommended for dev).
// ------------------------------
if (!function_exists('send_verification_email')) {
    function send_verification_email(string $toEmail, string $toName, string $token): bool
    {
        // ---------- CONFIGURE THESE ----------
        // Use Mailtrap credentials for local testing (recommended)
        $smtpHost = 'smtp.mailtrap.io';
        $smtpPort = 587;
        $smtpUser = 'YOUR_MAILTRAP_USERNAME';
        $smtpPass = 'YOUR_MAILTRAP_PASSWORD';

        // From address shown in the email
        $fromEmail = 'no-reply@techcenter.local';
        $fromName  = 'Techcenter Support';

        // Verification link (adjust domain/path if your folder name is different)
        $verifyLink = 'http://localhost/techcenter/public/verify_email.php?token=' . urlencode($token);
        // --------------------------------------

        // Prepare email content
        $subject = 'Verify your Techcenter account';
        $body = "<p>Hi " . htmlspecialchars($toName) . ",</p>
                 <p>Thanks for registering at Techcenter. Please verify your email address by clicking the link below:</p>
                 <p><a href=\"" . htmlspecialchars($verifyLink) . "\">Verify my email</a></p>
                 <p>This link will expire in 24 hours.</p>
                 <p>If you didn't create an account, just ignore this email.</p>
                 <p>— Techcenter</p>";

        // Ensure PHPMailer is available (composer autoload is loaded from init.php)
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            // Try to load composer autoload if not already loaded
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }
            if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                // PHPMailer not available
                error_log('PHPMailer not found. Install via composer require phpmailer/phpmailer');
                return false;
            }
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // SMTP settings
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;

            // From / To
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = "Hi {$toName},\n\nVerify your email by visiting: {$verifyLink}\n\nThis link expires in 24 hours.";

            return $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }
}
