<?php
// Authentication and Session Management
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_functions.php';

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

// Auto-require auth when this file is included
requireAuth();

?>
