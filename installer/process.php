<?php
// AJAX install processor
// Rule 1: Must have own guarded session_start() since hit independently via fetch()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once 'bootstrap.php';

// Session keys used in this file (Rule 1 documentation):
// $_SESSION['installer']['db_host']
// $_SESSION['installer']['db_port']
// $_SESSION['installer']['db_name']
// $_SESSION['installer']['db_username']
// $_SESSION['installer']['db_password']
// $_SESSION['installer']['sql_file_path']
// $_SESSION['installer']['app_name']
// $_SESSION['installer']['admin_full_name']
// $_SESSION['installer']['admin_email']
// $_SESSION['installer']['admin_password']

$dbHost = getInstallerSession('db_host', '');
$dbPort = getInstallerSession('db_port', '');
$dbName = getInstallerSession('db_name', '');
$dbUsername = getInstallerSession('db_username', '');
$dbPassword = getInstallerSession('db_password', '');
$sqlFilePath = getInstallerSession('sql_file_path', '');
$appName = getInstallerSession('app_name', '');
$adminFullName = getInstallerSession('admin_full_name', '');
$adminEmail = getInstallerSession('admin_email', '');
$adminPassword = getInstallerSession('admin_password', '');

// Validate all required session data
if (empty($dbHost) || empty($dbPort) || empty($dbName) || empty($dbUsername) || 
    empty($sqlFilePath) || empty($appName) || empty($adminFullName) || 
    empty($adminEmail) || empty($adminPassword)) {
    echo json_encode(['success' => false, 'message' => 'Missing required session data. Please start the installation from the beginning.']);
    exit();
}

// Check if SQL file exists
if (!file_exists($sqlFilePath)) {
    echo json_encode(['success' => false, 'message' => 'SQL file not found. Please upload it again.']);
    exit();
}

try {
    // Rule 6: Increase time limit for large SQL imports
    set_time_limit(300);
    
    // Step 1: Connect to MySQL server without dbname
    $dsn = "mysql:host={$dbHost};port={$dbPort}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, $options);
    
    // Step 2: CREATE DATABASE IF NOT EXISTS
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Step 3: Reconnect WITH dbname + MYSQL_ATTR_MULTI_STATEMENTS (Rule 6)
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true // Rule 6: Required for multi-statement SQL import
    ];
    
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, $options);
    
    // Step 4: Import the uploaded SQL file
    $sqlContent = file_get_contents($sqlFilePath);
    if ($sqlContent === false) {
        throw new Exception('Failed to read SQL file.');
    }
    
    // Execute the SQL import in a single call (Rule 6)
    $pdo->exec($sqlContent);
    
    // Step 5: Replace/insert admin account (Rule 5: Verify column names match actual schema)
    // Actual users table columns: id, role_id, full_name, email, phone, password, avatar, status, last_login, created_at, updated_at
    // We'll check if a user with role_id=1 (Admin) exists and has a placeholder email, or just UPDATE/INSERT
    
    // First, check if Admin role exists and get its ID
    $stmt = $pdo->query("SELECT id FROM roles WHERE role_name = 'Admin' LIMIT 1");
    $adminRole = $stmt->fetch();
    
    if (!$adminRole) {
        throw new Exception('Admin role not found in database. The SQL file may be corrupted.');
    }
    
    $adminRoleId = $adminRole['id'];
    
    // Check if an admin user already exists with this email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$adminEmail]);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        // Update existing admin
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ?, status = 'active' WHERE id = ?");
        $stmt->execute([$adminFullName, $hashedPassword, $existingAdmin['id']]);
    } else {
        // Insert new admin (Rule 5: Only list columns we've confirmed exist, let defaults handle timestamps)
        $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, password, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$adminRoleId, $adminFullName, $adminEmail, $hashedPassword]);
    }
    
    // Step 6: Update app settings (Rule 5: No separate settings table, update constants.php)
    // We'll update the constants.php file in Step 7 below
    
    // Step 7: Generate the real config files (Rule 8: Match existing format exactly)
    
    // Generate constants.php
    // Simple BASE_URL calculation
    $baseUrl = 'http://localhost:8000/'; // Default, user can change manually if needed
    
    $constantsContent = "<?php
// Application Constants
define('BASE_URL', '" . $baseUrl . "');
define('SITE_NAME', '" . addslashes($appName) . "');
define('SITE_EMAIL', '" . addslashes($adminEmail) . "');

// Database Constants
define('DB_HOST', '" . addslashes($dbHost) . "');
define('DB_NAME', '" . addslashes($dbName) . "');
define('DB_USER', '" . addslashes($dbUsername) . "');
define('DB_PASS', '" . addslashes($dbPassword) . "');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Role Constants
define('ROLE_ADMIN', 'Admin');
define('ROLE_DOCTOR', 'Doctor');
define('ROLE_RECEPTIONIST', 'Receptionist');
define('ROLE_PATIENT', 'Patient');

// User Status
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// Pagination
define('RECORDS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('UTC');
";
    
    $constantsPath = CONFIG_PATH . '/constants.php';
    if (file_put_contents($constantsPath, $constantsContent) === false) {
        throw new Exception('Failed to write constants.php file. Check config folder permissions.');
    }
    
    // db.php doesn't need changes as it reads from constants.php
    
    // Step 8: Cleanup temp file + session (Rule 9)
    if (file_exists($sqlFilePath)) {
        unlink($sqlFilePath);
    }
    
    clearInstallerSession();
    
    // Step 9: Create lock file (Rule 7)
    createInstallLock();
    
    echo json_encode([
        'success' => true, 
        'admin_email' => $adminEmail,
        'message' => 'Installation completed successfully!'
    ]);
    
} catch (PDOException $e) {
    // Rule 6: Show real database error in installer context
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
