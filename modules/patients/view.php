<?php
$pageTitle = 'Patient Profile';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../billing/helpers.php';

// Role-based access control
requireAuth();
$currentRole = $_SESSION['role_name'];

if ($currentRole === 'Patient') {
    die('<div class="alert alert-danger">Access Denied. Patients cannot access this page.</div>');
}

// Validate patient ID
$patientId = intval($_GET['id'] ?? 0);
if ($patientId <= 0) {
    die('<div class="alert alert-danger">Invalid patient ID.</div>');
}

// Fetch patient data
try {
    $stmt = $pdo->prepare("SELECT p.*, 
                          TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
                          u.full_name as registered_by_name
                          FROM patients p 
                          LEFT JOIN users u ON p.registered_by = u.id
                          WHERE p.id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        die('<div class="alert alert-danger">Patient not found.</div>');
    }
} catch (PDOException $e) {
    error_log("Patient Fetch Error: " . $e->getMessage());
    die('<div class="alert alert-danger">Error fetching patient data.</div>');
}

// Calculate age if not already calculated
$age = $patient['age'] ?? calculateAge($patient['date_of_birth']);
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Patient Profile</h2>
        <p class="text-muted mb-0"><?php echo htmlspecialchars($patient['patient_code']); ?></p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>modules/patients/list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
        <?php if (in_array($currentRole, ['Admin', 'Receptionist'])): ?>
            <a href="<?php echo BASE_URL; ?>modules/patients/edit.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit Patient
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Patient Profile Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <?php if ($patient['profile_photo']): ?>
                    <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($patient['profile_photo']); ?>" 
                         alt="Patient Photo" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="avatar-placeholder rounded-circle mb-3 d-flex align-items-center justify-content-center mx-auto" 
                         style="width: 150px; height: 150px; background-color: var(--primary-color); color: white; font-size: 60px;">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
                
                <h4 class="mb-1"><?php echo htmlspecialchars($patient['full_name']); ?></h4>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($patient['patient_code']); ?></p>
                
                <?php if ($patient['status'] === 'active'): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                <?php endif; ?>
            </div>
            
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Gender:</strong>
                        <span class="ms-2"><?php echo htmlspecialchars($patient['gender']); ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Age:</strong>
                        <span class="ms-2"><?php echo $age; ?> years</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Date of Birth:</strong>
                        <span class="ms-2"><?php echo $patient['date_of_birth'] ? htmlspecialchars($patient['date_of_birth']) : '--'; ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Blood Group:</strong>
                        <span class="ms-2"><?php echo $patient['blood_group'] ? htmlspecialchars($patient['blood_group']) : '--'; ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Phone:</strong>
                        <span class="ms-2"><?php echo htmlspecialchars($patient['phone']); ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Email:</strong>
                        <span class="ms-2"><?php echo $patient['email'] ? htmlspecialchars($patient['email']) : '--'; ?></span>
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Address:</strong>
                        <span class="ms-2"><?php echo $patient['address'] ? htmlspecialchars($patient['address']) : '--'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="patientTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" 
                type="button" role="tab">Overview</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" 
                type="button" role="tab">Appointments</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="treatment-tab" data-bs-toggle="tab" data-bs-target="#treatment" 
                type="button" role="tab">Treatment History</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" 
                type="button" role="tab">Payment History</button>
    </li>
</ul>

<div class="tab-content" id="patientTabsContent">
    <!-- Overview Tab -->
    <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Emergency Contact</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($patient['emergency_contact_name'] || $patient['emergency_contact_phone']): ?>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['emergency_contact_name'] ?? '--'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? '--'); ?></p>
                        <?php else: ?>
                            <p class="text-muted">No emergency contact information provided.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Registration Details</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Registered By:</strong> <?php echo htmlspecialchars($patient['registered_by_name'] ?? 'System'); ?></p>
                        <p><strong>Registration Date:</strong> <?php echo htmlspecialchars($patient['created_at']); ?></p>
                        <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($patient['updated_at']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Medical Notes</h5>
            </div>
            <div class="card-body">
                <?php if ($patient['medical_notes']): ?>
                    <p><?php echo nl2br(htmlspecialchars($patient['medical_notes'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No medical notes recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Appointments Tab -->
    <div class="tab-pane fade" id="appointments" role="tabpanel">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <h4 class="mt-3">Appointments Module</h4>
                <p class="text-muted">No appointment module yet - Coming in Phase 3</p>
            </div>
        </div>
    </div>
    
    <!-- Treatment History Tab -->
    <div class="tab-pane fade" id="treatment" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <?php
                // Get patient's treatment history
                $stmt = $pdo->prepare("SELECT tr.*, d.full_name as doctor_name 
                                      FROM treatment_records tr 
                                      JOIN users d ON tr.doctor_id = d.id 
                                      WHERE tr.patient_id = ? AND tr.status = 'active'
                                      ORDER BY tr.visit_date DESC, tr.created_at DESC");
                $stmt->execute([$patientId]);
                $treatmentHistory = $stmt->fetchAll();
                ?>
                
                <?php if (empty($treatmentHistory)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x fs-1 text-muted"></i>
                        <h4 class="mt-3">No Treatment History</h4>
                        <p class="text-muted">This patient has no treatment records yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Record Code</th>
                                    <th>Visit Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Follow-up</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatmentHistory as $record): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($record['record_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['visit_date']); ?></td>
                                        <td><?php echo htmlspecialchars($record['doctor_name']); ?></td>
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
                                            <a href="<?php echo BASE_URL; ?>modules/treatments/view.php?id=<?php echo $record['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Payment History Tab -->
    <div class="tab-pane fade" id="payment" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <?php
                // Get patient's invoice history
                $stmt = $pdo->prepare("SELECT i.*, tr.record_code as treatment_record_code 
                                      FROM invoices i 
                                      LEFT JOIN treatment_records tr ON i.treatment_record_id = tr.id 
                                      WHERE i.patient_id = ? AND i.status = 'active'
                                      ORDER BY i.invoice_date DESC, i.created_at DESC");
                $stmt->execute([$patientId]);
                $invoiceHistory = $stmt->fetchAll();
                ?>
                
                <?php if (empty($invoiceHistory)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-currency-x fs-1 text-muted"></i>
                        <h4 class="mt-3">No Payment History</h4>
                        <p class="text-muted">This patient has no invoices yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Invoice Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Due Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoiceHistory as $invoice): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($invoice['invoice_date']); ?></td>
                                        <td>$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                                        <td>$<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                                        <td class="<?php echo $invoice['due_amount'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>">
                                            $<?php echo number_format($invoice['due_amount'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getPaymentStatusBadgeClass($invoice['payment_status']); ?>">
                                                <?php echo htmlspecialchars($invoice['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $invoice['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
