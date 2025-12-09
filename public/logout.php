<?php
// public/logout.php
require_once __DIR__ . '/../src/init.php';

// clear all session data & destroy session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"] ?? '/', $params["domain"] ?? '', $params["secure"] ?? false, $params["httponly"] ?? true
    );
}
session_unset();
session_destroy();

// redirect to login page
header('Location: /techcenter/public/login.php');
exit;
