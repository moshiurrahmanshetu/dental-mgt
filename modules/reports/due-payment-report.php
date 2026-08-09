<?php
$pageTitle = 'Due Payment Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$minDueFilter = floatval($_GET['min_due'] ?? 0);
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["i.due_amount > 0"];
$params = [];

// Date range filter (by invoice date)
$dateCondition = buildDateRangeCondition('i.invoice_date', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

// Minimum due amount filter
if ($minDueFilter > 0) {
    $conditions[] = 'i.due_amount >= ?';
    $params[] = $minDueFilter;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get due invoices
$query = "SELECT i.invoice_code, i.invoice_date, i.total_amount, 
          COALESCE(SUM(p.amount), 0) as paid_amount, i.due_amount,
          p.full_name as patient_name
          FROM invoices i
          JOIN patients p ON i.patient_id = p.id
          LEFT JOIN payments pay ON i.id = pay.invoice_id
          $whereClause
          GROUP BY i.id
          ORDER BY i.due_amount DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$dueInvoices = $stmt->fetchAll();

// Calculate total outstanding
$totalOutstanding = 0;
foreach ($dueInvoices as $invoice) {
    $totalOutstanding += $invoice['due_amount'];
}

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($dueInvoices as $invoice) {
        $csvData[] = [
            $invoice['invoice_code'],
            $invoice['patient_name'],
            formatCurrency($invoice['total_amount']),
            formatCurrency($invoice['paid_amount']),
            formatCurrency($invoice['due_amount']),
            formatDate($invoice['invoice_date'])
        ];
    }
    
    $headers = ['Invoice Number', 'Patient', 'Total Amount', 'Paid', 'Due', 'Invoice Date'];
    $filename = 'due_payment_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Due Payment Report</h2>
        <p class="text-muted mb-0">Outstanding payment report</p>
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
                    <div class="icon-box bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 50px; height: 50px;">
                        <i class="bi bi-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">Total Outstanding</h6>
                        <h4 class="mb-0"><?php echo formatCurrency($totalOutstanding); ?></h4>
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
                <label for="min_due" class="form-label">Min Due Amount</label>
                <input type="number" class="form-control" id="min_due" name="min_due" 
                       placeholder="0.00" step="0.01" min="0"
                       value="<?php echo $minDueFilter > 0 ? $minDueFilter : ''; ?>">
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
        <?php if (empty($dueInvoices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-exclamation-circle fs-1 text-muted"></i>
                <p class="text-muted mt-3">No due payments found for the selected criteria</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Patient</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Invoice Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dueInvoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_code']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['patient_name']); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                                <td><strong><?php echo formatCurrency($invoice['due_amount']); ?></strong></td>
                                <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th colspan="4" class="text-end">Total Outstanding:</th>
                            <th><?php echo formatCurrency($totalOutstanding); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
