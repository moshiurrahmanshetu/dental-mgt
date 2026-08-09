<?php
require_once __DIR__ . '/../config/constants.php';

// Get current user info (session already started in auth_check.php)
$currentRole = $_SESSION['role_name'] ?? '';
$currentUser = [
    'full_name' => $_SESSION['full_name'] ?? 'User',
    'avatar' => $_SESSION['avatar'] ?? null
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container-fluid">
        <button class="btn btn-link text-white me-2" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
            <i class="bi bi-hospital me-2"></i>
            <?php echo SITE_NAME; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link position-relative" href="#">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                        </span>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                       data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" 
                                     alt="Avatar" class="rounded-circle">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>modules/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="wrapper">
    <aside class="sidebar" id="sidebar">
        <?php include __DIR__ . '/sidebar.php'; ?>
    </aside>
    
    <main class="main-content">
        <div class="container-fluid py-4">
