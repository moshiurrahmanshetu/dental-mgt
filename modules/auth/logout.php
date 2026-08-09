<?php
require_once __DIR__ . '/../../includes/auth_functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    logActivity('Logout', 'User logged out');
}

// Destroy session completely
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

session_destroy();

// Redirect to login with success message
header('Location: ' . BASE_URL . 'modules/auth/login.php?logged_out=true');
exit();
?>
