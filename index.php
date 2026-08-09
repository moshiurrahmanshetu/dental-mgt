<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    // Redirect to role-based dashboard
    $roleName = $_SESSION['role_name'];
    $dashboardMap = [
        'Admin' => 'admin.php',
        'Doctor' => 'doctor.php',
        'Receptionist' => 'receptionist.php',
        'Patient' => 'patient.php'
    ];
    
    $dashboard = $dashboardMap[$roleName] ?? 'admin.php';
    header('Location: ' . BASE_URL . 'dashboard/' . $dashboard);
    exit();
} else {
    // Redirect to login page
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}
?>
