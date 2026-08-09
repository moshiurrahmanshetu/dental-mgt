<?php
$pageTitle = 'View Appointment';
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

// Get appointment ID
$appointmentId = intval($_GET['id'] ?? 0);
if ($appointmentId <= 0) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.patient_code, 
                      p.phone as patient_phone, p.email as patient_email, p.address as patient_address,
                      p.profile_photo as patient_photo, p.date_of_birth as patient_dob,
                      d.full_name as doctor_name, d.email as doctor_email,
                      u.full_name as created_by_name
                      FROM appointments a 
                      JOIN patients p ON a.patient_id = p.id 
                      JOIN users d ON a.doctor_id = d.id 
                      LEFT JOIN users u ON a.created_by = u.id
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Doctor isolation check - doctors can only see their own appointments
if ($currentRole === 'Doctor' && $appointment['doctor_id'] != $_SESSION['user_id']) {
    die('<div class="alert alert-danger">Access Denied. You can only view your own appointments.</div>');
}

// Check if user can manage appointments
$canManage = in_array($currentRole, ['Admin', 'Receptionist']);
$canComplete = in_array($currentRole, ['Admin', 'Doctor']);

// Get allowed status transitions for current role
$allowedTransitions = getAllowedStatusTransitions($currentRole);

// Success message from redirect
$success = $_GET['success'] ?? '';
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Appointment Details</h2>
        <p class="text-muted mb-0">Code: <?php echo htmlspecialchars($appointment['appointment_code']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/appointments/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Appointment Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Appointment Information</h5>
                <span class="badge <?php echo getStatusBadgeClass($appointment['status']); ?> fs-6">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Appointment Code</label>
                        <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($appointment['appointment_code']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Appointment Type</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['appointment_type'] ?? '--'); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Date</label>
                        <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Time</label>
                        <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($appointment['appointment_time']); ?></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Reason for Visit</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['reason'] ?? '--'); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Additional Notes</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['notes'] ?? '--'); ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Created By</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['created_by_name'] ?? '--'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Created At</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['created_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Management Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Status Management</h5>
            </div>
            <div class="card-body">
                <?php if (in_array($appointment['status'], ['Pending', 'Confirmed']) && $canManage): ?>
                    <div class="d-flex gap-2">
                        <?php if ($appointment['status'] === 'Pending'): ?>
                            <button type="button" class="btn btn-primary" onclick="updateStatus(<?php echo $appointmentId; ?>, 'Confirmed')">
                                <i class="bi bi-check-circle me-2"></i>Confirm Appointment
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger" onclick="confirmCancel(<?php echo $appointmentId; ?>, '<?php echo htmlspecialchars($appointment['appointment_code']); ?>')">
                            <i class="bi bi-x-circle me-2"></i>Cancel Appointment
                        </button>
                    </div>
                <?php elseif ($appointment['status'] === 'Confirmed' && $canComplete): ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="updateStatus(<?php echo $appointmentId; ?>, 'Completed')">
                            <i class="bi bi-check-circle-fill me-2"></i>Mark as Completed
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="updateStatus(<?php echo $appointmentId; ?>, 'No Show')">
                            <i class="bi bi-person-x me-2"></i>Mark as No Show
                        </button>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No status changes available for <?php echo htmlspecialchars($appointment['status']); ?> appointments.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Treatment Record Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Treatment Record</h5>
            </div>
            <div class="card-body">
                <?php
                // Check if a treatment record already exists for this appointment
                $stmt = $pdo->prepare("SELECT id, record_code FROM treatment_records WHERE appointment_id = ?");
                $stmt->execute([$appointmentId]);
                $existingTreatment = $stmt->fetch();
                
                if ($existingTreatment): ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        Treatment record already created.
                        <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $existingTreatment['id']; ?>" 
                           class="alert-link">View Record</a>
                    </div>
                <?php elseif ($appointment['status'] === 'Completed' && $appointment['doctor_id'] == $_SESSION['user_id']): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">Add treatment record for this completed appointment</p>
                        <a href="<?php echo BASE_URL; ?>modules/treatments/add.php?appointment_id=<?php echo $appointmentId; ?>" 
                           class="btn btn-primary btn-lg">
                            <i class="bi bi-file-medical me-2"></i>Add Treatment Record
                        </a>
                    </div>
                <?php elseif ($appointment['status'] !== 'Completed'): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Treatment records can only be added for completed appointments.</p>
                    </div>
                <?php elseif ($appointment['doctor_id'] != $_SESSION['user_id']): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-lock fs-1 text-muted"></i>
                        <p class="text-muted mt-3">Only the assigned doctor can add treatment records.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-file-medical fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No treatment record available for this appointment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Patient Mini Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Patient Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <?php if ($appointment['patient_photo']): ?>
                        <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($appointment['patient_photo']); ?>" 
                             alt="Patient Photo" class="rounded-circle" width="80" height="80">
                    <?php else: ?>
                        <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px; background-color: var(--primary-color); color: white; font-size: 32px;">
                            <i class="bi bi-person"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h5 class="text-center mb-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></h5>
                <p class="text-center text-muted mb-3"><?php echo htmlspecialchars($appointment['patient_code']); ?></p>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Phone</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Email</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['patient_email'] ?? '--'); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Address</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['patient_address'] ?? '--'); ?></p>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $appointment['patient_id']; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person me-2"></i>View Full Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Doctor Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Doctor Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label text-muted small">Doctor Name</label>
                    <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Email</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['doctor_email']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($canManage && in_array($appointment['status'], ['Pending', 'Confirmed'])): ?>
                        <a href="<?php echo BASE_URL; ?>modules/appointments/edit.php?id=<?php echo $appointmentId; ?>" 
                           class="btn btn-warning">
                            <i class="bi bi-pencil me-2"></i>Edit Appointment
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $appointment['patient_id']; ?>" 
                       class="btn btn-info">
                        <i class="bi bi-person me-2"></i>View Patient History
                    </a>
                </div>
            </div>
        </div>
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

function updateStatus(appointmentId, newStatus) {
    if (!confirm('Are you sure you want to change the status to ' + newStatus + '?')) {
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>modules/appointments/update-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'appointment_id=' + appointmentId + '&new_status=' + newStatus + '&csrf_token=<?php echo generateCsrfToken(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update status. Please try again.');
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
