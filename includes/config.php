<?php
// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'pharmacy_db');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("<h3 style='color:red;font-family:sans-serif;padding:20px;'>
        ❌ Database Connection Failed!<br>
        <small>" . mysqli_connect_error() . "</small><br><br>
        <strong>Fix:</strong> Open <code>includes/config.php</code> and update DB_USER and DB_PASS.
    </h3>");
}

mysqli_set_charset($conn, "utf8");

// Session start (call once here)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// HELPER FUNCTIONS
// ============================================

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Check role access
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        header("Location: ../dashboard.php?error=access_denied");
        exit();
    }
}

// Sanitize input
function clean($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Flash message
function setMessage($type, $text) {
    $_SESSION['msg_type'] = $type;
    $_SESSION['msg_text'] = $text;
}

function getMessage() {
    if (isset($_SESSION['msg_text'])) {
        $type = $_SESSION['msg_type'];
        $text = $_SESSION['msg_text'];
        unset($_SESSION['msg_type'], $_SESSION['msg_text']);
        $color = $type === 'success' ? '#10b981' : ($type === 'error' ? '#ef4444' : '#f59e0b');
        return "<div class='flash-msg' style='background:{$color}20;border-left:4px solid {$color};padding:12px 16px;margin-bottom:16px;border-radius:4px;color:{$color};font-weight:600;'>$text</div>";
    }
    return '';
}
?>
