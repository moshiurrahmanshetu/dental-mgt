<?php
$pageTitle = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
requireAuth();
checkRole(['Doctor']);

$user = getCurrentUser();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="welcome-section mb-4">
    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    <p class="text-muted">Here's your schedule and patient information for today.</p>
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
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Pending Follow-ups</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-journal-medical"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Medical Records</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Today's Schedule</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Your appointment schedule will be displayed here in future phases.</p>
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
                    <button class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>New Appointment</button>
                    <button class="btn btn-success"><i class="bi bi-journal-plus me-2"></i>Add Medical Record</button>
                    <button class="btn btn-info"><i class="bi bi-search me-2"></i>Search Patient</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
