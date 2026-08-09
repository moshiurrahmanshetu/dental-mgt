<?php
// Authentication and Session Management
require_once __DIR__ . '/../config/db.php';

// Only require auth if this file is being included directly (not just for functions)
if (basename($_SERVER['PHP_SELF']) !== basename(__FILE__)) {
    // File is being included, don't auto-require auth
} else {
    // File is being accessed directly, require auth
    requireAuth();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit();
    }
}

// Check if current user has one of the allowed roles
function checkRole($allowedRoles = []) {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['role_name'], $allowedRoles)) {
        // Redirect to access denied page or show error
        die('<div class="alert alert-danger">Access Denied. You do not have permission to access this page.</div>');
    }
}

// Get current user info
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role_id' => $_SESSION['role_id'],
            'role_name' => $_SESSION['role_name'],
            'avatar' => $_SESSION['avatar'] ?? null
        ];
    }
    return null;
}

// Generate CSRF token
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log user activity
function logActivity($action, $description = null) {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

?>
