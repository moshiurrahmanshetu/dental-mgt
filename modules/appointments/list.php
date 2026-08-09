<?php
$pageTitle = 'Appointment Management';
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

// Check if user can manage appointments
$canManage = in_array($currentRole, ['Admin', 'Receptionist']);
$canEditStatus = in_array($currentRole, ['Admin', 'Doctor', 'Receptionist']);

// Filter parameters
$doctorFilter = intval($_GET['doctor'] ?? 0);
$patientSearch = trim($_GET['patient'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

// Doctor isolation for doctors
if ($currentRole === 'Doctor') {
    $conditions[] = 'a.doctor_id = ?';
    $params[] = $_SESSION['user_id'];
} elseif ($doctorFilter > 0) {
    $conditions[] = 'a.doctor_id = ?';
    $params[] = $doctorFilter;
}

if (!empty($statusFilter)) {
    $conditions[] = 'a.status = ?';
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = 'a.appointment_date >= ?';
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = 'a.appointment_date <= ?';
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
$countQuery = "SELECT COUNT(*) as total FROM appointments a 
                  JOIN patients p ON a.patient_id = p.id 
                  JOIN users d ON a.doctor_id = d.id 
                  $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalAppointments = $totalResult['total'];
$totalPages = ceil($totalAppointments / $perPage);

// Get appointments with pagination
$query = "SELECT a.*, p.full_name as patient_name, p.patient_code, 
          p.phone as patient_phone, p.profile_photo as patient_photo,
          d.full_name as doctor_name, d.email as doctor_email
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN users d ON a.doctor_id = d.id 
          $whereClause 
          ORDER BY a.appointment_date DESC, a.appointment_time DESC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get available doctors for filter dropdown
$doctors = getAvailableDoctors($pdo);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Appointment Management</h2>
        <p class="text-muted mb-0">Manage appointment scheduling and status</p>
    </div>
    <?php if ($canManage): ?>
        <a href="<?php echo BASE_URL; ?>modules/appointments/add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Book Appointment
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

<!-- Appointments Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="text-muted mt-3">No appointments found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($appointment['appointment_code']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($appointment['patient_photo']): ?>
                                            <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($appointment['patient_photo']); ?>" 
                                                 alt="Patient Photo" class="rounded-circle me-2" width="32" height="32">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; background-color: var(--primary-color); color: white; font-size: 14px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $appointment['patient_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                            </a>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($appointment['patient_code']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_type'] ?? '--'); ?></td>
                                <td>
                                    <span class="badge <?php echo getStatusBadgeClass($appointment['status']); ?>">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canManage && in_array($appointment['status'], ['Pending', 'Confirmed'])): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/appointments/edit.php?id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($canManage && in_array($appointment['status'], ['Pending', 'Confirmed'])): ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="confirmCancel(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['appointment_code']); ?>')"
                                                    title="Cancel">
                                                <i class="bi bi-x-circle"></i>
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
                <nav aria-label="Appointment pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&status=<?php echo $statusFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&status=<?php echo $statusFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&doctor=<?php echo $doctorFilter; ?>&patient=<?php echo urlencode($patientSearch); ?>&status=<?php echo $statusFilter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel appointment <strong id="cancelAppointmentCode"></strong>?</p>
                <p class="text-muted small">This will set the appointment status to Cancelled.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form id="cancelForm" method="POST" action="<?php echo BASE_URL; ?>modules/appointments/cancel.php">
                    <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-danger">Cancel Appointment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCancel(appointmentId, appointmentCode) {
    document.getElementById('cancelAppointmentId').value = appointmentId;
    document.getElementById('cancelAppointmentCode').textContent = appointmentCode;
    var cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    cancelModal.show();
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
