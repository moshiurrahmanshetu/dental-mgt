<?php
// Appointment Management Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

/**
 * Generate next appointment code in format APT-000001
 * Uses transaction to prevent race conditions
 * 
 * @param PDO $pdo Database connection
 * @return string Next appointment code
 */
function generateAppointmentCode($pdo) {
    try {
        // Start transaction to prevent race conditions
        $pdo->beginTransaction();
        
        // Get the maximum numeric part from existing appointment codes
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(appointment_code, 5) AS UNSIGNED)) as max_code FROM appointments");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxCode = $result['max_code'] ?? 0;
        $nextCode = $maxCode + 1;
        
        // Pad with leading zeros to 6 digits
        $paddedCode = str_pad($nextCode, 6, '0', STR_PAD_LEFT);
        $appointmentCode = 'APT-' . $paddedCode;
        
        $pdo->commit();
        
        return $appointmentCode;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Appointment Code Generation Error: " . $e->getMessage());
        throw new Exception("Failed to generate appointment code");
    }
}

/**
 * Get available doctors (active users with Doctor role)
 * 
 * @param PDO $pdo Database connection
 * @return array Array of doctor users
 */
function getAvailableDoctors($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.email FROM users u 
                                  JOIN roles r ON u.role_id = r.id 
                                  WHERE r.role_name = 'Doctor' AND u.status = 'active' 
                                  ORDER BY u.full_name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get Available Doctors Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check doctor availability for specific date and time
 * 
 * @param PDO $pdo Database connection
 * @param int $doctorId Doctor user ID
 * @param string $date Appointment date
 * @param string $time Appointment time
 * @param int $excludeAppointmentId Exclude this appointment from conflict check (for edits)
 * @return bool True if available, false if conflict exists
 */
function checkDoctorAvailability($pdo, $doctorId, $date, $time, $excludeAppointmentId = null) {
    try {
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND appointment_time = ? 
                AND status NOT IN ('Cancelled', 'No Show')";
        
        $params = [$doctorId, $date, $time];
        
        if ($excludeAppointmentId && is_numeric($excludeAppointmentId)) {
            $sql .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    } catch (PDOException $e) {
        error_log("Doctor Availability Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get appointment status badge class
 * 
 * @param string $status Appointment status
 * @return string Bootstrap badge class
 */
function getStatusBadgeClass($status) {
    $badgeClasses = [
        'Pending' => 'bg-warning',
        'Confirmed' => 'bg-primary',
        'Completed' => 'bg-success',
        'Cancelled' => 'bg-danger',
        'No Show' => 'bg-secondary'
    ];
    
    return $badgeClasses[$status] ?? 'bg-secondary';
}

/**
 * Check if status transition is allowed
 * 
 * @param string $currentStatus Current appointment status
 * @param string $newStatus Desired new status
 * @return bool True if transition is allowed
 */
function isStatusTransitionAllowed($currentStatus, $newStatus) {
    $allowedTransitions = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Confirmed' => ['Completed', 'Cancelled', 'No Show'],
        'Completed' => [], // No transitions allowed from completed
        'Cancelled' => [], // No transitions allowed from cancelled
        'No Show' => [] // No transitions allowed from no show
    ];
    
    return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
}

/**
 * Get allowed status transitions for a role
 * 
 * @param string $role User role
 * @return array Array of allowed status transitions
 */
function getAllowedStatusTransitions($role) {
    $roleTransitions = [
        'Admin' => [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['Completed', 'Cancelled', 'No Show']
        ],
        'Receptionist' => [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['Cancelled']
        ],
        'Doctor' => [
            'Confirmed' => ['Completed', 'No Show']
        ]
    ];
    
    return $roleTransitions[$role] ?? [];
}
?>
