<?php
$pageTitle = 'Edit Treatment Record';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Doctor (own records) or Admin can edit
$currentRole = $_SESSION['role_name'];
if ($currentRole === 'Patient' || $currentRole === 'Receptionist') {
    die('<div class="alert alert-danger">Access Denied. You do not have permission to edit treatment records.</div>');
}

requireAuth();

$user = getCurrentUser();

// Get treatment record ID
$recordId = intval($_GET['id'] ?? 0);
if ($recordId <= 0) {
    header("Location: " . BASE_URL . "modules/treatments/list.php");
    exit();
}

// Get treatment record details
$stmt = $pdo->prepare("SELECT tr.*, p.full_name as patient_name, p.patient_code, p.id as patient_id,
                      p.profile_photo as patient_photo, p.date_of_birth as patient_dob,
                      p.phone as patient_phone, d.full_name as doctor_name
                      FROM treatment_records tr 
                      JOIN patients p ON tr.patient_id = p.id 
                      JOIN users d ON tr.doctor_id = d.id 
                      WHERE tr.id = ?");
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    header("Location: " . BASE_URL . "modules/treatments/list.php");
    exit();
}

// Check edit permissions
if ($currentRole === 'Doctor' && $record['doctor_id'] != $_SESSION['user_id']) {
    die('<div class="alert alert-danger">Access Denied. You can only edit your own treatment records.</div>');
}

// Get existing treatment items
$stmt = $pdo->prepare("SELECT * FROM treatment_items WHERE treatment_record_id = ? ORDER BY id ASC");
$stmt->execute([$recordId]);
$existingTreatmentItems = $stmt->fetchAll();

// Get existing prescriptions
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE treatment_record_id = ? ORDER BY id ASC");
$stmt->execute([$recordId]);
$existingPrescriptions = $stmt->fetchAll();

$error = '';
$success = '';

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
                
                // Update treatment record
                $stmt = $pdo->prepare("UPDATE treatment_records 
                    SET visit_date = ?, chief_complaint = ?, diagnosis = ?, dental_findings = ?, 
                    follow_up_date = ?, doctor_notes = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                
                $stmt->execute([
                    $visitDate,
                    $chiefComplaint,
                    $diagnosis,
                    $dentalFindings,
                    $followUpDate,
                    $doctorNotes,
                    $recordId
                ]);
                
                // Delete existing treatment items
                $stmt = $pdo->prepare("DELETE FROM treatment_items WHERE treatment_record_id = ?");
                $stmt->execute([$recordId]);
                
                // Insert new treatment items
                if (isset($_POST['treatment_name']) && is_array($_POST['treatment_name'])) {
                    foreach ($_POST['treatment_name'] as $index => $treatmentName) {
                        if (!empty($treatmentName)) {
                            $toothNumber = trim($_POST['tooth_number'][$index] ?? '');
                            $treatmentNotes = trim($_POST['treatment_notes'][$index] ?? '');
                            
                            $stmt = $pdo->prepare("INSERT INTO treatment_items 
                                (treatment_record_id, treatment_name, tooth_number, treatment_notes) 
                                VALUES (?, ?, ?, ?)");
                            
                            $stmt->execute([$recordId, $treatmentName, $toothNumber, $treatmentNotes]);
                        }
                    }
                }
                
                // Delete existing prescriptions
                $stmt = $pdo->prepare("DELETE FROM prescriptions WHERE treatment_record_id = ?");
                $stmt->execute([$recordId]);
                
                // Insert new prescriptions
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
                            
                            $stmt->execute([$recordId, $medicineName, $dosage, $frequency, $duration, $instructions]);
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Log activity
                logActivity('Treatment Record Updated', "Treatment record updated: {$record['record_code']} for patient: {$record['patient_name']}");
                
                $success = "Treatment record updated successfully!";
                header("Location: " . BASE_URL . "modules/treatments/view.php?id=$recordId&success=" . urlencode($success));
                exit();
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Treatment Record Update Error: " . $e->getMessage());
                $error = 'Failed to update treatment record. Please try again.';
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
        <h2>Edit Treatment Record</h2>
        <p class="text-muted mb-0">Code: <?php echo htmlspecialchars($record['record_code']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $recordId; ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Record
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

<!-- Patient Mini Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <?php if ($record['patient_photo']): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($record['patient_photo']); ?>" 
                     alt="Patient Photo" class="rounded-circle me-3" width="60" height="60">
            <?php else: ?>
                <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                     style="width: 60px; height: 60px; background-color: var(--primary-color); color: white; font-size: 24px;">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
            <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($record['patient_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($record['patient_code']); ?> | Phone: <?php echo htmlspecialchars($record['patient_phone']); ?></p>
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
                           value="<?php echo htmlspecialchars($record['visit_date']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Record Code</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($record['record_code']); ?>" readonly>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="chief_complaint" class="form-label">Chief Complaint</label>
                <textarea class="form-control" id="chief_complaint" name="chief_complaint" rows="3"><?php echo htmlspecialchars($record['chief_complaint'] ?? ''); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"><?php echo htmlspecialchars($record['diagnosis'] ?? ''); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="dental_findings" class="form-label">Dental Findings</label>
                <textarea class="form-control" id="dental_findings" name="dental_findings" rows="3"><?php echo htmlspecialchars($record['dental_findings'] ?? ''); ?></textarea>
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
                        <?php foreach ($existingTreatmentItems as $index => $item): ?>
                            <div class="treatment-row mb-3">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label small">Treatment Name <span class="text-danger">*</span></label>
                                        <select class="form-select treatment-name" name="treatment_name[]" required>
                                            <option value="">Select Treatment</option>
                                            <?php foreach ($treatmentOptions as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $item['treatment_name'] === $option ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Tooth Number</label>
                                        <input type="text" class="form-control tooth-number" name="tooth_number[]" 
                                               value="<?php echo htmlspecialchars($item['tooth_number'] ?? ''); ?>" placeholder="e.g., #14">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label small">Treatment Notes</label>
                                        <input type="text" class="form-control treatment-notes" name="treatment_notes[]" 
                                               value="<?php echo htmlspecialchars($item['treatment_notes'] ?? ''); ?>" placeholder="Additional notes">
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-danger w-100" onclick="removeTreatmentRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($existingTreatmentItems)): ?>
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
                        <?php endif; ?>
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
                        <?php foreach ($existingPrescriptions as $index => $prescription): ?>
                            <div class="prescription-row mb-3">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Medicine Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control medicine-name" name="medicine_name[]" 
                                               value="<?php echo htmlspecialchars($prescription['medicine_name']); ?>" required placeholder="Medicine name">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Dosage</label>
                                        <input type="text" class="form-control dosage" name="dosage[]" 
                                               value="<?php echo htmlspecialchars($prescription['dosage'] ?? ''); ?>" placeholder="e.g., 500mg">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Frequency</label>
                                        <input type="text" class="form-control frequency" name="frequency[]" 
                                               value="<?php echo htmlspecialchars($prescription['frequency'] ?? ''); ?>" placeholder="e.g., 3x/day">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Duration</label>
                                        <input type="text" class="form-control duration" name="duration[]" 
                                               value="<?php echo htmlspecialchars($prescription['duration'] ?? ''); ?>" placeholder="e.g., 5 days">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Instructions</label>
                                        <input type="text" class="form-control instructions" name="instructions[]" 
                                               value="<?php echo htmlspecialchars($prescription['instructions'] ?? ''); ?>" placeholder="e.g., After meal">
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-danger w-100" onclick="removePrescriptionRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($existingPrescriptions)): ?>
                            <p class="text-muted">No prescriptions added. Click "Add Prescription" to add one.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="follow_up_date" class="form-label">Follow-up Date</label>
                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date" 
                           value="<?php echo htmlspecialchars($record['follow_up_date'] ?? ''); ?>"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <small class="text-muted">Leave empty if no follow-up needed</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="doctor_notes" class="form-label">Doctor Notes</label>
                <textarea class="form-control" id="doctor_notes" name="doctor_notes" rows="2"><?php echo htmlspecialchars($record['doctor_notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $recordId; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-file-medical me-2"></i>Update Treatment Record
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
