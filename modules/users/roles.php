<?php
$pageTitle = 'System Roles';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Role-based access control - Admin only
checkRole(['Admin']);

$user = getCurrentUser();

// Get all roles
try {
    $stmt = $pdo->prepare("SELECT * FROM roles ORDER BY id ASC");
    $stmt->execute();
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
    error_log("Roles fetch error: " . $e->getMessage());
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>System Roles</h2>
        <p class="text-muted mb-0">View system user roles (read-only)</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
</div>

<!-- Info Alert -->
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Note:</strong> Roles are fixed in the system. To modify permissions, please contact the system administrator.
</div>

<!-- Roles Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($roles)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-badge fs-1 text-muted"></i>
                <p class="text-muted mt-3">No roles found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo $role['id']; ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo match($role['role_name']) {
                                            'Admin' => 'bg-danger',
                                            'Doctor' => 'bg-primary',
                                            'Receptionist' => 'bg-info',
                                            'Patient' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($role['description'] ?? '--'); ?></td>
                                <td><?php echo htmlspecialchars($role['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
