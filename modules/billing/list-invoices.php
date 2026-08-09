<?php
$pageTitle = 'Invoices';
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

// Check if user can edit/add
$canEdit = in_array($currentRole, ['Admin', 'Receptionist']);
$canViewOnly = $currentRole === 'Doctor';

// Filter parameters
$patientSearch = trim($_GET['patient'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$paymentStatus = trim($_GET['payment_status'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($dateFrom)) {
    $conditions[] = 'i.invoice_date >= ?';
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = 'i.invoice_date <= ?';
    $params[] = $dateTo;
}

if (!empty($paymentStatus)) {
    $conditions[] = 'i.payment_status = ?';
    $params[] = $paymentStatus;
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
$countQuery = "SELECT COUNT(*) as total FROM invoices i 
                  JOIN patients p ON i.patient_id = p.id 
                  $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get invoices with pagination
$query = "SELECT i.*, p.full_name as patient_name, p.patient_code, 
          p.phone as patient_phone, p.profile_photo as patient_photo
          FROM invoices i 
          JOIN patients p ON i.patient_id = p.id 
          $whereClause 
          ORDER BY i.invoice_date DESC, i.created_at DESC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Invoices</h2>
        <p class="text-muted mb-0">Manage patient invoices and billing</p>
    </div>
    <?php if ($canEdit): ?>
        <a href="<?php echo BASE_URL; ?>modules/billing/add-invoice.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Create Invoice
        </a>
    <?php endif; ?>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
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
            
            <div class="col-md-2">
                <label for="payment_status" class="form-label">Payment Status</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">All Status</option>
                    <option value="Unpaid" <?php echo $paymentStatus === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Partially Paid" <?php echo $paymentStatus === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                    <option value="Paid" <?php echo $paymentStatus === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text fs-1 text-muted"></i>
                <p class="text-muted mt-3">No invoices found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Patient</th>
                            <th>Invoice Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($invoice['patient_photo']): ?>
                                            <img src="<?php echo BASE_URL; ?>assets/images/patients/<?php echo htmlspecialchars($invoice['patient_photo']); ?>" 
                                                 alt="Patient Photo" class="rounded-circle me-2" width="32" height="32">
                                        <?php else: ?>
                                            <div class="avatar-placeholder rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px; background-color: var(--primary-color); color: white; font-size: 14px;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $invoice['patient_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($invoice['patient_name']); ?>
                                            </a>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($invoice['patient_code']); ?></small>
                                        </div>
                                    </div>
                                </td>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $invoice['id']; ?>" 
                                           class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canEdit && $invoice['paid_amount'] == 0 && $invoice['status'] === 'active'): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/billing/edit-invoice.php?id=<?php echo $invoice['id']; ?>" 
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
                <nav aria-label="Invoices pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_status=<?php echo $paymentStatus; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_status=<?php echo $paymentStatus; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&patient=<?php echo urlencode($patientSearch); ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_status=<?php echo $paymentStatus; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
