<?php
$pageTitle = 'Upcoming Appointments';
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

// Get today's date
$today = date('Y-m-d');

// Build query conditions
$conditions = ["a.appointment_date > '$today'", "a.status IN ('Pending', 'Confirmed')"];
$params = [];

// Doctor isolation for doctors
if ($currentRole === 'Doctor') {
    $conditions[] = 'a.doctor_id = ?';
    $params[] = $_SESSION['user_id'];
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get upcoming appointments
$query = "SELECT a.*, p.full_name as patient_name, p.patient_code, 
          p.phone as patient_phone, p.profile_photo as patient_photo,
          d.full_name as doctor_name, d.email as doctor_email
          FROM appointments a 
          JOIN patients p ON a.patient_id = p.id 
          JOIN users d ON a.doctor_id = d.id 
          $whereClause 
          ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Upcoming Appointments</h2>
        <p class="text-muted mb-0">Future appointments (Pending & Confirmed)</p>
    </div>
    <?php if ($canManage): ?>
        <a href="<?php echo BASE_URL; ?>modules/appointments/add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Book Appointment
        </a>
    <?php endif; ?>
</div>

<!-- Appointments Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-event fs-1 text-muted"></i>
                <p class="text-muted mt-3">No upcoming appointments found</p>
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
