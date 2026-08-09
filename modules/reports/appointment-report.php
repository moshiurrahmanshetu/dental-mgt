<?php
$pageTitle = 'Appointment Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$doctorFilter = intval($_GET['doctor_id'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["1=1"];
$params = [];

// Date range filter
$dateCondition = buildDateRangeCondition('a.appointment_date', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

// Doctor filter
if ($doctorFilter > 0) {
    $conditions[] = 'a.doctor_id = ?';
    $params[] = $doctorFilter;
}

// Status filter
if (!empty($statusFilter)) {
    $conditions[] = 'a.status = ?';
    $params[] = $statusFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get appointments
$query = "SELECT a.appointment_code, p.full_name as patient_name, u.full_name as doctor_name, 
          a.appointment_date, a.appointment_time, a.status 
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN users u ON a.doctor_id = u.id 
          $whereClause 
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get status counts
$statusCounts = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Completed' => 0,
    'Cancelled' => 0,
    'No Show' => 0
];

foreach ($appointments as $appointment) {
    $status = $appointment['status'];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

// Get doctors for filter
$stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Doctor' AND u.status = 'active' ORDER BY u.full_name ASC");
$stmt->execute();
$doctors = $stmt->fetchAll();

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($appointments as $appointment) {
        $csvData[] = [
            $appointment['appointment_code'],
            $appointment['patient_name'],
            $appointment['doctor_name'],
            formatDate($appointment['appointment_date']),
            $appointment['appointment_time'],
            $appointment['status']
        ];
    }
    
    $headers = ['Appointment Code', 'Patient', 'Doctor', 'Date', 'Time', 'Status'];
    $filename = 'appointment_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Appointment Report</h2>
        <p class="text-muted mb-0">Appointment schedule report</p>
    </div>
    <a href="<?php echo BASE_URL; ?>dashboard/<?php echo strtolower($user['role_name']); ?>.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Pending</h6>
                <h4 class="mb-0 text-warning"><?php echo $statusCounts['Pending']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Confirmed</h6>
                <h4 class="mb-0 text-info"><?php echo $statusCounts['Confirmed']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Completed</h6>
                <h4 class="mb-0 text-success"><?php echo $statusCounts['Completed']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Cancelled</h6>
                <h4 class="mb-0 text-danger"><?php echo $statusCounts['Cancelled']; ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">No Show</h6>
                <h4 class="mb-0 text-secondary"><?php echo $statusCounts['No Show']; ?></h4>
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
            
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Confirmed" <?php echo $statusFilter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="No Show" <?php echo $statusFilter === 'No Show' ? 'selected' : ''; ?>>No Show</option>
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
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-event fs-1 text-muted"></i>
                <p class="text-muted mt-3">No appointments found for the selected criteria</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Appointment Code</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_code']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo formatDate($appointment['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo match($appointment['status']) {
                                            'Pending' => 'bg-warning',
                                            'Confirmed' => 'bg-info',
                                            'Completed' => 'bg-success',
                                            'Cancelled' => 'bg-danger',
                                            'No Show' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
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
