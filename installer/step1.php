<?php
// Step 1: Requirements Check
require_once INSTALLER_ROOT . '/bootstrap.php';

$requirements = [];
$allPassed = true;

// Check PHP version
$phpVersion = phpversion();
$phpVersionPassed = version_compare($phpVersion, '8.0', '>=');
$requirements[] = [
    'name' => 'PHP Version >= 8.0',
    'status' => $phpVersionPassed ? 'success' : 'error',
    'message' => $phpVersionPassed ? 'PHP ' . $phpVersion . ' (Good)' : 'PHP ' . $phpVersion . ' (Required: 8.0 or higher)'
];

if (!$phpVersionPassed) {
    $allPassed = false;
}

// Check PDO extension
$pdoExtension = extension_loaded('pdo');
$pdoExtensionPassed = $pdoExtension;
$requirements[] = [
    'name' => 'PDO Extension',
    'status' => $pdoExtensionPassed ? 'success' : 'error',
    'message' => $pdoExtensionPassed ? 'PDO extension loaded' : 'PDO extension required'
];

if (!$pdoExtensionPassed) {
    $allPassed = false;
}

// Check pdo_mysql extension
$pdoMysqlExtension = extension_loaded('pdo_mysql');
$pdoMysqlExtensionPassed = $pdoMysqlExtension;
$requirements[] = [
    'name' => 'PDO MySQL Extension',
    'status' => $pdoMysqlExtensionPassed ? 'success' : 'error',
    'message' => $pdoMysqlExtensionPassed ? 'pdo_mysql extension loaded' : 'pdo_mysql extension required'
];

if (!$pdoMysqlExtensionPassed) {
    $allPassed = false;
}

// Check fileinfo extension (non-critical)
$fileinfoExtension = extension_loaded('fileinfo');
$fileinfoExtensionPassed = $fileinfoExtension;
$requirements[] = [
    'name' => 'fileinfo Extension',
    'status' => $fileinfoExtensionPassed ? 'success' : 'warning',
    'message' => $fileinfoExtensionPassed ? 'fileinfo extension loaded' : 'fileinfo extension recommended for file validation'
];

// Check GD extension (non-critical)
$gdExtension = extension_loaded('gd');
$gdExtensionPassed = $gdExtension;
$requirements[] = [
    'name' => 'GD Extension',
    'status' => $gdExtensionPassed ? 'success' : 'warning',
    'message' => $gdExtensionPassed ? 'GD extension loaded' : 'GD extension recommended for image processing'
];

// Check config folder writability
$configPathWritable = is_writable(CONFIG_PATH);
$configPathPassed = $configPathWritable;
$requirements[] = [
    'name' => 'Config Folder Writable',
    'status' => $configPathPassed ? 'success' : 'error',
    'message' => $configPathPassed ? 'Config folder is writable' : 'Config folder must be writable (chmod 755)'
];

if (!$configPathPassed) {
    $allPassed = false;
}

// Check logs folder writability
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}
$logsPathWritable = is_writable(LOGS_PATH);
$logsPathPassed = $logsPathWritable;
$requirements[] = [
    'name' => 'Logs Folder Writable',
    'status' => $logsPathPassed ? 'success' : 'error',
    'message' => $logsPathPassed ? 'Logs folder is writable' : 'Logs folder must be writable (chmod 755)'
];

if (!$logsPathPassed) {
    $allPassed = false;
}

// Check temp folder writability
if (!is_dir(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0755, true);
}
$tempPathWritable = is_writable(TEMP_PATH);
$tempPathPassed = $tempPathWritable;
$requirements[] = [
    'name' => 'Temp Folder Writable',
    'status' => $tempPathPassed ? 'success' : 'error',
    'message' => $tempPathPassed ? 'Temp folder is writable' : 'Temp folder must be writable (chmod 755)'
];

if (!$tempPathPassed) {
    $allPassed = false;
}

// Upload file size check
$uploadMaxFileSize = getUploadMaxFileSize();
$uploadMaxFileSizeMB = is_numeric($uploadMaxFileSize) ? $uploadMaxFileSize / 1024 / 1024 : 0;
$uploadMaxFileSizePassed = is_numeric($uploadMaxFileSize) && $uploadMaxFileSize >= 5 * 1024 * 1024; // Minimum 5MB
$requirements[] = [
    'name' => 'File Upload Size',
    'status' => $uploadMaxFileSizePassed ? 'success' : 'warning',
    'message' => $uploadMaxFileSizePassed ? 'Max upload size: ' . formatFileSize($uploadMaxFileSize) : 'Max upload size: ' . formatFileSize($uploadMaxFileSize) . ' (Recommended: 5MB minimum)'
];

// Save step completion status
setInstallerSession('step1', true);
?>

<h3>System Requirements Check</h3>
<p>Please ensure your server meets the following requirements before proceeding.</p>

<?php if (!$allPassed): ?>
    <div class="error-message">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <strong>Some required requirements are not met. Please fix the issues above before proceeding.</strong>
    </div>
<?php endif; ?>

<?php foreach ($requirements as $req): ?>
    <div class="requirement-item <?php echo $req['status']; ?>">
        <div class="icon">
            <?php if ($req['status'] === 'success'): ?>
                <i class="bi bi-check-circle-fill"></i>
            <?php elseif ($req['status'] === 'error'): ?>
                <i class="bi bi-x-circle-fill"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-circle-fill"></i>
            <?php endif; ?>
        </div>
        <div>
            <strong><?php echo htmlspecialchars($req['name']); ?></strong>
            <div class="text-muted"><?php echo htmlspecialchars($req['message']); ?></div>
        </div>
    </div>
<?php endforeach; ?>

<div class="navigation-buttons">
    <button type="button" class="btn btn-secondary" disabled>Previous</button>
    <button type="button" class="btn btn-primary <?php echo $allPassed ? '' : 'disabled'; ?>" 
            onclick="if (<?php echo $allPassed ? 'true' : 'false'; ?>) window.location.href='index.php?step=2';">
        Next <i class="bi bi-arrow-right"></i>
    </button>
</div>
