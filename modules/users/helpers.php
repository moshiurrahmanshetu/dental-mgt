<?php
// User Management Helper Functions

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

/**
 * Get role badge class
 * 
 * @param string $roleName Role name
 * @return string Bootstrap badge class
 */
function getRoleBadgeClass($roleName) {
    switch ($roleName) {
        case 'Admin':
            return 'bg-danger';
        case 'Doctor':
            return 'bg-primary';
        case 'Receptionist':
            return 'bg-info';
        case 'Patient':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}

/**
 * Get status badge class
 * 
 * @param string $status Status
 * @return string Bootstrap badge class
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'bg-success';
        case 'inactive':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}

/**
 * Upload avatar image
 * 
 * @param array $file $_FILES array element
 * @param string $targetDir Target directory
 * @param string $oldAvatar Old avatar filename to delete
 * @return string|false New filename or false on failure
 */
function uploadAvatar($file, $targetDir, $oldAvatar = null) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }
    
    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return false;
    }
    
    // Generate random filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $targetDir . '/' . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Delete old avatar if exists
        if ($oldAvatar && file_exists($targetDir . '/' . $oldAvatar)) {
            unlink($targetDir . '/' . $oldAvatar);
        }
        return $newFilename;
    }
    
    return false;
}

/**
 * Get user initials for avatar placeholder
 * 
 * @param string $fullName User's full name
 * @return string Initials (up to 2 characters)
 */
function getUserInitials($fullName) {
    $words = explode(' ', $fullName);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials ?: 'U';
}
?>
