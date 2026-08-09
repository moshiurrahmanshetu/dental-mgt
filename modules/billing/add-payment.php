<?php
$pageTitle = 'Record Payment';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can record payments
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Get invoice ID from URL
$invoiceId = intval($_GET['invoice_id'] ?? 0);
if ($invoiceId <= 0) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// Get invoice details
$stmt = $pdo->prepare("SELECT i.*, p.full_name as patient_name, p.patient_code
                      FROM invoices i 
                      JOIN patients p ON i.patient_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// Check if invoice is active and has due amount
if ($invoice['status'] !== 'active') {
    header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&error=" . urlencode("Cannot record payment for cancelled invoices."));
    exit();
}

if ($invoice['due_amount'] <= 0) {
    header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&error=" . urlencode("This invoice has no due amount."));
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
        $paymentDate = trim($_POST['payment_date'] ?? '');
        $referenceNote = trim($_POST['reference_note'] ?? '');
        
        // Server-side validation
        if ($amount <= 0) {
            $error = 'Payment amount must be greater than zero.';
        } elseif (empty($paymentDate)) {
            $error = 'Please select a payment date.';
        } else {
            // Re-fetch current due amount from DB to avoid race conditions
            $stmt = $pdo->prepare("SELECT due_amount FROM invoices WHERE id = ?");
            $stmt->execute([$invoiceId]);
            $currentInvoice = $stmt->fetch();
            
            if (!$currentInvoice) {
                $error = 'Invoice not found.';
            } elseif ($amount > $currentInvoice['due_amount']) {
                $error = 'Payment amount cannot exceed the current due amount ($' . number_format($currentInvoice['due_amount'], 2) . ').';
            } else {
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Generate payment code
                    $paymentCode = generatePaymentCode($pdo);
                    
                    // Insert payment
                    $stmt = $pdo->prepare("INSERT INTO payments 
                        (payment_code, invoice_id, received_by, amount, payment_method, payment_date, reference_note) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $paymentCode,
                        $invoiceId,
                        $_SESSION['user_id'],
                        $amount,
                        $paymentMethod,
                        $paymentDate,
                        $referenceNote
                    ]);
                    
                    // Recalculate invoice totals
                    if (!recalculateInvoiceTotals($pdo, $invoiceId)) {
                        throw new Exception("Failed to recalculate invoice totals");
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    // Log activity
                    logActivity('Payment Recorded', "Payment recorded: $paymentCode for amount $" . number_format($amount, 2) . " on invoice {$invoice['invoice_number']}");
                    
                    $success = "Payment recorded successfully! Code: $paymentCode";
                    header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&success=" . urlencode($success));
                    exit();
                    
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log("Payment Recording Error: " . $e->getMessage());
                    $error = 'Failed to record payment. Please try again.';
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log("Payment Recording Error: " . $e->getMessage());
                    $error = $e->getMessage();
                }
            }
        }
    }
}
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
        <h2>Record Payment</h2>
        <p class="text-muted mb-0">Invoice: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Invoice
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Invoice Summary Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-2">Patient: <?php echo htmlspecialchars($invoice['patient_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($invoice['patient_code']); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <div class="mb-2">
                    <span class="text-muted">Total Amount:</span>
                    <strong class="ms-2">$<?php echo number_format($invoice['total_amount'], 2); ?></strong>
                </div>
                <div class="mb-2">
                    <span class="text-muted">Paid Amount:</span>
                    <strong class="ms-2">$<?php echo number_format($invoice['paid_amount'], 2); ?></strong>
                </div>
                <div class="mb-0">
                    <span class="text-muted">Due Amount:</span>
                    <strong class="ms-2 text-danger fs-5">$<?php echo number_format($invoice['due_amount'], 2); ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="amount" class="form-label">Payment Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               step="0.01" min="0.01" max="<?php echo $invoice['due_amount']; ?>" required
                               placeholder="Enter amount">
                    </div>
                    <small class="text-muted">Maximum amount: $<?php echo number_format($invoice['due_amount'], 2); ?></small>
                </div>
                
                <div class="col-md-6">
                    <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Banking">Mobile Banking</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="reference_note" class="form-label">Reference Note (Optional)</label>
                    <input type="text" class="form-control" id="reference_note" name="reference_note" 
                           placeholder="e.g., Transaction ID, Check number">
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> After recording this payment, the invoice status will be updated automatically.
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-cash me-2"></i>Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
