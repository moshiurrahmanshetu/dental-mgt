<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Require authentication
requireAuth();

$searchTerm = trim($_GET['term'] ?? '');

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $searchParam = '%' . $searchTerm . '%';
    $stmt = $pdo->prepare("SELECT id, full_name, patient_code, phone FROM patients 
                          WHERE (full_name LIKE ? OR phone LIKE ? OR patient_code LIKE ?) 
                          AND status = 'active' 
                          ORDER BY full_name ASC 
                          LIMIT 10");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $patients = $stmt->fetchAll();
    
    echo json_encode($patients);
} catch (PDOException $e) {
    error_log("Patient Search Error: " . $e->getMessage());
    echo json_encode([]);
}
