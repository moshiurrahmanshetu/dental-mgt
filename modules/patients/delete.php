<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/helpers.php';

// Role-based access control
checkRole(['Admin', 'Receptionist']);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'modules/patients/list.php');
    exit();
}

// CSRF validation
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: ' . BASE_URL . 'modules/patients/list.php');
    exit();
}

// Validate patient ID
$patientId = intval($_POST['patient_id'] ?? 0);
if ($patientId <= 0) {
    $_SESSION['error'] = 'Invalid patient ID.';
    header('Location: ' . BASE_URL . 'modules/patients/list.php');
    exit();
}

try {
    // Fetch patient info for logging
    $stmt = $pdo->prepare("SELECT full_name, patient_code FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        $_SESSION['error'] = 'Patient not found.';
        header('Location: ' . BASE_URL . 'modules/patients/list.php');
        exit();
    }
    
    // Soft delete - set status to inactive
    $stmt = $pdo->prepare("UPDATE patients SET status = 'inactive' WHERE id = ?");
    $result = $stmt->execute([$patientId]);
    
    if ($result) {
        // Log activity
        logActivity('Patient Deactivated', "Patient deactivated: {$patient['full_name']} (Code: {$patient['patient_code']})");
        
        $_SESSION['success'] = 'Patient deactivated successfully.';
    } else {
        $_SESSION['error'] = 'Failed to deactivate patient. Please try again.';
    }
} catch (PDOException $e) {
    error_log("Patient Delete Error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
}

// Redirect back to list
header('Location: ' . BASE_URL . 'modules/patients/list.php');
exit();
?>
