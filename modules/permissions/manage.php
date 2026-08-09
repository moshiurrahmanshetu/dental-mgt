<?php
$pageTitle = 'Permission Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - Admin only
checkRole(['Admin']);

$user = getCurrentUser();
$success = '';
$error = '';

// Get all roles
try {
    $stmt = $pdo->query("SELECT id, role_name FROM roles ORDER BY id ASC");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load roles.';
    $roles = [];
}

// Get all permissions grouped by module
try {
    $stmt = $pdo->query("SELECT * FROM permissions ORDER BY module_name ASC, permission_key ASC");
    $permissions = $stmt->fetchAll();
    
    // Group by module
    $groupedPermissions = [];
    foreach ($permissions as $permission) {
        $groupedPermissions[$permission['module_name']][] = $permission;
    }
} catch (PDOException $e) {
    $error = 'Failed to load permissions.';
    $groupedPermissions = [];
}

// Get current role permissions
try {
    $stmt = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
    $rolePermissions = $stmt->fetchAll();
    
    // Build lookup array
    $permissionLookup = [];
    foreach ($rolePermissions as $rp) {
        $permissionLookup[$rp['role_id']][$rp['permission_id']] = true;
    }
} catch (PDOException $e) {
    $error = 'Failed to load current permissions.';
    $permissionLookup = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Delete all existing role permissions
            $stmt = $pdo->query("DELETE FROM role_permissions");
            
            // Insert new permissions
            $insertStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            
            foreach ($_POST['permissions'] ?? [] as $roleId => $permissionIds) {
                foreach ($permissionIds as $permissionId) {
                    // Validate that role_id and permission_id exist
                    $roleId = intval($roleId);
                    $permissionId = intval($permissionId);
                    
                    if ($roleId > 0 && $permissionId > 0) {
                        $insertStmt->execute([$roleId, $permissionId]);
                    }
                }
            }
            
            $pdo->commit();
            
            // Log activity
            logActivity('Permissions Updated', 'Admin updated role permissions');
            
            $success = "Permissions updated successfully! Changes will apply on next login.";
            
            // Refresh permission lookup
            $stmt = $pdo->query("SELECT role_id, permission_id FROM role_permissions");
            $rolePermissions = $stmt->fetchAll();
            $permissionLookup = [];
            foreach ($rolePermissions as $rp) {
                $permissionLookup[$rp['role_id']][$rp['permission_id']] = true;
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Permission update error: " . $e->getMessage());
            $error = 'Failed to update permissions. Please try again.';
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Permission Management</h2>
        <p class="text-muted mb-0">Manage role-based access permissions</p>
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

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Info Alert -->
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Note:</strong> Permission changes will apply on the next login for affected users. This is to ensure session consistency.
</div>

<!-- Permission Matrix -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="min-width: 250px;">Permission</th>
                            <?php foreach ($roles as $role): ?>
                                <th style="min-width: 100px; text-align: center;">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                    <br>
                                    <small class="text-white-50">
                                        <input type="checkbox" class="select-all-role" data-role-id="<?php echo $role['id']; ?>">
                                        Select All
                                    </small>
                                    </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedPermissions as $moduleName => $modulePermissions): ?>
                            <tr class="table-light">
                                <td colspan="<?php echo count($roles) + 1; ?>" class="fw-bold">
                                    <?php echo htmlspecialchars($moduleName); ?>
                                </td>
                            </tr>
                            <?php foreach ($modulePermissions as $permission): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($permission['permission_key']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($permission['description']); ?></small>
                                    </td>
                                    <?php foreach ($roles as $role): ?>
                                        <td style="text-align: center;">
                                            <input type="checkbox" 
                                                   name="permissions[<?php echo $role['id']; ?>][]" 
                                                   value="<?php echo $permission['id']; ?>"
                                                   class="permission-checkbox"
                                                   data-role-id="<?php echo $role['id']; ?>"
                                                   <?php echo isset($permissionLookup[$role['id']][$permission['id']]) ? 'checked' : ''; ?>>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to save these permission changes? Changes will apply on next login.');">
                    <i class="bi bi-check-lg me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Select All functionality for each role column
document.querySelectorAll('.select-all-role').forEach(function(selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const roleId = this.dataset.roleId;
        const roleCheckboxes = document.querySelectorAll('.permission-checkbox[data-role-id="' + roleId + '"]');
        roleCheckboxes.forEach(function(checkbox) {
            checkbox.checked = selectAllCheckbox.checked;
        });
    });
});

// Update select-all checkbox state when individual checkboxes change
document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const roleId = this.dataset.roleId;
        const roleCheckboxes = document.querySelectorAll('.permission-checkbox[data-role-id="' + roleId + '"]');
        const selectAllCheckbox = document.querySelector('.select-all-role[data-role-id="' + roleId + '"]');
        
        const allChecked = Array.from(roleCheckboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
