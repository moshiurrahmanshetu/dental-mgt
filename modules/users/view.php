<?php
$pageTitle = 'View User';
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

// Get recent activity for this user
$stmt = $pdo->prepare("SELECT al.*, u.full_name as actor_name 
                      FROM activity_logs al 
                      JOIN users u ON al.user_id = u.id 
                      WHERE al.description LIKE ? OR al.description LIKE ?
                      ORDER BY al.created_at DESC 
                      LIMIT 15");
$searchTerm = "%User ID: {$userId}%";
$stmt->execute([$searchTerm, $searchTerm]);
$recentActivity = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>User Profile</h2>
        <p class="text-muted mb-0">View user account details</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Users
    </a>
</div>

<!-- User Profile Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center mb-4">
            <?php if ($userData['avatar']): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/users/<?php echo htmlspecialchars($userData['avatar']); ?>" 
                     alt="Avatar" class="rounded-circle me-3" width="120" height="120">
            <?php else: ?>
                <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                     style="width: 120px; height: 120px; background-color: var(--primary-color); color: white; font-size: 48px;">
                    <?php echo getUserInitials($userData['full_name']); ?>
                </div>
            <?php endif; ?>
            <div>
                <h3 class="mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h3>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($userData['email']); ?></p>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($userData['phone'] ?? '--'); ?></p>
                <div class="mt-2">
                    <span class="badge <?php echo getRoleBadgeClass($userData['role_name']); ?>">
                        <?php echo htmlspecialchars($userData['role_name']); ?>
                    </span>
                    <span class="badge <?php echo getStatusBadgeClass($userData['status']); ?> ms-2">
                        <?php echo htmlspecialchars($userData['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>Created At:</strong> <?php echo htmlspecialchars($userData['created_at']); ?></p>
                <p class="mb-1"><strong>Last Login:</strong> <?php echo $userData['last_login'] ? htmlspecialchars($userData['last_login']) : 'Never'; ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Account ID:</strong> <?php echo $userData['id']; ?></p>
                <p class="mb-1"><strong>Role ID:</strong> <?php echo $userData['role_id']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Recent Activity</h5>
    </div>
    <div class="card-body">
        <?php if (empty($recentActivity)): ?>
            <p class="text-muted">No recent activity found for this user.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['created_at']); ?></td>
                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                <td><?php echo htmlspecialchars($activity['actor_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
