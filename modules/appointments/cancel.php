<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control - only Admin and Receptionist can cancel appointments
checkRole(['Admin', 'Receptionist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// CSRF validation
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token. Please try again.');
}

$appointmentId = intval($_POST['appointment_id'] ?? 0);
if ($appointmentId <= 0) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name FROM appointments a 
                      JOIN patients p ON a.patient_id = p.id 
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: " . BASE_URL . "modules/appointments/list.php");
    exit();
}

// Check if appointment can be cancelled (not already completed or cancelled)
if (!in_array($appointment['status'], ['Pending', 'Confirmed'])) {
    $error = "This appointment is already {$appointment['status']} and cannot be cancelled.";
    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&error=" . urlencode($error));
    exit();
}

try {
    // Update appointment status to Cancelled
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'Cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$appointmentId]);
    
    // Log activity
    logActivity('Appointment Cancelled', "Appointment cancelled: {$appointment['appointment_code']} for patient: {$appointment['patient_name']}");
    
    $success = "Appointment cancelled successfully!";
    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&success=" . urlencode($success));
    exit();
    
} catch (PDOException $e) {
    error_log("Appointment Cancellation Error: " . $e->getMessage());
    $error = 'Failed to cancel appointment. Please try again.';
    header("Location: " . BASE_URL . "modules/appointments/view.php?id=$appointmentId&error=" . urlencode($error));
    exit();
}
