<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - Admin only
checkRole(['Admin']);

$user = getCurrentUser();

// Filter parameters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = ["r.role_name != 'Patient'"]; // Exclude patients
$params = [];

if (!empty($search)) {
    $conditions[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($roleFilter)) {
    $conditions[] = 'r.role_name = ?';
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $conditions[] = 'u.status = ?';
    $params[] = $statusFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get users with pagination
$query = "SELECT u.*, r.role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          $whereClause 
          ORDER BY u.created_at DESC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>User Management</h2>
        <p class="text-muted mb-0">Manage system user accounts</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/users/add.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add User
    </a>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Name, email, or phone" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="">All Roles</option>
                    <option value="Admin" <?php echo $roleFilter === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="Doctor" <?php echo $roleFilter === 'Doctor' ? 'selected' : ''; ?>>Doctor</option>
                    <option value="Receptionist" <?php echo $roleFilter === 'Receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted"></i>
                <p class="text-muted mt-3">No users found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userItem): ?>
                            <tr>
                                <td>
                                    <?php if ($userItem['avatar']): ?>
                                        <img src="<?php echo BASE_URL; ?>assets/images/users/<?php echo htmlspecialchars($userItem['avatar']); ?>" 
                                             alt="Avatar" class="rounded-circle" width="40" height="40">
                                    <?php else: ?>
                                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; background-color: var(--primary-color); color: white; font-size: 16px;">
                                            <?php echo getUserInitials($userItem['full_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>modules/users/view.php?id=<?php echo $userItem['id']; ?>" 
                                       class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($userItem['full_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                                <td><?php echo htmlspecialchars($userItem['phone'] ?? '--'); ?></td>
                                <td>
                                    <span class="badge <?php echo getRoleBadgeClass($userItem['role_name']); ?>">
                                        <?php echo htmlspecialchars($userItem['role_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($userItem['status']); ?>">
                                        <?php echo htmlspecialchars($userItem['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $userItem['last_login'] ? htmlspecialchars($userItem['last_login']) : 'Never'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>modules/users/view.php?id=<?php echo $userItem['id']; ?>" 
                                           class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>modules/users/edit.php?id=<?php echo $userItem['id']; ?>" 
                                           class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($userItem['id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm <?php echo $userItem['status'] === 'active' ? 'btn-danger' : 'btn-success'; ?>" 
                                                    onclick="toggleUserStatus(<?php echo $userItem['id']; ?>, '<?php echo $userItem['status']; ?>')">
                                                <i class="bi bi-<?php echo $userItem['status'] === 'active' ? 'x-circle' : 'check-circle'; ?>"></i>
                                            <?php echo $userItem['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Users pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        fetch('<?php echo BASE_URL; ?>modules/users/toggle-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&status=${newStatus}&csrf_token=<?php echo generateCsrfToken(); ?>`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Action failed');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
