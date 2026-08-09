<?php
$pageTitle = 'Doctor Dashboard';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../modules/appointments/helpers.php';
requireAuth();
checkRole(['Doctor']);

$user = getCurrentUser();

// Get doctor's today's appointments count
$today = date('Y-m-d');
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled', 'No Show')");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $result = $stmt->fetch();
    $todayAppointments = $result['total'];
} catch (PDOException $e) {
    $todayAppointments = '--';
    error_log("Doctor's today's appointments count error: " . $e->getMessage());
}

// Get doctor's upcoming appointments count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = ? AND appointment_date > ? AND status IN ('Pending', 'Confirmed')");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $result = $stmt->fetch();
    $upcomingAppointments = $result['total'];
} catch (PDOException $e) {
    $upcomingAppointments = '--';
    error_log("Doctor's upcoming appointments count error: " . $e->getMessage());
}

// Get doctor's total patients count
try {
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) as total FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $totalPatients = $result['total'];
} catch (PDOException $e) {
    $totalPatients = '--';
    error_log("Doctor's patients count error: " . $e->getMessage());
}

// Get doctor's today's appointments for mini table
try {
    $stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name, p.patient_code 
                          FROM appointments a 
                          JOIN patients p ON a.patient_id = p.id 
                          WHERE a.doctor_id = ? AND a.appointment_date = ? 
                          ORDER BY a.appointment_time ASC LIMIT 5");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $todayAppointmentsList = $stmt->fetchAll();
} catch (PDOException $e) {
    $todayAppointmentsList = [];
    error_log("Doctor's today's appointments list error: " . $e->getMessage());
}
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
                <h3><?php echo $todayAppointments; ?></h3>
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
                <h3><?php echo $totalPatients; ?></h3>
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
                <h3><?php echo $upcomingAppointments; ?></h3>
                <p>Upcoming Appointments</p>
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
                <?php if (empty($todayAppointmentsList)): ?>
                    <p class="text-muted">No appointments scheduled for today.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAppointmentsList as $appointment): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($appointment['appointment_time']); ?></strong></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>modules/patients/view.php?id=<?php echo $appointment['patient_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                            </a>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($appointment['patient_code']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_type'] ?? '--'); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                $badgeClass = '';
                                                switch($appointment['status']) {
                                                    case 'Pending': $badgeClass = 'bg-warning'; break;
                                                    case 'Confirmed': $badgeClass = 'bg-primary'; break;
                                                    case 'Completed': $badgeClass = 'bg-success'; break;
                                                    case 'Cancelled': $badgeClass = 'bg-danger'; break;
                                                    case 'No Show': $badgeClass = 'bg-secondary'; break;
                                                    default: $badgeClass = 'bg-secondary';
                                                }
                                                echo $badgeClass;
                                            ?>">
                                                <?php echo htmlspecialchars($appointment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>modules/appointments/view.php?id=<?php echo $appointment['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
