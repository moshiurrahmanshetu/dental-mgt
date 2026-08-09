<?php
$pageTitle = 'View Invoice';
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

// Get invoice ID
$invoiceId = intval($_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// Get invoice details
$stmt = $pdo->prepare("SELECT i.*, p.full_name as patient_name, p.patient_code, p.phone as patient_phone, 
                      p.email as patient_email, p.address as patient_address, p.profile_photo as patient_photo,
                      u.full_name as created_by_name, tr.record_code as treatment_record_code,
                      a.appointment_code
                      FROM invoices i 
                      JOIN patients p ON i.patient_id = p.id 
                      LEFT JOIN users u ON i.created_by = u.id 
                      LEFT JOIN treatment_records tr ON i.treatment_record_id = tr.id 
                      LEFT JOIN appointments a ON i.appointment_id = a.id
                      WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// Get invoice items
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$stmt->execute([$invoiceId]);
$invoiceItems = $stmt->fetchAll();

// Get payments
$stmt = $pdo->prepare("SELECT p.*, u.full_name as received_by_name 
                      FROM payments p 
                      LEFT JOIN users u ON p.received_by = u.id 
                      WHERE p.invoice_id = ? ORDER BY p.payment_date DESC, p.created_at DESC");
$stmt->execute([$invoiceId]);
$payments = $stmt->fetchAll();

// Check if user can edit (Admin or Receptionist, and no payments made)
$canEdit = in_array($currentRole, ['Admin', 'Receptionist']) && $invoice['paid_amount'] == 0 && $invoice['status'] === 'active';
$canAddPayment = in_array($currentRole, ['Admin', 'Receptionist']) && $invoice['due_amount'] > 0 && $invoice['status'] === 'active';

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
    .invoice-header {
        border-bottom: 2px solid #000 !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2>Invoice</h2>
        <p class="text-muted mb-0">Number: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
    </div>
    <div class="btn-group no-print">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print Invoice
        </button>
        <a href="<?php echo BASE_URL; ?>modules/billing/list-invoices.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
        <?php if ($canEdit): ?>
            <a href="<?php echo BASE_URL; ?>modules/billing/edit-invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit Invoice
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

<!-- Invoice Card -->
<div class="card mb-4">
    <div class="card-body">
        <!-- Invoice Header -->
        <div class="invoice-header pb-3 mb-3">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="mb-2">Dental Care Clinic</h4>
                    <p class="text-muted mb-0">Professional Dental Services</p>
                    <p class="text-muted mb-0">123 Dental Street, City</p>
                    <p class="text-muted mb-0">Phone: (555) 123-4567</p>
                </div>
                <div class="col-md-6 text-end">
                    <h3 class="mb-2">INVOICE</h3>
                    <p class="mb-1"><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p class="mb-1"><strong>Invoice Date:</strong> <?php echo htmlspecialchars($invoice['invoice_date']); ?></p>
                    <p class="mb-0"><strong>Status:</strong> 
                        <span class="badge <?php echo getPaymentStatusBadgeClass($invoice['payment_status']); ?>">
                            <?php echo htmlspecialchars($invoice['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Patient Information -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="mb-2">Bill To:</h6>
                <p class="mb-1"><strong><?php echo htmlspecialchars($invoice['patient_name']); ?></strong></p>
                <p class="mb-1"><?php echo htmlspecialchars($invoice['patient_code']); ?></p>
                <p class="mb-1"><?php echo htmlspecialchars($invoice['patient_phone']); ?></p>
                <p class="mb-0"><?php echo htmlspecialchars($invoice['patient_email'] ?? '--'); ?></p>
            </div>
            <div class="col-md-6 text-end">
                <?php if ($invoice['treatment_record_code']): ?>
                    <p class="mb-1"><strong>Treatment Record:</strong> <?php echo htmlspecialchars($invoice['treatment_record_code']); ?></p>
                <?php endif; ?>
                <?php if ($invoice['appointment_code']): ?>
                    <p class="mb-1"><strong>Appointment:</strong> <?php echo htmlspecialchars($invoice['appointment_code']); ?></p>
                <?php endif; ?>
                <p class="mb-0"><strong>Created By:</strong> <?php echo htmlspecialchars($invoice['created_by_name'] ?? '--'); ?></p>
            </div>
        </div>
        
        <!-- Invoice Items Table -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_description']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($item['line_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Totals Section -->
        <div class="row">
            <div class="col-md-6">
                <?php if ($invoice['notes']): ?>
                    <div class="mb-3">
                        <h6>Notes:</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end">$<?php echo number_format($invoice['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Discount (<?php echo $invoice['discount_type']; ?>):</strong></td>
                            <td class="text-end">-$<?php echo number_format($invoice['discount_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="fs-5"><strong>Total Amount:</strong></td>
                            <td class="text-end fs-5 fw-bold">$<?php echo number_format($invoice['total_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Paid Amount:</strong></td>
                            <td class="text-end">$<?php echo number_format($invoice['paid_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="fs-5 <?php echo $invoice['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                <strong>Due Amount:</strong>
                            </td>
                            <td class="text-end fs-5 fw-bold <?php echo $invoice['due_amount'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                $<?php echo number_format($invoice['due_amount'], 2); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Payment History Section -->
        <?php if (!empty($payments)): ?>
            <div class="mt-4">
                <h6 class="mb-3">Payment History</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Payment Code</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['payment_code']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                    <td class="text-end">$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['received_by_name'] ?? '--'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-top no-print">
            <?php if ($canAddPayment): ?>
                <a href="<?php echo BASE_URL; ?>modules/billing/add-payment.php?invoice_id=<?php echo $invoiceId; ?>" 
                   class="btn btn-success">
                    <i class="bi bi-cash me-2"></i>Record Payment
                </a>
            <?php endif; ?>
            
            <?php if ($invoice['status'] === 'active' && in_array($currentRole, ['Admin', 'Receptionist'])): ?>
                <a href="<?php echo BASE_URL; ?>modules/billing/list-invoices.php" class="btn btn-secondary">
                    <i class="bi bi-list me-2"></i>View All Invoices
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
