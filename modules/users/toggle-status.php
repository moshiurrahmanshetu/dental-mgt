<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Role-based access control - Admin only
checkRole(['Admin']);

header('Content-Type: application/json');

$userId = intval($_POST['user_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');

if ($userId <= 0 || !in_array($newStatus, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// CSRF validation
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Cannot deactivate own account
if ($userId == $_SESSION['user_id'] && $newStatus === 'inactive') {
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    // Log activity
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $action = $newStatus === 'active' ? 'Activated' : 'Deactivated';
    logActivity("User {$action}", "User: {$user['full_name']} (ID: {$userId})");
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("User Status Toggle Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
}
