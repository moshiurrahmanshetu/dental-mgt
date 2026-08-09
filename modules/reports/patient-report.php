<?php
$pageTitle = 'Patient Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Doctor']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$genderFilter = trim($_GET['gender'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["1=1"];
$params = [];

// Date range filter
$dateCondition = buildDateRangeCondition('created_at', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

// Gender filter
if (!empty($genderFilter)) {
    $conditions[] = 'gender = ?';
    $params[] = $genderFilter;
}

// Status filter
if (!empty($statusFilter)) {
    $conditions[] = 'status = ?';
    $params[] = $statusFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get patients
$query = "SELECT patient_code, full_name, gender, phone, created_at, status 
          FROM patients 
          $whereClause 
          ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM patients $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalPatients = $totalResult['total'];

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($patients as $patient) {
        $csvData[] = [
            $patient['patient_code'],
            $patient['full_name'],
            $patient['gender'],
            $patient['phone'],
            formatDate($patient['created_at']),
            $patient['status']
        ];
    }
    
    $headers = ['Patient Code', 'Full Name', 'Gender', 'Phone', 'Registration Date', 'Status'];
    $filename = 'patient_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Patient Report</h2>
        <p class="text-muted mb-0">Patient registration report</p>
    </div>
    <a href="<?php echo BASE_URL; ?>dashboard/<?php echo strtolower($user['role_name']); ?>.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Summary Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 50px; height: 50px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Total Patients</h6>
                        <h4 class="mb-0"><?php echo $totalPatients; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" 
                       value="<?php echo htmlspecialchars($dateRange['from_date']); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" 
                       value="<?php echo htmlspecialchars($dateRange['to_date']); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-select" id="gender" name="gender">
                    <option value="">All</option>
                    <option value="Male" <?php echo $genderFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $genderFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $genderFilter === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Results</h5>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-csv me-2"></i>Export CSV
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($patients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted"></i>
                <p class="text-muted mt-3">No patients found for the selected criteria</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Patient Code</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patient_code']); ?></td>
                                <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone'] ?? '--'); ?></td>
                                <td><?php echo formatDate($patient['created_at']); ?></td>
                                <td>
                                    <span class="badge <?php echo $patient['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo htmlspecialchars($patient['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
