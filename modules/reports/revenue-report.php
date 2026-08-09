<?php
$pageTitle = 'Revenue Report';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - Admin only
checkRole(['Admin']);

$user = getCurrentUser();

// Get filters
$dateRange = getReportDateRange();
$export = trim($_GET['export'] ?? '');

// Build query conditions
$conditions = ["1=1"];
$params = [];

// Date range filter (by invoice date)
$dateCondition = buildDateRangeCondition('i.invoice_date', $dateRange);
$conditions[] = ltrim($dateCondition['condition'], 'AND ');
$params = array_merge($params, $dateCondition['params']);

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

// Get invoices
$query = "SELECT i.invoice_number as invoice_code, i.invoice_date, i.subtotal, i.discount_amount, i.total_amount,
          COALESCE(SUM(pay.amount), 0) as paid_amount,
          (i.total_amount - COALESCE(SUM(pay.amount), 0)) as due_amount,
          p.full_name as patient_name
          FROM invoices i
          JOIN patients p ON i.patient_id = p.id
          LEFT JOIN payments pay ON i.id = pay.invoice_id
          $whereClause
          GROUP BY i.id
          ORDER BY i.invoice_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Calculate totals
$totalInvoiced = 0;
$totalCollected = 0;
$totalDiscount = 0;

foreach ($invoices as $invoice) {
    $totalInvoiced += $invoice['total_amount'];
    $totalCollected += $invoice['paid_amount'];
    $totalDiscount += $invoice['discount_amount'];
}

// Export to CSV
if ($export === 'csv') {
    $csvData = [];
    foreach ($invoices as $invoice) {
        $csvData[] = [
            $invoice['invoice_code'],
            formatDate($invoice['invoice_date']),
            $invoice['patient_name'],
            formatCurrency($invoice['subtotal']),
            formatCurrency($invoice['discount_amount']),
            formatCurrency($invoice['total_amount']),
            formatCurrency($invoice['paid_amount']),
            formatCurrency($invoice['due_amount'])
        ];
    }
    
    $headers = ['Invoice Code', 'Date', 'Patient', 'Subtotal', 'Discount', 'Total', 'Collected', 'Due'];
    $filename = 'revenue_report_' . date('Y-m-d') . '.csv';
    exportToCSV($csvData, $filename, $headers);
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Revenue Report</h2>
        <p class="text-muted mb-0">Financial revenue report</p>
    </div>
    <a href="<?php echo BASE_URL; ?>dashboard/admin.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Revenue Collected</h6>
                <h4 class="mb-0 text-success"><?php echo formatCurrency($totalCollected); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Invoiced Amount</h6>
                <h4 class="mb-0 text-primary"><?php echo formatCurrency($totalInvoiced); ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted mb-1">Total Discount Given</h6>
                <h4 class="mb-0 text-warning"><?php echo formatCurrency($totalDiscount); ?></h4>
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
        <?php if (empty($invoices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-currency-dollar fs-1 text-muted"></i>
                <p class="text-muted mt-3">No invoices found for the selected date range</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Invoice Code</th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Collected</th>
                            <th>Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_code']); ?></td>
                                <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['patient_name']); ?></td>
                                <td><?php echo formatCurrency($invoice['subtotal']); ?></td>
                                <td><?php echo formatCurrency($invoice['discount_amount']); ?></td>
                                <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                <td><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                                <td><?php echo formatCurrency($invoice['due_amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th colspan="3" class="text-end">Total:</th>
                            <th><?php echo formatCurrency($totalInvoiced - $totalDiscount); ?></th>
                            <th><?php echo formatCurrency($totalDiscount); ?></th>
                            <th><?php echo formatCurrency($totalInvoiced); ?></th>
                            <th><?php echo formatCurrency($totalCollected); ?></th>
                            <th><?php echo formatCurrency($totalInvoiced - $totalCollected); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
