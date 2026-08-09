<?php
// Treatment Management Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

/**
 * Generate next treatment record code in format TRT-000001
 * Uses transaction to prevent race conditions
 * 
 * @param PDO $pdo Database connection
 * @return string Next treatment record code
 */
function generateRecordCode($pdo) {
    try {
        // Only start transaction if one is not already active
        $transactionStarted = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $transactionStarted = true;
        }
        
        // Get the maximum numeric part from existing treatment record codes
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(record_code, 5) AS UNSIGNED)) as max_code FROM treatment_records");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $maxCode = $result['max_code'] ?? 0;
        $nextCode = $maxCode + 1;
        
        // Pad with leading zeros to 6 digits
        $paddedCode = str_pad($nextCode, 6, '0', STR_PAD_LEFT);
        $recordCode = 'TRT-' . $paddedCode;
        
        // Only commit if we started the transaction
        if ($transactionStarted) {
            $pdo->commit();
        }
        
        return $recordCode;
    } catch (PDOException $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Treatment Record Code Generation Error: " . $e->getMessage());
        throw new Exception("Failed to generate treatment record code");
    }
}

/**
 * Get treatment type options for dropdown
 * 
 * @return array Array of treatment type options
 */
function getTreatmentTypeOptions() {
    return [
        'Dental Checkup',
        'Tooth Filling',
        'Tooth Extraction',
        'Root Canal Treatment',
        'Teeth Cleaning',
        'Crown / Bridge',
        'Tooth Whitening',
        'Other'
    ];
}
?>
