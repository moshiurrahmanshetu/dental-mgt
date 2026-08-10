<?php
// Installer Bootstrap - Shared Constants and Session Handling
// Following Rule 1: Safe session_start() pattern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Following Rule 2: Define all paths as absolute using __DIR__
define('INSTALLER_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(INSTALLER_ROOT));
define('CONFIG_PATH', PROJECT_ROOT . '/config');
define('LOGS_PATH', PROJECT_ROOT . '/logs');
define('TEMP_PATH', INSTALLER_ROOT . '/temp');
define('LOCK_FILE', CONFIG_PATH . '/installed.lock');

// Session namespace for installer data (Rule 1)
define('INSTALLER_SESSION_KEY', 'installer');

// Installer session helper functions
function setInstallerSession($key, $value) {
    $_SESSION[INSTALLER_SESSION_KEY][$key] = $value;
}

function getInstallerSession($key, $default = null) {
    return $_SESSION[INSTALLER_SESSION_KEY][$key] ?? $default;
}

function clearInstallerSession() {
    unset($_SESSION[INSTALLER_SESSION_KEY]);
}

// Check if already installed
function isInstalled() {
    return file_exists(LOCK_FILE);
}

// Redirect if already installed
function checkAlreadyInstalled() {
    if (isInstalled()) {
        die('<!DOCTYPE html><html><head><title>Already Installed</title></head><body style="font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5;"><div style="text-align: center; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"><h1 style="color: #dc3545;">Already Installed</h1><p>The application is already installed. To reinstall, delete the lock file at: ' . htmlspecialchars(LOCK_FILE) . '</p><p><a href="' . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Go to Application</a></p></div></body></html>');
    }
}

// Install lock file management
function createInstallLock() {
    $timestamp = date('Y-m-d H:i:s');
    $content = "Installed on: $timestamp\n";
    file_put_contents(LOCK_FILE, $content);
}

// Helper to format file size
function formatFileSize($bytes) {
    if (!is_numeric($bytes)) {
        return 'Unknown';
    }
    
    $bytes = (int)$bytes;
    
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}

// PHP upload max file size info
function getUploadMaxFileSize() {
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    
    // Convert to bytes
    $uploadMaxBytes = returnBytes($uploadMax);
    $postMaxBytes = returnBytes($postMax);
    
    return min($uploadMaxBytes, $postMaxBytes);
}

// Helper to convert php.ini shorthand notation to bytes
function returnBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;
    
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}
?>
