<?php
$pageTitle = 'Payment Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$methodFilter = trim($_GET['payment_method'] ?? '');
$receivedByFilter = intval($_GET['received_by'] ?? 0);
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["1=1"];
$params = [];

// Date range filter
$dateCondition = buildDateRangeCondition('pay.payment_date', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

// Payment method filter
if (!empty($methodFilter)) {
    $conditions[] = 'pay.payment_method = ?';
    $params[] = $methodFilter;
}

// Received by filter
if ($receivedByFilter > 0) {
    $conditions[] = 'pay.received_by = ?';
    $params[] = $receivedByFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get payments
$query = "SELECT pay.payment_code, i.invoice_code, pat.full_name as patient_name, 
          pay.amount, pay.payment_method, pay.payment_date, u.full_name as received_by_name
          FROM payments pay
          JOIN invoices i ON pay.invoice_id = i.id
          JOIN patients pat ON i.patient_id = pat.id
          LEFT JOIN users u ON pay.received_by = u.id
          $whereClause
          ORDER BY pay.payment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Calculate total amount
$totalAmount = 0;
foreach ($payments as $payment) {
    $totalAmount += $payment['amount'];
}

// Get staff for filter
$stmt = $pdo->prepare("SELECT id, full_name FROM users u JOIN roles r ON u.role_id = r.id 
                      WHERE r.role_name IN ('Admin', 'Receptionist') AND u.status = 'active' 
                      ORDER BY full_name ASC");
$stmt->execute();
$staff = $stmt->fetchAll();

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($payments as $payment) {
        $csvData[] = [
            $payment['payment_code'],
            $payment['invoice_code'],
            $payment['patient_name'],
            formatCurrency($payment['amount']),
            $payment['payment_method'],
            formatDate($payment['payment_date']),
            $payment['received_by_name'] ?? 'System'
        ];
    }
    
    $headers = ['Payment Code', 'Invoice Number', 'Patient', 'Amount', 'Method', 'Date', 'Received By'];
    $filename = 'payment_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Payment Report</h2>
        <p class="text-muted mb-0">Payment collection report</p>
    </div>
    <a href="<?php echo BASE_URL; ?>dashboard/<?php echo strtolower($user['role_name']); ?>.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Summary Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 50px; height: 50px;">
                        <i class="bi bi-cash-coin fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Total Collected</h6>
                        <h4 class="mb-0"><?php echo formatCurrency($totalAmount); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" 
                       value="<?php echo htmlspecialchars($dateRange['from_date']); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" 
                       value="<?php echo htmlspecialchars($dateRange['to_date']); ?>">
            </div>
            
            <div class="col-md-2">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="">All Methods</option>
                    <option value="Cash" <?php echo $methodFilter === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Card" <?php echo $methodFilter === 'Card' ? 'selected' : ''; ?>>Card</option>
                    <option value="Bank Transfer" <?php echo $methodFilter === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="Mobile Banking" <?php echo $methodFilter === 'Mobile Banking' ? 'selected' : ''; ?>>Mobile Banking</option>
                    <option value="Other" <?php echo $methodFilter === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="received_by" class="form-label">Received By</label>
                <select class="form-select" id="received_by" name="received_by">
                    <option value="">All Staff</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?php echo $member['id']; ?>" <?php echo $receivedByFilter == $member['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($member['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Results</h5>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-csv me-2"></i>Export CSV
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cash-coin fs-1 text-muted"></i>
                <p class="text-muted mt-3">No payments found for the selected criteria</p>
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
                            <th>Date</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_code']); ?></td>
                                <td><?php echo htmlspecialchars($payment['invoice_code']); ?></td>
                                <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                                <td><?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['received_by_name'] ?? 'System'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th colspan="3" class="text-end">Total:</th>
                            <th><?php echo formatCurrency($totalAmount); ?></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
