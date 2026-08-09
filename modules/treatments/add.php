<?php
$pageTitle = 'Add Treatment Record';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Doctor can add treatment records
checkRole(['Doctor']);

$user = getCurrentUser();
$error = '';
$success = '';

// Get appointment ID from URL
$appointmentId = intval($_GET['appointment_id'] ?? 0);
if ($appointmentId <= 0) {
    header("Location: " . BASE_URL . "modules/appointments/list.php?error=" . urlencode("Invalid appointment ID"));
    exit();
}

// Get appointment details and validate
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.patient_code, p.id as patient_id,
                      p.profile_photo as patient_photo, p.date_of_birth as patient_dob,
                      p.phone as patient_phone, d.full_name as doctor_name
                      FROM appointments a 
                      JOIN patients p ON a.patient_id = p.id 
                      JOIN users d ON a.doctor_id = d.id 
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: " . BASE_URL . "modules/appointments/list.php?error=" . urlencode("Appointment not found"));
    exit();
}

// Validate: appointment must be Completed and assigned to current doctor
if ($appointment['status'] !== 'Completed') {
    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&error=" . urlencode("Only completed appointments can have treatment records"));
    exit();
}

if ($appointment['doctor_id'] != $_SESSION['user_id']) {
    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&error=" . urlencode("You can only add treatment records for your own appointments"));
    exit();
}

// Get patient's previous treatment history
$stmt = $pdo->prepare("SELECT tr.id, tr.record_code, tr.visit_date, tr.diagnosis, d.full_name as doctor_name
                      FROM treatment_records tr 
                      JOIN users d ON tr.doctor_id = d.id 
                      WHERE tr.patient_id = ? AND tr.status = 'active'
                      ORDER BY tr.visit_date DESC 
                      LIMIT 5");
$stmt->execute([$appointment['patient_id']]);
$previousTreatments = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $visitDate = trim($_POST['visit_date'] ?? '');
        $chiefComplaint = trim($_POST['chief_complaint'] ?? '');
        $diagnosis = trim($_POST['diagnosis'] ?? '');
        $dentalFindings = trim($_POST['dental_findings'] ?? '');
        $followUpDate = trim($_POST['follow_up_date'] ?? '');
        $doctorNotes = trim($_POST['doctor_notes'] ?? '');
        
        // Validate follow-up date if provided
        if (!empty($followUpDate) && strtotime($followUpDate) < strtotime(date('Y-m-d'))) {
            $error = 'Follow-up date cannot be in the past.';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate treatment record code
                $recordCode = generateRecordCode($pdo);
                
                // Insert treatment record
                $stmt = $pdo->prepare("INSERT INTO treatment_records 
                    (record_code, patient_id, doctor_id, appointment_id, visit_date, chief_complaint, diagnosis, dental_findings, follow_up_date, doctor_notes, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                
                $stmt->execute([
                    $recordCode,
                    $appointment['patient_id'],
                    $_SESSION['user_id'],
                    $appointmentId,
                    $visitDate,
                    $chiefComplaint,
                    $diagnosis,
                    $dentalFindings,
                    $followUpDate,
                    $doctorNotes
                ]);
                
                $treatmentRecordId = $pdo->lastInsertId();
                
                // Insert treatment items
                if (isset($_POST['treatment_name']) && is_array($_POST['treatment_name'])) {
                    foreach ($_POST['treatment_name'] as $index => $treatmentName) {
                        if (!empty($treatmentName)) {
                            $toothNumber = trim($_POST['tooth_number'][$index] ?? '');
                            $treatmentNotes = trim($_POST['treatment_notes'][$index] ?? '');
                            
                            $stmt = $pdo->prepare("INSERT INTO treatment_items 
                                (treatment_record_id, treatment_name, tooth_number, treatment_notes) 
                                VALUES (?, ?, ?, ?)");
                            
                            $stmt->execute([$treatmentRecordId, $treatmentName, $toothNumber, $treatmentNotes]);
                        }
                    }
                }
                
                // Insert prescriptions
                if (isset($_POST['medicine_name']) && is_array($_POST['medicine_name'])) {
                    foreach ($_POST['medicine_name'] as $index => $medicineName) {
                        if (!empty($medicineName)) {
                            $dosage = trim($_POST['dosage'][$index] ?? '');
                            $frequency = trim($_POST['frequency'][$index] ?? '');
                            $duration = trim($_POST['duration'][$index] ?? '');
                            $instructions = trim($_POST['instructions'][$index] ?? '');
                            
                            $stmt = $pdo->prepare("INSERT INTO prescriptions 
                                (treatment_record_id, medicine_name, dosage, frequency, duration, instructions) 
                                VALUES (?, ?, ?, ?, ?, ?)");
                            
                            $stmt->execute([$treatmentRecordId, $medicineName, $dosage, $frequency, $duration, $instructions]);
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Log activity
                logActivity('Treatment Record Added', "Treatment record added: $recordCode for patient: {$appointment['patient_name']}");
                
                $success = "Treatment record added successfully! Code: $recordCode";
                header("Location: " . BASE_URL . "modules/treatments/view.php?id=$treatmentRecordId&success=" . urlencode($success));
                exit();
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Treatment Record Creation Error: " . $e->getMessage());
                $error = 'Failed to add treatment record. Please try again.';
            }
        }
    }
}

$treatmentOptions = getTreatmentTypeOptions();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<style>
@media print {
    .sidebar, .navbar, .btn, .no-print {
        display: none !important;
    }
    .container {
        width: 100% !important;
        max-width: 100% !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Add Treatment Record</h2>
        <p class="text-muted mb-0">For Appointment: <?php echo htmlspecialchars($appointment['appointment_code']); ?></p>
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

<!-- Patient Previous Treatment History -->
<?php if (!empty($previousTreatments)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Previous Treatment History</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Diagnosis</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previousTreatments as $treatment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($treatment['visit_date']); ?></td>
                            <td><?php echo htmlspecialchars($treatment['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($treatment['diagnosis'] ?? '', 0, 50)) . (strlen($treatment['diagnosis'] ?? '') > 50 ? '...' : ''); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $treatment['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Patient Mini Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <?php if ($appointment['patient_photo']): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($appointment['patient_photo']); ?>" 
                     alt="Patient Photo" class="rounded-circle me-3" width="60" height="60">
            <?php else: ?>
                <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px; background-color: var(--primary-color); color: white; font-size: 24px;">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
            <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($appointment['patient_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($appointment['patient_code']); ?> | Phone: <?php echo htmlspecialchars($appointment['patient_phone']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Treatment Record Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="visit_date" class="form-label">Visit Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="visit_date" name="visit_date" 
                           value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Code</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($appointment['appointment_code']); ?>" readonly>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="chief_complaint" class="form-label">Chief Complaint</label>
                <textarea class="form-control" id="chief_complaint" name="chief_complaint" rows="3" 
                          placeholder="Describe the patient's main complaint..."></textarea>
            </div>
            
            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" 
                          placeholder="Enter the diagnosis..."></textarea>
            </div>
            
            <div class="mb-3">
                <label for="dental_findings" class="form-label">Dental Findings</label>
                <textarea class="form-control" id="dental_findings" name="dental_findings" rows="3" 
                          placeholder="Describe dental findings during examination..."></textarea>
            </div>
            
            <!-- Treatments Performed Section -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Treatments Performed</h6>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addTreatmentRow()">
                        <i class="bi bi-plus-circle"></i> Add Treatment
                    </button>
                </div>
                <div class="card-body">
                    <div id="treatments-container">
                        <div class="treatment-row mb-3">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small">Treatment Name <span class="text-danger">*</span></label>
                                    <select class="form-select treatment-name" name="treatment_name[]" required>
                                        <option value="">Select Treatment</option>
                                        <?php foreach ($treatmentOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small">Tooth Number</label>
                                    <input type="text" class="form-control tooth-number" name="tooth_number[]" placeholder="e.g., #14">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label small">Treatment Notes</label>
                                    <input type="text" class="form-control treatment-notes" name="treatment_notes[]" placeholder="Additional notes">
                                </div>
                                <div class="col-md-1 mb-2">
                                    <label class="form-label small">&nbsp;</label>
                                    <button type="button" class="btn btn-danger w-100" onclick="removeTreatmentRow(this)" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Prescriptions Section -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Prescriptions (Optional)</h6>
                    <button type="button" class="btn btn-sm btn-success" onclick="addPrescriptionRow()">
                        <i class="bi bi-plus-circle"></i> Add Prescription
                    </button>
                </div>
                <div class="card-body">
                    <div id="prescriptions-container">
                        <!-- No prescriptions by default - user can add them -->
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="follow_up_date" class="form-label">Follow-up Date</label>
                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <small class="text-muted">Leave empty if no follow-up needed</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="doctor_notes" class="form-label">Doctor Notes</label>
                <textarea class="form-control" id="doctor_notes" name="doctor_notes" rows="2" 
                          placeholder="Any additional notes or observations..."></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointmentId; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-file-medical me-2"></i>Save Treatment Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Treatment rows management
function addTreatmentRow() {
    const container = document.getElementById('treatments-container');
    const newRow = document.createElement('div');
    newRow.className = 'treatment-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label small">Treatment Name <span class="text-danger">*</span></label>
                <select class="form-select treatment-name" name="treatment_name[]" required>
                    <option value="">Select Treatment</option>
                    <?php foreach ($treatmentOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label small">Tooth Number</label>
                <input type="text" class="form-control tooth-number" name="tooth_number[]" placeholder="e.g., #14">
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label small">Treatment Notes</label>
                <input type="text" class="form-control treatment-notes" name="treatment_notes[]" placeholder="Additional notes">
            </div>
            <div class="col-md-1 mb-2">
                <label class="form-label small">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="removeTreatmentRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    updateRemoveButtons();
}

function removeTreatmentRow(button) {
    const row = button.closest('.treatment-row');
    row.remove();
    updateRemoveButtons();
}

// Prescription rows management
function addPrescriptionRow() {
    const container = document.getElementById('prescriptions-container');
    const newRow = document.createElement('div');
    newRow.className = 'prescription-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label small">Medicine Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control medicine-name" name="medicine_name[]" required placeholder="Medicine name">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Dosage</label>
                <input type="text" class="form-control dosage" name="dosage[]" placeholder="e.g., 500mg">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Frequency</label>
                <input type="text" class="form-control frequency" name="frequency[]" placeholder="e.g., 3x/day">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Duration</label>
                <input type="text" class="form-control duration" name="duration[]" placeholder="e.g., 5 days">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Instructions</label>
                <input type="text" class="form-control instructions" name="instructions[]" placeholder="e.g., After meal">
            </div>
            <div class="col-md-1 mb-2">
                <label class="form-label small">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="removePrescriptionRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
}

function removePrescriptionRow(button) {
    const row = button.closest('.prescription-row');
    row.remove();
}

function updateRemoveButtons() {
    const treatmentRows = document.querySelectorAll('.treatment-row');
    treatmentRows.forEach((row, index) => {
        const removeBtn = row.querySelector('button[type="button"]');
        removeBtn.disabled = treatmentRows.length === 1;
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
