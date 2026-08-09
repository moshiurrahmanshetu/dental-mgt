<?php
$pageTitle = 'Treatment Records';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
$currentRole = $_SESSION['role_name'];
if ($currentRole === 'Patient') {
    die('<div class="alert alert-danger">Access Denied. Patients cannot access this page.</div>');
}

requireAuth();

$user = getCurrentUser();

// Check if user can edit
$canEdit = in_array($currentRole, ['Admin', 'Doctor']);
$canViewOnly = $currentRole === 'Receptionist';

// Filter parameters
$doctorFilter = intval($_GET['doctor'] ?? 0);
$patientSearch = trim($_GET['patient'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

// Doctor isolation for doctors
if ($currentRole === 'Doctor') {
    $conditions[] = 'tr.doctor_id = ?';
    $params[] = $_SESSION['user_id'];
} elseif ($doctorFilter > 0) {
    $conditions[] = 'tr.doctor_id = ?';
    $params[] = $doctorFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = 'tr.visit_date >= ?';
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = 'tr.visit_date <= ?';
    $params[] = $dateTo;
}

if (!empty($patientSearch)) {
    $conditions[] = '(p.full_name LIKE ? OR p.phone LIKE ? OR p.patient_code LIKE ?)';
    $searchParam = '%' . $patientSearch . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM treatment_records tr 
                  JOIN patients p ON tr.patient_id = p.id 
                  JOIN users d ON tr.doctor_id = d.id 
                  $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get treatment records with pagination
$query = "SELECT tr.*, p.full_name as patient_name, p.patient_code, 
          p.phone as patient_phone, p.profile_photo as patient_photo,
          d.full_name as doctor_name, d.email as doctor_email
          FROM treatment_records tr 
          JOIN patients p ON tr.patient_id = p.id 
          JOIN users d ON tr.doctor_id = d.id 
          $whereClause 
          ORDER BY tr.visit_date DESC, tr.created_at DESC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get available doctors for filter dropdown
$stmt = $pdo->prepare("SELECT u.id, u.full_name FROM users u 
                          JOIN roles r ON u.role_id = r.id 
                          WHERE r.role_name = 'Doctor' AND u.status = 'active' 
                          ORDER BY u.full_name ASC");
$stmt->execute();
$doctors = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Treatment Records</h2>
        <p class="text-muted mb-0">Manage dental treatment records and medical history</p>
    </div>
    <?php if ($canEdit): ?>
        <a href="<?php echo BASE_URL; ?>modules/appointments/today.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Treatment
        </a>
    <?php endif; ?>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-2">
                <label for="doctor" class="form-label">Doctor</label>
                <select class="form-select" id="doctor" name="doctor">
                    <option value="">All Doctors</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>" <?php echo $doctorFilter == $doctor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($doctor['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="patient" class="form-label">Patient</label>
                <input type="text" class="form-control" id="patient" name="patient" 
                       placeholder="Name, phone, or code" 
                       value="<?php echo htmlspecialchars($patientSearch); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Treatment Records Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($records)): ?>
            <div class="text-center py-5">
                <i class="bi bi-file-medical fs-1 text-muted"></i>
                <p class="text-muted mt-3">No treatment records found</p>
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
                            <th>Diagnosis</th>
                            <th>Follow-up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($record['record_code']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($record['patient_photo']): ?>
                                            <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($record['patient_photo']); ?>" 
                                                 alt="Patient Photo" class="rounded-circle me-2" width="32" height="32">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; background-color: var(--primary-color); color: white; font-size: 14px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $record['patient_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($record['patient_name']); ?>
                                            </a>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($record['patient_code']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                                <td><?php echo htmlspecialchars(substr($record['diagnosis'] ?? '', 0, 50)) . (strlen($record['diagnosis'] ?? '') > 50 ? '...' : ''); ?></td>
                                <td>
                                    <?php if ($record['follow_up_date']): ?>
                                        <?php 
                                        $followUpDate = new DateTime($record['follow_up_date']);
                                        $today = new DateTime();
                                        $isOverdue = $followUpDate < $today;
                                        ?>
                                        <span class="<?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                            <?php echo htmlspecialchars($record['follow_up_date']); ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $record['id']; ?>" 
                                           class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canEdit): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/treatments/edit.php?id=<?php echo $record['id']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
                <nav aria-label="Treatment Records pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
