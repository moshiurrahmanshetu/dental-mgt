<?php
$pageTitle = 'Edit Invoice';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can edit invoices
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Get invoice ID
$invoiceId = intval($_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// Get invoice details
$stmt = $pdo->prepare("SELECT i.*, p.full_name as patient_name, p.patient_code, p.id as patient_id
                      FROM invoices i 
                      JOIN patients p ON i.patient_id = p.id 
                      WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header("Location: " . BASE_URL . "modules/billing/list-invoices.php");
    exit();
}

// HARD BLOCK: If invoice has payments, do not allow edit
if ($invoice['paid_amount'] > 0) {
    header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&error=" . urlencode("This invoice has payments recorded and cannot be edited. Please contact admin if changes are needed."));
    exit();
}

if ($invoice['status'] !== 'active') {
    header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&error=" . urlencode("Cannot edit cancelled invoices."));
    exit();
}

// Get existing invoice items
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC");
$stmt->execute([$invoiceId]);
$existingInvoiceItems = $stmt->fetchAll();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $invoiceDate = trim($_POST['invoice_date'] ?? '');
        $discountType = trim($_POST['discount_type'] ?? 'flat');
        $discountAmount = floatval($_POST['discount_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($invoiceDate)) {
            $error = 'Please select an invoice date.';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Server-side calculation of subtotal from submitted line items
                $subtotal = 0;
                if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
                    foreach ($_POST['item_description'] as $index => $description) {
                        if (!empty($description)) {
                            $quantity = max(1, intval($_POST['quantity'][$index] ?? 1));
                            $unitPrice = floatval($_POST['unit_price'][$index] ?? 0);
                            $lineTotal = $quantity * $unitPrice;
                            $subtotal += $lineTotal;
                        }
                    }
                }
                
                // Calculate discount
                $discountValue = 0;
                if ($discountType === 'percentage') {
                    $discountValue = ($subtotal * $discountAmount) / 100;
                } else {
                    $discountValue = $discountAmount;
                }
                
                // Calculate total amount
                $totalAmount = $subtotal - $discountValue;
                if ($totalAmount < 0) $totalAmount = 0;
                
                // Update invoice
                $stmt = $pdo->prepare("UPDATE invoices 
                    SET invoice_date = ?, 
                    subtotal = ?, 
                    discount_amount = ?, 
                    discount_type = ?, 
                    total_amount = ?, 
                    due_amount = ?, 
                    notes = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?");
                
                $stmt->execute([
                    $invoiceDate,
                    $subtotal,
                    $discountAmount,
                    $discountType,
                    $totalAmount,
                    $totalAmount,
                    $notes,
                    $invoiceId
                ]);
                
                // Delete existing invoice items
                $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
                $stmt->execute([$invoiceId]);
                
                // Insert new invoice items
                if (isset($_POST['item_description']) && is_array($_POST['item_description'])) {
                    foreach ($_POST['item_description'] as $index => $description) {
                        if (!empty($description)) {
                            $quantity = max(1, intval($_POST['quantity'][$index] ?? 1));
                            $unitPrice = floatval($_POST['unit_price'][$index] ?? 0);
                            $lineTotal = $quantity * $unitPrice;
                            
                            $stmt = $pdo->prepare("INSERT INTO invoice_items 
                                (invoice_id, item_description, quantity, unit_price, line_total) 
                                VALUES (?, ?, ?, ?, ?)");
                            
                            $stmt->execute([$invoiceId, $description, $quantity, $unitPrice, $lineTotal]);
                        }
                    }
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Log activity
                logActivity('Invoice Updated', "Invoice updated: {$invoice['invoice_number']} for patient: {$invoice['patient_name']}");
                
                $success = "Invoice updated successfully!";
                header("Location: " . BASE_URL . "modules/billing/view-invoice.php?id=$invoiceId&success=" . urlencode($success));
                exit();
                
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Invoice Update Error: " . $e->getMessage());
                $error = 'Failed to update invoice. Please try again.';
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
        <h2>Edit Invoice</h2>
        <p class="text-muted mb-0">Number: <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
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

<!-- Patient Info Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <h5 class="mb-1">Patient: <?php echo htmlspecialchars($invoice['patient_name']); ?></h5>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($invoice['patient_code']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                           value="<?php echo htmlspecialchars($invoice['invoice_date']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invoice Number</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" readonly>
                </div>
            </div>
            
            <!-- Invoice Items Section -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Invoice Items</h6>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addInvoiceItemRow()">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div id="invoice-items-container">
                        <?php foreach ($existingInvoiceItems as $item): ?>
                            <div class="invoice-item-row mb-3">
                                <div class="row">
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label small">Description <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control item-description" name="item_description[]" 
                                               value="<?php echo htmlspecialchars($item['item_description']); ?>" required placeholder="Item description">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control quantity" name="quantity[]" 
                                               value="<?php echo $item['quantity']; ?>" min="1" required onchange="calculateTotals()">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Unit Price <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control unit-price" name="unit_price[]" 
                                               value="<?php echo $item['unit_price']; ?>" min="0" step="0.01" required onchange="calculateTotals()">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Line Total</label>
                                        <input type="text" class="form-control line-total" name="line_total[]" 
                                               value="<?php echo number_format($item['line_total'], 2); ?>" readonly>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-danger w-100" onclick="removeInvoiceItemRow(this)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($existingInvoiceItems)): ?>
                            <div class="invoice-item-row mb-3">
                                <div class="row">
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label small">Description <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control item-description" name="item_description[]" 
                                               required placeholder="Item description">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control quantity" name="quantity[]" 
                                               value="1" min="1" required onchange="calculateTotals()">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Unit Price <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control unit-price" name="unit_price[]" 
                                               value="0.00" min="0" step="0.01" required onchange="calculateTotals()">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label class="form-label small">Line Total</label>
                                        <input type="text" class="form-control line-total" name="line_total[]" 
                                               value="0.00" readonly>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label small">&nbsp;</label>
                                        <button type="button" class="btn btn-danger w-100" onclick="removeInvoiceItemRow(this)" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Totals Section -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="subtotal" class="form-label">Subtotal</label>
                    <input type="text" class="form-control fw-bold" id="subtotal" value="<?php echo number_format($invoice['subtotal'], 2); ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label for="discount_type" class="form-label">Discount Type</label>
                    <select class="form-select" id="discount_type" name="discount_type" onchange="calculateTotals()">
                        <option value="flat" <?php echo $invoice['discount_type'] === 'flat' ? 'selected' : ''; ?>>Flat Amount</option>
                        <option value="percentage" <?php echo $invoice['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="discount_amount" class="form-label">Discount Value</label>
                    <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                           value="<?php echo $invoice['discount_amount']; ?>" min="0" step="0.01" onchange="calculateTotals()">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="total_amount" class="form-label">Total Amount</label>
                    <input type="text" class="form-control fw-bold fs-5" id="total_amount" value="<?php echo number_format($invoice['total_amount'], 2); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="1" placeholder="Optional notes..."><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?php echo BASE_URL; ?>modules/billing/view-invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-file-earmark-text me-2"></i>Update Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Invoice items management
function addInvoiceItemRow() {
    const container = document.getElementById('invoice-items-container');
    const newRow = document.createElement('div');
    newRow.className = 'invoice-item-row mb-3';
    newRow.innerHTML = `
        <div class="row">
            <div class="col-md-5 mb-2">
                <label class="form-label small">Description <span class="text-danger">*</span></label>
                <input type="text" class="form-control item-description" name="item_description[]" required placeholder="Item description">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Quantity <span class="text-danger">*</span></label>
                <input type="number" class="form-control quantity" name="quantity[]" value="1" min="1" required onchange="calculateTotals()">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Unit Price <span class="text-danger">*</span></label>
                <input type="number" class="form-control unit-price" name="unit_price[]" value="0.00" min="0" step="0.01" required onchange="calculateTotals()">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Line Total</label>
                <input type="text" class="form-control line-total" name="line_total[]" value="0.00" readonly>
            </div>
            <div class="col-md-1 mb-2">
                <label class="form-label small">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="removeInvoiceItemRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    updateRemoveButtons();
}

function removeInvoiceItemRow(button) {
    const row = button.closest('.invoice-item-row');
    row.remove();
    updateRemoveButtons();
    calculateTotals();
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.invoice-item-row');
    rows.forEach((row, index) => {
        const removeBtn = row.querySelector('button[type="button"]');
        removeBtn.disabled = rows.length === 1;
    });
}

function calculateTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.invoice-item-row');
    
    rows.forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const lineTotal = quantity * unitPrice;
        row.querySelector('.line-total').value = lineTotal.toFixed(2);
        subtotal += lineTotal;
    });
    
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    
    const discountType = document.getElementById('discount_type').value;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    
    let discountValue = 0;
    if (discountType === 'percentage') {
        discountValue = (subtotal * discountAmount) / 100;
    } else {
        discountValue = discountAmount;
    }
    
    const totalAmount = subtotal - discountValue;
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}

// Initial calculation
calculateTotals();
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
