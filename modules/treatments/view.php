<?php
$pageTitle = 'View Treatment Record';
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

// Get treatment record ID
$recordId = intval($_GET['id'] ?? 0);
if ($recordId <= 0) {
    header("Location: " . BASE_URL . "modules/treatments/list.php");
    exit();
}

// Get treatment record details
$stmt = $pdo->prepare("SELECT tr.*, p.full_name as patient_name, p.patient_code, p.phone as patient_phone, 
                      p.email as patient_email, p.address as patient_address, p.profile_photo as patient_photo,
                      p.date_of_birth as patient_dob, d.full_name as doctor_name, d.email as doctor_email,
                      a.appointment_code
                      FROM treatment_records tr 
                      JOIN patients p ON tr.patient_id = p.id 
                      JOIN users d ON tr.doctor_id = d.id 
                      LEFT JOIN appointments a ON tr.appointment_id = a.id
                      WHERE tr.id = ?");
$stmt->execute([$recordId]);
$record = $stmt->fetch();

if (!$record) {
    header("Location: " . BASE_URL . "modules/treatments/list.php");
    exit();
}

// Doctor isolation check - doctors can only view their own records
if ($currentRole === 'Doctor' && $record['doctor_id'] != $_SESSION['user_id']) {
    die('<div class="alert alert-danger">Access Denied. You can only view your own treatment records.</div>');
}

// Get treatment items
$stmt = $pdo->prepare("SELECT * FROM treatment_items WHERE treatment_record_id = ? ORDER BY id ASC");
$stmt->execute([$recordId]);
$treatmentItems = $stmt->fetchAll();

// Get prescriptions
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE treatment_record_id = ? ORDER BY id ASC");
$stmt->execute([$recordId]);
$prescriptions = $stmt->fetchAll();

// Check if user can edit (original doctor or Admin)
$canEdit = ($currentRole === 'Admin') || ($currentRole === 'Doctor' && $record['doctor_id'] == $_SESSION['user_id']);

// Success message from redirect
$success = $_GET['success'] ?? '';
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
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    body {
        background: white !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2>Treatment Record</h2>
        <p class="text-muted mb-0">Code: <?php echo htmlspecialchars($record['record_code']); ?></p>
    </div>
    <div class="btn-group no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print
        </button>
        <a href="<?php echo BASE_URL; ?>modules/treatments/list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
        <?php if ($canEdit): ?>
            <a href="<?php echo BASE_URL; ?>modules/treatments/edit.php?id=<?php echo $recordId; ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit Record
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <!-- Patient Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Patient Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <?php if ($record['patient_photo']): ?>
                        <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($record['patient_photo']); ?>" 
                             alt="Patient Photo" class="rounded-circle me-3" width="80" height="80">
                    <?php else: ?>
                        <div class="avatar-placeholder rounded-circle me-3 d-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px; background-color: var(--primary-color); color: white; font-size: 32px;">
                            <i class="bi bi-person"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($record['patient_name']); ?></h5>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($record['patient_code']); ?></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-muted small">Phone</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($record['patient_phone']); ?></p>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-muted small">Email</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($record['patient_email'] ?? '--'); ?></p>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label text-muted small">Address</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($record['patient_address'] ?? '--'); ?></p>
                    </div>
                </div>
                
                <div class="text-center mt-3 no-print">
                    <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $record['patient_id']; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-person me-2"></i>View Full Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Treatment Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Treatment Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Visit Date</label>
                        <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($record['visit_date']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Doctor</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($record['doctor_name']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small">Appointment Code</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($record['appointment_code'] ?? '--'); ?></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Chief Complaint</label>
                    <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($record['chief_complaint'] ?? '--')); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Diagnosis</label>
                    <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($record['diagnosis'] ?? '--')); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Dental Findings</label>
                    <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($record['dental_findings'] ?? '--')); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Follow-up Date</label>
                    <p class="form-control-plaintext">
                        <?php if ($record['follow_up_date']): ?>
                            <?php 
                            $followUpDate = new DateTime($record['follow_up_date']);
                            $today = new DateTime();
                            $isOverdue = $followUpDate < $today;
                            $isUpcoming = $followUpDate > $today;
                            ?>
                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ($isUpcoming ? 'text-primary fw-bold' : ''); ?>">
                                <?php echo htmlspecialchars($record['follow_up_date']); ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php elseif ($isUpcoming): ?>
                                    <span class="badge bg-primary">Upcoming</span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            No follow-up scheduled
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Doctor Notes</label>
                    <p class="form-control-plaintext"><?php echo nl2br(htmlspecialchars($record['doctor_notes'] ?? '--')); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Treatments Performed Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Treatments Performed</h5>
            </div>
            <div class="card-body">
                <?php if (empty($treatmentItems)): ?>
                    <p class="text-muted">No treatments recorded for this visit.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Treatment Name</th>
                                    <th>Tooth Number</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatmentItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['treatment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['tooth_number'] ?? '--'); ?></td>
                                        <td><?php echo htmlspecialchars($item['treatment_notes'] ?? '--'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Prescriptions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Prescriptions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($prescriptions)): ?>
                    <p class="text-muted">No prescriptions issued for this visit.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescriptions as $prescription): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prescription['medicine_name']); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['dosage'] ?? '--'); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['frequency'] ?? '--'); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['duration'] ?? '--'); ?></td>
                                        <td><?php echo htmlspecialchars($prescription['instructions'] ?? '--'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Quick Info Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Info</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label text-muted small">Record Code</label>
                    <p class="form-control-plaintext fw-bold"><?php echo htmlspecialchars($record['record_code']); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Created At</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($record['created_at']); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Last Updated</label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($record['updated_at']); ?></p>
                </div>
                
                <div class="mb-2">
                    <label class="form-label text-muted small">Status</label>
                    <span class="badge bg-<?php echo $record['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($record['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="card no-print">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $record['patient_id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-person me-2"></i>View Patient History
                    </a>
                    <?php if ($record['appointment_id']): ?>
                        <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $record['appointment_id']; ?>" 
                           class="btn btn-outline-info">
                            <i class="bi bi-calendar-check me-2"></i>View Appointment
                        </a>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                        <a href="<?php echo BASE_URL; ?>modules/treatments/edit.php?id=<?php echo $recordId; ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-pencil me-2"></i>Edit Record
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
