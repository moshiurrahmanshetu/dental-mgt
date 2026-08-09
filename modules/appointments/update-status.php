<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Role-based access control
$currentRole = $_SESSION['role_name'];
if (!in_array($currentRole, ['Admin', 'Doctor', 'Receptionist'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// CSRF validation
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$appointmentId = intval($_POST['appointment_id'] ?? 0);
$newStatus = trim($_POST['new_status'] ?? '');

if ($appointmentId <= 0 || empty($newStatus)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Get appointment details
$stmt = $pdo->prepare("SELECT a.*, p.full_name as patient_name FROM appointments a 
                      JOIN patients p ON a.patient_id = p.id 
                      WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    exit();
}

// Doctor isolation check - doctors can only update their own appointments
if ($currentRole === 'Doctor' && $appointment['doctor_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You can only update your own appointments']);
    exit();
}

// Check if status transition is allowed
if (!isStatusTransitionAllowed($appointment['status'], $newStatus)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status transition from ' . $appointment['status'] . ' to ' . $newStatus]);
    exit();
}

// Check if user has permission to make this status change
$allowedTransitions = getAllowedStatusTransitions($currentRole);
if (!isset($allowedTransitions[$appointment['status']]) || !in_array($newStatus, $allowedTransitions[$appointment['status']])) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to change status to ' . $newStatus]);
    exit();
}

try {
    // Update appointment status
    $stmt = $pdo->prepare("UPDATE appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$newStatus, $appointmentId]);
    
    // Log activity
    logActivity('Appointment Status Changed', "Appointment {$appointment['appointment_code']} status changed from {$appointment['status']} to {$newStatus}");
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    
} catch (PDOException $e) {
    error_log("Status Update Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}
