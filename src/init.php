<?php
// src/init.php
// This file is included by every page in your project.

// ---------------------------------------------
// 1. Start Session
// ---------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------
// 2. Load Composer Autoloader (PHPMailer, etc.)
// ---------------------------------------------
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // If autoload missing, show warning (for debugging)
    // Remove this message on production
    error_log("Composer autoload.php NOT FOUND! PHPMailer will NOT work.");
}

// ---------------------------------------------
// 3. Database Connection
// ---------------------------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'techcenter_db';
$DB_USER = 'root';
$DB_PASS = ''; // XAMPP default is empty password

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    exit("Database connection failed: " . $e->getMessage());
}

// ---------------------------------------------
// 4. Load Helper Functions
// ---------------------------------------------
require_once __DIR__ . '/functions.php';

// ---------------------------------------------
// 5. Common Helpers: logged_in / admin check
// ---------------------------------------------
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin(): bool {
    return !empty($_SESSION['is_admin']);
}
