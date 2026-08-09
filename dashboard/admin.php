<?php
$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../modules/billing/helpers.php';
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

// Get total treatment records count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM treatment_records WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalTreatmentRecords = $result['total'];
} catch (PDOException $e) {
    $totalTreatmentRecords = '--';
    error_log("Total treatment records count error: " . $e->getMessage());
}

// Get today's revenue
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_date = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    $todayRevenue = $result['total'];
} catch (PDOException $e) {
    $todayRevenue = '--';
    error_log("Today's revenue error: " . $e->getMessage());
}

// Get total due amount
try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(due_amount), 0) as total FROM invoices WHERE due_amount > 0 AND status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalDueAmount = $result['total'];
} catch (PDOException $e) {
    $totalDueAmount = '--';
    error_log("Total due amount error: " . $e->getMessage());
}

// Get total doctors count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'Doctor' AND u.status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalDoctors = $result['total'];
} catch (PDOException $e) {
    $totalDoctors = '--';
    error_log("Total doctors count error: " . $e->getMessage());
}

// Get completed appointments count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE status = 'Completed'");
    $stmt->execute();
    $result = $stmt->fetch();
    $completedAppointments = $result['total'];
} catch (PDOException $e) {
    $completedAppointments = '--';
    error_log("Completed appointments count error: " . $e->getMessage());
}

// Get recent activity
try {
    $stmt = $pdo->prepare("SELECT al.*, u.full_name as actor_name FROM activity_logs al JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentActivity = [];
    error_log("Recent activity error: " . $e->getMessage());
}

// Get upcoming appointments (first 5)
try {
    $stmt = $pdo->prepare("SELECT a.appointment_code, a.appointment_date, a.appointment_time, p.full_name as patient_name, u.full_name as doctor_name 
                          FROM appointments a 
                          JOIN patients p ON a.patient_id = p.id 
                          JOIN users u ON a.doctor_id = u.id 
                          WHERE a.appointment_date >= ? AND a.status IN ('Pending', 'Confirmed') 
                          ORDER BY a.appointment_date ASC, a.appointment_time ASC 
                          LIMIT 5");
    $stmt->execute([$today]);
    $upcomingAppointmentsList = $stmt->fetchAll();
} catch (PDOException $e) {
    $upcomingAppointmentsList = [];
    error_log("Upcoming appointments list error: " . $e->getMessage());
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="welcome-section mb-4">
    <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
    <p class="text-muted">Here's what's happening with your dental practice today.</p>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
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
    
    <div class="col-md-3 mb-3">
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
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-calendar2-event"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $upcomingAppointments; ?></h3>
                <p>Upcoming</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-secondary">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $completedAppointments; ?></h3>
                <p>Completed</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
                <h3>$<?php echo number_format($todayRevenue, 2); ?></h3>
                <p>Today's Revenue</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="bi bi-cash"></i>
            </div>
            <div class="stat-content">
                <h3>$<?php echo number_format($totalDueAmount, 2); ?></h3>
                <p>Total Due</p>
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
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted">No recent activity.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['actor_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingAppointmentsList)): ?>
                    <p class="text-muted">No upcoming appointments.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingAppointmentsList as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
