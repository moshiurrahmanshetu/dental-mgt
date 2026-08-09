<?php
$pageTitle = 'Edit Appointment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can edit appointments
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();
$error = '';
$success = '';

// Get appointment ID
$appointmentId = intval($_GET['id'] ?? 0);
if ($appointmentId <= 0) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.patient_code, 
                      p.phone as patient_phone, d.full_name as doctor_name
                      FROM appointments a 
                      JOIN patients p ON a.patient_id = p.id 
                      JOIN users d ON a.doctor_id = d.id 
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Check if appointment can be edited (not completed or cancelled)
if (in_array($appointment['status'], ['Completed', 'Cancelled'])) {
    $readOnly = true;
    $error = "This appointment is already {$appointment['status']} and cannot be edited.";
} else {
    $readOnly = false;
}

// Get available doctors
$doctors = getAvailableDoctors($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $doctorId = intval($_POST['doctor_id'] ?? 0);
        $appointmentDate = trim($_POST['appointment_date'] ?? '');
        $appointmentTime = trim($_POST['appointment_time'] ?? '');
        $appointmentType = trim($_POST['appointment_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if ($doctorId <= 0) {
            $error = 'Please select a doctor.';
        } elseif (empty($appointmentDate)) {
            $error = 'Please select an appointment date.';
        } elseif (empty($appointmentTime)) {
            $error = 'Please select an appointment time.';
        } elseif (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            $error = 'Appointment date cannot be in the past.';
        } else {
            // Check doctor availability (excluding current appointment)
            if (!checkDoctorAvailability($pdo, $doctorId, $appointmentDate, $appointmentTime, $appointmentId)) {
                $error = 'Doctor already has an appointment at this time. Please choose a different time.';
            } else {
                try {
                    // Update appointment
                    $stmt = $pdo->prepare("UPDATE appointments 
                        SET doctor_id = ?, appointment_date = ?, appointment_time = ?, 
                        appointment_type = ?, reason = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?");
                    
                    $stmt->execute([
                        $doctorId,
                        $appointmentDate,
                        $appointmentTime,
                        $appointmentType,
                        $reason,
                        $notes,
                        $appointmentId
                    ]);
                    
                    // Log activity
                    logActivity('Appointment Rescheduled', "Appointment rescheduled: {$appointment['appointment_code']} to $appointmentDate at $appointmentTime");
                    
                    $success = "Appointment rescheduled successfully!";
                    
                    // Redirect to view.php after successful update
                    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&success=" . urlencode($success));
                    exit();
                    
                } catch (PDOException $e) {
                    error_log("Appointment Edit Error: " . $e->getMessage());
                    $error = 'Failed to update appointment. Please try again.';
                }
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Edit Appointment</h2>
        <p class="text-muted mb-0">Reschedule appointment: <?php echo htmlspecialchars($appointment['appointment_code']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Appointment
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if ($readOnly): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                This appointment is <?php echo htmlspecialchars($appointment['status']); ?> and cannot be edited.
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Patient</label>
                    <p class="form-control-plaintext">
                        <?php echo htmlspecialchars($appointment['patient_name']); ?> 
                        <small class="text-muted">(<?php echo htmlspecialchars($appointment['patient_code']); ?>)</small>
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Doctor</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Date</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['appointment_date']); ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Time</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['appointment_time']); ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Type</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['appointment_type'] ?? '--'); ?></p>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Reason</label>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['reason'] ?? '--'); ?></p>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Notes</label>
                <p class="form-control-plaintext"><?php echo htmlspecialchars($appointment['notes'] ?? '--'); ?></p>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <span class="badge <?php echo getStatusBadgeClass($appointment['status']); ?>">
                    <?php echo htmlspecialchars($appointment['status']); ?>
                </span>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointmentId; ?>" class="btn btn-primary">
                    View Appointment Details
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Patient</label>
                        <p class="form-control-plaintext">
                            <?php echo htmlspecialchars($appointment['patient_name']); ?> 
                            <small class="text-muted">(<?php echo htmlspecialchars($appointment['patient_code']); ?>)</small>
                        </p>
                        <input type="hidden" name="patient_id" value="<?php echo $appointment['patient_id']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="doctor_id" class="form-label fw-bold">Doctor <span class="text-danger">*</span></label>
                        <select class="form-select" id="doctor_id" name="doctor_id" required>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['id']; ?>" 
                                        <?php echo $doctor['id'] == $appointment['doctor_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($doctor['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="appointment_date" class="form-label fw-bold">Appointment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                               value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="appointment_time" class="form-label fw-bold">Appointment Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" id="appointment_time" name="appointment_time" 
                               value="<?php echo htmlspecialchars($appointment['appointment_time']); ?>" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="appointment_type" class="form-label fw-bold">Appointment Type</label>
                        <select class="form-select" id="appointment_type" name="appointment_type">
                            <option value="">Select Type</option>
                            <option value="Checkup" <?php echo $appointment['appointment_type'] === 'Checkup' ? 'selected' : ''; ?>>Checkup</option>
                            <option value="Follow-up" <?php echo $appointment['appointment_type'] === 'Follow-up' ? 'selected' : ''; ?>>Follow-up</option>
                            <option value="Root Canal" <?php echo $appointment['appointment_type'] === 'Root Canal' ? 'selected' : ''; ?>>Root Canal</option>
                            <option value="Extraction" <?php echo $appointment['appointment_type'] === 'Extraction' ? 'selected' : ''; ?>>Extraction</option>
                            <option value="Cleaning" <?php echo $appointment['appointment_type'] === 'Cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                            <option value="Emergency" <?php echo $appointment['appointment_type'] === 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                            <option value="Other" <?php echo $appointment['appointment_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="reason" class="form-label fw-bold">Reason for Visit</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3"><?php echo htmlspecialchars($appointment['reason'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label fw-bold">Additional Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calendar-check me-2"></i>Update Appointment
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
