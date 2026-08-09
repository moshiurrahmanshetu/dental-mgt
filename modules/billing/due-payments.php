<?php
$pageTitle = 'Due Payments';
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can view due payments
checkRole(['Admin', 'Receptionist']);

$user = getCurrentUser();

// Filter parameters
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM invoices i 
                  JOIN patients p ON i.patient_id = p.id 
                  WHERE i.due_amount > 0 AND i.status = 'active'";
$stmt = $pdo->prepare($countQuery);
$stmt->execute();
$totalResult = $stmt->fetch();
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get invoices with due amounts, ordered by invoice_date ASC (oldest unpaid first)
$query = "SELECT i.*, p.full_name as patient_name, p.patient_code, 
          p.phone as patient_phone, p.profile_photo as patient_photo
          FROM invoices i 
          JOIN patients p ON i.patient_id = p.id 
          WHERE i.due_amount > 0 AND i.status = 'active'
          ORDER BY i.invoice_date ASC, i.created_at ASC 
          LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute();
$invoices = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Due Payments</h2>
        <p class="text-muted mb-0">Invoices with outstanding balances</p>
    </div>
    <a href="<?php echo BASE_URL; ?>modules/billing/list-invoices.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to All Invoices
    </a>
</div>

<!-- Due Payments Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Total Due Amount</h5>
                <h3 class="card-text">$<?php 
                    $stmt = $pdo->query("SELECT SUM(due_amount) as total FROM invoices WHERE due_amount > 0 AND status = 'active'");
                    $result = $stmt->fetch();
                    echo number_format($result['total'] ?? 0, 2);
                ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning">
            <div class="card-body">
                <h5 class="card-title">Partially Paid</h5>
                <h3 class="card-text"><?php 
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE due_amount > 0 AND paid_amount > 0 AND status = 'active'");
                    $result = $stmt->fetch();
                    echo $result['count'] ?? 0;
                ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger">
            <div class="card-body">
                <h5 class="card-title">Unpaid</h5>
                <h3 class="card-text"><?php 
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE due_amount > 0 AND paid_amount = 0 AND status = 'active'");
                    $result = $stmt->fetch();
                    echo $result['count'] ?? 0;
                ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Due Payments Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <p class="text-muted mt-3">No due payments - all invoices are paid!</p>
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
                                <td class="text-danger fw-bold">$<?php echo number_format($invoice['due_amount'], 2); ?></td>
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
                                        <a href="<?php echo BASE_URL; ?>modules/billing/add-payment.php?invoice_id=<?php echo $invoice['id']; ?>" 
                                           class="btn btn-success" title="Record Payment">
                                            <i class="bi bi-cash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Due Payments pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <li class="page-item active">
                                    <span class="page-link"><?php echo $i; ?></span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
