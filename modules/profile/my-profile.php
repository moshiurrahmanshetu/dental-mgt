<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../users/helpers.php';

$user = getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } else {
            try {
                // Handle avatar upload
                $avatarFilename = $user['avatar'];
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $newAvatar = uploadAvatar($_FILES['avatar'], __DIR__ . '/../../assets/images/users/', $user['avatar']);
                    if ($newAvatar) {
                        $avatarFilename = $newAvatar;
                    } else {
                        $error = 'Failed to upload avatar. Please check file type (JPG/PNG) and size (max 2MB).';
                    }
                }
                
                if (!$error) {
                    // Update user
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$fullName, $phone, $avatarFilename, $_SESSION['user_id']]);
                    
                    // Update session variables
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['avatar'] = $avatarFilename;
                    
                    // Log activity
                    logActivity('Profile Updated', "User updated their own profile");
                    
                    $success = "Profile updated successfully!";
                    header("Location: " . BASE_URL . "modules/profile/my-profile.php?success=" . urlencode($success));
                    exit();
                }
            } catch (PDOException $e) {
                error_log("Profile Update Error: " . $e->getMessage());
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// Refresh user data from session
$user = getCurrentUser();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>My Profile</h2>
        <p class="text-muted mb-0">Manage your profile settings</p>
    </div>
    <a href="<?php echo BASE_URL; ?>dashboard/<?php echo strtolower($user['role_name']); ?>.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
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

<!-- Profile Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <?php if ($user['avatar']): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/users/<?php echo htmlspecialchars($user['avatar']); ?>" 
                     alt="Avatar" class="rounded-circle me-3" width="120" height="120" id="currentAvatar">
            <?php else: ?>
                <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                     style="width: 120px; height: 120px; background-color: var(--primary-color); color: white; font-size: 48px;">
                    <?php echo getUserInitials($user['full_name']); ?>
                </div>
            <?php endif; ?>
            <div>
                <h3 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge <?php echo getRoleBadgeClass($user['role_name']); ?> mt-2">
                    <?php echo htmlspecialchars($user['role_name']); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Profile Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    <small class="text-muted">Contact Admin to change email</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            
            <div class="mb-3">
                <label for="avatar" class="form-label">Avatar (Optional)</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png">
                <small class="text-muted">JPG or PNG, max 2MB</small>
            </div>
            
            <div class="mb-3" id="avatarPreview" style="display: none;">
                <label class="form-label">Avatar Preview</label>
                <img id="previewImage" src="" alt="Avatar Preview" class="rounded-circle" width="120" height="120">
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/profile/change-password.php" class="btn btn-warning">
                    <i class="bi bi-key me-2"></i>Change Password
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Avatar preview
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('avatarPreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
