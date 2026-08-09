<?php
$pageTitle = 'Treatment Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Doctor']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$doctorFilter = intval($_GET['doctor_id'] ?? 0);
$treatmentTypeFilter = trim($_GET['treatment_type'] ?? '');
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["1=1"];
$params = [];

// Date range filter
$dateCondition = buildDateRangeCondition('tr.visit_date', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

// Doctor filter - restricted based on role
if ($user['role_name'] === 'Doctor') {
    // Doctors can only see their own records
    $conditions[] = 'tr.doctor_id = ?';
    $params[] = $_SESSION['user_id'];
} elseif ($doctorFilter > 0) {
    // Admin can filter by doctor
    $conditions[] = 'tr.doctor_id = ?';
    $params[] = $doctorFilter;
}

// Treatment type filter
if (!empty($treatmentTypeFilter)) {
    $conditions[] = 'ti.treatment_name LIKE ?';
    $params[] = '%' . $treatmentTypeFilter . '%';
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get treatment records
$query = "SELECT tr.record_code, p.full_name as patient_name, u.full_name as doctor_name, 
          tr.visit_date, GROUP_CONCAT(ti.treatment_name SEPARATOR ', ') as treatments_performed,
          tr.diagnosis
          FROM treatment_records tr
          JOIN patients p ON tr.patient_id = p.id
          JOIN users u ON tr.doctor_id = u.id
          LEFT JOIN treatment_items ti ON tr.id = ti.treatment_record_id
          $whereClause
          GROUP BY tr.id
          ORDER BY tr.visit_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$treatments = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(DISTINCT tr.id) as total FROM treatment_records tr 
               LEFT JOIN treatment_items ti ON tr.id = ti.treatment_record_id 
               $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalTreatments = $totalResult['total'];

// Get doctors for filter (Admin only)
$doctors = [];
if ($user['role_name'] === 'Admin') {
    $stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Doctor' AND u.status = 'active' ORDER BY u.full_name ASC");
    $stmt->execute();
    $doctors = $stmt->fetchAll();
}

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($treatments as $treatment) {
        $csvData[] = [
            $treatment['record_code'],
            $treatment['patient_name'],
            $treatment['doctor_name'],
            formatDate($treatment['visit_date']),
            $treatment['treatments_performed'],
            substr($treatment['diagnosis'], 0, 100)
        ];
    }
    
    $headers = ['Record Code', 'Patient', 'Doctor', 'Visit Date', 'Treatments Performed', 'Diagnosis'];
    $filename = 'treatment_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Treatment Report</h2>
        <p class="text-muted mb-0">Dental treatment records report</p>
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
                        <i class="bi bi-clipboard-check fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Total Treatments</h6>
                        <h4 class="mb-0"><?php echo $totalTreatments; ?></h4>
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
            
            <?php if ($user['role_name'] === 'Admin'): ?>
                <div class="col-md-2">
                    <label for="doctor_id" class="form-label">Doctor</label>
                    <select class="form-select" id="doctor_id" name="doctor_id">
                        <option value="">All Doctors</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo $doctorFilter == $doctor['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-2">
                <label for="treatment_type" class="form-label">Treatment Type</label>
                <input type="text" class="form-control" id="treatment_type" name="treatment_type" 
                       placeholder="Search treatment..." 
                       value="<?php echo htmlspecialchars($treatmentTypeFilter); ?>">
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
        <?php if (empty($treatments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard-check fs-1 text-muted"></i>
                <p class="text-muted mt-3">No treatment records found for the selected criteria</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Record Code</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Visit Date</th>
                            <th>Treatments Performed</th>
                            <th>Diagnosis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($treatments as $treatment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($treatment['record_code']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['doctor_name']); ?></td>
                                <td><?php echo formatDate($treatment['visit_date']); ?></td>
                                <td><?php echo htmlspecialchars($treatment['treatments_performed']); ?></td>
                                <td><?php echo htmlspecialchars(substr($treatment['diagnosis'], 0, 50)) . (strlen($treatment['diagnosis']) > 50 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
