<?php
$pageTitle = 'Edit User';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - Admin only
checkRole(['Admin']);

$user = getCurrentUser();

// Get user ID
$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header("Location: " . BASE_URL . "modules/users/list.php");
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

if (!$userData) {
    header("Location: " . BASE_URL . "modules/users/list.php");
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    
    if ($action === 'update_info') {
        // CSRF validation
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
        } else {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $roleId = intval($_POST['role_id'] ?? 0);
            
            if (empty($fullName)) {
                $error = 'Full name is required.';
            } elseif (empty($email)) {
                $error = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } elseif ($roleId <= 0) {
                $error = 'Please select a role.';
            } else {
                try {
                    // Handle avatar upload
                    $avatarFilename = $userData['avatar'];
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $newAvatar = uploadAvatar($_FILES['avatar'], __DIR__ . '/../../assets/images/users/', $userData['avatar']);
                        if ($newAvatar) {
                            $avatarFilename = $newAvatar;
                        }
                    }
                    
                    // Update user
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ?, avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$fullName, $email, $phone, $roleId, $avatarFilename, $userId]);
                    
                    // Update session if editing own profile
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['avatar'] = $avatarFilename;
                    }
                    
                    // Log activity
                    logActivity('User Updated', "User updated: {$fullName} (ID: {$userId})");
                    
                    $success = "User updated successfully!";
                    header("Location: " . BASE_URL . "modules/users/list.php?success=" . urlencode($success));
                    exit();
                } catch (PDOException $e) {
                    error_log("User Update Error: " . $e->getMessage());
                    $error = 'Failed to update user. Please try again.';
                }
            }
        }
    } elseif ($action === 'reset_password') {
        // CSRF validation
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token. Please try again.';
        } else {
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');
            
            if (empty($newPassword)) {
                $error = 'New password is required.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Passwords do not match.';
            } else {
                try {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    
                    // Log activity
                    logActivity('Password Reset by Admin', "Password reset for user: {$userData['full_name']} (ID: {$userId})");
                    
                    $success = "Password reset successfully!";
                    header("Location: " . BASE_URL . "modules/users/list.php?success=" . urlencode($success));
                    exit();
                } catch (PDOException $e) {
                    error_log("Password Reset Error: " . $e->getMessage());
                    $error = 'Failed to reset password. Please try again.';
                }
            }
        }
    }
}

// Get available roles
$stmt = $pdo->prepare("SELECT id, role_name FROM roles WHERE role_name IN ('Admin', 'Doctor', 'Receptionist') ORDER BY role_name ASC");
$stmt->execute();
$roles = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Edit User</h2>
        <p class="text-muted mb-0">Edit user account: <?php echo htmlspecialchars($userData['full_name']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- User Info Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <?php if ($userData['avatar']): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/users/<?php echo htmlspecialchars($userData['avatar']); ?>" 
                     alt="Avatar" class="rounded-circle me-3" width="80" height="80">
            <?php else: ?>
                <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                     style="width: 80px; height: 80px; background-color: var(--primary-color); color: white; font-size: 32px;">
                    <?php echo getUserInitials($userData['full_name']); ?>
                </div>
            <?php endif; ?>
            <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($userData['email']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- User Info Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">User Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="update_info">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo $userData['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="avatar" class="form-label">Avatar (Optional)</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png">
                <small class="text-muted">JPG or PNG, max 2MB. Leave empty to keep current avatar.</small>
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Update User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Password Reset Form -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Reset Password</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="reset_password">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           placeholder="Min 8 characters" required>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter new password" required>
                </div>
            </div>
            
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This will reset the user's password immediately. The user will need to use the new password to log in.
            </div>
            
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-key me-2"></i>Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
