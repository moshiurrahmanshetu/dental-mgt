<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/auth_functions.php';
requireAuth();
checkRole(['Admin']);

$user = getCurrentUser();

// Get real patient count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalPatients = $result['total'];
} catch (PDOException $e) {
    $totalPatients = '--';
    error_log("Patient count error: " . $e->getMessage());
}

// Get today's appointments count
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = ? AND status NOT IN ('Cancelled', 'No Show')");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    $todayAppointments = $result['total'];
} catch (PDOException $e) {
    $todayAppointments = '--';
    error_log("Today's appointments count error: " . $e->getMessage());
}

// Get upcoming appointments count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date > ? AND status IN ('Pending', 'Confirmed')");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    $upcomingAppointments = $result['total'];
} catch (PDOException $e) {
    $upcomingAppointments = '--';
    error_log("Upcoming appointments count error: " . $e->getMessage());
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="welcome-section mb-4">
    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    <p class="text-muted">Here's what's happening with your dental practice today.</p>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $totalPatients; ?></h3>
                <p>Total Patients</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $todayAppointments; ?></h3>
                <p>Today's Appointments</p>
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
                <p>Today's Revenue</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-content">
                <h3>--</h3>
                <p>Active Doctors</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Activity logs will be displayed here in future phases.</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <h4 class="mb-1"><?php echo $upcomingAppointments; ?></h4>
                <p class="text-muted">Future appointments (Pending & Confirmed)</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
