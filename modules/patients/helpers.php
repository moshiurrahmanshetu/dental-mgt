<?php
// Patient Management Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

function generatePatientCode($pdo) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(patient_code, 5) AS UNSIGNED)) as max_code FROM patients");
        $stmt->execute();
        $result = $stmt->fetch();
        $maxCode = $result['max_code'] ?? 0;
        $nextCode = $maxCode + 1;
        $paddedCode = str_pad($nextCode, 6, '0', STR_PAD_LEFT);
        $patientCode = 'PAT-' . $paddedCode;
        $pdo->commit();
        return $patientCode;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Patient Code Generation Error: " . $e->getMessage());
        throw new Exception("Failed to generate patient code");
    }
}

function calculateAge($dateOfBirth) {
    if (empty($dateOfBirth)) {
        return '--';
    }
    $birthDate = new DateTime($dateOfBirth);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

function validateImageUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024;
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'message' => 'No file uploaded or upload error'];
    }
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'File size exceeds 2MB limit'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'message' => 'Only JPG and PNG images are allowed'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $errorMessage = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Unknown upload error';
        return ['valid' => false, 'message' => $errorMessage];
    }
    return ['valid' => true, 'message' => ''];
}

function saveUploadedPhoto($file, $uploadDir) {
    $validation = validateImageUpload($file);
    if (!$validation['valid']) {
        return ['success' => false, 'filename' => '', 'message' => $validation['message']];
    }
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('patient_', true) . '.' . $extension;
    $filepath = $uploadDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'message' => ''];
    } else {
        return ['success' => false, 'filename' => '', 'message' => 'Failed to save uploaded file'];
    }
}

function deletePatientPhoto($filename, $uploadDir) {
    if (empty($filename)) {
        return true;
    }
    $filepath = $uploadDir . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}
?>
