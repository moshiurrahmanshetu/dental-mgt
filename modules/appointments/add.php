<?php
$pageTitle = 'Book Appointment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can book appointments
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();
$error = '';
$success = '';

// Get available doctors
$doctors = getAvailableDoctors($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $patientId = intval($_POST['patient_id'] ?? 0);
        $doctorId = intval($_POST['doctor_id'] ?? 0);
        $appointmentDate = trim($_POST['appointment_date'] ?? '');
        $appointmentTime = trim($_POST['appointment_time'] ?? '');
        $appointmentType = trim($_POST['appointment_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if ($patientId <= 0) {
            $error = 'Please select a patient.';
        } elseif ($doctorId <= 0) {
            $error = 'Please select a doctor.';
        } elseif (empty($appointmentDate)) {
            $error = 'Please select an appointment date.';
        } elseif (empty($appointmentTime)) {
            $error = 'Please select an appointment time.';
        } elseif (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            $error = 'Appointment date cannot be in the past.';
        } else {
            // Check if patient exists
            $stmt = $pdo->prepare("SELECT id, full_name FROM patients WHERE id = ? AND status = 'active'");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch();
            
            if (!$patient) {
                $error = 'Invalid patient selected.';
            } else {
                // Check doctor availability
                if (!checkDoctorAvailability($pdo, $doctorId, $appointmentDate, $appointmentTime)) {
                    $error = 'Doctor already has an appointment at this time. Please choose a different time.';
                } else {
                    try {
                        // Generate appointment code
                        $appointmentCode = generateAppointmentCode($pdo);
                        
                        // Insert appointment
                        $stmt = $pdo->prepare("INSERT INTO appointments 
                            (appointment_code, patient_id, doctor_id, created_by, appointment_date, appointment_time, appointment_type, reason, notes, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        
                        $stmt->execute([
                            $appointmentCode,
                            $patientId,
                            $doctorId,
                            $_SESSION['user_id'],
                            $appointmentDate,
                            $appointmentTime,
                            $appointmentType,
                            $reason,
                            $notes
                        ]);
                        
                        // Log activity
                        logActivity('Appointment Booked', "New appointment booked: $appointmentCode for patient: {$patient['full_name']}");
                        
                        $success = "Appointment booked successfully! Code: $appointmentCode";
                        
                        // Redirect to list.php after successful booking
                        header("Location: " . BASE_URL . "modules/appointments/list.php?success=" . urlencode($success));
                        exit();
                        
                    } catch (PDOException $e) {
                        error_log("Appointment Booking Error: " . $e->getMessage());
                        $error = 'Failed to book appointment. Please try again.';
                    }
                }
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Book Appointment</h2>
        <p class="text-muted mb-0">Schedule a new appointment for a patient</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/appointments/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to List
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="patient_id" class="form-label">Patient <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="patient_search" placeholder="Search patient by name, phone, or code..." required>
                    <input type="hidden" name="patient_id" id="patient_id" required>
                    <div id="patient_results" class="dropdown-menu" style="max-height: 200px; overflow-y: auto;"></div>
                    <small class="text-muted">Start typing to search patients</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="doctor_id" class="form-label">Doctor <span class="text-danger">*</span></label>
                    <select class="form-select" id="doctor_id" name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                <?php echo htmlspecialchars($doctor['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="appointment_date" class="form-label">Appointment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="appointment_time" class="form-label">Appointment Time <span class="text-danger">*</span></label>
                    <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="appointment_type" class="form-label">Appointment Type</label>
                    <select class="form-select" id="appointment_type" name="appointment_type">
                        <option value="">Select Type</option>
                        <option value="Checkup">Checkup</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Root Canal">Root Canal</option>
                        <option value="Extraction">Extraction</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="reason" class="form-label">Reason for Visit</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" 
                          placeholder="Describe the reason for this appointment..."></textarea>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" 
                          placeholder="Any additional notes or special instructions..."></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/appointments/list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-calendar-check me-2"></i>Book Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Patient search functionality
const patientSearch = document.getElementById('patient_search');
const patientId = document.getElementById('patient_id');
const patientResults = document.getElementById('patient_results');

patientSearch.addEventListener('input', function() {
    const searchTerm = this.value.trim();
    
    if (searchTerm.length < 2) {
        patientResults.innerHTML = '';
        patientResults.classList.remove('show');
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>modules/patients/search.php?term=' + encodeURIComponent(searchTerm))
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                patientResults.innerHTML = data.map(patient => `
                    <a class="dropdown-item" href="#" data-patient-id="${patient.id}" data-patient-name="${patient.full_name} (${patient.patient_code})">
                        <div>
                            <strong>${patient.full_name}</strong>
                            <small class="text-muted d-block">${patient.patient_code} - ${patient.phone}</small>
                        </div>
                    </a>
                `).join('');
                patientResults.classList.add('show');
            } else {
                patientResults.innerHTML = '<a class="dropdown-item disabled">No patients found</a>';
                patientResults.classList.add('show');
            }
        })
        .catch(error => {
            console.error('Error searching patients:', error);
        });
});

// Handle patient selection
patientResults.addEventListener('click', function(e) {
    const item = e.target.closest('.dropdown-item');
    if (item && !item.classList.contains('disabled')) {
        patientId.value = item.dataset.patientId;
        patientSearch.value = item.dataset.patientName;
        patientResults.innerHTML = '';
        patientResults.classList.remove('show');
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!patientSearch.contains(e.target) && !patientResults.contains(e.target)) {
        patientResults.innerHTML = '';
        patientResults.classList.remove('show');
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
