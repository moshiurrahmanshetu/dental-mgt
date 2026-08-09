<?php
$pageTitle = 'Add User';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - Admin only
checkRole(['Admin']);

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
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $roleId = intval($_POST['role_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        
        // Validation
        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (empty($email)) {
            $error = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (empty($password)) {
            $error = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif ($roleId <= 0) {
            $error = 'Please select a role.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already exists.';
            } else {
                try {
                    // Handle avatar upload
                    $avatarFilename = null;
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $avatarFilename = uploadAvatar($_FILES['avatar'], __DIR__ . '/../../assets/images/users/');
                        if (!$avatarFilename) {
                            $error = 'Failed to upload avatar. Please check file type (JPG/PNG) and size (max 2MB).';
                        }
                    }
                    
                    if (!$error) {
                        // Hash password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert user
                        $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, phone, password, avatar, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'active')");
                        
                        $stmt->execute([$roleId, $fullName, $email, $phone, $hashedPassword, $avatarFilename]);
                        
                        // Log activity
                        $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
                        $stmt->execute([$roleId]);
                        $role = $stmt->fetch();
                        logActivity('User Created', "User created: {$fullName} (Role: {$role['role_name']})");
                        
                        $success = "User created successfully!";
                        header("Location: " . BASE_URL . "modules/users/list.php?success=" . urlencode($success));
                        exit();
                    }
                } catch (PDOException $e) {
                    error_log("User Creation Error: " . $e->getMessage());
                    $error = 'Failed to create user. Please try again.';
                }
            }
        }
    }
}

// Get available roles (Admin, Doctor, Receptionist - not Patient)
$stmt = $pdo->prepare("SELECT id, role_name FROM roles WHERE role_name IN ('Admin', 'Doctor', 'Receptionist') ORDER BY role_name ASC");
$stmt->execute();
$roles = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Add User</h2>
        <p class="text-muted mb-0">Create a new system user account</p>
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

<!-- User Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($fullName ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo isset($roleId) && $roleId == $role['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Min 8 characters" required>
                    <small class="text-muted">Must be at least 8 characters long</small>
                </div>
                
                <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter password" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="avatar" class="form-label">Avatar (Optional)</label>
                <input type="file" class="form-control" id="avatar" name="avatar" accept="image/jpeg,image/jpg,image/png">
                <small class="text-muted">JPG or PNG, max 2MB</small>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
