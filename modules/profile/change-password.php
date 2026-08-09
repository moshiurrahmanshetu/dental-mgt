<?php
$pageTitle = 'Change Password';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

$user = getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        if (empty($currentPassword)) {
            $error = 'Current password is required.';
        } elseif (empty($newPassword)) {
            $error = 'New password is required.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif ($newPassword === $currentPassword) {
            $error = 'New password must be different from current password.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $currentUser = $stmt->fetch();
                
                if (!$currentUser || !password_verify($currentPassword, $currentUser['password'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                    
                    // Log activity
                    logActivity('Password Changed', "User changed their own password");
                    
                    $success = "Password changed successfully!";
                    header("Location: " . BASE_URL . "modules/profile/change-password.php?success=" . urlencode($success));
                    exit();
                }
            } catch (PDOException $e) {
                error_log("Password Change Error: " . $e->getMessage());
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Change Password</h2>
        <p class="text-muted mb-0">Update your account password</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/profile/my-profile.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Profile
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_GET['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Password Change Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="current_password" name="current_password" 
                       placeholder="Enter your current password" required>
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="new_password" name="new_password" 
                       placeholder="Min 8 characters" required>
                <small class="text-muted">Must be at least 8 characters long and different from current password</small>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                       placeholder="Re-enter new password" required>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Security Note:</strong> Your new password will take effect immediately. You do not need to log in again.
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check me-2"></i>Change Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
