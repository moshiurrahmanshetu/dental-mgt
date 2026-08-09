<?php
$pageTitle = 'Patient Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
requireAuth();
checkRole(['Patient']);

$user = getCurrentUser();

// Note: Patient self-service portal linking is out of scope for this version per original requirements.
// The patient role exists but is not connected to real patient data in the database.
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="welcome-section mb-4">
    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    <p class="text-muted">View your appointments, medical history, and billing information.</p>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Upcoming Appointments</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="bi bi-journal-medical"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Medical Records</p>
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
                <p>Outstanding Bills</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-prescription2"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Prescriptions</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">My Appointments</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Your appointment history will be displayed here in future phases.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary"><i class="bi bi-calendar-plus me-2"></i>Book Appointment</button>
                    <button class="btn btn-success"><i class="bi bi-file-earmark-medical me-2"></i>View Medical History</button>
                    <button class="btn btn-info"><i class="bi bi-receipt me-2"></i>View Bills</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
