<?php
// Step 3: Admin Account & App Settings
require_once INSTALLER_ROOT . '/bootstrap.php';

// Session keys used in this file (Rule 1 documentation):
// $_SESSION['installer']['app_name']
// $_SESSION['installer']['admin_full_name']
// $_SESSION['installer']['admin_email']
// $_SESSION['installer']['admin_password']

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = trim($_POST['app_name'] ?? '');
    $adminFullName = trim($_POST['admin_full_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
    
    // Validate fields
    if (empty($appName)) {
        $error = 'Application name is required.';
    } elseif (empty($adminFullName)) {
        $error = 'Admin full name is required.';
    } elseif (empty($adminEmail)) {
        $error = 'Admin email is required.';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (empty($adminPassword)) {
        $error = 'Admin password is required.';
    } elseif (strlen($adminPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $adminPassword) || !preg_match('/[0-9]/', $adminPassword)) {
        $error = 'Password must contain at least one letter and one number.';
    } elseif ($adminPassword !== $adminPasswordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        // Save to session (Rule 1: using installer session namespace)
        setInstallerSession('app_name', $appName);
        setInstallerSession('admin_full_name', $adminFullName);
        setInstallerSession('admin_email', $adminEmail);
        setInstallerSession('admin_password', $adminPassword);
        setInstallerSession('step3', true);
        
        // Redirect to step 4
        header('Location: index.php?step=4');
        exit();
    }
}

// Pre-fill form with saved values
$appName = getInstallerSession('app_name', 'Dental Management System');
$adminFullName = getInstallerSession('admin_full_name', '');
$adminEmail = getInstallerSession('admin_email', '');
?>

<h3>Admin Account & Application Settings</h3>
<p>Set up your administrator account and application name.</p>

<?php if ($error): ?>
    <div class="error-message">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<form method="POST" action="" onsubmit="return validateStep3();">
    <div class="mb-3">
        <label for="app_name" class="form-label">Application Name</label>
        <input type="text" class="form-control" id="app_name" name="app_name" 
               value="<?php echo htmlspecialchars($appName); ?>" required>
    </div>
    
    <hr>
    
    <h5>Administrator Account</h5>
    
    <div class="mb-3">
        <label for="admin_full_name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="admin_full_name" name="admin_full_name" 
               value="<?php echo htmlspecialchars($adminFullName); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="admin_email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="admin_email" name="admin_email" 
               value="<?php echo htmlspecialchars($adminEmail); ?>" required>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="admin_password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="admin_password" name="admin_password" 
                       required minlength="8">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('admin_password')">
                    <i class="bi bi-eye" id="admin_password_icon"></i>
                </button>
            </div>
            <div class="form-text">
                Minimum 8 characters, must include at least one letter and one number.
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <label for="admin_password_confirm" class="form-label">Confirm Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" 
                       required minlength="8">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('admin_password_confirm')">
                    <i class="bi bi-eye" id="admin_password_confirm_icon"></i>
                </button>
            </div>
        </div>
    </div>
    
    <div class="navigation-buttons">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php?step=2'">
            <i class="bi bi-arrow-left"></i> Previous
        </button>
        <button type="submit" class="btn btn-primary">
            Next <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function validateStep3() {
    const appName = document.getElementById('app_name').value;
    const adminFullName = document.getElementById('admin_full_name').value;
    const adminEmail = document.getElementById('admin_email').value;
    const adminPassword = document.getElementById('admin_password').value;
    const adminPasswordConfirm = document.getElementById('admin_password_confirm').value;
    
    if (!appName) {
        alert('Please enter an application name.');
        return false;
    }
    
    if (!adminFullName) {
        alert('Please enter admin full name.');
        return false;
    }
    
    if (!adminEmail) {
        alert('Please enter admin email.');
        return false;
    }
    
    if (!adminPassword) {
        alert('Please enter admin password.');
        return false;
    }
    
    if (adminPassword.length < 8) {
        alert('Password must be at least 8 characters long.');
        return false;
    }
    
    if (!/[A-Za-z]/.test(adminPassword) || !/[0-9]/.test(adminPassword)) {
        alert('Password must contain at least one letter and one number.');
        return false;
    }
    
    if (adminPassword !== adminPasswordConfirm) {
        alert('Passwords do not match.');
        return false;
    }
    
    return true;
}
</script>
