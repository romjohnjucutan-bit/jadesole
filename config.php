<?php
// =============================================
// Jade Sole - Database Configuration
// =============================================

// Read database settings from environment when available (for Railway/Aiven)
define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQL_DB') ?: 'jade_sole');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // In production, don't expose raw DB errors. Keep simple message.
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');

// Site Configuration
define('SITE_NAME', getenv('SITE_NAME') ?: 'Jade Sole');
define('SITE_TAGLINE', getenv('SITE_TAGLINE') ?: 'Step Into Style');
define('SITE_LOCATION', getenv('SITE_LOCATION') ?: 'Moto Norte, Loon, Bohol');
define('SITE_CONTACT', getenv('SITE_CONTACT') ?: '09701933534');
define('SITE_HOURS', getenv('SITE_HOURS') ?: 'Mon–Sun, 9AM – 9PM');
define('DISCOUNT_THRESHOLD', 500);
define('DISCOUNT_PERCENT', 10);
define('FREE_DELIVERY_THRESHOLD', 300);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['staff_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function generateOrderId() {
    return 'JS-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function sanitize($conn, $val) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($val))));
}
?>
