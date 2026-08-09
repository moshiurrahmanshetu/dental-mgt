<?php
$pageTitle = 'Receptionist Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
requireAuth();
checkRole(['Receptionist']);

$user = getCurrentUser();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="welcome-section mb-4">
    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    <p class="text-muted">Manage appointments, patients, and billing from here.</p>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Today's Appointments</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Total Patients</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Pending Bills</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-telephone"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>New Inquiries</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Today's Appointments</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Today's appointment list will be displayed here in future phases.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Registrations</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Recently registered patients will be displayed here in future phases.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
