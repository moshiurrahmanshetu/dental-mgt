<?php
$pageTitle = 'Payment History';
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

// Filter parameters
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$paymentMethod = trim($_GET['payment_method'] ?? '');
$patientSearch = trim($_GET['patient'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query conditions
$conditions = [];
$params = [];

if (!empty($dateFrom)) {
    $conditions[] = 'p.payment_date >= ?';
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = 'p.payment_date <= ?';
    $params[] = $dateTo;
}

if (!empty($paymentMethod)) {
    $conditions[] = 'p.payment_method = ?';
    $params[] = $paymentMethod;
}

if (!empty($patientSearch)) {
    $conditions[] = '(pat.full_name LIKE ? OR pat.phone LIKE ? OR pat.patient_code LIKE ?)';
    $searchParam = '%' . $patientSearch . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM payments p 
                  JOIN invoices i ON p.invoice_id = i.id 
                  JOIN patients pat ON i.patient_id = pat.id 
                  $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalResult = $stmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get payments with pagination
$query = "SELECT p.*, i.invoice_number, i.invoice_date, pat.full_name as patient_name, 
          pat.patient_code, u.full_name as received_by_name
          FROM payments p 
          JOIN invoices i ON p.invoice_id = i.id 
          JOIN patients pat ON i.patient_id = pat.id 
          LEFT JOIN users u ON p.received_by = u.id 
          $whereClause 
          ORDER BY p.payment_date DESC, p.created_at DESC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Payment History</h2>
        <p class="text-muted mb-0">View all recorded payments</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/billing/list-invoices.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Invoices
    </a>
</div>

<!-- Filters Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
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
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="">All Methods</option>
                    <option value="Cash" <?php echo $paymentMethod === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Card" <?php echo $paymentMethod === 'Card' ? 'selected' : ''; ?>>Card</option>
                    <option value="Bank Transfer" <?php echo $paymentMethod === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="Mobile Banking" <?php echo $paymentMethod === 'Mobile Banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                    <option value="Other" <?php echo $paymentMethod === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="patient" class="form-label">Patient</label>
                <input type="text" class="form-control" id="patient" name="patient" 
                       placeholder="Name, phone, or code" 
                       value="<?php echo htmlspecialchars($patientSearch); ?>">
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cash fs-1 text-muted"></i>
                <p class="text-muted mt-3">No payments found</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Payment Code</th>
                            <th>Invoice Number</th>
                            <th>Patient</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Payment Date</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($payment['payment_code']); ?></strong></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $payment['invoice_id']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($payment['patient_name']); ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($payment['patient_code']); ?></small>
                                    </div>
                                </td>
                                <td class="fw-bold">$<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['received_by_name'] ?? '--'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Payments pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_method=<?php echo $paymentMethod; ?>&patient=<?php echo urlencode($patientSearch); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_method=<?php echo $paymentMethod; ?>&patient=<?php echo urlencode($patientSearch); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>&payment_method=<?php echo $paymentMethod; ?>&patient=<?php echo urlencode($patientSearch); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
