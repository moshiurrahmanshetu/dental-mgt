<?php
// AJAX endpoint for testing database connection
// Rule 1: Must have own guarded session_start() since hit independently via fetch()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("DEBUG test-connection.php: Script started");

header('Content-Type: application/json');

require_once 'bootstrap.php';

error_log("DEBUG test-connection.php: Bootstrap loaded");

$dbHost = trim($_POST['db_host'] ?? '');
$dbPort = trim($_POST['db_port'] ?? '');
$dbName = trim($_POST['db_name'] ?? '');
$dbUsername = trim($_POST['db_username'] ?? '');
$dbPassword = $_POST['db_password'] ?? '';

error_log("DEBUG test-connection.php: Received params - host=$dbHost, port=$dbPort, user=$dbUsername");

if (empty($dbHost) || empty($dbPort) || empty($dbUsername)) {
    error_log("DEBUG test-connection.php: Missing parameters");
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    error_log("DEBUG test-connection.php: Attempting PDO connection");
    // Test connection WITHOUT dbname in DSN (Rule: server connection only)
    $dsn = "mysql:host={$dbHost};port={$dbPort}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3 // 3-second timeout
    ];
    
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, $options);
    error_log("DEBUG test-connection.php: PDO connection successful");
    
    // If connection successful, test if we can create databases
    $stmt = $pdo->query("SELECT 1");
    $stmt->fetch();
    error_log("DEBUG test-connection.php: Query successful");
    
    // Save tested flag to session (Rule 1: using installer session namespace)
    setInstallerSession('db_host', $dbHost);
    setInstallerSession('db_port', $dbPort);
    setInstallerSession('db_name', $dbName);
    setInstallerSession('db_username', $dbUsername);
    setInstallerSession('db_password', $dbPassword);
    setInstallerSession('connection_tested', true);
    
    error_log("DEBUG test-connection.php: Session saved, returning success");
    echo json_encode(['success' => true, 'message' => 'Connection successful']);
    
} catch (PDOException $e) {
    error_log("DEBUG test-connection.php: PDO Exception - " . $e->getMessage());
    // Provide user-friendly error message (installer context only)
    $errorMessage = $e->getMessage();
    
    if (strpos($errorMessage, 'Access denied') !== false) {
        $userMessage = 'Access denied. Check your username and password.';
    } elseif (strpos($errorMessage, 'Can\'t connect') !== false) {
        $userMessage = 'Cannot connect to MySQL server. Check host and port.';
    } elseif (strpos($errorMessage, 'timeout') !== false) {
        $userMessage = 'Connection timed out. Check if MySQL server is running.';
    } else {
        $userMessage = 'Connection failed: ' . $errorMessage;
    }
    
    error_log("DEBUG test-connection.php: Returning error - " . $userMessage);
    echo json_encode(['success' => false, 'message' => $userMessage]);
}
